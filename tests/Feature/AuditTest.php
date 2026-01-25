<?php

namespace Tests\Feature;

use App\Enums\Applicability;
use App\Enums\Effectiveness;
use App\Enums\WorkflowStatus;
use App\Models\Audit;
use App\Models\AuditItem;
use App\Models\Control;
use App\Models\DataRequest;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function audit_can_be_created_with_required_fields(): void
    {
        $audit = Audit::create([
            'title' => 'Annual Security Audit 2024',
            'status' => WorkflowStatus::NOTSTARTED,
            'audit_type' => 'standards',
            'start_date' => '2024-01-01',
            'end_date' => '2024-03-31',
        ]);

        $this->assertDatabaseHas('audits', [
            'title' => 'Annual Security Audit 2024',
            'status' => 'Not Started',
            'audit_type' => 'standards',
        ]);

        $this->assertEquals('Annual Security Audit 2024', $audit->title);
        $this->assertEquals(WorkflowStatus::NOTSTARTED, $audit->status);
        $this->assertEquals('standards', $audit->audit_type);
    }

    #[Test]
    public function audit_can_be_created_with_all_fields(): void
    {
        $manager = User::factory()->create();
        $program = Program::factory()->create();

        $audit = Audit::create([
            'title' => 'Comprehensive Security Review',
            'description' => 'A detailed security audit covering all controls.',
            'status' => WorkflowStatus::INPROGRESS,
            'audit_type' => 'implementations',
            'start_date' => '2024-01-01',
            'end_date' => '2024-03-31',
            'manager_id' => $manager->id,
            'program_id' => $program->id,
        ]);

        $this->assertDatabaseHas('audits', [
            'title' => 'Comprehensive Security Review',
            'audit_type' => 'implementations',
            'manager_id' => $manager->id,
            'program_id' => $program->id,
        ]);

        $this->assertEquals('A detailed security audit covering all controls.', $audit->description);
        $this->assertEquals(WorkflowStatus::INPROGRESS, $audit->status);
    }

    #[Test]
    public function audit_belongs_to_manager(): void
    {
        $manager = User::factory()->create(['name' => 'Audit Manager']);
        $audit = Audit::factory()->withManager($manager)->create();

        $this->assertInstanceOf(User::class, $audit->manager);
        $this->assertEquals('Audit Manager', $audit->manager->name);
        $this->assertEquals($manager->id, $audit->manager->id);
    }

    #[Test]
    public function audit_belongs_to_program(): void
    {
        $program = Program::factory()->create(['name' => 'Security Program']);
        $audit = Audit::factory()->create([
            'program_id' => $program->id,
        ]);

        $this->assertInstanceOf(Program::class, $audit->program);
        $this->assertEquals('Security Program', $audit->program->name);
    }

    #[Test]
    public function audit_has_many_audit_items(): void
    {
        $audit = Audit::factory()->create();

        $item1 = AuditItem::factory()->create([
            'audit_id' => $audit->id,
        ]);

        $item2 = AuditItem::factory()->create([
            'audit_id' => $audit->id,
        ]);

        $item3 = AuditItem::factory()->create([
            'audit_id' => $audit->id,
        ]);

        $audit->refresh();
        $this->assertCount(3, $audit->auditItems);
        $this->assertTrue($audit->auditItems->contains($item1));
        $this->assertTrue($audit->auditItems->contains($item2));
        $this->assertTrue($audit->auditItems->contains($item3));
    }

    #[Test]
    public function audit_has_many_data_requests(): void
    {
        $audit = Audit::factory()->create();

        $request1 = DataRequest::factory()->create([
            'audit_id' => $audit->id,
        ]);

        $request2 = DataRequest::factory()->create([
            'audit_id' => $audit->id,
        ]);

        $this->assertCount(2, $audit->dataRequest);
        $this->assertTrue($audit->dataRequest->contains($request1));
        $this->assertTrue($audit->dataRequest->contains($request2));
    }

    #[Test]
    public function audit_belongs_to_many_members(): void
    {
        $audit = Audit::factory()->create();
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();

        $audit->members()->attach([$member1->id, $member2->id]);

        $this->assertCount(2, $audit->members);
        $this->assertTrue($audit->members->contains($member1));
        $this->assertTrue($audit->members->contains($member2));
    }

    #[Test]
    public function audit_status_workflow_not_started(): void
    {
        $audit = Audit::factory()->notStarted()->create();

        $this->assertEquals(WorkflowStatus::NOTSTARTED, $audit->status);
    }

    #[Test]
    public function audit_status_workflow_in_progress(): void
    {
        $audit = Audit::factory()->inProgress()->create();

        $this->assertEquals(WorkflowStatus::INPROGRESS, $audit->status);
    }

    #[Test]
    public function audit_status_workflow_completed(): void
    {
        $audit = Audit::factory()->completed()->create();

        $this->assertEquals(WorkflowStatus::COMPLETED, $audit->status);
    }

    #[Test]
    public function audit_status_can_be_changed(): void
    {
        $audit = Audit::factory()->notStarted()->create();

        $this->assertEquals(WorkflowStatus::NOTSTARTED, $audit->status);

        // Change to in progress
        $audit->update(['status' => WorkflowStatus::INPROGRESS]);
        $audit->refresh();

        $this->assertEquals(WorkflowStatus::INPROGRESS, $audit->status);

        // Change to completed
        $audit->update(['status' => WorkflowStatus::COMPLETED]);
        $audit->refresh();

        $this->assertEquals(WorkflowStatus::COMPLETED, $audit->status);
    }

    #[Test]
    public function audit_dates_are_cast_correctly(): void
    {
        $startDate = Carbon::parse('2024-06-01');
        $endDate = Carbon::parse('2024-08-31');

        $audit = Audit::factory()->create([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $this->assertEquals('2024-06-01', $audit->start_date->format('Y-m-d'));
        $this->assertEquals('2024-08-31', $audit->end_date->format('Y-m-d'));
    }

    #[Test]
    public function audit_factory_creates_valid_audit(): void
    {
        $audit = Audit::factory()->create();

        $this->assertDatabaseHas('audits', [
            'id' => $audit->id,
        ]);

        $this->assertNotNull($audit->title);
        $this->assertInstanceOf(WorkflowStatus::class, $audit->status);
    }

    #[Test]
    public function audit_factory_with_manager_state(): void
    {
        $manager = User::factory()->create();
        $audit = Audit::factory()->withManager($manager)->create();

        $this->assertEquals($manager->id, $audit->manager_id);
        $this->assertEquals($manager->name, $audit->manager->name);
    }

    #[Test]
    public function audit_factory_with_dates_state(): void
    {
        $startDate = Carbon::parse('2024-01-15');
        $endDate = Carbon::parse('2024-04-15');

        $audit = Audit::factory()->withDates($startDate, $endDate)->create();

        $this->assertEquals('2024-01-15', $audit->start_date->format('Y-m-d'));
        $this->assertEquals('2024-04-15', $audit->end_date->format('Y-m-d'));
    }

    #[Test]
    public function audit_can_add_audit_item_for_control(): void
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
        ]);

        $audit->refresh();
        $this->assertCount(1, $audit->auditItems);
        $this->assertEquals($control->id, $audit->auditItems->first()->auditable_id);
    }

    #[Test]
    public function audit_can_track_multiple_controls(): void
    {
        $audit = Audit::factory()->create();
        $controls = Control::factory()->count(5)->create();

        foreach ($controls as $control) {
            AuditItem::factory()->forControl($control)->create([
                'audit_id' => $audit->id,
            ]);
        }

        $audit->refresh();
        $this->assertCount(5, $audit->auditItems);
    }
}
