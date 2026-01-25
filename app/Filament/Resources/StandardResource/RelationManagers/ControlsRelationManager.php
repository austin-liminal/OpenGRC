<?php

namespace App\Filament\Resources\StandardResource\RelationManagers;

use App\Filament\Resources\ControlResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ControlsRelationManager extends RelationManager
{
    protected static string $relationship = 'Controls';

    public function form(Schema $schema): Schema
    {
        return ControlResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('code')
                    ->sortable(),
                TextColumn::make('title')
                    ->wrap()
                    ->html()
                    ->sortable(),
                TextColumn::make('description')
                    ->html()
                    ->wrap()
                    ->limit(300)
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()->label('Add New Control'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->hiddenLabel()
                    ->url(fn ($record) => route('filament.app.resources.controls.view', $record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
