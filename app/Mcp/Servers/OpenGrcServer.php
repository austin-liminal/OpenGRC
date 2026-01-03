<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreatePolicyTool;
use App\Mcp\Tools\GetPolicyTool;
use App\Mcp\Tools\GetTaxonomyValuesTool;
use App\Mcp\Tools\ListControlsTool;
use App\Mcp\Tools\ListPoliciesTool;
use App\Mcp\Tools\ListStandardsTool;
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
    protected string $version = '1.0.0';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        # OpenGRC MCP Server

        This MCP server provides tools to interact with OpenGRC, a Governance, Risk, and Compliance (GRC) platform.

        ## Available Tools

        ### Policy Management
        - **CreatePolicy**: Create new security/compliance policies with structured fields
        - **ListPolicies**: List all policies with filtering and pagination
        - **GetPolicy**: Get detailed information about a specific policy

        ### Standards & Controls
        - **ListStandards**: List available compliance frameworks (NIST, ISO, SOC2, etc.)
        - **ListControls**: List security controls, optionally filtered by standard

        ### Reference Data
        - **GetTaxonomyValues**: Get valid values for policy status, scope, departments

        ## Creating Policies

        When creating policies:
        1. Use `ListPolicies` first to see existing policy codes and naming patterns
        2. Use `GetTaxonomyValues` to understand valid status and scope values
        3. Use `ListStandards` and `ListControls` to find relevant controls to link
        4. Use `CreatePolicy` with structured content:
           - `name`: Clear, descriptive policy name
           - `purpose`: HTML content explaining the policy's objective
           - `policy_scope`: HTML content defining who/what the policy applies to
           - `body`: HTML content with detailed requirements (sections, lists, etc.)

        ## HTML Formatting

        Policy content fields (purpose, policy_scope, body) support HTML formatting:
        - Use `<p>` for paragraphs
        - Use `<ul>` and `<li>` for lists
        - Use `<h2>`, `<h3>` for section headers
        - Use `<strong>` or `<b>` for emphasis

        ## Policy Code Convention

        If no code is provided, policies are auto-assigned codes following the POL-XXX pattern
        (e.g., POL-001, POL-002). You can also provide custom codes if needed.
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        CreatePolicyTool::class,
        ListPoliciesTool::class,
        GetPolicyTool::class,
        ListStandardsTool::class,
        ListControlsTool::class,
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
