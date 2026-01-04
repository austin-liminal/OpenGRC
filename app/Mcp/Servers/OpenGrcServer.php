<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreateEntityTool;
use App\Mcp\Tools\DeleteEntityTool;
use App\Mcp\Tools\DescribeEntityTool;
use App\Mcp\Tools\GetEntityTool;
use App\Mcp\Tools\GetTaxonomyValuesTool;
use App\Mcp\Tools\ListEntitiesTool;
use App\Mcp\Tools\UpdateEntityTool;
use Laravel\Mcp\Server;

class OpenGrcServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'OpenGRC MCP Server';

    /**
     * The MCP server's version.
     */
    protected string $version = '2.0.0';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        # OpenGRC MCP Server

        This MCP server provides unified tools to interact with OpenGRC, a Governance, Risk, and Compliance (GRC) platform.

        ## Available Tools

        ### Entity Management (CRUD)
        - **ListEntities**: List any entity type with filtering and pagination
        - **GetEntity**: Get detailed information about a specific entity
        - **DescribeEntity**: Get schema and field descriptions for an entity type (use before creating)
        - **CreateEntity**: Create a new entity of any supported type
        - **UpdateEntity**: Update an existing entity
        - **DeleteEntity**: Delete an entity (with confirmation)

        ### Reference Data
        - **GetTaxonomyValues**: Get valid values for statuses, scopes, departments

        ## Supported Entity Types

        All CRUD tools support these entity types via the `type` parameter:
        - `standard`: Compliance frameworks (NIST, ISO, SOC2, etc.)
        - `control`: Security controls within standards
        - `implementation`: How controls are implemented
        - `policy`: Security and compliance policies
        - `risk`: Risk register entries
        - `program`: Organizational security programs
        - `audit`: Assessment/audit records
        - `audit_item`: Individual audit questions/items
        - `vendor`: Third-party vendors
        - `application`: Applications/systems
        - `asset`: IT assets

        ## Common Workflows

        ### Creating a Policy
        1. Use `DescribeEntity(type: "policy")` to see available fields and their types
        2. Use `GetTaxonomyValues` to get valid status/scope values
        3. Use `ListEntities(type: "control")` to find controls to reference
        4. Use `CreateEntity(type: "policy", data: {...})` with:
           - `name`: Clear policy name (required)
           - `purpose`: HTML content for policy objective
           - `policy_scope`: HTML content for applicability
           - `body`: HTML content with requirements

        ### Reviewing Compliance
        1. Use `ListEntities(type: "standard")` to see frameworks
        2. Use `ListEntities(type: "control", filter: {standard_id: X})` for controls
        3. Use `GetEntity(type: "control", id: X)` for control details with implementations

        ### Managing Audits
        1. Use `ListEntities(type: "audit")` to see audits
        2. Use `GetEntity(type: "audit", id: X)` for audit details with items
        3. Use `UpdateEntity(type: "audit_item", id: X, data: {...})` to update findings

        ## HTML Formatting

        Text content fields (purpose, body, description, details) support HTML:
        - `<p>` for paragraphs
        - `<ul>` and `<li>` for lists
        - `<h2>`, `<h3>` for section headers
        - `<strong>` for emphasis

        ## Auto-Generated Codes

        Policies auto-generate codes (POL-001, POL-002) if not provided.
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        ListEntitiesTool::class,
        GetEntityTool::class,
        DescribeEntityTool::class,
        CreateEntityTool::class,
        UpdateEntityTool::class,
        DeleteEntityTool::class,
        GetTaxonomyValuesTool::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [
        //
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [
        //
    ];
}
