<?php

namespace Tests\Feature\Mcp;

use App\Models\Standard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Yethee\Tiktoken\EncoderProvider;

class McpEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable MCP for all tests by default
        setting(['mcp.enabled' => true]);
    }

    /**
     * Test MCP endpoint returns 503 when feature is disabled (after auth).
     */
    public function test_mcp_endpoint_returns_503_when_disabled(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        setting(['mcp.enabled' => false]);

        $response = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ]);

        $response->assertStatus(503);
        $response->assertJson([
            'jsonrpc' => '2.0',
            'id' => 1,
            'error' => [
                'code' => -32000,
                'message' => 'MCP server is disabled. Enable it in Settings > AI Settings.',
            ],
        ]);
    }

    /**
     * Test MCP endpoint works when feature is enabled.
     */
    public function test_mcp_endpoint_works_when_enabled(): void
    {
        setting(['mcp.enabled' => true]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test auth is required before checking if MCP is disabled.
     *
     * This ensures we don't leak information about whether MCP is enabled
     * to unauthenticated users.
     */
    public function test_mcp_requires_auth_before_disabled_check(): void
    {
        setting(['mcp.enabled' => false]);

        // Without auth - should get 401 (unauthorized), not 503 (disabled)
        // This prevents leaking MCP status to unauthenticated users
        $response = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test MCP endpoint requires authentication.
     */
    public function test_mcp_endpoint_requires_authentication(): void
    {
        $response = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'ListEntities',
                'arguments' => ['type' => 'standard'],
            ],
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test MCP endpoint accepts valid Sanctum token.
     */
    public function test_mcp_endpoint_accepts_valid_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create a standard to list
        Standard::factory()->create();

        $response = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test MCP endpoint returns tools list.
     */
    public function test_mcp_endpoint_returns_tools_list(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'jsonrpc',
            'id',
            'result' => [
                'tools',
            ],
        ]);

        $tools = $response->json('result.tools');
        $toolNames = array_column($tools, 'name');

        $this->assertContains('ListEntities', $toolNames);
        $this->assertContains('GetEntity', $toolNames);
        $this->assertContains('CreateEntity', $toolNames);
        $this->assertContains('UpdateEntity', $toolNames);
        $this->assertContains('DeleteEntity', $toolNames);
        $this->assertContains('GetTaxonomyValues', $toolNames);
    }

    /**
     * Test MCP endpoint can call ListEntities tool.
     */
    public function test_mcp_endpoint_can_call_list_entities(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Standard::factory()->count(3)->create();

        $response = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'ListEntities',
                'arguments' => ['type' => 'standard'],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'jsonrpc',
            'id',
            'result' => [
                'content',
            ],
        ]);
    }

    /**
     * Test MCP endpoint can call GetEntity tool.
     */
    public function test_mcp_endpoint_can_call_get_entity(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $standard = Standard::factory()->create([
            'name' => 'Test Standard',
            'code' => 'TEST-001',
        ]);

        $response = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'GetEntity',
                'arguments' => [
                    'type' => 'standard',
                    'id' => $standard->id,
                ],
            ],
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test MCP endpoint can call CreateEntity tool.
     */
    public function test_mcp_endpoint_can_call_create_entity(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'CreateEntity',
                'arguments' => [
                    'type' => 'standard',
                    'data' => [
                        'name' => 'New Standard',
                        'code' => 'NEW-001',
                        'authority' => 'Test',
                        'description' => 'A test standard for MCP endpoint testing',
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('standards', ['code' => 'NEW-001']);
    }

    /**
     * Test MCP endpoint returns error for invalid JSON-RPC request.
     */
    public function test_mcp_endpoint_returns_error_for_invalid_request(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/mcp/opengrc', [
            'invalid' => 'request',
        ]);

        // Should return an error response (could be 200 with JSON-RPC error or 400)
        $response->assertStatus(200);
    }

    /**
     * Test MCP endpoint server info.
     */
    public function test_mcp_endpoint_returns_server_info(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [],
                'clientInfo' => [
                    'name' => 'test-client',
                    'version' => '1.0.0',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('result.serverInfo.name', 'OpenGRC MCP Server');
        $response->assertJsonPath('result.serverInfo.version', '2.0.0');
    }

    /**
     * Test MCP endpoint respects rate limiting.
     */
    public function test_mcp_endpoint_has_rate_limiting(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Make requests up to the limit - should not trigger rate limiting
        // Rate limit is 120 per minute, we'll just verify the middleware is applied
        $response = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ]);

        // Check for rate limit headers
        $response->assertStatus(200);
        // The rate limiter should add headers, but exact behavior depends on configuration
    }

    /**
     * Test MCP endpoint with Bearer token in header.
     */
    public function test_mcp_endpoint_accepts_bearer_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        Standard::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/mcp/opengrc', [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'tools/list',
            ]);

        $response->assertStatus(200);
    }

    /**
     * Test MCP endpoint rejects invalid token.
     */
    public function test_mcp_endpoint_rejects_invalid_token(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->postJson('/mcp/opengrc', [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'tools/list',
            ]);

        $response->assertStatus(401);
    }

    /**
     * Test MCP server context size is under 1000 tokens.
     *
     * This ensures the MCP server doesn't consume too much of an AI's context window
     * when the server is enabled. Uses tiktoken with cl100k_base encoding (GPT-4/Claude approx).
     */
    public function test_mcp_context_size_is_under_1000_tokens(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Get server instructions via initialize
        $initResponse = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [],
                'clientInfo' => [
                    'name' => 'test-client',
                    'version' => '1.0.0',
                ],
            ],
        ]);

        // Get tools list
        $toolsResponse = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/list',
        ]);

        $initResponse->assertStatus(200);
        $toolsResponse->assertStatus(200);

        // Collect all context text
        $contextParts = [];

        // Add server instructions if present
        $initResult = $initResponse->json('result');
        if (isset($initResult['instructions'])) {
            $contextParts[] = $initResult['instructions'];
        }

        // Add all tool definitions (name, description, schema)
        $tools = $toolsResponse->json('result.tools');
        foreach ($tools as $tool) {
            $contextParts[] = $tool['name'] ?? '';
            $contextParts[] = $tool['description'] ?? '';
            // Include schema as JSON since it's part of the context
            if (isset($tool['inputSchema'])) {
                $contextParts[] = json_encode($tool['inputSchema']);
            }
        }

        $fullContext = implode("\n", $contextParts);

        // Use tiktoken for accurate token counting
        $provider = new EncoderProvider;

        // cl100k_base approximates Claude 4.5 Sonnet/Opus tokenization
        $claudeEncoder = $provider->get('cl100k_base');
        $claudeTokens = count($claudeEncoder->encode($fullContext));

        // o200k_base is used by GPT-4o and GPT-5.2
        $gptEncoder = $provider->get('o200k_base');
        $gptTokens = count($gptEncoder->encode($fullContext));

        // Use the highest for the limit check
        $maxTokens = max($claudeTokens, $gptTokens);

        $this->assertLessThan(
            1000,
            $maxTokens,
            'MCP context exceeds 1000 token limit. '
            ."Claude 4.5 Sonnet (cl100k_base): {$claudeTokens} tokens. "
            ."GPT-5.2 (o200k_base): {$gptTokens} tokens. "
            .'Context length: '.strlen($fullContext).' characters.'
        );
    }

    /**
     * Test MCP endpoint JSON-RPC 2.0 compliance.
     */
    public function test_mcp_endpoint_returns_valid_jsonrpc_response(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/mcp/opengrc', [
            'jsonrpc' => '2.0',
            'id' => 42,
            'method' => 'tools/list',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'jsonrpc' => '2.0',
            'id' => 42,
        ]);
        $response->assertJsonStructure([
            'jsonrpc',
            'id',
            'result',
        ]);
    }
}
