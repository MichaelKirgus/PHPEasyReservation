<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DiagnosticsService;
use Illuminate\Http\JsonResponse;

class DiagnosticsController extends Controller
{
    public function __construct(private readonly DiagnosticsService $diagnostics)
    {
    }

    public function show(): JsonResponse
    {
        return response()->json($this->diagnostics->snapshot());
    }
}
