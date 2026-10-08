<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Ticket::class);

        $filters = $request->validate([
            'status' => ['sometimes', 'string', Rule::in(Ticket::STATUSES)],
            'priority' => ['sometimes', 'string', Rule::in(Ticket::PRIORITIES)],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $tickets = $request->user()->tickets()
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['priority']), fn ($query) => $query->where('priority', $filters['priority']))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 20));

        return response()->json($tickets);
    }

    public function store(CreateTicketRequest $request): JsonResponse
    {
        Gate::authorize('create', Ticket::class);

        $validated = $request->validated();
        $validated['status'] ??= 'open';
        $validated['priority'] ??= 'medium';

        $ticket = $request->user()->tickets()->create($validated);

        return response()->json(['data' => $ticket], 201);
    }

    public function show(Request $request, string $ticket): JsonResponse
    {
        $ticket = $request->user()->tickets()->findOrFail($ticket);
        Gate::authorize('view', $ticket);

        return response()->json(['data' => $ticket]);
    }

    public function update(UpdateTicketRequest $request, string $ticket): JsonResponse
    {
        $ticket = $request->user()->tickets()->findOrFail($ticket);
        Gate::authorize('update', $ticket);
        $ticket->update($request->validated());

        return response()->json(['data' => $ticket->refresh()]);
    }

    public function destroy(Request $request, string $ticket): JsonResponse
    {
        $ticket = $request->user()->tickets()->findOrFail($ticket);
        Gate::authorize('delete', $ticket);
        $ticket->delete();

        return response()->json([], 204);
    }
}
