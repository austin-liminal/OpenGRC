<?php

namespace Tests\Feature;

use App\Enums\VendorRiskRating;
use App\Enums\VendorStatus;
use App\Models\Application;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class VendorTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test vendor can be created with required fields.
     */
    public function test_vendor_can_be_created(): void
    {
        $user = User::factory()->create();

        $vendor = Vendor::create([
            'name' => 'Test Vendor Inc.',
            'description' => 'A test vendor for testing purposes',
            'url' => 'https://testvendor.com',
            'vendor_manager_id' => $user->id,
            'status' => VendorStatus::PENDING->value,
            'risk_rating' => VendorRiskRating::MEDIUM->value,
            'notes' => 'Test notes',
        ]);

        $this->assertDatabaseHas('vendors', [
            'name' => 'Test Vendor Inc.',
            'vendor_manager_id' => $user->id,
            'status' => 'Pending',
            'risk_rating' => 'Medium',
        ]);

        $this->assertEquals('Test Vendor Inc.', $vendor->name);
        $this->assertEquals($user->id, $vendor->vendor_manager_id);
    }

    /**
     * Test vendor belongs to vendor manager (user).
     */
    public function test_vendor_belongs_to_vendor_manager(): void
    {
        $user = User::factory()->create(['name' => 'John Manager']);

        $vendor = Vendor::create([
            'name' => 'Test Vendor',
            'vendor_manager_id' => $user->id,
            'status' => VendorStatus::ACCEPTED->value,
            'risk_rating' => VendorRiskRating::LOW->value,
        ]);

        $this->assertInstanceOf(User::class, $vendor->vendorManager);
        $this->assertEquals('John Manager', $vendor->vendorManager->name);
        $this->assertEquals($user->id, $vendor->vendorManager->id);
    }

    /**
     * Test vendor has many applications relationship.
     */
    public function test_vendor_has_many_applications(): void
    {
        $user = User::factory()->create();

        $vendor = Vendor::create([
            'name' => 'Microsoft Corporation',
            'vendor_manager_id' => $user->id,
            'status' => VendorStatus::ACCEPTED->value,
            'risk_rating' => VendorRiskRating::LOW->value,
        ]);

        // Create multiple applications for this vendor
        $app1 = Application::create([
            'name' => 'Microsoft 365',
            'vendor_id' => $vendor->id,
            'owner_id' => $user->id,
            'type' => 'SaaS',
            'status' => 'Approved',
        ]);

        $app2 = Application::create([
            'name' => 'Azure',
            'vendor_id' => $vendor->id,
            'owner_id' => $user->id,
            'type' => 'SaaS',
            'status' => 'Approved',
        ]);

        $app3 = Application::create([
            'name' => 'Defender',
            'vendor_id' => $vendor->id,
            'owner_id' => $user->id,
            'type' => 'SaaS',
            'status' => 'Approved',
        ]);

        $this->assertCount(3, $vendor->applications);
        $this->assertTrue($vendor->applications->contains($app1));
        $this->assertTrue($vendor->applications->contains($app2));
        $this->assertTrue($vendor->applications->contains($app3));
    }

    /**
     * Test vendor status enum casting.
     */
    public function test_vendor_status_enum_casting(): void
    {
        $user = User::factory()->create();

        $vendor = Vendor::create([
            'name' => 'Test Vendor',
            'vendor_manager_id' => $user->id,
            'status' => VendorStatus::ACCEPTED->value,
            'risk_rating' => VendorRiskRating::HIGH->value,
        ]);

        $this->assertInstanceOf(VendorStatus::class, $vendor->status);
        $this->assertEquals(VendorStatus::ACCEPTED, $vendor->status);
        $this->assertEquals('Accepted', $vendor->status->value);
    }

    /**
     * Test vendor risk rating enum casting.
     */
    public function test_vendor_risk_rating_enum_casting(): void
    {
        $user = User::factory()->create();

        $vendor = Vendor::create([
            'name' => 'High Risk Vendor',
            'vendor_manager_id' => $user->id,
            'status' => VendorStatus::PENDING->value,
            'risk_rating' => VendorRiskRating::CRITICAL->value,
        ]);

        $this->assertInstanceOf(VendorRiskRating::class, $vendor->risk_rating);
        $this->assertEquals(VendorRiskRating::CRITICAL, $vendor->risk_rating);
        $this->assertEquals('Critical', $vendor->risk_rating->value);
    }

    /**
     * Test vendor can be soft deleted.
     */
    public function test_vendor_can_be_soft_deleted(): void
    {
        $user = User::factory()->create();

        $vendor = Vendor::create([
            'name' => 'Delete Me Vendor',
            'vendor_manager_id' => $user->id,
            'status' => VendorStatus::TERMINATED->value,
            'risk_rating' => VendorRiskRating::MEDIUM->value,
        ]);

        $vendorId = $vendor->id;
        $vendor->delete();

        $this->assertSoftDeleted('vendors', ['id' => $vendorId]);
        $this->assertNull(Vendor::find($vendorId));
        $this->assertNotNull(Vendor::withTrashed()->find($vendorId));
    }

    /**
     * Test vendor factory works correctly.
     */
    public function test_vendor_factory_creates_valid_vendor(): void
    {
        $vendor = Vendor::factory()->create();

        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'name' => $vendor->name,
        ]);

        $this->assertNotNull($vendor->name);
        $this->assertNotNull($vendor->vendor_manager_id);
        $this->assertInstanceOf(VendorStatus::class, $vendor->status);
        $this->assertInstanceOf(VendorRiskRating::class, $vendor->risk_rating);
    }

    /**
     * Test vendor can be restored after soft delete.
     */
    public function test_vendor_can_be_restored_after_soft_delete(): void
    {
        $user = User::factory()->create();

        $vendor = Vendor::create([
            'name' => 'Restore Test Vendor',
            'vendor_manager_id' => $user->id,
            'status' => VendorStatus::ACCEPTED->value,
            'risk_rating' => VendorRiskRating::LOW->value,
        ]);

        $vendorId = $vendor->id;
        $vendor->delete();

        $this->assertSoftDeleted('vendors', ['id' => $vendorId]);

        // Restore the vendor
        $deletedVendor = Vendor::withTrashed()->find($vendorId);
        $deletedVendor->restore();

        $this->assertDatabaseHas('vendors', [
            'id' => $vendorId,
            'name' => 'Restore Test Vendor',
        ]);
        $this->assertNotNull(Vendor::find($vendorId));
    }
}
