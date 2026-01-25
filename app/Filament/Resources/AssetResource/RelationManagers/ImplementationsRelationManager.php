<?php

namespace App\Filament\Resources\AssetResource\RelationManagers;

use App\Enums\Effectiveness;
use App\Enums\ImplementationStatus;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ImplementationsRelationManager extends RelationManager
{
    protected static string $relationship = 'implementations';

    protected static ?string $title = 'Implementations';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('details')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('details')
            ->columns([
                TextColumn::make('details')
                    ->label('Details')
                    ->searchable()
                    ->limit(50),

                TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('effectiveness')
                    ->badge()
                    ->sortable(),

                TextColumn::make('controls_count')
                    ->counts('controls')
                    ->label('Controls'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ImplementationStatus::class),
                SelectFilter::make('effectiveness')
                    ->options(Effectiveness::class),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect(),
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
