<?php

namespace App\Filament\Resources\RiskResource\RelationManagers;

use App\Enums\MitigationType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Livewire\Attributes\On;

class MitigationsRelationManager extends RelationManager
{
    protected static string $relationship = 'mitigations';

    #[On('refreshRelationManager')]
    public function refreshRelationManager(string $manager): void
    {
        if ($manager === 'mitigations') {
            $this->resetTable();
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('description')
                    ->label('Description')
                    ->required()
                    ->columnSpanFull(),
                DatePicker::make('date_implemented')
                    ->label('Date Implemented')
                    ->native(false),
                Select::make('strategy')
                    ->label('Mitigation Strategy')
                    ->enum(MitigationType::class)
                    ->options(MitigationType::class)
                    ->default(MitigationType::OPEN)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->defaultSort('date_implemented', 'desc')
            ->columns([
                TextColumn::make('description')
                    ->label('Description')
                    ->wrap()
                    ->limit(100)
                    ->searchable(),
                TextColumn::make('date_implemented')
                    ->label('Date Implemented')
                    ->date()
                    ->sortable(),
                TextColumn::make('strategy')
                    ->label('Strategy')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('strategy')
                    ->options(MitigationType::class),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
