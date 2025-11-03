<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\SendRecipientJob;
use App\Models\Campaign;
use App\Models\Recipient;
use App\Models\Sender;
use App\Models\EmailTemplate;
use App\Services\EmailVerificationService;
use App\Services\SmtpRotationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    /**
     * Create a new campaign and dispatch jobs for recipients.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'sender_id' => 'required|exists:senders,id',
            'smtp_configuration_id' => 'required|exists:smtp_configurations,id',
            'template_id' => 'nullable|exists:email_templates,id',
            'recipients' => 'required|string|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Parse recipients (one per line or CSV format)
            $recipientData = $this->parseRecipients($request->recipients);

            if (empty($recipientData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid email addresses provided'
                ], 422);
            }

            // Limit recipients per campaign
            if (count($recipientData) > 500) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maximum 500 recipients per campaign allowed'
                ], 422);
            }

            // Use SMTP rotation if no specific SMTP is selected
            $smtpRotationService = app(SmtpRotationService::class);
            $smtpConfigurationId = $request->smtp_configuration_id;
            
            if (!$smtpConfigurationId) {
                // Use rotation to get best SMTP
                $rotatedSmtp = $smtpRotationService->getNextSmtp();
                if ($rotatedSmtp) {
                    $smtpConfigurationId = $rotatedSmtp->id;
                    Log::info("Using rotated SMTP: {$rotatedSmtp->name} (ID: {$rotatedSmtp->id})");
                }
            }

            // Verify emails if verification is enabled
            $verificationService = app(EmailVerificationService::class);
            $verifiedRecipients = [];
            $verificationStats = [
                'total' => count($recipientData),
                'valid' => 0,
                'invalid' => 0,
                'disposable' => 0
            ];

            foreach ($recipientData as $recipient) {
                $verification = $verificationService->verify($recipient['email']);
                
                $verifiedRecipients[] = [
                    'email' => $recipient['email'],
                    'name' => $recipient['name'] ?? null,
                    'is_verified' => $verification['is_valid'],
                    'is_disposable' => $verification['is_disposable'],
                    'is_role_based' => $verification['is_role_based'],
                    'has_mx_record' => $verification['has_mx_record'],
                    'verification_details' => json_encode($verification['details'] ?? []),
                    'verification_result' => $verification
                ];

                if ($verification['is_valid']) {
                    $verificationStats['valid']++;
                } else {
                    $verificationStats['invalid']++;
                }
                
                if ($verification['is_disposable']) {
                    $verificationStats['disposable']++;
                }
            }

            // Create campaign
            $campaign = Campaign::create([
                'sender_id' => $request->sender_id,
                'smtp_configuration_id' => $smtpConfigurationId,
                'template_id' => $request->template_id ?? null,
                'name' => $request->name,
                'subject' => $request->subject,
                'body' => $request->body,
                'total_recipients' => count($verifiedRecipients),
                'status' => 'queued',
            ]);

            // Create recipient records with verification data
            $recipients = [];
            foreach ($verifiedRecipients as $recipient) {
                $recipients[] = [
                    'campaign_id' => $campaign->id,
                    'email' => $recipient['email'],
                    'name' => $recipient['name'] ?? null,
                    'status' => 'pending',
                    'is_verified' => $recipient['is_verified'],
                    'is_disposable' => $recipient['is_disposable'],
                    'is_role_based' => $recipient['is_role_based'],
                    'has_mx_record' => $recipient['has_mx_record'],
                    'verification_details' => $recipient['verification_details'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Recipient::insert($recipients);

            // Dispatch jobs for each recipient
            $recipientIds = Recipient::where('campaign_id', $campaign->id)
                ->pluck('id')
                ->toArray();

            foreach ($recipientIds as $recipientId) {
                SendRecipientJob::dispatch($recipientId)->onQueue('emails');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'campaign_id' => $campaign->id,
                'total_recipients' => $campaign->total_recipients,
                'verification_stats' => $verificationStats,
                'message' => 'Campaign created and jobs dispatched successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create campaign: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pause a campaign
     */
    public function pause(string $id): JsonResponse
    {
        try {
            $campaign = Campaign::findOrFail($id);
            
            if (!in_array($campaign->status, ['queued', 'sending'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign can only be paused if it is queued or sending'
                ], 400);
            }

            $campaign->update(['status' => 'paused']);

            return response()->json([
                'success' => true,
                'message' => 'Campaign paused successfully',
                'campaign' => $campaign
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to pause campaign: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resume a paused campaign
     */
    public function resume(string $id): JsonResponse
    {
        try {
            $campaign = Campaign::findOrFail($id);
            
            if ($campaign->status !== 'paused') {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign is not paused'
                ], 400);
            }

            // Get pending recipients count
            $pendingCount = $campaign->recipients()->where('status', 'pending')->count();
            
            if ($pendingCount > 0) {
                $campaign->update(['status' => 'sending']);
                
                // Re-dispatch jobs for pending recipients
                $pendingRecipientIds = $campaign->recipients()
                    ->where('status', 'pending')
                    ->pluck('id')
                    ->toArray();

                foreach ($pendingRecipientIds as $recipientId) {
                    SendRecipientJob::dispatch($recipientId)->onQueue('emails');
                }
            } else {
                $campaign->update(['status' => 'completed']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Campaign resumed successfully',
                'campaign' => $campaign
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resume campaign: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get campaign status and recipient counts.
     */
    public function status(string $id): JsonResponse
    {
        $campaign = Campaign::with(['recipients' => function($query) {
            $query->latest()->limit(10);
        }])->find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }

        $statusCounts = $campaign->getRecipientsCountByStatus();
        
        $total = $campaign->total_recipients;
        $sent = $statusCounts['sent'] ?? 0;
        $failed = $statusCounts['failed'] ?? 0;
        $pending = $statusCounts['pending'] ?? 0;

        // Update campaign status based on recipient statuses
        if ($pending > 0) {
            $campaign->update(['status' => 'sending']);
        } elseif ($sent + $failed >= $total) {
            $campaign->update(['status' => 'completed']);
        }

        return response()->json([
            'success' => true,
            'campaign_id' => $campaign->id,
            'campaign_status' => $campaign->status,
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'pending' => $pending,
            'recent_recipients' => $campaign->recipients->map(function($recipient) {
                return [
                    'email' => $recipient->email,
                    'status' => $recipient->status,
                    'last_error' => $recipient->last_error,
                    'sent_at' => $recipient->sent_at,
                ];
            })
        ]);
    }

    /**
     * Get all available senders.
     */
    public function senders(): JsonResponse
    {
        $senders = Sender::select('id', 'name', 'email', 'from_name', 'from_address')
            ->get();

        return response()->json([
            'success' => true,
            'senders' => $senders
        ]);
    }

    /**
     * Create a new sender (admin only).
     */
    public function createSender(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'smtp_host' => 'required|string|max:255',
            'smtp_port' => 'required|integer|min:1|max:65535',
            'smtp_username' => 'required|string|max:255',
            'smtp_password' => 'required|string',
            'smtp_encryption' => ['required', Rule::in(['tls', 'ssl', 'none'])],
            'from_name' => 'required|string|max:255',
            'from_address' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $sender = Sender::create($request->all());

            return response()->json([
                'success' => true,
                'sender' => [
                    'id' => $sender->id,
                    'name' => $sender->name,
                    'email' => $sender->email,
                    'from_name' => $sender->from_name,
                    'from_address' => $sender->from_address,
                ],
                'message' => 'Sender created successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create sender: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Parse recipients from text input (supports both newline-separated and CSV format)
     */
    private function parseRecipients(string $recipients): array
    {
        $lines = array_filter(array_map('trim', explode("\n", $recipients)));
        $recipientData = [];

        foreach ($lines as $line) {
            if (empty($line)) continue;

            // Check if line contains comma (CSV format)
            if (strpos($line, ',') !== false) {
                $parts = $this->parseCsvLine($line);
                if (count($parts) >= 1) {
                    $email = trim($parts[0]);
                    $name = isset($parts[1]) ? trim($parts[1]) : null;
                    
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $recipientData[] = [
                            'email' => $email,
                            'name' => $name
                        ];
                    }
                }
            } else {
                // Simple email per line format
                if (filter_var($line, FILTER_VALIDATE_EMAIL)) {
                    $recipientData[] = [
                        'email' => $line,
                        'name' => null
                    ];
                }
            }
        }

        return $recipientData;
    }

    /**
     * Get dashboard statistics
     */
    public function dashboard(): JsonResponse
    {
        try {
            // Get campaign statistics
            $totalCampaigns = Campaign::count();
            $totalRecipients = Campaign::sum('total_recipients');
            
            // Get status counts
            $statusCounts = Campaign::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
            
            // Get aggregate recipient statistics across all campaigns
            $recipientStats = \App\Models\Recipient::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
            
            // Get recent campaigns
                $recentCampaigns = Campaign::with(['sender', 'smtpConfiguration'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($campaign) {
                        return [
                            'id' => $campaign->id,
                            'name' => $campaign->name,
                            'subject' => $campaign->subject,
                            'status' => $campaign->status,
                            'total_recipients' => $campaign->total_recipients,
                            'created_at' => $campaign->created_at->format('M d, Y H:i'),
                            'sender_name' => $campaign->sender->name ?? 'Unknown',
                            'smtp_configuration_name' => $campaign->smtpConfiguration->name ?? 'Unknown'
                        ];
                    });

            // Calculate overall progress (completed campaigns)
            $completedCampaigns = $statusCounts['completed'] ?? 0;
            $overallProgress = $totalCampaigns > 0 ? round(($completedCampaigns / $totalCampaigns) * 100) : 0;
            
            // Calculate sidebar progress (recipients sent/failed vs total)
            $sentRecipients = $recipientStats['sent'] ?? 0;
            $failedRecipients = $recipientStats['failed'] ?? 0;
            $sidebarProgress = $totalRecipients > 0 ? round((($sentRecipients + $failedRecipients) / $totalRecipients) * 100) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_campaigns' => $totalCampaigns,
                    'total_recipients' => $totalRecipients,
                    'status_counts' => [
                        'completed' => $statusCounts['completed'] ?? 0,
                        'sending' => $statusCounts['sending'] ?? 0,
                        'queued' => $statusCounts['queued'] ?? 0,
                        'draft' => $statusCounts['draft'] ?? 0,
                        'failed' => $statusCounts['failed'] ?? 0
                    ],
                    'recipient_stats' => [
                        'total' => $totalRecipients,
                        'sent' => $recipientStats['sent'] ?? 0,
                        'pending' => $recipientStats['pending'] ?? 0,
                        'failed' => $recipientStats['failed'] ?? 0
                    ],
                    'overall_progress' => $overallProgress,
                    'sidebar_progress' => $sidebarProgress,
                    'recent_campaigns' => $recentCampaigns
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Parse a CSV line handling quoted values
     */
    private function parseCsvLine(string $line): array
    {
        $result = [];
        $current = '';
        $inQuotes = false;

        for ($i = 0; $i < strlen($line); $i++) {
            $char = $line[$i];
            
            if ($char === '"') {
                $inQuotes = !$inQuotes;
            } elseif ($char === ',' && !$inQuotes) {
                $result[] = $current;
                $current = '';
            } else {
                $current .= $char;
            }
        }
        
        $result[] = $current;
        return $result;
    }

    /**
     * Proxy request to n8n webhook (server-side to avoid CORS)
     */
    public function sendToN8n(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required',
            'sender_name' => 'nullable|string',
            'sender_email' => 'nullable|string|email',
            'subject' => 'required|string',
            'message' => 'required|string',
            'recipients' => 'required|string',
            'smtp_configuration_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $webhookUrl = 'https://humayounai.app.n8n.cloud/webhook-test/mail';
            
            $data = [
                'sender_id' => $request->sender_id,
                'sender_name' => $request->sender_name,
                'sender_email' => $request->sender_email,
                'subject' => $request->subject,
                'message' => $request->message,
                'recipients' => $request->recipients,
                'smtp_configuration_id' => $request->smtp_configuration_id,
            ];

            $response = Http::timeout(30)->post($webhookUrl, $data);

            $statusCode = $response->status();

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data sent to n8n successfully',
                    'status' => $statusCode,
                    'response' => $response->json() ?? $response->body()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'n8n webhook returned an error',
                    'status' => $statusCode,
                    'response' => $response->body()
                ], $statusCode);
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Log::error('N8n webhook connection error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to n8n webhook. Please check the webhook URL and network connection.'
            ], 500);
        } catch (\Exception $e) {
            \Log::error('N8n webhook exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
