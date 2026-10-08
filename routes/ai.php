<?php

use App\Mcp\Servers\TicketSupportServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/support', TicketSupportServer::class)
    ->middleware(['auth:sanctum', 'abilities:ai:tools', 'throttle:60,1']);
