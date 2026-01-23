<?php

namespace App\Filament\Exports;

use App\Models\Standard;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class StandardExporter extends Exporter
{
    protected static ?string $model = Standard::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('code')
                ->label('Code'),
            ExportColumn::make('title')
                ->label('Title'),
            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? ''),
            ExportColumn::make('description')
                ->label('Description'),
            ExportColumn::make('authority')
                ->label('Authority'),
            ExportColumn::make('version')
                ->label('Version'),
            ExportColumn::make('created_at')
                ->label('Created At'),
            ExportColumn::make('updated_at')
                ->label('Updated At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your standard export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

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
