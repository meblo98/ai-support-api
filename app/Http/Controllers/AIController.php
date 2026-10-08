<?php

namespace App\Http\Controllers;

use App\Services\AI\AIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIController extends Controller
{
    public function tools(AIService $service): JsonResponse
    {
        return response()->json(['tools' => $service->tools()]);
    }

    public function execute(Request $request, string $tool, AIService $service): JsonResponse
    {
        $validated = $request->validate([
            'arguments' => ['sometimes', 'array'],
        ]);

        return response()->json($service->execute(
            $request->user(),
            $tool,
            $validated['arguments'] ?? [],
        ));
    }
}
