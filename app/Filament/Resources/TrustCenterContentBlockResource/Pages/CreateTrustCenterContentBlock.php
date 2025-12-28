<?php

namespace App\Filament\Resources\TrustCenterContentBlockResource\Pages;

use App\Filament\Resources\TrustCenterContentBlockResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTrustCenterContentBlock extends CreateRecord
{
    protected static string $resource = TrustCenterContentBlockResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
