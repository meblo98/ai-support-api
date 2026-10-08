<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreateTicket;
use App\Mcp\Tools\GetTicket;
use App\Mcp\Tools\ListTickets;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Ticket Support Server')]
#[Version('1.0.0')]
#[Instructions('Serveur MCP du support client. Les outils agissent uniquement sur les tickets de l’utilisateur authentifié. Ne demande jamais un identifiant utilisateur et ne divulgue aucune donnée appartenant à un autre utilisateur.')]
class TicketSupportServer extends Server
{
    protected array $tools = [
        ListTickets::class,
        GetTicket::class,
        CreateTicket::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
