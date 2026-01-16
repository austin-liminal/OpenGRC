<?php

namespace App\Filament\Resources\RiskResource\RelationManagers;

use App\Enums\MitigationType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('date_implemented')
                    ->label('Date Implemented')
                    ->native(false),
                Forms\Components\Select::make('strategy')
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
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->wrap()
                    ->limit(100)
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_implemented')
                    ->label('Date Implemented')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('strategy')
                    ->label('Strategy')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('strategy')
                    ->options(MitigationType::class),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
