<?php

namespace App\Filament\Exports;

use App\Models\AuditItem;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class AuditItemExporter extends Exporter
{
    protected static ?string $model = AuditItem::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('audit.title')
                ->label('Audit'),
            ExportColumn::make('control.identifier')
                ->label('Control ID'),
            ExportColumn::make('control.title')
                ->label('Control Title'),
            ExportColumn::make('user.name')
                ->label('Assigned To'),
            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? ''),
            ExportColumn::make('effectiveness')
                ->label('Effectiveness')
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? ''),
            ExportColumn::make('applicability')
                ->label('Applicability')
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? ''),
            ExportColumn::make('auditor_notes')
                ->label('Auditor Notes'),
            ExportColumn::make('created_at')
                ->label('Created At'),
            ExportColumn::make('updated_at')
                ->label('Updated At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your audit item export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

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
