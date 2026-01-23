<?php

namespace App\Filament\Exports;

use App\Models\Application;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ApplicationExporter extends Exporter
{
    protected static ?string $model = Application::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')
                ->label('Name'),
            ExportColumn::make('type')
                ->label('Type')
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? ''),
            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? ''),
            ExportColumn::make('owner.name')
                ->label('Owner'),
            ExportColumn::make('vendor.name')
                ->label('Vendor'),
            ExportColumn::make('url')
                ->label('URL'),
            ExportColumn::make('description')
                ->label('Description'),
            ExportColumn::make('notes')
                ->label('Notes'),
            ExportColumn::make('created_at')
                ->label('Created At'),
            ExportColumn::make('updated_at')
                ->label('Updated At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your application export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }

    public function getFileDisk(): string
    {
        return setting('storage.driver', 'private');
    }
}
