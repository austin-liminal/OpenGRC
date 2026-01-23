<?php

namespace App\Filament\Exports;

use App\Models\FileAttachment;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class FileAttachmentExporter extends Exporter
{
    protected static ?string $model = FileAttachment::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('file_name')
                ->label('File Name'),
            ExportColumn::make('file_path')
                ->label('File Path'),
            ExportColumn::make('file_size')
                ->label('File Size'),
            ExportColumn::make('description')
                ->label('Description'),
            ExportColumn::make('audit.title')
                ->label('Audit'),
            ExportColumn::make('uploaded_at')
                ->label('Uploaded At'),
            ExportColumn::make('created_at')
                ->label('Created At'),
            ExportColumn::make('updated_at')
                ->label('Updated At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your file attachment export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

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
