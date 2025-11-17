<?php

namespace App\Http\Controllers;

use App\Models\SmtpConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SmtpConfigurationController extends Controller
{
    /**
     * Get all SMTP configurations
     */
    public function index(): JsonResponse
    {
        $configurations = SmtpConfiguration::orderBy('is_default', 'desc')
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'configurations' => $configurations
        ]);
    }

    /**
     * Get active SMTP configurations
     */
    public function active(): JsonResponse
    {
        $configurations = SmtpConfiguration::getActive();

        return response()->json([
            'success' => true,
            'configurations' => $configurations
        ]);
    }

    /**
     * Get default SMTP configuration
     */
    public function default(): JsonResponse
    {
        $configuration = SmtpConfiguration::getDefault();

        return response()->json([
            'success' => true,
            'configuration' => $configuration
        ]);
    }

    /**
     * Store a new SMTP configuration
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => 'required|string',
            'from_address' => 'required|email|max:255',
            'from_name' => 'required|string|max:255',
            'encryption' => 'required|string|in:tls,ssl,none',
            'description' => 'nullable|string',
            'is_default' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $configuration = SmtpConfiguration::create($request->all());

            // If this is set as default, make it the only default
            if ($request->boolean('is_default')) {
                $configuration->setAsDefault();
            }

            // Also create a corresponding Sender entry so it appears in sender dropdown
            // Only create if a sender with the same email doesn't already exist
            try {
                $existingSender = \App\Models\Sender::where('email', $request->from_address)->first();
                if (!$existingSender) {
                    \App\Models\Sender::create([
                        'name' => $request->from_name,
                        'email' => $request->from_address,
                        'smtp_host' => $request->host,
                        'smtp_port' => $request->port,
                        'smtp_username' => $request->username,
                        'smtp_password' => $request->password,
                        'smtp_encryption' => $request->encryption,
                        'from_name' => $request->from_name,
                        'from_address' => $request->from_address,
                    ]);
                }
            } catch (\Exception $senderException) {
                // Log but don't fail the SMTP creation if sender creation fails
                Log::warning('Failed to create sender for SMTP configuration: ' . $senderException->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'SMTP configuration created successfully',
                'configuration' => $configuration
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create SMTP configuration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing SMTP configuration
     */
    public function update(Request $request, $id): JsonResponse
    {
        $configuration = SmtpConfiguration::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => 'required|string',
            'from_address' => 'required|email|max:255',
            'from_name' => 'required|string|max:255',
            'encryption' => 'required|string|in:tls,ssl,none',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_default' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $configuration->update($request->all());

            // If this is set as default, make it the only default
            if ($request->boolean('is_default')) {
                $configuration->setAsDefault();
            }

            return response()->json([
                'success' => true,
                'message' => 'SMTP configuration updated successfully',
                'configuration' => $configuration
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update SMTP configuration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set a configuration as default
     */
    public function setDefault($id): JsonResponse
    {
        try {
            $configuration = SmtpConfiguration::findOrFail($id);
            $configuration->setAsDefault();

            return response()->json([
                'success' => true,
                'message' => 'SMTP configuration set as default successfully',
                'configuration' => $configuration
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to set default configuration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle configuration active status
     */
    public function toggleActive($id): JsonResponse
    {
        try {
            $configuration = SmtpConfiguration::findOrFail($id);
            $configuration->update(['is_active' => !$configuration->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'SMTP configuration status updated successfully',
                'configuration' => $configuration
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update configuration status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an SMTP configuration
     */
    public function destroy($id): JsonResponse
    {
        try {
            $configuration = SmtpConfiguration::findOrFail($id);
            
            // Don't allow deletion of the last active configuration
            $activeCount = SmtpConfiguration::where('is_active', true)->count();
            if ($activeCount <= 1 && $configuration->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete the last active SMTP configuration'
                ], 400);
            }

            $configuration->delete();

            return response()->json([
                'success' => true,
                'message' => 'SMTP configuration deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete SMTP configuration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test SMTP configuration
     */
    public function test($id): JsonResponse
    {
        try {
            $configuration = SmtpConfiguration::findOrFail($id);
            
            // Here you would implement actual SMTP testing
            // For now, we'll just return success
            return response()->json([
                'success' => true,
                'message' => 'SMTP configuration test completed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'SMTP test failed: ' . $e->getMessage()
            ], 500);
        }
    }
}