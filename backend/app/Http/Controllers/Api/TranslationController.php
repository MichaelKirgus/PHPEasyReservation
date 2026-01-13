<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TranslationController extends Controller
{
    public function show(string $lang): JsonResponse
    {
        $file = resource_path("lang/{$lang}.json");
        if (! file_exists($file)) {
            throw new NotFoundHttpException('Translations not found');
        }

        $data = json_decode((string) file_get_contents($file), true) ?? [];

        return response()->json($data);
    }
}
