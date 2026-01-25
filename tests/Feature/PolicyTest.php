<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Models\Control;
use App\Models\Implementation;
use App\Models\Policy;
use App\Models\PolicyException;
use App\Models\Risk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function policy_can_be_created_with_required_fields(): void
    {
        $policy = Policy::create([
            'code' => 'POL-001',
            'name' => 'Information Security Policy',
            'document_type' => DocumentType::Policy,
        ]);

        $this->assertDatabaseHas('policies', [
            'code' => 'POL-001',
            'name' => 'Information Security Policy',
            'document_type' => 'policy',
        ]);

        $this->assertEquals('POL-001', $policy->code);
        $this->assertEquals('Information Security Policy', $policy->name);
        $this->assertEquals(DocumentType::Policy, $policy->document_type);
    }

    #[Test]
    public function policy_can_be_created_with_all_fields(): void
    {
        $user = User::factory()->create();

        $policy = Policy::create([
            'code' => 'POL-002',
            'name' => 'Access Control Policy',
            'document_type' => DocumentType::Policy,
            'policy_scope' => 'This policy applies to all employees.',
            'purpose' => 'To establish access control requirements.',
            'body' => 'Detailed policy content goes here.',
            'owner_id' => $user->id,
            'effective_date' => '2024-01-01',
            'revision_history' => [
                ['version' => '1.0', 'date' => '2024-01-01', 'author' => 'Admin', 'changes' => 'Initial release'],
            ],
        ]);

        $this->assertDatabaseHas('policies', [
            'code' => 'POL-002',
            'name' => 'Access Control Policy',
            'owner_id' => $user->id,
        ]);

        $this->assertEquals('This policy applies to all employees.', $policy->policy_scope);
        $this->assertEquals('To establish access control requirements.', $policy->purpose);
        $this->assertIsArray($policy->revision_history);
        $this->assertCount(1, $policy->revision_history);
    }

    #[Test]
    public function policy_belongs_to_owner(): void
    {
        $user = User::factory()->create(['name' => 'Policy Owner']);

        $policy = Policy::factory()->create([
            'owner_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $policy->owner);
        $this->assertEquals('Policy Owner', $policy->owner->name);
        $this->assertEquals($user->id, $policy->owner->id);
    }

    #[Test]
    public function policy_belongs_to_creator(): void
    {
        $user = User::factory()->create(['name' => 'Creator User']);
        $this->actingAs($user);

        $policy = Policy::create([
            'code' => 'POL-003',
            'name' => 'Test Policy',
            'document_type' => DocumentType::Policy,
        ]);

        $this->assertInstanceOf(User::class, $policy->creator);
        $this->assertEquals('Creator User', $policy->creator->name);
        $this->assertEquals($user->id, $policy->created_by);
    }

    #[Test]
    public function policy_belongs_to_updater(): void
    {
        $creator = User::factory()->create(['name' => 'Creator']);
        $updater = User::factory()->create(['name' => 'Updater']);

        $this->actingAs($creator);
        $policy = Policy::create([
            'code' => 'POL-004',
            'name' => 'Test Policy',
            'document_type' => DocumentType::Policy,
        ]);

        $this->actingAs($updater);
        $policy->update(['name' => 'Updated Policy Name']);

        $policy->refresh();
        $this->assertInstanceOf(User::class, $policy->updater);
        $this->assertEquals('Updater', $policy->updater->name);
        $this->assertEquals($updater->id, $policy->updated_by);
    }

    #[Test]
    public function policy_has_many_exceptions(): void
    {
        $policy = Policy::factory()->create();

        $exception1 = PolicyException::factory()->create([
            'policy_id' => $policy->id,
            'name' => 'Exception 1',
        ]);

        $exception2 = PolicyException::factory()->create([
            'policy_id' => $policy->id,
            'name' => 'Exception 2',
        ]);

        $this->assertCount(2, $policy->exceptions);
        $this->assertTrue($policy->exceptions->contains($exception1));
        $this->assertTrue($policy->exceptions->contains($exception2));
    }

    #[Test]
    public function policy_belongs_to_many_controls(): void
    {
        $policy = Policy::factory()->create();
        $control1 = Control::factory()->create();
        $control2 = Control::factory()->create();

        $policy->controls()->attach([$control1->id, $control2->id]);

        $this->assertCount(2, $policy->controls);
        $this->assertTrue($policy->controls->contains($control1));
        $this->assertTrue($policy->controls->contains($control2));
    }

    #[Test]
    public function policy_belongs_to_many_implementations(): void
    {
        $policy = Policy::factory()->create();
        $implementation1 = Implementation::factory()->create();
        $implementation2 = Implementation::factory()->create();

        $policy->implementations()->attach([$implementation1->id, $implementation2->id]);

        $this->assertCount(2, $policy->implementations);
        $this->assertTrue($policy->implementations->contains($implementation1));
        $this->assertTrue($policy->implementations->contains($implementation2));
    }

    #[Test]
    public function policy_belongs_to_many_risks(): void
    {
        $policy = Policy::factory()->create();
        $risk1 = Risk::factory()->create();
        $risk2 = Risk::factory()->create();

        $policy->risks()->attach([$risk1->id, $risk2->id]);

        $this->assertCount(2, $policy->risks);
        $this->assertTrue($policy->risks->contains($risk1));
        $this->assertTrue($policy->risks->contains($risk2));
    }

    #[Test]
    public function policy_document_type_enum_casting(): void
    {
        $policy = Policy::factory()->policy()->create();

        $this->assertInstanceOf(DocumentType::class, $policy->document_type);
        $this->assertEquals(DocumentType::Policy, $policy->document_type);
        $this->assertEquals('policy', $policy->document_type->value);
    }

    #[Test]
    public function policy_effective_date_casting(): void
    {
        $policy = Policy::factory()->create([
            'effective_date' => '2024-06-15',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $policy->effective_date);
        $this->assertEquals('2024-06-15', $policy->effective_date->format('Y-m-d'));
    }

    #[Test]
    public function policy_retired_date_casting(): void
    {
        $policy = Policy::factory()->retired()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $policy->retired_date);
    }

    #[Test]
    public function policy_revision_history_array_casting(): void
    {
        $policy = Policy::factory()->withRevisionHistory()->create();

        $this->assertIsArray($policy->revision_history);
        $this->assertCount(2, $policy->revision_history);
        $this->assertArrayHasKey('version', $policy->revision_history[0]);
        $this->assertArrayHasKey('date', $policy->revision_history[0]);
        $this->assertArrayHasKey('author', $policy->revision_history[0]);
        $this->assertArrayHasKey('changes', $policy->revision_history[0]);
    }

    #[Test]
    public function policy_can_be_soft_deleted(): void
    {
        $policy = Policy::factory()->create([
            'name' => 'Delete Me Policy',
        ]);

        $policyId = $policy->id;
        $policy->delete();

        $this->assertSoftDeleted('policies', ['id' => $policyId]);
        $this->assertNull(Policy::find($policyId));
        $this->assertNotNull(Policy::withTrashed()->find($policyId));
    }

    #[Test]
    public function policy_can_be_restored_after_soft_delete(): void
    {
        $policy = Policy::factory()->create([
            'name' => 'Restore Test Policy',
        ]);

        $policyId = $policy->id;
        $policy->delete();

        $this->assertSoftDeleted('policies', ['id' => $policyId]);

        $deletedPolicy = Policy::withTrashed()->find($policyId);
        $deletedPolicy->restore();

        $this->assertDatabaseHas('policies', [
            'id' => $policyId,
            'name' => 'Restore Test Policy',
        ]);
        $this->assertNotNull(Policy::find($policyId));
    }

    #[Test]
    public function policy_factory_creates_valid_policy(): void
    {
        $policy = Policy::factory()->create();

        $this->assertDatabaseHas('policies', [
            'id' => $policy->id,
            'code' => $policy->code,
        ]);

        $this->assertNotNull($policy->code);
        $this->assertNotNull($policy->name);
        $this->assertInstanceOf(DocumentType::class, $policy->document_type);
    }

    #[Test]
    public function policy_factory_with_owner_state(): void
    {
        $user = User::factory()->create();
        $policy = Policy::factory()->withOwner($user)->create();

        $this->assertEquals($user->id, $policy->owner_id);
        $this->assertEquals($user->name, $policy->owner->name);
    }

    #[Test]
    public function policy_factory_procedure_state(): void
    {
        $policy = Policy::factory()->procedure()->create();

        $this->assertEquals(DocumentType::Procedure, $policy->document_type);
    }

    #[Test]
    public function policy_factory_standard_state(): void
    {
        $policy = Policy::factory()->standard()->create();

        $this->assertEquals(DocumentType::Standard, $policy->document_type);
    }
}
