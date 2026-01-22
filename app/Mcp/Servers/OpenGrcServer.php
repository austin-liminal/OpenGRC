<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\AuditPreparationPrompt;
use App\Mcp\Prompts\ComplianceSummaryPrompt;
use App\Mcp\Prompts\GapAnalysisPrompt;
use App\Mcp\Prompts\PolicyDraftPrompt;
use App\Mcp\Prompts\RiskAssessmentPrompt;
use App\Mcp\Tools\ManageApplicationTool;
use App\Mcp\Tools\ManageAssetTool;
use App\Mcp\Tools\ManageAuditItemTool;
use App\Mcp\Tools\ManageAuditTool;
use App\Mcp\Tools\ManageControlTool;
use App\Mcp\Tools\ManageImplementationTool;
use App\Mcp\Tools\ManagePolicyTool;
use App\Mcp\Tools\ManageProgramTool;
use App\Mcp\Tools\ManageRiskTool;
use App\Mcp\Tools\ManageStandardTool;
use App\Mcp\Tools\ManageTaxonomyTool;
use App\Mcp\Tools\ManageVendorTool;
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
    protected string $version = '3.0.0';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        # OpenGRC MCP Server

        This MCP server provides tools and resources to interact with OpenGRC, a Governance, Risk, and Compliance (GRC) platform.

        ## Tools (Entity Management)

        Each entity type has a management tool with list, get, create, update, and delete actions:

        - `ManageApplication` - Manage applications/systems
        - `ManageAsset` - Manage IT assets
        - `ManageAudit` - Manage assessment/audit records
        - `ManageAuditItem` - Manage individual audit questions/items
        - `ManageControl` - Manage security controls within standards
        - `ManageImplementation` - Manage how controls are implemented
        - `ManagePolicy` - Manage security and compliance policies
        - `ManageProgram` - Manage organizational security programs
        - `ManageRisk` - Manage risk register entries
        - `ManageStandard` - Manage compliance frameworks (NIST, ISO, SOC2, etc.)
        - `ManageTaxonomy` - **Manage taxonomy types and terms** (departments, scopes, statuses, etc.)
        - `ManageVendor` - Manage third-party vendors

        ### Tool Actions

        All Manage* tools (except ManageTaxonomy) support these actions:

        **List (paginated):**
        ```json
        {"action": "list"}
        {"action": "list", "page": 2}
        ```

        **Get (by ID):**
        ```json
        {"action": "get", "id": 1}
        ```

        **Create:**
        ```json
        {"action": "create", "data": {"name": "Security Policy", "purpose": "<p>Objective...</p>"}}
        ```

        **Update:**
        ```json
        {"action": "update", "id": 1, "data": {"name": "Updated Name"}}
        ```

        **Delete:**
        ```json
        {"action": "delete", "id": 1, "confirm": true}
        ```

        ## Taxonomies (IMPORTANT)

        Many entities use **taxonomy fields** for categorization. These are foreign key references to taxonomy terms. Before creating or updating entities with taxonomy fields, you MUST look up the correct taxonomy term ID.

        ### Common Taxonomy Fields

        - **Policy**: `department_id`, `scope_id`, `status_id`
        - **Asset**: `asset_type_id`, `status_id`, `condition_id`, `compliance_status_id`, `data_classification_id`

        ### ManageTaxonomy Actions

        **1. List taxonomy types** (see all available taxonomies):
        ```json
        {"action": "list_types"}
        ```
        Returns: Department, Scope, Policy Status, Asset Type, Asset Status, etc.

        **2. List terms within a type** (see available values):
        ```json
        {"action": "list_terms", "type": "department"}
        {"action": "list_terms", "type": "policy-status"}
        {"action": "list_terms", "type": "scope"}
        ```
        Returns: All terms with their IDs.

        **3. Get term by ID or by type+name**:
        ```json
        {"action": "get", "id": 5}
        {"action": "get", "type": "department", "name": "IT"}
        ```
        Returns: The term's ID and details.

        **4. Create a new term**:
        ```json
        {"action": "create", "type": "department", "data": {"name": "Legal", "description": "Legal department"}}
        ```

        **5. Update a term**:
        ```json
        {"action": "update", "id": 5, "data": {"name": "Updated Name"}}
        ```

        **6. Delete a term**:
        ```json
        {"action": "delete", "id": 5, "confirm": true}
        ```

        ### Example: Creating a Policy with Department

        1. First, find the department ID:
        ```json
        ManageTaxonomy: {"action": "get", "type": "department", "name": "IT"}
        // Response: {"id": 3, "name": "IT", ...}
        ```

        2. Then create the policy with that ID:
        ```json
        ManagePolicy: {"action": "create", "data": {"name": "Access Control Policy", "department_id": 3, "purpose": "<p>...</p>"}}
        ```

        ### Example: Setting Asset Classification

        1. Find available data classifications:
        ```json
        ManageTaxonomy: {"action": "list_terms", "type": "data-classification"}
        // Response: Confidential (id: 10), Internal (id: 11), Public (id: 12), Restricted (id: 13)
        ```

        2. Update the asset:
        ```json
        ManageAsset: {"action": "update", "id": 1, "data": {"data_classification_id": 10}}
        ```

        ## Common Workflows

        ### Creating a Policy
        1. Use ManageTaxonomy with action="list_terms" type="department" to find departments
        2. Use ManageTaxonomy with action="list_terms" type="scope" to find scopes
        3. Use ManagePolicy with action="create" including department_id and scope_id

        ### Reviewing Compliance
        1. Use ManageStandard with action="list" to see frameworks
        2. Use ManageControl with action="list" to list controls
        3. Use ManageControl with action="get" and id for control details

        ### Managing Audits
        1. Use ManageAudit with action="list" to see audits
        2. Use ManageAudit with action="get" and id for audit details
        3. Use ManageAuditItem with action="update" to update items

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
        ManageApplicationTool::class,
        ManageAssetTool::class,
        ManageAuditTool::class,
        ManageAuditItemTool::class,
        ManageControlTool::class,
        ManageImplementationTool::class,
        ManagePolicyTool::class,
        ManageProgramTool::class,
        ManageRiskTool::class,
        ManageStandardTool::class,
        ManageTaxonomyTool::class,
        ManageVendorTool::class,
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [
        GapAnalysisPrompt::class,
        RiskAssessmentPrompt::class,
        PolicyDraftPrompt::class,
        AuditPreparationPrompt::class,
        ComplianceSummaryPrompt::class,
    ];
}
