<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\JsonResponse;

class EmailTemplateController extends Controller
{
    /**
     * Get all email templates
     */
    public function index(): JsonResponse
    {
        $templates = EmailTemplate::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'templates' => $templates
        ]);
    }

    /**
     * Get active email templates
     */
    public function active(): JsonResponse
    {
        $templates = EmailTemplate::getActive();

        return response()->json([
            'success' => true,
            'templates' => $templates
        ]);
    }

    /**
     * Get a specific email template
     */
    public function show($id): JsonResponse
    {
        $template = EmailTemplate::find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'template' => $template
        ]);
    }
}
