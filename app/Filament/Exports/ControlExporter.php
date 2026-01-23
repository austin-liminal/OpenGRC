<?php

namespace App\Filament\Exports;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use App\Models\Control;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ControlExporter extends Exporter
{
    protected static ?string $model = Control::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('identifier')
                ->label('Identifier'),
            ExportColumn::make('title')
                ->label('Title'),
            ExportColumn::make('standard.title')
                ->label('Standard'),
            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? ''),
            ExportColumn::make('effectiveness')
                ->label('Effectiveness')
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? ''),
            ExportColumn::make('type')
                ->label('Type')
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? ''),
            ExportColumn::make('category')
                ->label('Category')
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? ''),
            ExportColumn::make('enforcement')
                ->label('Enforcement')
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? ''),
            ExportColumn::make('controlOwner.name')
                ->label('Owner'),
            ExportColumn::make('description')
                ->label('Description'),
            ExportColumn::make('department')
                ->label('Department')
                ->state(function (Control $record): ?string {
                    $parent = Taxonomy::where('slug', 'department')->whereNull('parent_id')->first();
                    if (! $parent) {
                        return null;
                    }

                    return $record->taxonomies()->where('parent_id', $parent->id)->first()?->name;
                }),
            ExportColumn::make('scope')
                ->label('Scope')
                ->state(function (Control $record): ?string {
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
        $body = 'Your control export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

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
