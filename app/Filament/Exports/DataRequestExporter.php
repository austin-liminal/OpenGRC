<?php

namespace App\Filament\Exports;

use App\Models\DataRequest;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class DataRequestExporter extends Exporter
{
    protected static ?string $model = DataRequest::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('code')
                ->label('Code'),
            ExportColumn::make('status')
                ->label('Status'),
            ExportColumn::make('audit.title')
                ->label('Audit'),
            ExportColumn::make('createdBy.name')
                ->label('Created By'),
            ExportColumn::make('assignedTo.name')
                ->label('Assigned To'),
            ExportColumn::make('details')
                ->label('Details'),
            ExportColumn::make('response')
                ->label('Response'),
            ExportColumn::make('created_at')
                ->label('Created At'),
            ExportColumn::make('updated_at')
                ->label('Updated At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your data request export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

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
