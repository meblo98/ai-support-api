<?php

namespace App\Services\AI;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ListUserTickets
{
    /** @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    public function execute(User $user, array $arguments): array
    {
        $validator = Validator::make($arguments, [
            'status' => ['sometimes', 'string', Rule::in(Ticket::STATUSES)],
            'priority' => ['sometimes', 'string', Rule::in(Ticket::PRIORITIES)],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        foreach (array_diff(array_keys($arguments), ['status', 'priority', 'limit']) as $key) {
            $validator->errors()->add($key, 'Ce paramètre n’est pas autorisé.');
        }

        $filters = $validator->validate();
        $tickets = $user->tickets()
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['priority']), fn ($query) => $query->where('priority', $filters['priority']))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit((int) ($filters['limit'] ?? 50))
            ->get();

        return ['tickets' => $tickets->map(fn (Ticket $ticket): array => $ticket->toArray())->all()];
    }
}
