<?php

namespace App\Filament\Admin\Resources\TaxonomyResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TaxonomyChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    protected static ?string $title = 'Terms';

    protected static ?string $modelLabel = 'term';

    protected static ?string $pluralModelLabel = 'terms';

    /**
     * Protected system taxonomy slugs whose child terms cannot be deleted
     */
    protected static array $protectedParentSlugs = [
        'scope',
        'department',
        'asset-type',
        'asset-status',
        'asset-condition',
        'compliance-status',
        'data-classification',
    ];

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Term Name'),
                Forms\Components\Hidden::make('slug'),
                Forms\Components\Textarea::make('description')
                    ->maxLength(1000)
                    ->columnSpanFull()
                    ->label('Description'),
                Forms\Components\Hidden::make('type')
                    ->default(fn ($livewire) => $livewire->ownerRecord->type ?? 'general'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(fn () => 'No ' . $this->getOwnerRecord()->name . ' Terms')
            ->emptyStateDescription(fn () => 'Click "Add ' . $this->getOwnerRecord()->name . ' Term" to add ' . $this->getOwnerRecord()->name . ' terms.')
            ->emptyStateIcon('heroicon-o-tag')
            ->heading(fn () => \Str::plural($this->getOwnerRecord()->name))
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Term Name'),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->sortable()
                    ->label('Slug'),
                Tables\Columns\TextColumn::make('children_count')
                    ->counts('children')
                    ->label('Sub-Terms')
                    ->sortable()
                    ->placeholder('0'),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 50 ? $state : null;
                    })
                    ->label('Description'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(fn () => 'Add ' . \Str::plural($this->getOwnerRecord()->name))
                    ->modalHeading(fn () => 'Create New ' . $this->getOwnerRecord()->name . ' Term')
                    ->createAnother(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View')
                    ->modalHeading(fn ($record) => 'View ' . $this->getOwnerRecord()->name . ' Term: ' . $record->name),
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->modalHeading(fn ($record) => 'Edit ' . $this->getOwnerRecord()->name . ' Term: ' . $record->name),
                Tables\Actions\DeleteAction::make()
                    ->label('Delete')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => 'Delete ' . $this->getOwnerRecord()->name . ' Term')
                    ->modalDescription(fn ($record) => 'Are you sure you want to delete "' . $record->name . '"? This action cannot be undone.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
