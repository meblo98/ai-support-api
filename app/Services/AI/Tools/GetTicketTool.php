<?php

namespace App\Services\AI\Tools;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class GetTicketTool
{
    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function execute(User $user, array $arguments): array
    {
        $validator = Validator::make($arguments, [
            'ticket_id' => ['required', 'integer', 'min:1'],
        ]);

        foreach (array_diff(array_keys($arguments), ['ticket_id']) as $key) {
            $validator->errors()->add($key, 'Ce paramètre n’est pas autorisé.');
        }

        $validated = $validator->validate();
        $ticket = $user->tickets()->findOrFail($validated['ticket_id']);
        Gate::forUser($user)->authorize('view', $ticket);

        return ['ticket' => $ticket->toArray()];
    }
}