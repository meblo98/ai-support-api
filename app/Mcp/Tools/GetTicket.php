<?php

namespace App\Mcp\Tools;

use App\Models\User;
use App\Services\AI\Tools\GetTicketTool as GetTicketAction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Consulte un ticket appartenant à l’utilisateur connecté.')]
class GetTicket extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('Authentification requise.');
        }

        try {
            return Response::structured(app(GetTicketAction::class)->execute($user, $request->all()));
        } catch (ValidationException $exception) {
            return Response::error($exception->getMessage());
        } catch (ModelNotFoundException) {
            return Response::error('Ticket introuvable.');
        }
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()->min(1)
                ->description('Identifiant du ticket à consulter.')
                ->required(),
        ];
    }
}
