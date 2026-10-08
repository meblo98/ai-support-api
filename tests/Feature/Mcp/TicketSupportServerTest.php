<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\TicketSupportServer;
use App\Mcp\Tools\CreateTicket;
use App\Mcp\Tools\ListTickets;
use App\Models\User;
use Tests\TestCase;

class TicketSupportServerTest extends TestCase
{
    public function test_mcp_list_tool_only_returns_tickets_owned_by_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $user->tickets()->create([
            'subject' => 'Ticket du client',
            'description' => 'Visible dans son espace.',
            'status' => 'open',
            'priority' => 'medium',
        ]);
        $otherUser->tickets()->create([
            'subject' => 'Ticket privé',
            'description' => 'Ne doit pas être exposé.',
            'status' => 'open',
            'priority' => 'high',
        ]);

        $response = TicketSupportServer::actingAs($user)
            ->tool(ListTickets::class);

        $response->assertOk()
            ->assertSee('Ticket du client')
            ->assertDontSee('Ticket privé');
    }

    public function test_mcp_create_tool_assigns_the_ticket_to_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = TicketSupportServer::actingAs($user)
            ->tool(CreateTicket::class, [
                'subject' => 'Commande bloquée',
                'description' => 'La commande ne peut pas être finalisée.',
                'priority' => 'urgent',
            ]);

        $response->assertOk()
            ->assertSee('Commande bloquée');
        $this->assertDatabaseHas('tickets', [
            'user_id' => $user->id,
            'subject' => 'Commande bloquée',
            'priority' => 'urgent',
        ]);
    }
}
