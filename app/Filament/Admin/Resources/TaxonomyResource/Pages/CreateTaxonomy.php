<?php

namespace App\Filament\Admin\Resources\TaxonomyResource\Pages;

use App\Filament\Admin\Resources\TaxonomyResource;
use App\Filament\Concerns\RestoresSoftDeletedTaxonomies;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTaxonomy extends CreateRecord
{
    use RestoresSoftDeletedTaxonomies;

    protected static string $resource = TaxonomyResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return $this->createOrRestoreTaxonomy($data);
    }
}
