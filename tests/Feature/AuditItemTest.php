<?php

namespace Tests\Feature;

use App\Enums\Applicability;
use App\Enums\Effectiveness;
use App\Enums\WorkflowStatus;
use App\Models\Audit;
use App\Models\AuditItem;
use App\Models\Control;
use App\Models\DataRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditItemTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function audit_item_can_be_created_with_required_fields(): void
    {
        $audit = Audit::factory()->create();
        $control = Control::factory()->create();

        $auditItem = AuditItem::create([
            'audit_id' => $audit->id,
            'auditable_id' => $control->id,
            'auditable_type' => Control::class,
            'status' => WorkflowStatus::NOTSTARTED,
            'effectiveness' => Effectiveness::UNKNOWN,
            'applicability' => Applicability::UNKNOWN,
        ]);

        $this->assertDatabaseHas('audit_items', [
            'audit_id' => $audit->id,
            'auditable_id' => $control->id,
            'auditable_type' => Control::class,
            'status' => 'Not Started',
        ]);

        $this->assertEquals($audit->id, $auditItem->audit_id);
        $this->assertEquals($control->id, $auditItem->auditable_id);
    }

    #[Test]
    public function audit_item_can_be_created_with_all_fields(): void
    {
        $audit = Audit::factory()->create();
        $control = Control::factory()->create();
        $user = User::factory()->create();

        $auditItem = AuditItem::create([
            'audit_id' => $audit->id,
            'auditable_id' => $control->id,
            'auditable_type' => Control::class,
            'user_id' => $user->id,
            'status' => WorkflowStatus::COMPLETED,
            'effectiveness' => Effectiveness::EFFECTIVE,
            'applicability' => Applicability::APPLICABLE,
            'auditor_notes' => 'Control is fully implemented and tested.',
        ]);

        $this->assertDatabaseHas('audit_items', [
            'audit_id' => $audit->id,
            'auditable_id' => $control->id,
            'user_id' => $user->id,
        ]);

        $this->assertEquals('Control is fully implemented and tested.', $auditItem->auditor_notes);
        $this->assertEquals(Effectiveness::EFFECTIVE, $auditItem->effectiveness);
        $this->assertEquals(Applicability::APPLICABLE, $auditItem->applicability);
    }

    #[Test]
    public function audit_item_belongs_to_audit(): void
    {
        $audit = Audit::factory()->create(['title' => 'Test Audit']);
        $auditItem = AuditItem::factory()->create([
            'audit_id' => $audit->id,
        ]);

        $this->assertInstanceOf(Audit::class, $auditItem->audit);
        $this->assertEquals('Test Audit', $auditItem->audit->title);
        $this->assertEquals($audit->id, $auditItem->audit->id);
    }

    #[Test]
    public function audit_item_has_auditable_relationship(): void
    {
        $control = Control::factory()->create(['title' => 'Access Control']);
        $auditItem = AuditItem::factory()->forControl($control)->create();

        $this->assertInstanceOf(Control::class, $auditItem->auditable);
        $this->assertEquals('Access Control', $auditItem->auditable->title);
        $this->assertEquals($control->id, $auditItem->auditable->id);
    }

    #[Test]
    public function audit_item_belongs_to_user(): void
    {
        $user = User::factory()->create(['name' => 'Auditor User']);
        $auditItem = AuditItem::factory()->withUser($user)->create();

        $this->assertInstanceOf(User::class, $auditItem->user);
        $this->assertEquals('Auditor User', $auditItem->user->name);
        $this->assertEquals($user->id, $auditItem->user->id);
    }

    #[Test]
    public function audit_item_has_many_data_requests(): void
    {
        $auditItem = AuditItem::factory()->create();

        $request1 = DataRequest::factory()->create([
            'audit_item_id' => $auditItem->id,
            'audit_id' => $auditItem->audit_id,
        ]);

        $request2 = DataRequest::factory()->create([
            'audit_item_id' => $auditItem->id,
            'audit_id' => $auditItem->audit_id,
        ]);

        $this->assertCount(2, $auditItem->dataRequests);
        $this->assertTrue($auditItem->dataRequests->contains($request1));
        $this->assertTrue($auditItem->dataRequests->contains($request2));
    }

    #[Test]
    public function audit_item_status_workflow_not_started(): void
    {
        $auditItem = AuditItem::factory()->notStarted()->create();

        $this->assertEquals(WorkflowStatus::NOTSTARTED, $auditItem->status);
        $this->assertEquals(Effectiveness::UNKNOWN, $auditItem->effectiveness);
    }

    #[Test]
    public function audit_item_status_workflow_in_progress(): void
    {
        $auditItem = AuditItem::factory()->inProgress()->create();

        $this->assertEquals(WorkflowStatus::INPROGRESS, $auditItem->status);
    }

    #[Test]
    public function audit_item_status_workflow_completed(): void
    {
        $auditItem = AuditItem::factory()->completed()->create();

        $this->assertEquals(WorkflowStatus::COMPLETED, $auditItem->status);
    }

    #[Test]
    public function audit_item_status_can_be_changed(): void
    {
        $auditItem = AuditItem::factory()->notStarted()->create();

        $this->assertEquals(WorkflowStatus::NOTSTARTED, $auditItem->status);

        // Change to in progress
        $auditItem->update(['status' => WorkflowStatus::INPROGRESS]);
        $auditItem->refresh();

        $this->assertEquals(WorkflowStatus::INPROGRESS, $auditItem->status);

        // Change to completed
        $auditItem->update(['status' => WorkflowStatus::COMPLETED]);
        $auditItem->refresh();

        $this->assertEquals(WorkflowStatus::COMPLETED, $auditItem->status);
    }

    #[Test]
    public function audit_item_effectiveness_can_be_set(): void
    {
        $auditItem = AuditItem::factory()->effective()->create();

        $this->assertEquals(Effectiveness::EFFECTIVE, $auditItem->effectiveness);
        $this->assertEquals(WorkflowStatus::COMPLETED, $auditItem->status);
    }

    #[Test]
    public function audit_item_effectiveness_partially_effective(): void
    {
        $auditItem = AuditItem::factory()->partiallyEffective()->create();

        $this->assertEquals(Effectiveness::PARTIAL, $auditItem->effectiveness);
    }

    #[Test]
    public function audit_item_effectiveness_ineffective(): void
    {
        $auditItem = AuditItem::factory()->ineffective()->create();

        $this->assertEquals(Effectiveness::INEFFECTIVE, $auditItem->effectiveness);
    }

    #[Test]
    public function audit_item_effectiveness_can_be_changed(): void
    {
        $auditItem = AuditItem::factory()->notStarted()->create();

        $this->assertEquals(Effectiveness::UNKNOWN, $auditItem->effectiveness);

        // Update to partially effective
        $auditItem->update(['effectiveness' => Effectiveness::PARTIAL]);
        $auditItem->refresh();

        $this->assertEquals(Effectiveness::PARTIAL, $auditItem->effectiveness);

        // Update to effective
        $auditItem->update(['effectiveness' => Effectiveness::EFFECTIVE]);
        $auditItem->refresh();

        $this->assertEquals(Effectiveness::EFFECTIVE, $auditItem->effectiveness);
    }

    #[Test]
    public function audit_item_applicability_applicable(): void
    {
        $auditItem = AuditItem::factory()->applicable()->create();

        $this->assertEquals(Applicability::APPLICABLE, $auditItem->applicability);
    }

    #[Test]
    public function audit_item_applicability_not_applicable(): void
    {
        $auditItem = AuditItem::factory()->notApplicable()->create();

        $this->assertEquals(Applicability::NOTAPPLICABLE, $auditItem->applicability);
    }

    #[Test]
    public function audit_item_applicability_can_be_changed(): void
    {
        $auditItem = AuditItem::factory()->applicable()->create();

        $this->assertEquals(Applicability::APPLICABLE, $auditItem->applicability);

        // Change to not applicable
        $auditItem->update(['applicability' => Applicability::NOTAPPLICABLE]);
        $auditItem->refresh();

        $this->assertEquals(Applicability::NOTAPPLICABLE, $auditItem->applicability);

        // Change to partially applicable
        $auditItem->update(['applicability' => Applicability::PARTIALLYAPPLICABLE]);
        $auditItem->refresh();

        $this->assertEquals(Applicability::PARTIALLYAPPLICABLE, $auditItem->applicability);
    }

    #[Test]
    public function audit_item_auditor_notes_can_be_updated(): void
    {
        $auditItem = AuditItem::factory()->create([
            'auditor_notes' => 'Initial notes.',
        ]);

        $this->assertEquals('Initial notes.', $auditItem->auditor_notes);

        $auditItem->update(['auditor_notes' => 'Updated notes with more detail.']);
        $auditItem->refresh();

        $this->assertEquals('Updated notes with more detail.', $auditItem->auditor_notes);
    }

    #[Test]
    public function audit_item_factory_creates_valid_item(): void
    {
        $auditItem = AuditItem::factory()->create();

        $this->assertDatabaseHas('audit_items', [
            'id' => $auditItem->id,
        ]);

        $this->assertNotNull($auditItem->audit_id);
        $this->assertNotNull($auditItem->auditable_id);
        $this->assertNotNull($auditItem->auditable_type);
        $this->assertInstanceOf(WorkflowStatus::class, $auditItem->status);
        $this->assertInstanceOf(Effectiveness::class, $auditItem->effectiveness);
        $this->assertInstanceOf(Applicability::class, $auditItem->applicability);
    }

    #[Test]
    public function audit_item_factory_with_user_state(): void
    {
        $user = User::factory()->create();
        $auditItem = AuditItem::factory()->withUser($user)->create();

        $this->assertEquals($user->id, $auditItem->user_id);
        $this->assertEquals($user->name, $auditItem->user->name);
    }

    #[Test]
    public function audit_item_complete_workflow(): void
    {
        // Create an audit and control
        $audit = Audit::factory()->inProgress()->create();
        $control = Control::factory()->create();
        $auditor = User::factory()->create();

        // Create an audit item in not started state
        $auditItem = AuditItem::create([
            'audit_id' => $audit->id,
            'auditable_id' => $control->id,
            'auditable_type' => Control::class,
            'user_id' => $auditor->id,
            'status' => WorkflowStatus::NOTSTARTED,
            'effectiveness' => Effectiveness::UNKNOWN,
            'applicability' => Applicability::UNKNOWN,
        ]);

        // Start the audit item
        $auditItem->update([
            'status' => WorkflowStatus::INPROGRESS,
            'auditor_notes' => 'Beginning review of control.',
        ]);
        $auditItem->refresh();

        $this->assertEquals(WorkflowStatus::INPROGRESS, $auditItem->status);

        // Complete the audit item with findings
        $auditItem->update([
            'status' => WorkflowStatus::COMPLETED,
            'effectiveness' => Effectiveness::PARTIAL,
            'applicability' => Applicability::APPLICABLE,
            'auditor_notes' => 'Control is partially implemented. Needs improvement in area X.',
        ]);
        $auditItem->refresh();

        $this->assertEquals(WorkflowStatus::COMPLETED, $auditItem->status);
        $this->assertEquals(Effectiveness::PARTIAL, $auditItem->effectiveness);
        $this->assertEquals(Applicability::APPLICABLE, $auditItem->applicability);
        $this->assertStringContainsString('partially implemented', $auditItem->auditor_notes);
    }
}
