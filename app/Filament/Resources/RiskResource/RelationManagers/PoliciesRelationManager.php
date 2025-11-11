<?php

namespace App\Filament\Resources\RiskResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PoliciesRelationManager extends RelationManager
{
    protected static string $relationship = 'policies';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('primary')
                    ->wrap(),

                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'In Review' => 'info',
                        'Awaiting Feedback' => 'warning',
                        'Pending Approval' => 'warning',
                        'Approved' => 'success',
                        'Archived' => 'gray',
                        'Superseded', 'Retired' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Relate to Policy')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function (Builder $query) {
                        $query->select(['policies.id', 'policies.code', 'policies.name']);
                    })
                    ->recordTitle(function ($record) {
                        return strip_tags("({$record->code}) {$record->name}");
                    })
                    ->recordSelectSearchColumns(['code', 'name']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.app.resources.policies.view', $record)),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()->label('Detach from Risk'),
                ]),
            ]);
    }

    public function canCreate(): bool
    {
        return false;
    }
}
