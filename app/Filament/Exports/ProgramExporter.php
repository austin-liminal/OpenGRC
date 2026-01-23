<?php

namespace App\Filament\Exports;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use App\Models\Program;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ProgramExporter extends Exporter
{
    protected static ?string $model = Program::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')
                ->label('Name'),
            ExportColumn::make('description')
                ->label('Description'),
            ExportColumn::make('programManager.name')
                ->label('Program Manager'),
            ExportColumn::make('scope_status')
                ->label('Scope Status'),
            ExportColumn::make('last_audit_date')
                ->label('Last Audit Date'),
            ExportColumn::make('department')
                ->label('Department')
                ->state(function (Program $record): ?string {
                    $parent = Taxonomy::where('slug', 'department')->whereNull('parent_id')->first();
                    if (! $parent) {
                        return null;
                    }

                    return $record->taxonomies()->where('parent_id', $parent->id)->first()?->name;
                }),
            ExportColumn::make('scope')
                ->label('Scope')
                ->state(function (Program $record): ?string {
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
        $body = 'Your program export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

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
