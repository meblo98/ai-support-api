<?php

namespace Tests\Feature\Api;

use App\Ai\Agents\SupportAgent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_when_listing_tickets_without_authentication(): void
    {
        $this->getJson('/api/tickets')->assertUnauthorized();
    }

    public function test_registration_creates_a_user_and_returns_a_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.test',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'ada@example.test')
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);
        $this->assertDatabaseHas('users', ['email' => 'ada@example.test']);
    }

    public function test_creating_a_ticket_uses_default_status_and_priority(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['tickets:write']);

        $response = $this->postJson('/api/tickets', [
            'subject' => 'Connexion impossible',
            'description' => 'Le lien de connexion ne fonctionne pas.',
            'user_id' => User::factory()->create()->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.priority', 'medium')
            ->assertJsonPath('data.user_id', $user->id);
        $this->assertDatabaseHas('tickets', [
            'user_id' => $user->id,
            'subject' => 'Connexion impossible',
            'status' => 'open',
            'priority' => 'medium',
        ]);
    }

    public function test_returns_404_when_reading_another_users_ticket(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = $owner->tickets()->create([
            'subject' => 'Privé',
            'description' => 'Contenu privé',
            'status' => 'open',
            'priority' => 'low',
        ]);
        Sanctum::actingAs($otherUser, ['tickets:read']);

        $this->getJson('/api/tickets/'.$ticket->id)->assertNotFound();
    }

    public function test_ai_list_tool_returns_only_tickets_owned_by_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $user->tickets()->create([
            'subject' => 'Mon ticket',
            'description' => 'Visible',
            'status' => 'open',
            'priority' => 'medium',
        ]);
        $otherUser->tickets()->create([
            'subject' => 'Ticket confidentiel',
            'description' => 'Invisible',
            'status' => 'open',
            'priority' => 'medium',
        ]);
        Sanctum::actingAs($user, ['ai:tools']);

        $this->postJson('/api/ai/tools/list_tickets', [
            'arguments' => ['limit' => 100],
        ])->assertOk()
            ->assertJsonCount(1, 'tickets')
            ->assertJsonPath('tickets.0.subject', 'Mon ticket')
            ->assertJsonMissing(['subject' => 'Ticket confidentiel']);
    }

    public function test_unknown_ai_tool_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['ai:tools']);

        $this->postJson('/api/ai/tools/delete_everything', ['arguments' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tool');
    }

    public function test_rejects_status_values_outside_the_supported_set(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['tickets:write']);

        $this->postJson('/api/tickets', [
            'subject' => 'Ticket invalide',
            'description' => 'Description',
            'status' => 'in_progress',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_chat_starts_a_persisted_conversation(): void
    {
        config()->set('ai.providers.openai.key', 'test-key');
        SupportAgent::fake(['Votre ticket est bien pris en charge.'])->preventStrayPrompts();
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['ai:tools']);

        $response = $this->postJson('/api/ai/chat', ['message' => 'Bonjour']);

        $response->assertOk()
            ->assertJsonPath('message', 'Votre ticket est bien pris en charge.')
            ->assertJsonStructure(['conversation_id', 'message']);
        SupportAgent::assertPrompted('Bonjour');
        $this->assertDatabaseHas('agent_conversations', [
            'id' => $response->json('conversation_id'),
            'participant_id' => $user->id,
        ]);
    }

    public function test_chat_cannot_continue_another_users_conversation(): void
    {
        config()->set('ai.providers.openai.key', 'test-key');
        SupportAgent::fake()->preventStrayPrompts();
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $conversationId = app(ConversationStore::class)->storeConversation(
            $owner->getMorphClass(),
            $owner->getKey(),
            'Conversation privée',
        );
        Sanctum::actingAs($otherUser, ['ai:tools']);

        $this->postJson('/api/ai/chat', [
            'message' => 'Montre-moi cette conversation',
            'conversation_id' => $conversationId,
        ])->assertNotFound();

        SupportAgent::assertNeverPrompted();
    }
}
