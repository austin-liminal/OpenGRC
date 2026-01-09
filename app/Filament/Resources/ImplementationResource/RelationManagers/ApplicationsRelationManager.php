<?php

namespace App\Filament\Resources\ImplementationResource\RelationManagers;

use App\Filament\Resources\ApplicationResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ApplicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'applications';

    public function form(Form $form): Form
    {
        return ApplicationResource::form($form);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->color(fn ($record) => $record->type->getColor())
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn ($record) => $record->status->getColor())
                    ->sortable(),

                Tables\Columns\TextColumn::make('owner.name')
                    ->label(__('Owner'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('vendor.name')
                    ->label(__('Vendor'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('url')
                    ->label(__('URL'))
                    ->url(fn ($record) => $record->url, true)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Relate to Application')
                    ->preloadRecordSelect(),
                Tables\Actions\CreateAction::make()
                    ->label('Create a New Application'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => ApplicationResource::getUrl('view', ['record' => $record])),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
