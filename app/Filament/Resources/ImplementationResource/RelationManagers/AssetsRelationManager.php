<?php

namespace App\Filament\Resources\ImplementationResource\RelationManagers;

use App\Filament\Resources\AssetResource;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AssetsRelationManager extends RelationManager
{
    protected static string $relationship = 'assets';

    public function form(Schema $schema): Schema
    {
        return AssetResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('asset_type')
                    ->label('Type')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('owner')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('location')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('value')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('serial_number')
                    ->label('Serial #')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Relate to Asset')
                    ->preloadRecordSelect(),
                CreateAction::make()
                    ->label('Create a New Asset'),
            ])
            ->recordActions([
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
