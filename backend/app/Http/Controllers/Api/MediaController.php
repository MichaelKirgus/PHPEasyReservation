<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function __construct(private readonly MediaService $media)
    {
    }

    public function images(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(config('media.categories', [])))]
        ]);

        $items = $this->media->listImages($validated['category']);

        return response()->json($items);
    }
}
