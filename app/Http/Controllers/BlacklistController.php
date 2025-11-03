<?php

namespace App\Http\Controllers;

use App\Models\Blacklist;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class BlacklistController extends Controller
{
    /**
     * Get all blacklist entries
     */
    public function index(): JsonResponse
    {
        $blacklist = Blacklist::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'blacklist' => $blacklist
        ]);
    }

    /**
     * Add email to blacklist
     */
    public function addEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $entry = Blacklist::addEmail($request->email, $request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Email added to blacklist successfully',
                'entry' => $entry
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add email to blacklist: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add domain to blacklist
     */
    public function addDomain(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'domain' => 'required|string|max:255',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $entry = Blacklist::addDomain($request->domain, $request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Domain added to blacklist successfully',
                'entry' => $entry
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add domain to blacklist: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove from blacklist
     */
    public function destroy($id): JsonResponse
    {
        try {
            $entry = Blacklist::findOrFail($id);
            $entry->delete();

            return response()->json([
                'success' => true,
                'message' => 'Removed from blacklist successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove from blacklist: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk add from CSV/text
     */
    public function bulkAdd(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'entries' => 'required|string',
            'type' => 'required|in:email,domain',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $lines = array_filter(array_map('trim', explode("\n", $request->entries)));
            $added = 0;
            $skipped = 0;

            foreach ($lines as $line) {
                if (empty($line)) continue;

                try {
                    if ($request->type === 'email') {
                        if (filter_var($line, FILTER_VALIDATE_EMAIL)) {
                            Blacklist::addEmail($line);
                            $added++;
                        } else {
                            $skipped++;
                        }
                    } else {
                        Blacklist::addDomain($line);
                        $added++;
                    }
                } catch (\Exception $e) {
                    $skipped++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Added {$added} entries, skipped {$skipped}",
                'added' => $added,
                'skipped' => $skipped
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk add: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if email is blacklisted
     */
    public function check(string $email): JsonResponse
    {
        $isBlacklisted = Blacklist::isBlacklisted($email);

        return response()->json([
            'success' => true,
            'email' => $email,
            'blacklisted' => $isBlacklisted
        ]);
    }
}
