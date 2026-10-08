<?php

namespace App\Services\AI;

use App\Models\User;
use App\Services\AI\Tools\CreateTicketTool;
use App\Services\AI\Tools\GetTicketTool;
use App\Services\AI\Tools\ListTicketsTool;
use Illuminate\Validation\ValidationException;

class AIService
{
    public function __construct(
        private readonly ListTicketsTool $listTickets,
        private readonly GetTicketTool $getTicket,
        private readonly CreateTicketTool $createTicket,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function tools(): array
    {
        return [
            [
                'name' => 'list_tickets',
                'description' => 'Lister les tickets appartenant à l’utilisateur connecté.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'status' => ['type' => 'string', 'enum' => ['open', 'pending', 'resolved', 'closed']],
                        'priority' => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'urgent']],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                    ],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'get_ticket',
                'description' => 'Consulter un ticket appartenant à l’utilisateur connecté.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => ['ticket_id' => ['type' => 'integer', 'minimum' => 1]],
                    'required' => ['ticket_id'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'create_ticket',
                'description' => 'Créer un ticket pour l’utilisateur connecté.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'subject' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'status' => ['type' => 'string', 'enum' => ['open', 'pending', 'resolved', 'closed']],
                        'priority' => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'urgent']],
                    ],
                    'required' => ['subject', 'description'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function execute(User $user, string $tool, array $arguments): array
    {
        return match ($tool) {
            'list_tickets' => $this->listTickets->execute($user, $arguments),
            'get_ticket' => $this->getTicket->execute($user, $arguments),
            'create_ticket' => $this->createTicket->execute($user, $arguments),
            default => throw ValidationException::withMessages([
                'tool' => 'Cet outil n’est pas disponible.',
            ]),
        };
    }
}
