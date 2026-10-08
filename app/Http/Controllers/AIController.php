<?php

namespace App\Http\Controllers;

use App\Ai\Agents\SupportAgent;
use App\Http\Requests\ChatRequest;
use App\Services\AI\AIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Contracts\VerifiesConversationOwnership;

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

    public function chat(ChatRequest $request, ConversationStore $conversations): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $conversationId = $validated['conversation_id'] ?? null;

        if ($conversationId !== null) {
            abort_unless($conversations instanceof VerifiesConversationOwnership
                && $conversations->conversationBelongsTo(
                    $conversationId,
                    $user->getMorphClass(),
                    $user->getKey(),
                ), 404);
        }

        $provider = config('ai.providers.'.config('ai.default'), []);

        if (blank($provider['key'] ?? null) && ! in_array($provider['driver'] ?? null, ['ollama', 'openai-compatible'], true)) {
            return response()->json([
                'message' => 'Le fournisseur IA n’est pas configuré.',
            ], 503);
        }

        $agent = SupportAgent::make(user: $user);
        $response = $conversationId !== null
            ? $agent->continue($conversationId, as: $user)->prompt($validated['message'])
            : $agent->forUser($user)->prompt($validated['message']);

        return response()->json([
            'conversation_id' => $response->conversationId,
            'message' => $response->text,
        ]);
    }
}
