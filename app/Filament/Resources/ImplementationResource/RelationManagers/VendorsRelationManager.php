<?php

namespace App\Filament\Resources\ImplementationResource\RelationManagers;

use App\Filament\Resources\VendorResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VendorsRelationManager extends RelationManager
{
    protected static string $relationship = 'vendors';

    public function form(Form $form): Form
    {
        return VendorResource::form($form);
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

                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn ($record) => $record->status->getColor())
                    ->sortable(),

                Tables\Columns\TextColumn::make('risk_rating')
                    ->label(__('Risk Rating'))
                    ->badge()
                    ->color(fn ($record) => $record->risk_rating->getColor())
                    ->sortable(),

                Tables\Columns\TextColumn::make('vendorManager.name')
                    ->label(__('Vendor Manager'))
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
                    ->label('Relate to Vendor')
                    ->preloadRecordSelect(),
                Tables\Actions\CreateAction::make()
                    ->label('Create a New Vendor'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => VendorResource::getUrl('view', ['record' => $record])),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
