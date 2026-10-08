# API de support client

API REST Laravel de support client avec authentification par token, gestion de tickets et acces aux tickets par un assistant IA. Les outils de l'assistant sont egalement exposes a des clients externes via un serveur MCP.

## Fonctionnalites

- Inscription, connexion et deconnexion avec Laravel Sanctum.
- CRUD des tickets, filtres par statut et priorite, pagination.
- Autorisation basee sur le proprietaire du ticket.
- Conversations IA persistantes et tool calling avec Laravel AI SDK.
- Serveur MCP HTTP authentifie exposant les outils de tickets.

## Technologies

- PHP 8.3 ou plus recent, Laravel 13.
- Laravel Sanctum 4 pour l'authentification API.
- Laravel AI SDK 1.2 pour le chat et les outils IA.
- Laravel MCP 1.0 pour l'integration avec les clients MCP.

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure la base de donnees dans `.env`. `.env.example` utilise SQLite par defaut; les pilotes MySQL et autres pilotes Laravel peuvent egalement etre configures.

Pour activer les appels au modele, renseigne la cle du fournisseur dans `.env`. OpenAI est le fournisseur par defaut :

```dotenv
OPENAI_API_KEY=your-api-key
```

Ne committe jamais cette cle. Le serveur MCP fonctionne independamment du fournisseur IA et ne necessite pas de cle OpenAI.

Applique les migrations et demarre le serveur :

```bash
php artisan migrate
php artisan serve
```

L'API est disponible sur `http://127.0.0.1:8000`.

## Donnees

- Un `User` possede plusieurs `Ticket`.
- Un `Ticket` appartient a un utilisateur et possede plusieurs `TicketMessage`.
- Un `TicketMessage` appartient a un ticket et peut etre associe a un utilisateur.
- Les conversations du SDK sont stockees dans `agent_conversations` et `agent_conversation_messages`.

Statuts de ticket : `open`, `pending`, `resolved`, `closed`.

Priorites : `low`, `medium`, `high`, `urgent`.

A la creation, le statut vaut `open` et la priorite `medium` si aucune valeur n'est fournie. Les messages de ticket disposent d'un modele et de relations Eloquent; aucune route REST dediee aux messages n'est exposee pour le moment.

## Authentification

Creer un compte :

```bash
curl -X POST http://127.0.0.1:8000/api/register \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Client Test",
    "email": "client.test@example.com",
    "password": "SupportTest2026!",
    "password_confirmation": "SupportTest2026!"
  }'
```

La reponse contient un token Sanctum. Garde-le dans une variable shell pour les commandes suivantes :

```bash
TOKEN='paste-token-here'
```

Se connecter a un compte existant : `POST /api/login` avec `email` et `password`. L'inscription et la connexion sont limitees a 5 requetes par minute. Les tokens emis possedent les capacites `tickets:read`, `tickets:write` et `ai:tools`.

## API REST

Toutes les routes ci-dessous, sauf `/api/register` et `/api/login`, necessitent l'en-tete `Authorization: Bearer <TOKEN>`.

| Methode | Chemin | Description |
| --- | --- | --- |
| `POST` | `/api/register` | Creer un compte et emettre un token |
| `POST` | `/api/login` | Se connecter et emettre un token |
| `GET` | `/api/user` | Obtenir l'utilisateur connecte |
| `POST` | `/api/logout` | Revoquer le token courant |
| `GET` | `/api/tickets` | Lister ses tickets |
| `POST` | `/api/tickets` | Creer un ticket |
| `GET` | `/api/tickets/{ticket}` | Consulter un ticket |
| `PATCH` | `/api/tickets/{ticket}` | Modifier un ticket |
| `DELETE` | `/api/tickets/{ticket}` | Supprimer un ticket |

Exemple de creation :

```bash
curl -X POST http://127.0.0.1:8000/api/tickets \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "subject": "Paiement en double",
    "description": "Deux prelevements ont ete faits pour ma commande.",
    "priority": "urgent"
  }'
```

Le `user_id` est toujours determine par le token, jamais par le corps de la requete. Les filtres de `GET /api/tickets` sont `status`, `priority` et `per_page` (1 a 100, 20 par defaut). Un utilisateur ne peut pas consulter les tickets d'un autre; un acces direct a un ticket qui ne lui appartient pas renvoie `404`.

## Chat IA

Le endpoint `POST /api/ai/chat` accepte `message` et un `conversation_id` facultatif. Sans identifiant, une nouvelle conversation est creee; avec un identifiant, elle est reprise si elle appartient a l'utilisateur authentifie.

```bash
curl -X POST http://127.0.0.1:8000/api/ai/chat \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "message": "Cree un ticket urgent pour un paiement en double."
  }'
```

La reponse contient `message` et `conversation_id`. Pour continuer la conversation, envoie l'identifiant renvoye avec ton prochain message. L'assistant peut appeler `list_tickets`, `get_ticket` et `create_ticket`. `GET /api/ai/tools` retourne leurs schemas; `POST /api/ai/tools/{tool}` permet de les appeler directement avec `{ "arguments": { ... } }`. Le chat est limite a 10 requetes par minute. Sans cle de fournisseur, le chat renvoie `503`.

## Serveur MCP

Le serveur MCP HTTP est disponible a l'adresse :

```text
http://127.0.0.1:8000/mcp/support
```

Configure le client MCP avec cette URL et l'en-tete bearer `Authorization: Bearer <TOKEN>`. Le token doit inclure la capacite `ai:tools`. Le serveur expose trois outils :

- `list_tickets` : lister les tickets de l'utilisateur, avec filtres facultatifs `status`, `priority` et `limit`.
- `get_ticket` : consulter un ticket avec `ticket_id`.
- `create_ticket` : creer un ticket avec `subject`, `description` et, en option, `status` et `priority`.

Les outils utilisent l'utilisateur authentifie, valident les arguments et ne peuvent pas acceder aux tickets d'un autre utilisateur. Le endpoint MCP est limite a 60 requetes par minute. Pour l'inspecter localement, utilise `php artisan mcp:inspector mcp/support`.

## Tests

Lancer tous les tests :

```bash
php artisan test
```

La configuration PHPUnit utilise SQLite en memoire; les tests de fonctionnalite necessitent l'extension PHP `pdo_sqlite`. Pour lancer uniquement la suite unitaire :

```bash
php artisan test --testsuite=Unit
```
