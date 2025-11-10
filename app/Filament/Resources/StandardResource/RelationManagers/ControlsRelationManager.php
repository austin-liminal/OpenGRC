<?php

namespace App\Filament\Resources\StandardResource\RelationManagers;

use App\Filament\Resources\ControlResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ControlsRelationManager extends RelationManager
{
    protected static string $relationship = 'Controls';

    public function form(Form $form): Form
    {
        return ControlResource::form($form);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->wrap()
                    ->html()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->html()
                    ->wrap()
                    ->limit(300)
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Add New Control'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->hiddenLabel()
                    ->url(fn ($record) => route('filament.app.resources.controls.view', $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
