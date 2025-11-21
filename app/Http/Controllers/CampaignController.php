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
use Illuminate\Support\Facades\Log;
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
            'bcc' => 'nullable|string',
            'cc' => 'nullable|string',
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

            // Ultra-fast email verification (syntax check only - no DNS lookups)
            // Full verification happens in background via jobs
            $verifiedRecipients = [];
            $verificationStats = [
                'total' => count($recipientData),
                'valid' => 0,
                'invalid' => 0,
                'disposable' => 0
            ];

            // Process recipients in a single optimized loop
            foreach ($recipientData as $recipient) {
                // Quick syntax check only (fast - no DNS lookups)
                $isValidSyntax = filter_var($recipient['email'], FILTER_VALIDATE_EMAIL) !== false;
                
                // Skip all DNS checks during creation for speed
                // Full verification will happen in background jobs
                $verifiedRecipients[] = [
                    'email' => $recipient['email'],
                    'name' => $recipient['name'] ?? null,
                    'is_verified' => $isValidSyntax,
                    'is_disposable' => false, // Will be verified in background
                    'is_role_based' => false, // Will be verified in background
                    'has_mx_record' => true, // Assume true, verify in background
                    'verification_details' => json_encode($isValidSyntax ? ['Basic syntax valid'] : ['Invalid email syntax'])
                ];

                if ($isValidSyntax) {
                    $verificationStats['valid']++;
                } else {
                    $verificationStats['invalid']++;
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
                'bcc' => $request->bcc ?? null,
                'cc' => $request->cc ?? null,
                'total_recipients' => count($verifiedRecipients),
                'status' => 'queued',
            ]);

            // Create recipient records with verification data (optimized bulk insert)
            $now = now();
            $recipients = array_map(function($recipient) use ($campaign, $now) {
                return [
                    'campaign_id' => $campaign->id,
                    'email' => $recipient['email'],
                    'name' => $recipient['name'] ?? null,
                    'status' => 'pending',
                    'is_verified' => $recipient['is_verified'],
                    'is_disposable' => $recipient['is_disposable'],
                    'is_role_based' => $recipient['is_role_based'],
                    'has_mx_record' => $recipient['has_mx_record'],
                    'verification_details' => $recipient['verification_details'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $verifiedRecipients);

            // Bulk insert in chunks for better performance
            $chunkSize = 100;
            foreach (array_chunk($recipients, $chunkSize) as $chunk) {
                Recipient::insert($chunk);
            }

            // Get recipient IDs in one query
            $recipientIds = Recipient::where('campaign_id', $campaign->id)
                ->pluck('id')
                ->toArray();

            // Update campaign status before committing
            if (count($recipientIds) > 0) {
                $campaign->update(['status' => 'sending']);
            }
            
            DB::commit();
            
            // Dispatch jobs AFTER committing transaction (fast, non-blocking)
            // Jobs are dispatched asynchronously and won't block the HTTP response
            if (count($recipientIds) > 0) {
                // Dispatch jobs quickly in batches (non-blocking)
                $batchSize = 100; // Larger batches for faster dispatch
                $batches = array_chunk($recipientIds, $batchSize);
                $baseDelay = 1;
                
                foreach ($batches as $batchIndex => $batch) {
                    $delay = $baseDelay + ($batchIndex * 2);
                    foreach ($batch as $recipientId) {
                        SendRecipientJob::dispatch($recipientId)
                            ->onQueue('emails')
                            ->delay(now()->addSeconds($delay + rand(0, 1))); // Small random stagger
                    }
                }
                
                // Trigger queue processing in background (non-blocking)
                $maxJobs = min(count($recipientIds) + 20, 100);
                $command = sprintf(
                    'php "%s" queue:auto-process --max-jobs=%d > nul 2>&1',
                    base_path('artisan'),
                    $maxJobs
                );
                
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    pclose(popen("start /B " . $command, "r"));
                } else {
                    exec($command . " &");
                }
            }

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
                
                // Trigger queue processing
                $maxJobs = min($pendingCount + 20, 100);
                $command = sprintf(
                    'php "%s" queue:auto-process --max-jobs=%d > nul 2>&1',
                    base_path('artisan'),
                    $maxJobs
                );
                
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    pclose(popen("start /B " . $command, "r"));
                } else {
                    exec($command . " &");
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
     * Delete a campaign and its recipients
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $campaign = Campaign::findOrFail($id);
            
            // Check if campaign is currently sending (warn user)
            if (in_array($campaign->status, ['sending', 'queued'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a campaign that is currently sending. Please pause it first.'
                ], 400);
            }

            $campaignName = $campaign->name;
            $recipientCount = $campaign->recipients()->count();

            // Delete recipients first (cascade should handle this, but being explicit)
            $campaign->recipients()->delete();
            
            // Delete the campaign
            $campaign->delete();

            return response()->json([
                'success' => true,
                'message' => "Campaign '{$campaignName}' and {$recipientCount} recipient(s) deleted successfully"
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete campaign: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete campaign: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restart stuck campaigns by re-dispatching pending recipient jobs
     */
    public function restartStuckCampaigns(): JsonResponse
    {
        try {
            // Find stuck campaigns (sending/queued with pending recipients that haven't updated recently)
            $stuckCampaigns = Campaign::whereIn('status', ['sending', 'queued'])
                ->with('recipients')
                ->get()
                ->filter(function($campaign) {
                    $statusCounts = $campaign->getRecipientsCountByStatus();
                    $pending = $statusCounts['pending'] ?? 0;
                    
                    // Campaign is stuck if it has pending recipients and hasn't updated in last 5 minutes
                    return $pending > 0 && $campaign->updated_at < now()->subMinutes(5);
                });

            $restartedCount = 0;
            $totalRecipientsRestarted = 0;
            $restartedCampaigns = [];

            foreach ($stuckCampaigns as $campaign) {
                $pendingRecipients = $campaign->recipients()
                    ->where('status', 'pending')
                    ->get();

                if ($pendingRecipients->count() > 0) {
                    // Re-dispatch jobs for pending recipients
                    $recipientIds = $pendingRecipients->pluck('id')->toArray();
                    
                    foreach ($recipientIds as $recipientId) {
                        SendRecipientJob::dispatch($recipientId)
                            ->onQueue('emails')
                            ->delay(now()->addSeconds(rand(1, 3)));
                    }

                    // Update campaign status to sending if not already
                    if ($campaign->status !== 'sending') {
                        $campaign->update(['status' => 'sending']);
                    }

                    $restartedCount++;
                    $totalRecipientsRestarted += count($recipientIds);
                    $restartedCampaigns[] = [
                        'id' => $campaign->id,
                        'name' => $campaign->name,
                        'pending_count' => count($recipientIds)
                    ];
                }
            }

            // Trigger queue processing in background
            if ($totalRecipientsRestarted > 0) {
                $maxJobs = min($totalRecipientsRestarted + 20, 100);
                $command = sprintf(
                    'php "%s" queue:auto-process --max-jobs=%d > nul 2>&1',
                    base_path('artisan'),
                    $maxJobs
                );
                
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    pclose(popen("start /B " . $command, "r"));
                } else {
                    exec($command . " &");
                }
            }

            return response()->json([
                'success' => true,
                'message' => $restartedCount > 0 
                    ? "Restarted {$restartedCount} stuck campaign(s) with {$totalRecipientsRestarted} pending recipients"
                    : 'No stuck campaigns found',
                'restarted_campaigns' => $restartedCampaigns,
                'total_campaigns_restarted' => $restartedCount,
                'total_recipients_restarted' => $totalRecipientsRestarted
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to restart stuck campaigns: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to restart stuck campaigns: ' . $e->getMessage()
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
        // Only update if status needs to change
        $newStatus = $campaign->status;
        if ($pending > 0) {
            $newStatus = 'sending';
        } elseif ($sent + $failed >= $total && $total > 0) {
            $newStatus = 'completed';
        } elseif ($total == 0) {
            $newStatus = 'completed';
        }
        
        if ($newStatus !== $campaign->status) {
            $campaign->update(['status' => $newStatus]);
        }

        return response()->json([
            'success' => true,
            'campaign_id' => $campaign->id,
            'campaign_status' => $campaign->status,
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'pending' => $pending,
            'recent_recipients' => $campaign->recipients()->orderBy('created_at', 'desc')->limit(10)->get()->map(function($recipient) {
                return [
                    'email' => $recipient->email,
                    'status' => $recipient->status,
                    'last_error' => $recipient->last_error,
                    'sent_at' => $recipient->sent_at ? $recipient->sent_at->format('M d, Y H:i:s') : null,
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
            
            // Get recent campaigns (optimized: only select needed columns and relationships)
            $recentCampaigns = Campaign::with([
                'sender:id,name',
                'smtpConfiguration:id,name'
            ])
                ->select('id', 'name', 'subject', 'status', 'total_recipients', 'created_at', 'sender_id', 'smtp_configuration_id')
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
     * Get all campaigns with their recipients for history view
     */
    public function campaignsHistory(): JsonResponse
    {
        try {
            // Optimize: Load campaigns with relationships, limit recipients per campaign
            $campaigns = Campaign::with([
                'sender:id,name',
                'smtpConfiguration:id,name',
                'recipients' => function($query) {
                    // Only load most recent 100 recipients per campaign for performance
                    $query->orderBy('created_at', 'desc')->limit(100);
                }
            ])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($campaign) {
                    // Get recipient counts by status (optimized single query)
                    $recipientCounts = $campaign->recipients()
                        ->selectRaw('status, COUNT(*) as count')
                        ->groupBy('status')
                        ->pluck('count', 'status')
                        ->toArray();

                    // Map only loaded recipients (limited to 100)
                    $recipients = $campaign->recipients->map(function ($recipient) {
                        return [
                            'id' => $recipient->id,
                            'email' => $recipient->email,
                            'name' => $recipient->name,
                            'status' => $recipient->status,
                            'sent_at' => $recipient->sent_at ? $recipient->sent_at->format('M d, Y H:i:s') : null,
                            'last_error' => $recipient->last_error,
                            'attempt_count' => $recipient->attempt_count,
                        ];
                    });

                    return [
                        'id' => $campaign->id,
                        'name' => $campaign->name,
                        'subject' => $campaign->subject,
                        'body' => $campaign->body,
                        'status' => $campaign->status,
                        'total_recipients' => $campaign->total_recipients,
                        'created_at' => $campaign->created_at ? $campaign->created_at->format('M d, Y H:i') : '-',
                        'updated_at' => $campaign->updated_at ? $campaign->updated_at->format('M d, Y H:i') : '-',
                        'sender_name' => $campaign->sender->name ?? 'Unknown',
                        'smtp_configuration_name' => $campaign->smtpConfiguration->name ?? 'Unknown',
                        'recipient_counts' => [
                            'sent' => $recipientCounts['sent'] ?? 0,
                            'pending' => $recipientCounts['pending'] ?? 0,
                            'failed' => $recipientCounts['failed'] ?? 0,
                        ],
                        'recipients' => $recipients,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $campaigns
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch campaigns history: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch campaigns history: ' . $e->getMessage()
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
