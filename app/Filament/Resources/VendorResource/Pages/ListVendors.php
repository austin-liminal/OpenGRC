<?php

namespace App\Filament\Resources\VendorResource\Pages;

use App\Filament\Resources\VendorResource;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\ListRecords;

class ListVendors extends ListRecords
{
    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTableRecordUrlUsing(): ?Closure
    {
        return fn ($record) => $this->getResource()::getUrl('view', ['record' => $record]);
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
