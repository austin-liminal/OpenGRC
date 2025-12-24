<?php

namespace Tests\Browser;

use App\Models\Standard;
use Illuminate\Support\Facades\Artisan;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class StandardTest extends DuskTestCase
{
    protected static string $testCode = 'DUSK-TEST-001';

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache to reset rate limiting
        Artisan::call('cache:clear');

        // Clean up any existing test standard before each test
        Standard::where('code', self::$testCode)->forceDelete();
    }

    protected function loginAs(Browser $browser): Browser
    {
        $user = \App\Models\User::where('email', 'admin@example.com')->first();

        return $browser->loginAs($user)
            ->visit('/app');
    }

    /**
     * Test the complete CRUD workflow for Standards:
     * Create -> Verify in table/view -> Edit -> Verify changes -> Delete -> Verify removal
     */
    public function test_standard_crud_workflow(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser);

            // ==========================================
            // STEP 1: CREATE a new standard
            // ==========================================
            $browser->visit('/app/standards/create')
                ->assertSee('Create Standard')
                ->pause(500);

            // Fill out all fields
            $browser->type('#data\\.name', 'Test Standard for Dusk')
                ->type('#data\\.code', self::$testCode)
                ->type('#data\\.authority', 'Dusk Testing Authority')
                ->type('#data\\.reference_url', 'https://example.com/dusk-test')
                ->pause(300);

            // Wait for Choices.js to initialize, then select dropdown options
            $browser->pause(1000)
                ->filamentSelect('department', 'Security')
                ->filamentSelect('scope', 'Global');

            // Fill the description (Trix editor)
            $browser->click('trix-editor#data\\.description')
                ->pause(200)
                ->type('trix-editor#data\\.description', 'This is a test description for the Dusk browser test.')
                ->pause(500);

            // Submit
            $browser->scrollIntoView('button[type="submit"]')
                ->pause(300)
                ->press('Create')
                ->pause(2000);

            // Should redirect to view page after creation
            $browser->assertSee('Test Standard for Dusk');

            // ==========================================
            // STEP 2: VERIFY standard displays in VIEW
            // ==========================================
            $browser->assertSee(self::$testCode)
                ->assertSee('Dusk Testing Authority')
                ->assertSee('Security')  // Department
                ->assertSee('Global')    // Scope
                ->assertSee('This is a test description');

            // ==========================================
            // STEP 3: VERIFY standard displays in TABLE
            // ==========================================
            $browser->visit('/app/standards')
                ->pause(1000)
                ->assertSee(self::$testCode)
                ->assertSee('Test Standard for Dusk')
                ->assertSee('Dusk Testing Authority');

            // ==========================================
            // STEP 4: EDIT the standard
            // ==========================================
            // Click on the standard name to go to view, then edit
            $standard = Standard::where('code', self::$testCode)->first();

            $browser->visit("/app/standards/{$standard->id}/edit")
                ->pause(1000)
                ->assertSee('Edit Standard');

            // Update fields
            $browser->clear('#data\\.name')
                ->type('#data\\.name', 'Updated Test Standard')
                ->clear('#data\\.authority')
                ->type('#data\\.authority', 'Updated Authority')
                ->clear('#data\\.reference_url')
                ->type('#data\\.reference_url', 'https://example.com/dusk-updated')
                ->pause(300);

            // Submit changes
            $browser->scrollIntoView('button[type="submit"]')
                ->pause(300)
                ->press('Save changes')
                ->pause(2000);

            // ==========================================
            // STEP 5: VERIFY edited standard in VIEW
            // ==========================================
            $browser->assertSee('Updated Test Standard')
                ->assertSee(self::$testCode)
                ->assertSee('Updated Authority');

            // ==========================================
            // STEP 6: VERIFY edited standard in TABLE
            // ==========================================
            $browser->visit('/app/standards')
                ->pause(1000)
                ->assertSee('Updated Test Standard')
                ->assertSee('Updated Authority')
                ->assertDontSee('Test Standard for Dusk');

            // ==========================================
            // STEP 7: DELETE the standard
            // ==========================================
            $browser->visit("/app/standards/{$standard->id}/edit")
                ->pause(1000)
                ->assertSee('Edit Standard');

            // Click Delete button in header
            $browser->press('Delete')
                ->pause(1000);

            // Confirm in modal
            $browser->waitFor('.fi-modal', 10)
                ->pause(500);

            $browser->within('.fi-modal', function ($modal) {
                $modal->press('Delete');
            });
            $browser->pause(2000);

            // ==========================================
            // STEP 8: VERIFY standard is deleted
            // ==========================================
            $browser->visit('/app/standards')
                ->pause(1000)
                ->assertDontSee(self::$testCode)
                ->assertDontSee('Updated Test Standard');
        });
    }
}
