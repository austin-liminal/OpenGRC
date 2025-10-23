<?php

namespace Database\Seeders;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AssetTaxonomySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createAssetTypeTaxonomies();
        $this->createAssetStatusTaxonomies();
        $this->createAssetConditionTaxonomies();
        $this->createComplianceStatusTaxonomies();
        $this->createDataClassificationTaxonomies();
    }

    /**
     * Create Asset Type taxonomies.
     */
    private function createAssetTypeTaxonomies(): void
    {
        $types = [
            ['name' => 'Laptop', 'description' => 'Portable laptop computer'],
            ['name' => 'Desktop', 'description' => 'Desktop computer workstation'],
            ['name' => 'Server', 'description' => 'Server hardware'],
            ['name' => 'Monitor', 'description' => 'Display monitor'],
            ['name' => 'Phone', 'description' => 'Mobile phone or desk phone'],
            ['name' => 'Tablet', 'description' => 'Tablet device'],
            ['name' => 'Network Equipment', 'description' => 'Routers, switches, access points'],
            ['name' => 'Peripheral', 'description' => 'Keyboard, mouse, printer, etc.'],
            ['name' => 'Software License', 'description' => 'Software licensing asset'],
            ['name' => 'Other', 'description' => 'Other IT asset type'],
        ];

        foreach ($types as $index => $typeData) {
            Taxonomy::firstOrCreate(
                [
                    'slug' => Str::slug($typeData['name']),
                    'type' => 'asset_type',
                ],
                [
                    'name' => $typeData['name'],
                    'description' => $typeData['description'],
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    /**
     * Create Asset Status taxonomies.
     */
    private function createAssetStatusTaxonomies(): void
    {
        $statuses = [
            ['name' => 'Available', 'description' => 'Asset is available for assignment'],
            ['name' => 'In Use', 'description' => 'Asset is currently assigned and in use'],
            ['name' => 'In Repair', 'description' => 'Asset is being repaired'],
            ['name' => 'Retired', 'description' => 'Asset has been retired from service'],
            ['name' => 'Lost', 'description' => 'Asset has been lost'],
            ['name' => 'Stolen', 'description' => 'Asset has been stolen'],
            ['name' => 'Disposed', 'description' => 'Asset has been disposed of'],
        ];

        foreach ($statuses as $index => $statusData) {
            Taxonomy::firstOrCreate(
                [
                    'slug' => Str::slug($statusData['name']),
                    'type' => 'asset_status',
                ],
                [
                    'name' => $statusData['name'],
                    'description' => $statusData['description'],
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    /**
     * Create Asset Condition taxonomies.
     */
    private function createAssetConditionTaxonomies(): void
    {
        $conditions = [
            ['name' => 'Excellent', 'description' => 'Asset is in excellent condition'],
            ['name' => 'Good', 'description' => 'Asset is in good condition'],
            ['name' => 'Fair', 'description' => 'Asset is in fair condition with minor wear'],
            ['name' => 'Poor', 'description' => 'Asset is in poor condition'],
            ['name' => 'Damaged', 'description' => 'Asset is damaged'],
        ];

        foreach ($conditions as $index => $conditionData) {
            Taxonomy::firstOrCreate(
                [
                    'slug' => Str::slug($conditionData['name']),
                    'type' => 'asset_condition',
                ],
                [
                    'name' => $conditionData['name'],
                    'description' => $conditionData['description'],
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    /**
     * Create Compliance Status taxonomies.
     */
    private function createComplianceStatusTaxonomies(): void
    {
        $statuses = [
            ['name' => 'Compliant', 'description' => 'Asset meets all compliance requirements'],
            ['name' => 'Non-Compliant', 'description' => 'Asset does not meet compliance requirements'],
            ['name' => 'Exempt', 'description' => 'Asset is exempt from compliance requirements'],
            ['name' => 'Pending', 'description' => 'Compliance status is being reviewed'],
        ];

        foreach ($statuses as $index => $statusData) {
            Taxonomy::firstOrCreate(
                [
                    'slug' => Str::slug($statusData['name']),
                    'type' => 'compliance_status',
                ],
                [
                    'name' => $statusData['name'],
                    'description' => $statusData['description'],
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    /**
     * Create Data Classification taxonomies.
     */
    private function createDataClassificationTaxonomies(): void
    {
        $classifications = [
            ['name' => 'Public', 'description' => 'Information intended for public disclosure'],
            ['name' => 'Internal', 'description' => 'Information for internal use only'],
            ['name' => 'Confidential', 'description' => 'Sensitive business information'],
            ['name' => 'Restricted', 'description' => 'Highly sensitive, regulated information'],
        ];

        foreach ($classifications as $index => $classData) {
            Taxonomy::firstOrCreate(
                [
                    'slug' => Str::slug($classData['name']),
                    'type' => 'data_classification',
                ],
                [
                    'name' => $classData['name'],
                    'description' => $classData['description'],
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}
