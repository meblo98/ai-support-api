<?php

namespace App\Ai\Tools;

use App\Models\Ticket;
use App\Models\User;
use App\Services\AI\ListUserTickets as ListTicketsAction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListTickets implements Tool
{
    public function __construct(
        private readonly User $user,
        private readonly ListTicketsAction $action,
    ) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Lister les tickets de l’utilisateur connecté, avec filtres facultatifs.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        return json_encode($this->action->execute($this->user, $request->all()), JSON_THROW_ON_ERROR);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->enum(Ticket::STATUSES),
            'priority' => $schema->string()->enum(Ticket::PRIORITIES),
            'limit' => $schema->integer()->min(1)->max(100),
        ];
    }
}
