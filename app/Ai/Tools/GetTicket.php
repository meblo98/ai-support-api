<?php

namespace App\Ai\Tools;

use App\Models\User;
use App\Services\AI\Tools\GetTicketTool as GetTicketAction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetTicket implements Tool
{
    public function __construct(
        private readonly User $user,
        private readonly GetTicketAction $action,
    ) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Consulter un ticket appartenant à l’utilisateur connecté.';
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
            'ticket_id' => $schema->integer()->min(1)->required(),
        ];
    }
}
