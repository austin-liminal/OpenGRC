<?php

namespace App\Filament\Exports;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use App\Models\Audit;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class AuditExporter extends Exporter
{
    protected static ?string $model = Audit::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('title')
                ->label('Title'),
            ExportColumn::make('audit_type')
                ->label('Audit Type'),
            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? ''),
            ExportColumn::make('program.name')
                ->label('Program'),
            ExportColumn::make('manager.name')
                ->label('Manager'),
            ExportColumn::make('start_date')
                ->label('Start Date'),
            ExportColumn::make('end_date')
                ->label('End Date'),
            ExportColumn::make('description')
                ->label('Description'),
            ExportColumn::make('department')
                ->label('Department')
                ->state(function (Audit $record): ?string {
                    $parent = Taxonomy::where('slug', 'department')->whereNull('parent_id')->first();
                    if (! $parent) {
                        return null;
                    }

                    return $record->taxonomies()->where('parent_id', $parent->id)->first()?->name;
                }),
            ExportColumn::make('scope')
                ->label('Scope')
                ->state(function (Audit $record): ?string {
                    $parent = Taxonomy::where('slug', 'scope')->whereNull('parent_id')->first();
                    if (! $parent) {
                        return null;
                    }

                    return $record->taxonomies()->where('parent_id', $parent->id)->first()?->name;
                }),
            ExportColumn::make('created_at')
                ->label('Created At'),
            ExportColumn::make('updated_at')
                ->label('Updated At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your audit export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

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
