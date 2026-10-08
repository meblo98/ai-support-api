<?php

namespace App\Mcp\Tools;

use App\Models\Ticket;
use App\Models\User;
use App\Services\AI\ListUserTickets as ListTicketsAction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Liste les tickets de l’utilisateur connecté, avec filtres facultatifs par statut et priorité.')]
class ListTickets extends Tool
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
            return Response::structured(app(ListTicketsAction::class)->execute($user, $request->all()));
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
            'status' => $schema->string()->enum(Ticket::STATUSES)
                ->description('Filtrer sur le statut du ticket.'),
            'priority' => $schema->string()->enum(Ticket::PRIORITIES)
                ->description('Filtrer sur la priorité du ticket.'),
            'limit' => $schema->integer()->min(1)->max(100)
                ->description('Nombre maximum de tickets retournés.'),
        ];
    }
}
