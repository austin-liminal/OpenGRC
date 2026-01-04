<?php

namespace Tests\Feature\Mcp;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use App\Mcp\EntityConfig;
use App\Mcp\Tools\CreateEntityTool;
use App\Mcp\Tools\DeleteEntityTool;
use App\Mcp\Tools\DescribeEntityTool;
use App\Mcp\Tools\GetEntityTool;
use App\Mcp\Tools\GetTaxonomyValuesTool;
use App\Mcp\Tools\ListEntitiesTool;
use App\Mcp\Tools\UpdateEntityTool;
use App\Models\Control;
use App\Models\Policy;
use App\Models\Standard;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Tests\TestCase;

class McpToolsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        EntityConfig::clearCache();
    }

    /**
     * Helper to get JSON response from a tool.
     */
    protected function getToolResponse(object $tool, array $arguments): array
    {
        $request = new Request($arguments);
        $response = $tool->handle($request);

        return json_decode((string) $response->content(), true);
    }

    // ========================================
    // ListEntitiesTool Tests
    // ========================================

    /**
     * Test ListEntitiesTool returns error for invalid type.
     */
    public function test_list_entities_returns_error_for_invalid_type(): void
    {
        $tool = new ListEntitiesTool;
        $result = $this->getToolResponse($tool, ['type' => 'invalid_type']);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Unknown entity type', $result['error']);
        $this->assertArrayHasKey('available_types', $result);
    }

    /**
     * Test ListEntitiesTool lists vendors.
     */
    public function test_list_entities_lists_vendors(): void
    {
        Vendor::factory()->count(3)->create();

        $tool = new ListEntitiesTool;
        $result = $this->getToolResponse($tool, ['type' => 'vendor']);

        $this->assertTrue($result['success']);
        $this->assertEquals('vendor', $result['type']);
        $this->assertEquals('Vendor', $result['label']);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertArrayHasKey('vendors', $result);
        $this->assertCount(3, $result['vendors']);
    }

    /**
     * Test ListEntitiesTool pagination works.
     */
    public function test_list_entities_pagination_works(): void
    {
        Vendor::factory()->count(25)->create();

        $tool = new ListEntitiesTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'vendor',
            'per_page' => 10,
            'page' => 2,
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['pagination']['current_page']);
        $this->assertEquals(10, $result['pagination']['per_page']);
        $this->assertEquals(25, $result['pagination']['total']);
        $this->assertEquals(3, $result['pagination']['last_page']);
        $this->assertCount(10, $result['vendors']);
    }

    /**
     * Test ListEntitiesTool search works with Vendor.
     */
    public function test_list_entities_search_works(): void
    {
        Vendor::factory()->create(['name' => 'Microsoft Corporation']);
        Vendor::factory()->create(['name' => 'Google Inc']);
        Vendor::factory()->create(['name' => 'Amazon Web Services']);

        $tool = new ListEntitiesTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'vendor',
            'search' => 'Microsoft',
        ]);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['vendors']);
        $this->assertEquals('Microsoft Corporation', $result['vendors'][0]['name']);
    }

    /**
     * Test ListEntitiesTool filter by related ID works.
     */
    public function test_list_entities_filter_by_related_id(): void
    {
        $standard1 = Standard::factory()->create();
        $standard2 = Standard::factory()->create();

        Control::factory()->count(3)->create(['standard_id' => $standard1->id]);
        Control::factory()->count(2)->create(['standard_id' => $standard2->id]);

        $tool = new ListEntitiesTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'control',
            'filter' => ['standard_id' => $standard1->id],
        ]);

        $this->assertTrue($result['success']);
        $this->assertCount(3, $result['controls']);
    }

    /**
     * Test ListEntitiesTool includes relation data.
     */
    public function test_list_entities_includes_relation_data(): void
    {
        $user = User::factory()->create(['name' => 'Test Manager']);
        Vendor::factory()->create(['name' => 'Test Vendor', 'vendor_manager_id' => $user->id]);

        $tool = new ListEntitiesTool;
        $result = $this->getToolResponse($tool, ['type' => 'vendor']);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('vendor_manager', $result['vendors'][0]);
        $this->assertEquals('Test Manager', $result['vendors'][0]['vendor_manager']['name']);
    }

    /**
     * Test ListEntitiesTool includes counts.
     */
    public function test_list_entities_includes_counts(): void
    {
        $standard = Standard::factory()->create();
        Control::factory()->count(5)->create(['standard_id' => $standard->id]);

        $tool = new ListEntitiesTool;
        $result = $this->getToolResponse($tool, ['type' => 'standard']);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('controls_count', $result['standards'][0]);
        $this->assertEquals(5, $result['standards'][0]['controls_count']);
    }

    /**
     * Test ListEntitiesTool includes URL.
     */
    public function test_list_entities_includes_url(): void
    {
        $vendor = Vendor::factory()->create();

        $tool = new ListEntitiesTool;
        $result = $this->getToolResponse($tool, ['type' => 'vendor']);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('url', $result['vendors'][0]);
        $this->assertStringContainsString('/app/vendors/', $result['vendors'][0]['url']);
    }

    // ========================================
    // GetEntityTool Tests
    // ========================================

    /**
     * Test GetEntityTool returns error for invalid type.
     */
    public function test_get_entity_returns_error_for_invalid_type(): void
    {
        $tool = new GetEntityTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'invalid_type',
            'id' => 1,
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Unknown entity type', $result['error']);
    }

    /**
     * Test GetEntityTool returns error when neither id nor code provided.
     */
    public function test_get_entity_returns_error_without_id_or_code(): void
    {
        $tool = new GetEntityTool;
        $result = $this->getToolResponse($tool, ['type' => 'vendor']);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('id or code', $result['error']);
    }

    /**
     * Test GetEntityTool retrieves entity by ID.
     */
    public function test_get_entity_retrieves_by_id(): void
    {
        $vendor = Vendor::factory()->create([
            'name' => 'Test Vendor',
            'description' => 'A test vendor',
        ]);

        $tool = new GetEntityTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'vendor',
            'id' => $vendor->id,
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('vendor', $result['type']);
        $this->assertArrayHasKey('vendor', $result);
        $this->assertEquals('Test Vendor', $result['vendor']['name']);
    }

    /**
     * Test GetEntityTool retrieves policy by code.
     */
    public function test_get_entity_retrieves_by_code(): void
    {
        Policy::create([
            'name' => 'Security Policy',
            'code' => 'SEC-001',
        ]);

        $tool = new GetEntityTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'policy',
            'code' => 'SEC-001',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('Security Policy', $result['policy']['name']);
    }

    /**
     * Test GetEntityTool returns error for non-existent entity.
     */
    public function test_get_entity_returns_error_for_nonexistent(): void
    {
        $tool = new GetEntityTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'vendor',
            'id' => 99999,
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['error']);
    }

    /**
     * Test GetEntityTool includes relations.
     */
    public function test_get_entity_includes_relations(): void
    {
        $standard = Standard::factory()->create();
        Control::factory()->count(3)->create(['standard_id' => $standard->id]);

        $tool = new GetEntityTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'standard',
            'id' => $standard->id,
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('controls', $result['standard']);
        $this->assertCount(3, $result['standard']['controls']);
    }

    /**
     * Test GetEntityTool includes URL.
     */
    public function test_get_entity_includes_url(): void
    {
        $vendor = Vendor::factory()->create();

        $tool = new GetEntityTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'vendor',
            'id' => $vendor->id,
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('url', $result['vendor']);
        $this->assertStringContainsString("/app/vendors/{$vendor->id}", $result['vendor']['url']);
    }

    // ========================================
    // DescribeEntityTool Tests
    // ========================================

    /**
     * Test DescribeEntityTool returns error for invalid type.
     */
    public function test_describe_entity_returns_error_for_invalid_type(): void
    {
        $tool = new DescribeEntityTool;
        $result = $this->getToolResponse($tool, ['type' => 'invalid_type']);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Unknown entity type', $result['error']);
        $this->assertArrayHasKey('available_types', $result);
    }

    /**
     * Test DescribeEntityTool returns field descriptions.
     */
    public function test_describe_entity_returns_field_descriptions(): void
    {
        $tool = new DescribeEntityTool;
        $result = $this->getToolResponse($tool, ['type' => 'policy']);

        $this->assertTrue($result['success']);
        $this->assertEquals('policy', $result['type']);
        $this->assertEquals('Policy', $result['label']);
        $this->assertArrayHasKey('fields', $result);
        $this->assertArrayHasKey('name', $result['fields']);
        $this->assertArrayHasKey('description', $result['fields']['name']);
    }

    /**
     * Test DescribeEntityTool includes field types.
     */
    public function test_describe_entity_includes_field_types(): void
    {
        $tool = new DescribeEntityTool;
        $result = $this->getToolResponse($tool, ['type' => 'policy']);

        $this->assertArrayHasKey('type', $result['fields']['name']);
        $this->assertArrayHasKey('required', $result['fields']['name']);
    }

    /**
     * Test DescribeEntityTool includes foreign key references.
     */
    public function test_describe_entity_includes_foreign_key_references(): void
    {
        $tool = new DescribeEntityTool;
        $result = $this->getToolResponse($tool, ['type' => 'policy']);

        $this->assertArrayHasKey('owner_id', $result['fields']);
        $this->assertArrayHasKey('references', $result['fields']['owner_id']);
        $this->assertStringContainsString('users', $result['fields']['owner_id']['references']);
    }

    /**
     * Test DescribeEntityTool includes relations.
     */
    public function test_describe_entity_includes_relations(): void
    {
        $tool = new DescribeEntityTool;
        $result = $this->getToolResponse($tool, ['type' => 'standard']);

        $this->assertArrayHasKey('relations', $result);
        $this->assertArrayHasKey('controls', $result['relations']);
        $this->assertArrayHasKey('type', $result['relations']['controls']);
    }

    /**
     * Test DescribeEntityTool includes notes.
     */
    public function test_describe_entity_includes_notes(): void
    {
        $tool = new DescribeEntityTool;
        $result = $this->getToolResponse($tool, ['type' => 'policy']);

        $this->assertArrayHasKey('notes', $result);
        $this->assertIsArray($result['notes']);
        $this->assertNotEmpty($result['notes']);
    }

    /**
     * Test DescribeEntityTool notes mention auto-generated codes.
     */
    public function test_describe_entity_notes_mention_auto_code(): void
    {
        $tool = new DescribeEntityTool;
        $result = $this->getToolResponse($tool, ['type' => 'policy']);

        $notesText = implode(' ', $result['notes']);
        $this->assertStringContainsString('POL', $notesText);
        $this->assertStringContainsString('auto-generated', $notesText);
    }

    /**
     * Test DescribeEntityTool notes mention HTML support.
     */
    public function test_describe_entity_notes_mention_html_support(): void
    {
        $tool = new DescribeEntityTool;
        $result = $this->getToolResponse($tool, ['type' => 'policy']);

        $notesText = implode(' ', $result['notes']);
        $this->assertStringContainsString('HTML', $notesText);
    }

    /**
     * Test DescribeEntityTool includes app URL.
     */
    public function test_describe_entity_includes_app_url(): void
    {
        $tool = new DescribeEntityTool;
        $result = $this->getToolResponse($tool, ['type' => 'vendor']);

        $notesText = implode(' ', $result['notes']);
        $this->assertStringContainsString('/app/vendors', $notesText);
    }

    // ========================================
    // CreateEntityTool Tests
    // ========================================

    /**
     * Test CreateEntityTool returns error for invalid type.
     */
    public function test_create_entity_returns_error_for_invalid_type(): void
    {
        $tool = new CreateEntityTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'invalid_type',
            'data' => ['name' => 'Test'],
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Unknown entity type', $result['error']);
    }

    /**
     * Test CreateEntityTool creates vendor with required manager.
     */
    public function test_create_entity_creates_vendor(): void
    {
        // Vendor requires a vendor_manager_id (FK to users)
        $user = User::factory()->create();

        $tool = new CreateEntityTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'vendor',
            'data' => [
                'name' => 'Acme Corporation',
                'description' => 'A vendor for testing',
                'vendor_manager_id' => $user->id,
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('created successfully', $result['message']);
        $this->assertArrayHasKey('vendor', $result);
        $this->assertEquals('Acme Corporation', $result['vendor']['name']);

        $this->assertDatabaseHas('vendors', [
            'name' => 'Acme Corporation',
        ]);
    }

    /**
     * Test CreateEntityTool validates required fields.
     */
    public function test_create_entity_validates_required_fields(): void
    {
        $tool = new CreateEntityTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'vendor',
            'data' => [
                // Missing required 'name' field
                'description' => 'Test description',
            ],
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Validation failed', $result['error']);
        $this->assertArrayHasKey('validation_errors', $result);
    }

    /**
     * Test CreateEntityTool prevents duplicate codes for Policy.
     */
    public function test_create_entity_prevents_duplicate_codes(): void
    {
        Policy::create(['name' => 'First Policy', 'code' => 'POL-001']);

        $tool = new CreateEntityTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'policy',
            'data' => [
                'name' => 'New Policy',
                'code' => 'POL-001',
            ],
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already exists', $result['error']);
    }

    /**
     * Test CreateEntityTool auto-generates policy code.
     */
    public function test_create_entity_auto_generates_policy_code(): void
    {
        // Create taxonomy for policy status
        Taxonomy::create([
            'type' => 'policy-status',
            'name' => 'Draft',
            'slug' => 'draft',
        ]);

        $tool = new CreateEntityTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'policy',
            'data' => [
                'name' => 'Test Policy',
                // No code provided - should auto-generate
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('POL-', $result['policy']['code']);
    }

    /**
     * Test CreateEntityTool sequential policy code generation.
     */
    public function test_create_entity_sequential_policy_codes(): void
    {
        // Create taxonomy for policy status
        Taxonomy::create([
            'type' => 'policy-status',
            'name' => 'Draft',
            'slug' => 'draft',
        ]);

        // Create first policy
        Policy::create(['name' => 'First', 'code' => 'POL-001']);

        $tool = new CreateEntityTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'policy',
            'data' => ['name' => 'Second Policy'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('POL-002', $result['policy']['code']);
    }

    /**
     * Test CreateEntityTool returns URL in response.
     */
    public function test_create_entity_returns_url(): void
    {
        $user = User::factory()->create();

        $tool = new CreateEntityTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'vendor',
            'data' => [
                'name' => 'URL Test Vendor',
                'vendor_manager_id' => $user->id,
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('url', $result['vendor']);
        $this->assertStringContainsString('/app/vendors/', $result['vendor']['url']);
    }

    // ========================================
    // UpdateEntityTool Tests
    // ========================================

    /**
     * Test UpdateEntityTool returns error for invalid type.
     */
    public function test_update_entity_returns_error_for_invalid_type(): void
    {
        $tool = new UpdateEntityTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'invalid_type',
            'id' => 1,
            'data' => ['name' => 'Updated'],
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Unknown entity type', $result['error']);
    }

    /**
     * Test UpdateEntityTool returns error for nonexistent entity.
     */
    public function test_update_entity_returns_error_for_nonexistent(): void
    {
        $tool = new UpdateEntityTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'vendor',
            'id' => 99999,
            'data' => ['name' => 'Updated'],
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['error']);
    }

    /**
     * Test UpdateEntityTool updates entity.
     */
    public function test_update_entity_updates_entity(): void
    {
        $vendor = Vendor::factory()->create(['name' => 'Original Name']);

        $tool = new UpdateEntityTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'vendor',
            'id' => $vendor->id,
            'data' => ['name' => 'Updated Name'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('updated successfully', $result['message']);
        $this->assertContains('name', $result['updated_fields']);

        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'name' => 'Updated Name',
        ]);
    }

    /**
     * Test UpdateEntityTool prevents duplicate codes for Policy.
     */
    public function test_update_entity_prevents_duplicate_codes(): void
    {
        Policy::create(['name' => 'First', 'code' => 'POL-001']);
        $policy = Policy::create(['name' => 'Second', 'code' => 'POL-002']);

        $tool = new UpdateEntityTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'policy',
            'id' => $policy->id,
            'data' => ['code' => 'POL-001'],
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already exists', $result['error']);
    }

    /**
     * Test UpdateEntityTool only updates allowed fields.
     */
    public function test_update_entity_only_updates_allowed_fields(): void
    {
        $vendor = Vendor::factory()->create(['name' => 'Original']);

        $tool = new UpdateEntityTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'vendor',
            'id' => $vendor->id,
            'data' => [
                'name' => 'Updated',
                'id' => 99999, // Should be ignored
            ],
        ]);

        $this->assertTrue($result['success']);
        $vendor->refresh();
        $this->assertEquals('Updated', $vendor->name);
        $this->assertNotEquals(99999, $vendor->id);
    }

    /**
     * Test UpdateEntityTool returns error when no valid fields provided.
     */
    public function test_update_entity_returns_error_when_no_valid_fields(): void
    {
        $vendor = Vendor::factory()->create();

        $tool = new UpdateEntityTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'vendor',
            'id' => $vendor->id,
            'data' => [
                'invalid_field' => 'value',
            ],
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No valid fields', $result['error']);
    }

    // ========================================
    // DeleteEntityTool Tests
    // ========================================

    /**
     * Test DeleteEntityTool returns error without confirmation.
     */
    public function test_delete_entity_returns_error_without_confirmation(): void
    {
        $vendor = Vendor::factory()->create();

        $tool = new DeleteEntityTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'vendor',
            'id' => $vendor->id,
            'confirm' => false,
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not confirmed', $result['error']);
    }

    /**
     * Test DeleteEntityTool returns error for invalid type.
     */
    public function test_delete_entity_returns_error_for_invalid_type(): void
    {
        $tool = new DeleteEntityTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'invalid_type',
            'id' => 1,
            'confirm' => true,
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Unknown entity type', $result['error']);
    }

    /**
     * Test DeleteEntityTool returns error for nonexistent entity.
     */
    public function test_delete_entity_returns_error_for_nonexistent(): void
    {
        $tool = new DeleteEntityTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'vendor',
            'id' => 99999,
            'confirm' => true,
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['error']);
    }

    /**
     * Test DeleteEntityTool deletes entity.
     */
    public function test_delete_entity_deletes_entity(): void
    {
        $vendor = Vendor::factory()->create(['name' => 'Delete Me']);

        $tool = new DeleteEntityTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'vendor',
            'id' => $vendor->id,
            'confirm' => true,
        ]);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('deleted', $result['message']);
        $this->assertStringContainsString('Delete Me', $result['message']);
    }

    /**
     * Test DeleteEntityTool soft deletes when model supports it.
     */
    public function test_delete_entity_soft_deletes(): void
    {
        $vendor = Vendor::factory()->create();

        $tool = new DeleteEntityTool;
        $result = $this->getToolResponse($tool, [
            'type' => 'vendor',
            'id' => $vendor->id,
            'confirm' => true,
        ]);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['soft_deleted']);
        $this->assertTrue($result['restorable']);

        // Vendor should be soft deleted
        $this->assertSoftDeleted('vendors', ['id' => $vendor->id]);
    }

    // ========================================
    // GetTaxonomyValuesTool Tests
    // ========================================

    /**
     * Test GetTaxonomyValuesTool returns default taxonomies.
     */
    public function test_get_taxonomy_values_returns_defaults(): void
    {
        // Create some taxonomy values
        Taxonomy::create(['type' => 'policy-status', 'name' => 'Draft', 'slug' => 'draft']);
        Taxonomy::create(['type' => 'policy-status', 'name' => 'Approved', 'slug' => 'approved']);
        Taxonomy::create(['type' => 'policy-scope', 'name' => 'Organization-wide', 'slug' => 'organization-wide']);

        $tool = new GetTaxonomyValuesTool;
        $result = $this->getToolResponse($tool, []);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('taxonomies', $result);
        $this->assertArrayHasKey('policy-status', $result['taxonomies']);
        $this->assertArrayHasKey('policy-scope', $result['taxonomies']);
    }

    /**
     * Test GetTaxonomyValuesTool returns specific type.
     */
    public function test_get_taxonomy_values_returns_specific_type(): void
    {
        Taxonomy::create(['type' => 'policy-status', 'name' => 'Draft', 'slug' => 'draft']);
        Taxonomy::create(['type' => 'department', 'name' => 'IT', 'slug' => 'it']);

        $tool = new GetTaxonomyValuesTool;
        $result = $this->getToolResponse($tool, ['type' => 'policy-status']);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('policy-status', $result['taxonomies']);
        $this->assertArrayNotHasKey('department', $result['taxonomies']);
    }

    /**
     * Test GetTaxonomyValuesTool includes taxonomy details.
     */
    public function test_get_taxonomy_values_includes_details(): void
    {
        Taxonomy::create([
            'type' => 'policy-status',
            'name' => 'Draft',
            'slug' => 'draft',
            'description' => 'A draft policy',
        ]);

        $tool = new GetTaxonomyValuesTool;
        $result = $this->getToolResponse($tool, ['type' => 'policy-status']);

        $this->assertTrue($result['success']);
        $taxonomy = $result['taxonomies']['policy-status'][0];
        $this->assertArrayHasKey('id', $taxonomy);
        $this->assertArrayHasKey('name', $taxonomy);
        $this->assertArrayHasKey('slug', $taxonomy);
        $this->assertArrayHasKey('description', $taxonomy);
        $this->assertEquals('Draft', $taxonomy['name']);
    }

    /**
     * Test GetTaxonomyValuesTool returns empty array for nonexistent type.
     */
    public function test_get_taxonomy_values_returns_empty_for_nonexistent(): void
    {
        $tool = new GetTaxonomyValuesTool;
        $result = $this->getToolResponse($tool, ['type' => 'nonexistent-type']);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('nonexistent-type', $result['taxonomies']);
        $this->assertEmpty($result['taxonomies']['nonexistent-type']);
    }
}
