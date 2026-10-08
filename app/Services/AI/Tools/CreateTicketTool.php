<?php

namespace App\Services\AI\Tools;

use App\Http\Requests\CreateTicketRequest;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CreateTicketTool
{
    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function execute(User $user, array $arguments): array
    {
        Gate::forUser($user)->authorize('create', Ticket::class);

        $validator = Validator::make($arguments, CreateTicketRequest::ticketRules());

        foreach (array_diff(array_keys($arguments), ['subject', 'description', 'status', 'priority']) as $key) {
            $validator->errors()->add($key, 'Ce paramètre n’est pas autorisé.');
        }

        $validated = $validator->validate();
        $validated['status'] ??= 'open';
        $validated['priority'] ??= 'medium';

        $ticket = $user->tickets()->create($validated);

        return ['ticket' => $ticket->toArray()];
    }
}
