<?php

namespace App\Filament\Resources\PolicyResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ImplementationsRelationManager extends RelationManager
{
    protected static string $relationship = 'implementations';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->sortable()
                    ->searchable()
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(fn ($state): string => match ($state?->value ?? $state) {
                        'Not Started' => 'danger',
                        'In Progress' => 'warning',
                        'Completed' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('controls_count')
                    ->label('Controls')
                    ->counts('controls')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Owner')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Attach Implementation')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function (Builder $query) {
                        $query->select(['id', 'title']);
                    })
                    ->recordTitle(function ($record) {
                        return strip_tags($record->title);
                    })
                    ->recordSelectSearchColumns(['title']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.app.resources.implementations.view', $record)),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()->label('Detach from Policy'),
                ]),
            ]);
    }

    public function canCreate(): bool
    {
        return false;
    }
}
