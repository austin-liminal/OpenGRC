<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Models\Application;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test application can be created with required fields.
     */
    public function test_application_can_be_created(): void
    {
        $owner = User::factory()->create();
        $vendor = Vendor::factory()->create();

        $application = Application::create([
            'name' => 'Test Application',
            'owner_id' => $owner->id,
            'vendor_id' => $vendor->id,
            'type' => ApplicationType::SAAS->value,
            'status' => ApplicationStatus::APPROVED->value,
            'description' => 'A test application for testing purposes',
            'url' => 'https://testapp.com',
            'notes' => 'Test notes',
        ]);

        $this->assertDatabaseHas('applications', [
            'name' => 'Test Application',
            'owner_id' => $owner->id,
            'vendor_id' => $vendor->id,
            'type' => 'SaaS',
            'status' => 'Approved',
        ]);

        $this->assertEquals('Test Application', $application->name);
        $this->assertEquals($owner->id, $application->owner_id);
        $this->assertEquals($vendor->id, $application->vendor_id);
    }

    /**
     * Test application belongs to owner (user).
     */
    public function test_application_belongs_to_owner(): void
    {
        $owner = User::factory()->create(['name' => 'App Owner']);
        $vendor = Vendor::factory()->create();

        $application = Application::create([
            'name' => 'Owned Application',
            'owner_id' => $owner->id,
            'vendor_id' => $vendor->id,
            'type' => ApplicationType::DESKTOP->value,
            'status' => ApplicationStatus::APPROVED->value,
        ]);

        $this->assertInstanceOf(User::class, $application->owner);
        $this->assertEquals('App Owner', $application->owner->name);
        $this->assertEquals($owner->id, $application->owner->id);
    }

    /**
     * Test application belongs to vendor.
     */
    public function test_application_belongs_to_vendor(): void
    {
        $owner = User::factory()->create();
        $vendor = Vendor::factory()->create(['name' => 'Microsoft Corporation']);

        $application = Application::create([
            'name' => 'Microsoft 365',
            'owner_id' => $owner->id,
            'vendor_id' => $vendor->id,
            'type' => ApplicationType::SAAS->value,
            'status' => ApplicationStatus::APPROVED->value,
        ]);

        $this->assertInstanceOf(Vendor::class, $application->vendor);
        $this->assertEquals('Microsoft Corporation', $application->vendor->name);
        $this->assertEquals($vendor->id, $application->vendor->id);
    }

    /**
     * Test application type enum casting.
     */
    public function test_application_type_enum_casting(): void
    {
        $owner = User::factory()->create();
        $vendor = Vendor::factory()->create();

        $application = Application::create([
            'name' => 'Desktop App',
            'owner_id' => $owner->id,
            'vendor_id' => $vendor->id,
            'type' => ApplicationType::DESKTOP->value,
            'status' => ApplicationStatus::APPROVED->value,
        ]);

        $this->assertInstanceOf(ApplicationType::class, $application->type);
        $this->assertEquals(ApplicationType::DESKTOP, $application->type);
        $this->assertEquals('Desktop', $application->type->value);
    }

    /**
     * Test application status enum casting.
     */
    public function test_application_status_enum_casting(): void
    {
        $owner = User::factory()->create();
        $vendor = Vendor::factory()->create();

        $application = Application::create([
            'name' => 'Limited App',
            'owner_id' => $owner->id,
            'vendor_id' => $vendor->id,
            'type' => ApplicationType::SAAS->value,
            'status' => ApplicationStatus::LIMITED->value,
        ]);

        $this->assertInstanceOf(ApplicationStatus::class, $application->status);
        $this->assertEquals(ApplicationStatus::LIMITED, $application->status);
        $this->assertEquals('Limited', $application->status->value);
    }

    /**
     * Test application can be soft deleted.
     */
    public function test_application_can_be_soft_deleted(): void
    {
        $owner = User::factory()->create();
        $vendor = Vendor::factory()->create();

        $application = Application::create([
            'name' => 'Delete Me App',
            'owner_id' => $owner->id,
            'vendor_id' => $vendor->id,
            'type' => ApplicationType::OTHER->value,
            'status' => ApplicationStatus::EXPIRED->value,
        ]);

        $applicationId = $application->id;
        $application->delete();

        $this->assertSoftDeleted('applications', ['id' => $applicationId]);
        $this->assertNull(Application::find($applicationId));
        $this->assertNotNull(Application::withTrashed()->find($applicationId));
    }

    /**
     * Test application factory works correctly.
     */
    public function test_application_factory_creates_valid_application(): void
    {
        $application = Application::factory()->create();

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'name' => $application->name,
        ]);

        $this->assertNotNull($application->name);
        $this->assertNotNull($application->owner_id);
        $this->assertNotNull($application->vendor_id);
        $this->assertInstanceOf(ApplicationType::class, $application->type);
        $this->assertInstanceOf(ApplicationStatus::class, $application->status);
    }

    /**
     * Test multiple applications can belong to same vendor.
     */
    public function test_multiple_applications_can_belong_to_same_vendor(): void
    {
        $owner = User::factory()->create();
        $vendor = Vendor::factory()->create(['name' => 'Adobe Systems']);

        $app1 = Application::create([
            'name' => 'Adobe Creative Cloud',
            'owner_id' => $owner->id,
            'vendor_id' => $vendor->id,
            'type' => ApplicationType::SAAS->value,
            'status' => ApplicationStatus::APPROVED->value,
        ]);

        $app2 = Application::create([
            'name' => 'Adobe Acrobat Pro',
            'owner_id' => $owner->id,
            'vendor_id' => $vendor->id,
            'type' => ApplicationType::DESKTOP->value,
            'status' => ApplicationStatus::APPROVED->value,
        ]);

        $this->assertEquals($vendor->id, $app1->vendor_id);
        $this->assertEquals($vendor->id, $app2->vendor_id);
        $this->assertCount(2, $vendor->applications);
    }

    /**
     * Test application types are correctly assigned.
     */
    public function test_application_types_are_correctly_assigned(): void
    {
        $owner = User::factory()->create();
        $vendor = Vendor::factory()->create();

        $saasApp = Application::create([
            'name' => 'SaaS App',
            'owner_id' => $owner->id,
            'vendor_id' => $vendor->id,
            'type' => ApplicationType::SAAS->value,
            'status' => ApplicationStatus::APPROVED->value,
        ]);

        $serverApp = Application::create([
            'name' => 'Server App',
            'owner_id' => $owner->id,
            'vendor_id' => $vendor->id,
            'type' => ApplicationType::SERVER->value,
            'status' => ApplicationStatus::APPROVED->value,
        ]);

        $applianceApp = Application::create([
            'name' => 'Appliance App',
            'owner_id' => $owner->id,
            'vendor_id' => $vendor->id,
            'type' => ApplicationType::APPLIANCE->value,
            'status' => ApplicationStatus::APPROVED->value,
        ]);

        $this->assertEquals(ApplicationType::SAAS, $saasApp->type);
        $this->assertEquals(ApplicationType::SERVER, $serverApp->type);
        $this->assertEquals(ApplicationType::APPLIANCE, $applianceApp->type);
    }

    /**
     * Test application statuses are correctly assigned.
     */
    public function test_application_statuses_are_correctly_assigned(): void
    {
        $owner = User::factory()->create();
        $vendor = Vendor::factory()->create();

        $approvedApp = Application::create([
            'name' => 'Approved App',
            'owner_id' => $owner->id,
            'vendor_id' => $vendor->id,
            'type' => ApplicationType::SAAS->value,
            'status' => ApplicationStatus::APPROVED->value,
        ]);

        $rejectedApp = Application::create([
            'name' => 'Rejected App',
            'owner_id' => $owner->id,
            'vendor_id' => $vendor->id,
            'type' => ApplicationType::SAAS->value,
            'status' => ApplicationStatus::REJECTED->value,
        ]);

        $limitedApp = Application::create([
            'name' => 'Limited App',
            'owner_id' => $owner->id,
            'vendor_id' => $vendor->id,
            'type' => ApplicationType::SAAS->value,
            'status' => ApplicationStatus::LIMITED->value,
        ]);

        $expiredApp = Application::create([
            'name' => 'Expired App',
            'owner_id' => $owner->id,
            'vendor_id' => $vendor->id,
            'type' => ApplicationType::SAAS->value,
            'status' => ApplicationStatus::EXPIRED->value,
        ]);

        $this->assertEquals(ApplicationStatus::APPROVED, $approvedApp->status);
        $this->assertEquals(ApplicationStatus::REJECTED, $rejectedApp->status);
        $this->assertEquals(ApplicationStatus::LIMITED, $limitedApp->status);
        $this->assertEquals(ApplicationStatus::EXPIRED, $expiredApp->status);
    }
}
