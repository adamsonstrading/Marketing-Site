<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\SendRecipientJob;
use App\Models\Campaign;
use App\Models\Recipient;
use App\Models\Sender;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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

            // Create campaign
            $campaign = Campaign::create([
                'sender_id' => $request->sender_id,
                'smtp_configuration_id' => $request->smtp_configuration_id,
                'name' => $request->name,
                'subject' => $request->subject,
                'body' => $request->body,
                'total_recipients' => count($recipientData),
                'status' => 'queued',
            ]);

            // Create recipient records
            $recipients = [];
            foreach ($recipientData as $recipient) {
                $recipients[] = [
                    'campaign_id' => $campaign->id,
                    'email' => $recipient['email'],
                    'name' => $recipient['name'] ?? null,
                    'status' => 'pending',
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
                    'overall_progress' => $overallProgress,
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
}
