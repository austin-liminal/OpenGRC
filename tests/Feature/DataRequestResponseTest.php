<?php

namespace Tests\Feature;

use App\Enums\ResponseStatus;
use App\Models\DataRequest;
use App\Models\DataRequestResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DataRequestResponseTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function data_request_response_can_be_created_with_required_fields(): void
    {
        $dataRequest = DataRequest::factory()->create();
        $requester = User::factory()->create();
        $requestee = User::factory()->create();

        $response = DataRequestResponse::create([
            'data_request_id' => $dataRequest->id,
            'requester_id' => $requester->id,
            'requestee_id' => $requestee->id,
            'status' => ResponseStatus::PENDING,
        ]);

        $this->assertDatabaseHas('data_request_responses', [
            'data_request_id' => $dataRequest->id,
            'requester_id' => $requester->id,
            'requestee_id' => $requestee->id,
            'status' => 'Pending',
        ]);

        $this->assertEquals(ResponseStatus::PENDING, $response->status);
    }

    #[Test]
    public function data_request_response_can_be_created_with_all_fields(): void
    {
        $dataRequest = DataRequest::factory()->create();
        $requester = User::factory()->create();
        $requestee = User::factory()->create();
        $dueDate = Carbon::now()->addDays(7);

        $response = DataRequestResponse::create([
            'data_request_id' => $dataRequest->id,
            'requester_id' => $requester->id,
            'requestee_id' => $requestee->id,
            'response' => 'Here is the requested documentation.',
            'status' => ResponseStatus::RESPONDED,
            'due_at' => $dueDate,
        ]);

        $this->assertDatabaseHas('data_request_responses', [
            'data_request_id' => $dataRequest->id,
            'status' => 'Responded',
        ]);

        $this->assertEquals('Here is the requested documentation.', $response->response);
        $this->assertEquals($dueDate->format('Y-m-d'), $response->due_at->format('Y-m-d'));
    }

    #[Test]
    public function data_request_response_belongs_to_data_request(): void
    {
        $dataRequest = DataRequest::factory()->create(['code' => 'DR-TEST-001']);
        $response = DataRequestResponse::factory()->create([
            'data_request_id' => $dataRequest->id,
        ]);

        $this->assertInstanceOf(DataRequest::class, $response->dataRequest);
        $this->assertEquals('DR-TEST-001', $response->dataRequest->code);
        $this->assertEquals($dataRequest->id, $response->dataRequest->id);
    }

    #[Test]
    public function data_request_response_belongs_to_requester(): void
    {
        $requester = User::factory()->create(['name' => 'Requester User']);
        $response = DataRequestResponse::factory()->create([
            'requester_id' => $requester->id,
        ]);

        $this->assertInstanceOf(User::class, $response->requester);
        $this->assertEquals('Requester User', $response->requester->name);
        $this->assertEquals($requester->id, $response->requester->id);
    }

    #[Test]
    public function data_request_response_belongs_to_requestee(): void
    {
        $requestee = User::factory()->create(['name' => 'Requestee User']);
        $response = DataRequestResponse::factory()->create([
            'requestee_id' => $requestee->id,
        ]);

        $this->assertInstanceOf(User::class, $response->requestee);
        $this->assertEquals('Requestee User', $response->requestee->name);
        $this->assertEquals($requestee->id, $response->requestee->id);
    }

    #[Test]
    public function data_request_response_status_pending(): void
    {
        $response = DataRequestResponse::factory()->pending()->create();

        $this->assertEquals(ResponseStatus::PENDING, $response->status);
        $this->assertNull($response->response);
    }

    #[Test]
    public function data_request_response_status_responded(): void
    {
        $response = DataRequestResponse::factory()->responded()->create();

        $this->assertEquals(ResponseStatus::RESPONDED, $response->status);
    }

    #[Test]
    public function data_request_response_status_accepted(): void
    {
        $response = DataRequestResponse::factory()->accepted()->create();

        $this->assertEquals(ResponseStatus::ACCEPTED, $response->status);
    }

    #[Test]
    public function data_request_response_status_rejected(): void
    {
        $response = DataRequestResponse::factory()->rejected()->create();

        $this->assertEquals(ResponseStatus::REJECTED, $response->status);
    }

    #[Test]
    public function data_request_response_status_can_be_changed(): void
    {
        $response = DataRequestResponse::factory()->pending()->create();

        $this->assertEquals(ResponseStatus::PENDING, $response->status);

        // Submit response
        $response->update([
            'status' => ResponseStatus::RESPONDED,
            'response' => 'Here is my response.',
        ]);
        $response->refresh();

        $this->assertEquals(ResponseStatus::RESPONDED, $response->status);

        // Accept response
        $response->update(['status' => ResponseStatus::ACCEPTED]);
        $response->refresh();

        $this->assertEquals(ResponseStatus::ACCEPTED, $response->status);
    }

    #[Test]
    public function data_request_response_due_at_casting(): void
    {
        $dueDate = Carbon::parse('2024-06-30 17:00:00');
        $response = DataRequestResponse::factory()->create([
            'due_at' => $dueDate,
        ]);

        $this->assertInstanceOf(Carbon::class, $response->due_at);
        $this->assertEquals('2024-06-30', $response->due_at->format('Y-m-d'));
    }

    #[Test]
    public function data_request_response_with_due_date_state(): void
    {
        $dueDate = Carbon::now()->addDays(14);
        $response = DataRequestResponse::factory()->withDueDate($dueDate)->create();

        $this->assertEquals($dueDate->format('Y-m-d'), $response->due_at->format('Y-m-d'));
    }

    #[Test]
    public function data_request_response_overdue_state(): void
    {
        $response = DataRequestResponse::factory()->overdue()->create();

        $this->assertEquals(ResponseStatus::PENDING, $response->status);
        $this->assertTrue($response->due_at->isPast());
    }

    #[Test]
    public function data_request_response_can_be_updated(): void
    {
        $response = DataRequestResponse::factory()->pending()->create([
            'response' => null,
        ]);

        $this->assertNull($response->response);

        $response->update([
            'response' => 'Updated with the requested information.',
            'status' => ResponseStatus::RESPONDED,
        ]);
        $response->refresh();

        $this->assertEquals('Updated with the requested information.', $response->response);
        $this->assertEquals(ResponseStatus::RESPONDED, $response->status);
    }

    #[Test]
    public function data_request_response_factory_creates_valid_response(): void
    {
        $response = DataRequestResponse::factory()->create();

        $this->assertDatabaseHas('data_request_responses', [
            'id' => $response->id,
        ]);

        $this->assertNotNull($response->data_request_id);
        $this->assertNotNull($response->requester_id);
        $this->assertNotNull($response->requestee_id);
        $this->assertInstanceOf(ResponseStatus::class, $response->status);
    }

    #[Test]
    public function data_request_response_complete_workflow(): void
    {
        // Create a data request with pending response
        $dataRequest = DataRequest::factory()->pending()->create();
        $requester = User::factory()->create();
        $requestee = User::factory()->create();
        $dueDate = Carbon::now()->addDays(5);

        // Create pending response
        $response = DataRequestResponse::create([
            'data_request_id' => $dataRequest->id,
            'requester_id' => $requester->id,
            'requestee_id' => $requestee->id,
            'status' => ResponseStatus::PENDING,
            'due_at' => $dueDate,
        ]);

        $this->assertEquals(ResponseStatus::PENDING, $response->status);

        // Requestee submits response
        $response->update([
            'response' => 'Here is the requested access control policy document.',
            'status' => ResponseStatus::RESPONDED,
        ]);
        $response->refresh();

        $this->assertEquals(ResponseStatus::RESPONDED, $response->status);
        $this->assertNotNull($response->response);

        // Update parent data request status
        $dataRequest->update(['status' => 'Responded']);
        $dataRequest->refresh();

        $this->assertEquals('Responded', $dataRequest->status);

        // Requester accepts the response
        $response->update(['status' => ResponseStatus::ACCEPTED]);
        $response->refresh();

        $this->assertEquals(ResponseStatus::ACCEPTED, $response->status);

        // Update parent data request status
        $dataRequest->update(['status' => 'Accepted']);
        $dataRequest->refresh();

        $this->assertEquals('Accepted', $dataRequest->status);
    }

    #[Test]
    public function data_request_response_rejection_workflow(): void
    {
        $response = DataRequestResponse::factory()->responded()->create([
            'response' => 'Initial response that needs more detail.',
        ]);

        // Requester rejects the response
        $response->update(['status' => ResponseStatus::REJECTED]);
        $response->refresh();

        $this->assertEquals(ResponseStatus::REJECTED, $response->status);

        // Requestee can update and resubmit
        $response->update([
            'response' => 'Updated response with additional details and clarifications.',
            'status' => ResponseStatus::RESPONDED,
        ]);
        $response->refresh();

        $this->assertEquals(ResponseStatus::RESPONDED, $response->status);
        $this->assertStringContainsString('additional details', $response->response);
    }

    #[Test]
    public function data_request_can_have_multiple_responses(): void
    {
        $dataRequest = DataRequest::factory()->create();

        // First response - rejected
        $response1 = DataRequestResponse::factory()->rejected()->create([
            'data_request_id' => $dataRequest->id,
            'response' => 'First attempt - incomplete.',
        ]);

        // Second response - accepted
        $response2 = DataRequestResponse::factory()->accepted()->create([
            'data_request_id' => $dataRequest->id,
            'response' => 'Second attempt - complete with all details.',
        ]);

        $dataRequest->refresh();
        $this->assertCount(2, $dataRequest->responses);

        // Verify response statuses
        $this->assertEquals(ResponseStatus::REJECTED, $response1->status);
        $this->assertEquals(ResponseStatus::ACCEPTED, $response2->status);
    }

    #[Test]
    public function data_request_response_status_enum_has_correct_labels(): void
    {
        $this->assertEquals('Pending', ResponseStatus::PENDING->getLabel());
        $this->assertEquals('Responded', ResponseStatus::RESPONDED->getLabel());
        $this->assertEquals('Accepted', ResponseStatus::ACCEPTED->getLabel());
        $this->assertEquals('Rejected', ResponseStatus::REJECTED->getLabel());
    }

    #[Test]
    public function data_request_response_status_enum_has_correct_colors(): void
    {
        $this->assertEquals('primary', ResponseStatus::PENDING->getColor());
        $this->assertEquals('warning', ResponseStatus::RESPONDED->getColor());
        $this->assertEquals('success', ResponseStatus::ACCEPTED->getColor());
        $this->assertEquals('danger', ResponseStatus::REJECTED->getColor());
    }
}
