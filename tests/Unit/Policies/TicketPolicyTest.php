<?php

namespace Tests\Unit\Policies;

use App\Models\Ticket;
use App\Models\User;
use App\Policies\TicketPolicy;
use PHPUnit\Framework\TestCase;

class TicketPolicyTest extends TestCase
{
    public function test_only_the_owner_can_view_update_or_delete_a_ticket(): void
    {
        $owner = new User;
        $owner->id = 7;
        $otherUser = new User;
        $otherUser->id = 8;
        $ticket = new Ticket;
        $ticket->user_id = $owner->id;
        $policy = new TicketPolicy;

        $this->assertTrue($policy->view($owner, $ticket));
        $this->assertTrue($policy->update($owner, $ticket));
        $this->assertTrue($policy->delete($owner, $ticket));
        $this->assertFalse($policy->view($otherUser, $ticket));
        $this->assertFalse($policy->update($otherUser, $ticket));
        $this->assertFalse($policy->delete($otherUser, $ticket));
    }
}
