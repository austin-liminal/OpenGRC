<?php

namespace App\Filament\Admin\Resources\BundleResource\Pages;

use App\Filament\Admin\Resources\BundleResource;
use App\Filament\Admin\Resources\BundleResource\Widgets\BundleHeader;
use Filament\Resources\Pages\ListRecords;

class ListBundles extends ListRecords
{
    protected static string $resource = BundleResource::class;

    protected static ?string $title = 'Content Bundles';

    protected function getHeaderWidgets(): array
    {
        return [
            BundleHeader::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
