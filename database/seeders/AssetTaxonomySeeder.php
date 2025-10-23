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
        $this->createAssetTypeTaxonomy();
        $this->createAssetStatusTaxonomy();
        $this->createAssetConditionTaxonomy();
        $this->createComplianceStatusTaxonomy();
        $this->createDataClassificationTaxonomy();
    }

    /**
     * Create Asset Type taxonomy with hierarchical terms.
     */
    private function createAssetTypeTaxonomy(): void
    {
        // Create parent taxonomy
        $parent = Taxonomy::firstOrCreate(
            [
                'slug' => 'asset-type',
                'type' => 'asset',
            ],
            [
                'name' => 'Asset Type',
                'description' => 'Categories of IT assets',
                'sort_order' => 1,
            ]
        );

        // Create child terms
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
                    'type' => 'asset',
                    'parent_id' => $parent->id,
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
     * Create Asset Status taxonomy with hierarchical terms.
     */
    private function createAssetStatusTaxonomy(): void
    {
        // Create parent taxonomy
        $parent = Taxonomy::firstOrCreate(
            [
                'slug' => 'asset-status',
                'type' => 'asset',
            ],
            [
                'name' => 'Asset Status',
                'description' => 'Current status of assets',
                'sort_order' => 2,
            ]
        );

        // Create child terms
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
                    'type' => 'asset',
                    'parent_id' => $parent->id,
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
     * Create Asset Condition taxonomy with hierarchical terms.
     */
    private function createAssetConditionTaxonomy(): void
    {
        // Create parent taxonomy
        $parent = Taxonomy::firstOrCreate(
            [
                'slug' => 'asset-condition',
                'type' => 'asset',
            ],
            [
                'name' => 'Asset Condition',
                'description' => 'Physical condition of assets',
                'sort_order' => 3,
            ]
        );

        // Create child terms
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
                    'type' => 'asset',
                    'parent_id' => $parent->id,
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
     * Create Compliance Status taxonomy with hierarchical terms.
     */
    private function createComplianceStatusTaxonomy(): void
    {
        // Create parent taxonomy
        $parent = Taxonomy::firstOrCreate(
            [
                'slug' => 'compliance-status',
                'type' => 'asset',
            ],
            [
                'name' => 'Compliance Status',
                'description' => 'Compliance status of assets',
                'sort_order' => 4,
            ]
        );

        // Create child terms
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
                    'type' => 'asset',
                    'parent_id' => $parent->id,
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
     * Create Data Classification taxonomy with hierarchical terms.
     */
    private function createDataClassificationTaxonomy(): void
    {
        // Create parent taxonomy
        $parent = Taxonomy::firstOrCreate(
            [
                'slug' => 'data-classification',
                'type' => 'asset',
            ],
            [
                'name' => 'Data Classification',
                'description' => 'Data sensitivity classification levels',
                'sort_order' => 5,
            ]
        );

        // Create child terms
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
                    'type' => 'asset',
                    'parent_id' => $parent->id,
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
