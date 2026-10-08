<?php

namespace App\Mcp\Tools;

use App\Models\Ticket;
use App\Models\User;
use App\Services\AI\Tools\CreateTicketTool as CreateTicketAction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Crée un ticket pour l’utilisateur connecté.')]
class CreateTicket extends Tool
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
            return Response::structured(app(CreateTicketAction::class)->execute($user, $request->all()));
        } catch (ValidationException $exception) {
            return Response::error($exception->getMessage());
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
            'subject' => $schema->string()->max(255)
                ->description('Résumé court du problème.')
                ->required(),
            'description' => $schema->string()
                ->description('Détails fournis par le client.')
                ->required(),
            'status' => $schema->string()->enum(Ticket::STATUSES)
                ->description('Statut initial du ticket.'),
            'priority' => $schema->string()->enum(Ticket::PRIORITIES)
                ->description('Priorité du ticket.'),
        ];
    }
}
