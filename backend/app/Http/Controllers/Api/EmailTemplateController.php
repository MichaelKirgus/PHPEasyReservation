<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(EmailTemplate::query()->orderBy('id')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:50'],
            'subject' => ['required', 'string'],
            'body' => ['required', 'string'],
        ]);

        $template = EmailTemplate::create([
            'name' => $data['name'],
            'type' => $data['type'] ?? 'validation',
            'subject' => $data['subject'],
            'body' => $data['body'],
        ]);

        return response()->json($template, 201);
    }

    public function update(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'max:50'],
            'subject' => ['sometimes', 'string'],
            'body' => ['sometimes', 'string'],
        ]);

        $emailTemplate->fill($data);
        $emailTemplate->save();

        return response()->json($emailTemplate);
    }

    public function destroy(EmailTemplate $emailTemplate): JsonResponse
    {
        $emailTemplate->delete();

        return response()->json(['message' => 'Email template deleted.']);
    }
}
