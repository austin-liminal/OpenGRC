<?php

namespace App\Filament\Exports;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use App\Models\Implementation;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ImplementationExporter extends Exporter
{
    protected static ?string $model = Implementation::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('code')
                ->label('Code'),
            ExportColumn::make('title')
                ->label('Title'),
            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? ''),
            ExportColumn::make('effectiveness')
                ->label('Effectiveness')
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? ''),
            ExportColumn::make('implementationOwner.name')
                ->label('Owner'),
            ExportColumn::make('details')
                ->label('Details'),
            ExportColumn::make('test_procedure')
                ->label('Test Procedure'),
            ExportColumn::make('notes')
                ->label('Notes'),
            ExportColumn::make('department')
                ->label('Department')
                ->state(function (Implementation $record): ?string {
                    $parent = Taxonomy::where('slug', 'department')->whereNull('parent_id')->first();
                    if (! $parent) {
                        return null;
                    }

                    return $record->taxonomies()->where('parent_id', $parent->id)->first()?->name;
                }),
            ExportColumn::make('scope')
                ->label('Scope')
                ->state(function (Implementation $record): ?string {
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
        $body = 'Your implementation export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

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
