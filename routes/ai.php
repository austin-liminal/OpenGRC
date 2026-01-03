<?php

use App\Mcp\Servers\OpenGrcServer;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| AI Routes (MCP Servers)
|--------------------------------------------------------------------------
|
| This file defines the MCP (Model Context Protocol) server endpoints.
| These routes allow AI clients to interact with OpenGRC via HTTP.
|
| Endpoint: POST /mcp/opengrc
| Auth: Sanctum Bearer token
|
*/

// HTTP MCP endpoint - requires Sanctum API token
// Works locally (http://127.0.0.1:8000/mcp/opengrc) and remotely
Mcp::web('/mcp/opengrc', OpenGrcServer::class)
    ->middleware(['auth:sanctum', 'throttle:mcp']);
