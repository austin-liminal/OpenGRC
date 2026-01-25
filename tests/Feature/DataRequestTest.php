<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\AuditItem;
use App\Models\DataRequest;
use App\Models\DataRequestResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DataRequestTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function data_request_can_be_created_with_required_fields(): void
    {
        $audit = Audit::factory()->create();
        $creator = User::factory()->create();
        $assignee = User::factory()->create();

        $dataRequest = DataRequest::create([
            'code' => 'DR-001',
            'audit_id' => $audit->id,
            'created_by_id' => $creator->id,
            'assigned_to_id' => $assignee->id,
        ]);

        $this->assertDatabaseHas('data_requests', [
            'code' => 'DR-001',
            'audit_id' => $audit->id,
            'created_by_id' => $creator->id,
            'assigned_to_id' => $assignee->id,
        ]);

        $this->assertEquals('DR-001', $dataRequest->code);
    }

    #[Test]
    public function data_request_can_be_created_with_all_fields(): void
    {
        $audit = Audit::factory()->create();
        $auditItem = AuditItem::factory()->create(['audit_id' => $audit->id]);
        $creator = User::factory()->create();
        $assignee = User::factory()->create();

        $dataRequest = DataRequest::create([
            'code' => 'DR-002',
            'details' => 'Please provide access logs for Q1 2024.',
            'status' => 'Pending',
            'audit_id' => $audit->id,
            'audit_item_id' => $auditItem->id,
            'created_by_id' => $creator->id,
            'assigned_to_id' => $assignee->id,
        ]);

        $this->assertDatabaseHas('data_requests', [
            'code' => 'DR-002',
            'status' => 'Pending',
        ]);

        $this->assertEquals('Please provide access logs for Q1 2024.', $dataRequest->details);
        $this->assertEquals($auditItem->id, $dataRequest->audit_item_id);
    }

    #[Test]
    public function data_request_belongs_to_audit(): void
    {
        $audit = Audit::factory()->create(['title' => 'Test Audit']);
        $dataRequest = DataRequest::factory()->create([
            'audit_id' => $audit->id,
        ]);

        $this->assertInstanceOf(Audit::class, $dataRequest->audit);
        $this->assertEquals('Test Audit', $dataRequest->audit->title);
        $this->assertEquals($audit->id, $dataRequest->audit->id);
    }

    #[Test]
    public function data_request_belongs_to_audit_item(): void
    {
        $auditItem = AuditItem::factory()->create();
        $dataRequest = DataRequest::factory()->forAuditItem($auditItem)->create();

        $this->assertInstanceOf(AuditItem::class, $dataRequest->auditItem);
        $this->assertEquals($auditItem->id, $dataRequest->auditItem->id);
    }

    #[Test]
    public function data_request_belongs_to_creator(): void
    {
        $creator = User::factory()->create(['name' => 'Request Creator']);
        $dataRequest = DataRequest::factory()->create([
            'created_by_id' => $creator->id,
        ]);

        $this->assertInstanceOf(User::class, $dataRequest->createdBy);
        $this->assertEquals('Request Creator', $dataRequest->createdBy->name);
    }

    #[Test]
    public function data_request_belongs_to_assignee(): void
    {
        $assignee = User::factory()->create(['name' => 'Request Assignee']);
        $dataRequest = DataRequest::factory()->create([
            'assigned_to_id' => $assignee->id,
        ]);

        $this->assertInstanceOf(User::class, $dataRequest->assignedTo);
        $this->assertEquals('Request Assignee', $dataRequest->assignedTo->name);
    }

    #[Test]
    public function data_request_has_many_responses(): void
    {
        $dataRequest = DataRequest::factory()->create();

        $response1 = DataRequestResponse::factory()->create([
            'data_request_id' => $dataRequest->id,
        ]);

        $response2 = DataRequestResponse::factory()->create([
            'data_request_id' => $dataRequest->id,
        ]);

        $this->assertCount(2, $dataRequest->responses);
        $this->assertTrue($dataRequest->responses->contains($response1));
        $this->assertTrue($dataRequest->responses->contains($response2));
    }

    #[Test]
    public function data_request_status_pending(): void
    {
        $dataRequest = DataRequest::factory()->pending()->create();

        $this->assertEquals('Pending', $dataRequest->status);
    }

    #[Test]
    public function data_request_status_responded(): void
    {
        $dataRequest = DataRequest::factory()->responded()->create();

        $this->assertEquals('Responded', $dataRequest->status);
    }

    #[Test]
    public function data_request_status_accepted(): void
    {
        $dataRequest = DataRequest::factory()->accepted()->create();

        $this->assertEquals('Accepted', $dataRequest->status);
    }

    #[Test]
    public function data_request_status_rejected(): void
    {
        $dataRequest = DataRequest::factory()->rejected()->create();

        $this->assertEquals('Rejected', $dataRequest->status);
    }

    #[Test]
    public function data_request_status_can_be_changed(): void
    {
        $dataRequest = DataRequest::factory()->pending()->create();

        $this->assertEquals('Pending', $dataRequest->status);

        // Change to responded
        $dataRequest->update(['status' => 'Responded']);
        $dataRequest->refresh();

        $this->assertEquals('Responded', $dataRequest->status);

        // Change to accepted
        $dataRequest->update(['status' => 'Accepted']);
        $dataRequest->refresh();

        $this->assertEquals('Accepted', $dataRequest->status);
    }

    #[Test]
    public function data_request_can_be_reassigned(): void
    {
        $originalAssignee = User::factory()->create();
        $newAssignee = User::factory()->create();

        $dataRequest = DataRequest::factory()->create([
            'assigned_to_id' => $originalAssignee->id,
        ]);

        $this->assertEquals($originalAssignee->id, $dataRequest->assigned_to_id);

        $dataRequest->update(['assigned_to_id' => $newAssignee->id]);
        $dataRequest->refresh();

        $this->assertEquals($newAssignee->id, $dataRequest->assigned_to_id);
    }

    #[Test]
    public function data_request_factory_creates_valid_request(): void
    {
        $dataRequest = DataRequest::factory()->create();

        $this->assertDatabaseHas('data_requests', [
            'id' => $dataRequest->id,
        ]);

        $this->assertNotNull($dataRequest->code);
        $this->assertNotNull($dataRequest->audit_id);
        $this->assertNotNull($dataRequest->created_by_id);
        $this->assertNotNull($dataRequest->assigned_to_id);
    }

    #[Test]
    public function data_request_factory_for_audit_item_state(): void
    {
        $auditItem = AuditItem::factory()->create();
        $dataRequest = DataRequest::factory()->forAuditItem($auditItem)->create();

        $this->assertEquals($auditItem->id, $dataRequest->audit_item_id);
        $this->assertEquals($auditItem->audit_id, $dataRequest->audit_id);
    }

    #[Test]
    public function data_request_complete_workflow(): void
    {
        // Create audit context
        $audit = Audit::factory()->inProgress()->create();
        $auditItem = AuditItem::factory()->inProgress()->create(['audit_id' => $audit->id]);
        $auditor = User::factory()->create();
        $responder = User::factory()->create();

        // Create a data request
        $dataRequest = DataRequest::create([
            'code' => 'DR-WF-001',
            'details' => 'Please provide evidence of control implementation.',
            'status' => 'Pending',
            'audit_id' => $audit->id,
            'audit_item_id' => $auditItem->id,
            'created_by_id' => $auditor->id,
            'assigned_to_id' => $responder->id,
        ]);

        $this->assertEquals('Pending', $dataRequest->status);

        // Add a response
        $response = DataRequestResponse::factory()->responded()->create([
            'data_request_id' => $dataRequest->id,
            'requester_id' => $auditor->id,
            'requestee_id' => $responder->id,
            'response' => 'Here is the requested evidence.',
        ]);

        // Update request status to responded
        $dataRequest->update(['status' => 'Responded']);
        $dataRequest->refresh();

        $this->assertEquals('Responded', $dataRequest->status);
        $this->assertCount(1, $dataRequest->responses);

        // Accept the response
        $dataRequest->update(['status' => 'Accepted']);
        $dataRequest->refresh();

        $this->assertEquals('Accepted', $dataRequest->status);
    }

    #[Test]
    public function data_request_rejection_workflow(): void
    {
        $dataRequest = DataRequest::factory()->pending()->create();

        // Submit a response
        DataRequestResponse::factory()->responded()->create([
            'data_request_id' => $dataRequest->id,
        ]);

        // Reject the response (request more info)
        $dataRequest->update(['status' => 'Rejected']);
        $dataRequest->refresh();

        $this->assertEquals('Rejected', $dataRequest->status);

        // Responder submits updated response
        DataRequestResponse::factory()->responded()->create([
            'data_request_id' => $dataRequest->id,
            'response' => 'Updated response with additional details.',
        ]);

        // Update status to responded again
        $dataRequest->update(['status' => 'Responded']);
        $dataRequest->refresh();

        $this->assertEquals('Responded', $dataRequest->status);
        $this->assertCount(2, $dataRequest->responses);
    }

    #[Test]
    public function data_request_multiple_requests_for_same_audit(): void
    {
        $audit = Audit::factory()->create();

        $request1 = DataRequest::factory()->create([
            'code' => 'DR-A-001',
            'audit_id' => $audit->id,
        ]);

        $request2 = DataRequest::factory()->create([
            'code' => 'DR-A-002',
            'audit_id' => $audit->id,
        ]);

        $request3 = DataRequest::factory()->create([
            'code' => 'DR-A-003',
            'audit_id' => $audit->id,
        ]);

        $audit->refresh();
        $this->assertCount(3, $audit->dataRequest);
    }
}
