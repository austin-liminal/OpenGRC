<?php

namespace Tests\Feature;

use App\Enums\PolicyExceptionStatus;
use App\Models\Policy;
use App\Models\PolicyException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PolicyExceptionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function policy_exception_can_be_created_with_required_fields(): void
    {
        $policy = Policy::factory()->create();

        $exception = PolicyException::create([
            'policy_id' => $policy->id,
            'name' => 'Test Exception',
            'status' => PolicyExceptionStatus::Pending,
        ]);

        $this->assertDatabaseHas('policy_exceptions', [
            'policy_id' => $policy->id,
            'name' => 'Test Exception',
            'status' => 'pending',
        ]);

        $this->assertEquals('Test Exception', $exception->name);
        $this->assertEquals(PolicyExceptionStatus::Pending, $exception->status);
    }

    #[Test]
    public function policy_exception_can_be_created_with_all_fields(): void
    {
        $policy = Policy::factory()->create();
        $requester = User::factory()->create();
        $approver = User::factory()->create();

        $exception = PolicyException::create([
            'policy_id' => $policy->id,
            'name' => 'Full Exception',
            'description' => 'This is a detailed exception description.',
            'justification' => 'Business critical requirement.',
            'risk_assessment' => 'Low risk due to compensating controls.',
            'compensating_controls' => 'Additional monitoring in place.',
            'status' => PolicyExceptionStatus::Approved,
            'requested_date' => '2024-01-15',
            'effective_date' => '2024-02-01',
            'expiration_date' => '2024-12-31',
            'requested_by' => $requester->id,
            'approved_by' => $approver->id,
        ]);

        $this->assertDatabaseHas('policy_exceptions', [
            'policy_id' => $policy->id,
            'name' => 'Full Exception',
            'status' => 'approved',
        ]);

        $this->assertEquals('This is a detailed exception description.', $exception->description);
        $this->assertEquals('Business critical requirement.', $exception->justification);
        $this->assertEquals($requester->id, $exception->requested_by);
        $this->assertEquals($approver->id, $exception->approved_by);
    }

    #[Test]
    public function policy_exception_belongs_to_policy(): void
    {
        $policy = Policy::factory()->create(['name' => 'Test Policy']);
        $exception = PolicyException::factory()->create([
            'policy_id' => $policy->id,
        ]);

        $this->assertInstanceOf(Policy::class, $exception->policy);
        $this->assertEquals('Test Policy', $exception->policy->name);
        $this->assertEquals($policy->id, $exception->policy->id);
    }

    #[Test]
    public function policy_exception_belongs_to_requester(): void
    {
        $requester = User::factory()->create(['name' => 'Requester User']);
        $exception = PolicyException::factory()->create([
            'requested_by' => $requester->id,
        ]);

        $this->assertInstanceOf(User::class, $exception->requester);
        $this->assertEquals('Requester User', $exception->requester->name);
        $this->assertEquals($requester->id, $exception->requester->id);
    }

    #[Test]
    public function policy_exception_belongs_to_approver(): void
    {
        $approver = User::factory()->create(['name' => 'Approver User']);
        $exception = PolicyException::factory()->approved()->create([
            'approved_by' => $approver->id,
        ]);

        $this->assertInstanceOf(User::class, $exception->approver);
        $this->assertEquals('Approver User', $exception->approver->name);
        $this->assertEquals($approver->id, $exception->approver->id);
    }

    #[Test]
    public function policy_exception_belongs_to_creator(): void
    {
        $user = User::factory()->create(['name' => 'Creator User']);
        $policy = Policy::factory()->create();
        $this->actingAs($user);

        $exception = PolicyException::create([
            'policy_id' => $policy->id,
            'name' => 'Test Exception',
            'status' => PolicyExceptionStatus::Pending,
        ]);

        $this->assertInstanceOf(User::class, $exception->creator);
        $this->assertEquals('Creator User', $exception->creator->name);
        $this->assertEquals($user->id, $exception->created_by);
    }

    #[Test]
    public function policy_exception_belongs_to_updater(): void
    {
        $creator = User::factory()->create(['name' => 'Creator']);
        $updater = User::factory()->create(['name' => 'Updater']);
        $policy = Policy::factory()->create();

        $this->actingAs($creator);
        $exception = PolicyException::create([
            'policy_id' => $policy->id,
            'name' => 'Test Exception',
            'status' => PolicyExceptionStatus::Pending,
        ]);

        $this->actingAs($updater);
        $exception->update(['name' => 'Updated Exception Name']);

        $exception->refresh();
        $this->assertInstanceOf(User::class, $exception->updater);
        $this->assertEquals('Updater', $exception->updater->name);
        $this->assertEquals($updater->id, $exception->updated_by);
    }

    #[Test]
    public function policy_exception_status_enum_casting(): void
    {
        $exception = PolicyException::factory()->pending()->create();

        $this->assertInstanceOf(PolicyExceptionStatus::class, $exception->status);
        $this->assertEquals(PolicyExceptionStatus::Pending, $exception->status);
        $this->assertEquals('pending', $exception->status->value);
    }

    #[Test]
    public function policy_exception_requested_date_casting(): void
    {
        $exception = PolicyException::factory()->create([
            'requested_date' => '2024-03-15',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $exception->requested_date);
        $this->assertEquals('2024-03-15', $exception->requested_date->format('Y-m-d'));
    }

    #[Test]
    public function policy_exception_effective_date_casting(): void
    {
        $exception = PolicyException::factory()->create([
            'effective_date' => '2024-04-01',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $exception->effective_date);
        $this->assertEquals('2024-04-01', $exception->effective_date->format('Y-m-d'));
    }

    #[Test]
    public function policy_exception_expiration_date_casting(): void
    {
        $exception = PolicyException::factory()->create([
            'expiration_date' => '2024-12-31',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $exception->expiration_date);
        $this->assertEquals('2024-12-31', $exception->expiration_date->format('Y-m-d'));
    }

    #[Test]
    public function policy_exception_is_active_when_approved_and_within_dates(): void
    {
        $exception = PolicyException::factory()->approved()->create([
            'effective_date' => now()->subDays(10),
            'expiration_date' => now()->addDays(30),
        ]);

        $this->assertTrue($exception->isActive());
    }

    #[Test]
    public function policy_exception_is_not_active_when_pending(): void
    {
        $exception = PolicyException::factory()->pending()->create([
            'effective_date' => now()->subDays(10),
            'expiration_date' => now()->addDays(30),
        ]);

        $this->assertFalse($exception->isActive());
    }

    #[Test]
    public function policy_exception_is_not_active_when_denied(): void
    {
        $exception = PolicyException::factory()->denied()->create([
            'effective_date' => now()->subDays(10),
            'expiration_date' => now()->addDays(30),
        ]);

        $this->assertFalse($exception->isActive());
    }

    #[Test]
    public function policy_exception_is_not_active_before_effective_date(): void
    {
        $exception = PolicyException::factory()->approved()->create([
            'effective_date' => now()->addDays(5),
            'expiration_date' => now()->addDays(30),
        ]);

        $this->assertFalse($exception->isActive());
    }

    #[Test]
    public function policy_exception_is_not_active_after_expiration_date(): void
    {
        $exception = PolicyException::factory()->approved()->create([
            'effective_date' => now()->subDays(30),
            'expiration_date' => now()->subDays(1),
        ]);

        $this->assertFalse($exception->isActive());
    }

    #[Test]
    public function policy_exception_is_active_with_no_expiration_date(): void
    {
        $exception = PolicyException::factory()->approved()->create([
            'effective_date' => now()->subDays(10),
            'expiration_date' => null,
        ]);

        $this->assertTrue($exception->isActive());
    }

    #[Test]
    public function policy_exception_is_expired_after_expiration_date(): void
    {
        $exception = PolicyException::factory()->create([
            'expiration_date' => now()->subDays(5),
        ]);

        $this->assertTrue($exception->isExpired());
    }

    #[Test]
    public function policy_exception_is_not_expired_before_expiration_date(): void
    {
        $exception = PolicyException::factory()->create([
            'expiration_date' => now()->addDays(5),
        ]);

        $this->assertFalse($exception->isExpired());
    }

    #[Test]
    public function policy_exception_is_not_expired_with_no_expiration_date(): void
    {
        $exception = PolicyException::factory()->create([
            'expiration_date' => null,
        ]);

        $this->assertFalse($exception->isExpired());
    }

    #[Test]
    public function policy_exception_scope_active(): void
    {
        $activeException = PolicyException::factory()->approved()->create([
            'effective_date' => now()->subDays(10),
            'expiration_date' => now()->addDays(30),
        ]);

        $pendingException = PolicyException::factory()->pending()->create();

        $expiredException = PolicyException::factory()->approved()->create([
            'expiration_date' => now()->subDays(5),
        ]);

        $activeExceptions = PolicyException::active()->get();

        $this->assertTrue($activeExceptions->contains($activeException));
        $this->assertFalse($activeExceptions->contains($pendingException));
        $this->assertFalse($activeExceptions->contains($expiredException));
    }

    #[Test]
    public function policy_exception_scope_pending(): void
    {
        $pendingException = PolicyException::factory()->pending()->create();
        $approvedException = PolicyException::factory()->approved()->create();

        $pendingExceptions = PolicyException::pending()->get();

        $this->assertTrue($pendingExceptions->contains($pendingException));
        $this->assertFalse($pendingExceptions->contains($approvedException));
    }

    #[Test]
    public function policy_exception_scope_expired(): void
    {
        $expiredException = PolicyException::factory()->approved()->create([
            'expiration_date' => now()->subDays(5),
        ]);

        $activeException = PolicyException::factory()->approved()->create([
            'expiration_date' => now()->addDays(30),
        ]);

        $pendingException = PolicyException::factory()->pending()->create([
            'expiration_date' => now()->subDays(5),
        ]);

        $expiredExceptions = PolicyException::expired()->get();

        $this->assertTrue($expiredExceptions->contains($expiredException));
        $this->assertFalse($expiredExceptions->contains($activeException));
        $this->assertFalse($expiredExceptions->contains($pendingException));
    }

    #[Test]
    public function policy_exception_can_be_soft_deleted(): void
    {
        $exception = PolicyException::factory()->create([
            'name' => 'Delete Me Exception',
        ]);

        $exceptionId = $exception->id;
        $exception->delete();

        $this->assertSoftDeleted('policy_exceptions', ['id' => $exceptionId]);
        $this->assertNull(PolicyException::find($exceptionId));
        $this->assertNotNull(PolicyException::withTrashed()->find($exceptionId));
    }

    #[Test]
    public function policy_exception_can_be_restored_after_soft_delete(): void
    {
        $exception = PolicyException::factory()->create([
            'name' => 'Restore Test Exception',
        ]);

        $exceptionId = $exception->id;
        $exception->delete();

        $this->assertSoftDeleted('policy_exceptions', ['id' => $exceptionId]);

        $deletedException = PolicyException::withTrashed()->find($exceptionId);
        $deletedException->restore();

        $this->assertDatabaseHas('policy_exceptions', [
            'id' => $exceptionId,
            'name' => 'Restore Test Exception',
        ]);
        $this->assertNotNull(PolicyException::find($exceptionId));
    }

    #[Test]
    public function policy_exception_factory_creates_valid_exception(): void
    {
        $exception = PolicyException::factory()->create();

        $this->assertDatabaseHas('policy_exceptions', [
            'id' => $exception->id,
        ]);

        $this->assertNotNull($exception->name);
        $this->assertNotNull($exception->policy_id);
        $this->assertInstanceOf(PolicyExceptionStatus::class, $exception->status);
    }

    #[Test]
    public function policy_exception_factory_pending_state(): void
    {
        $exception = PolicyException::factory()->pending()->create();

        $this->assertEquals(PolicyExceptionStatus::Pending, $exception->status);
        $this->assertNull($exception->approved_by);
    }

    #[Test]
    public function policy_exception_factory_approved_state(): void
    {
        $exception = PolicyException::factory()->approved()->create();

        $this->assertEquals(PolicyExceptionStatus::Approved, $exception->status);
        $this->assertNotNull($exception->approved_by);
    }

    #[Test]
    public function policy_exception_factory_denied_state(): void
    {
        $exception = PolicyException::factory()->denied()->create();

        $this->assertEquals(PolicyExceptionStatus::Denied, $exception->status);
    }

    #[Test]
    public function policy_exception_factory_expired_state(): void
    {
        $exception = PolicyException::factory()->expired()->create();

        $this->assertEquals(PolicyExceptionStatus::Expired, $exception->status);
        $this->assertTrue($exception->expiration_date->isPast());
    }

    #[Test]
    public function policy_exception_factory_revoked_state(): void
    {
        $exception = PolicyException::factory()->revoked()->create();

        $this->assertEquals(PolicyExceptionStatus::Revoked, $exception->status);
    }
}
