<?php

namespace App\Filament\Admin\Resources\TaxonomyResource\RelationManagers;

use App\Filament\Concerns\RestoresSoftDeletedTaxonomies;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Str;

class TaxonomyChildrenRelationManager extends RelationManager
{
    use RestoresSoftDeletedTaxonomies;

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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Term Name'),
                Hidden::make('slug'),
                Textarea::make('description')
                    ->maxLength(1000)
                    ->columnSpanFull()
                    ->label('Description'),
                Hidden::make('type')
                    ->default(fn ($livewire) => $livewire->ownerRecord->type ?? 'general'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(fn () => 'No '.$this->getOwnerRecord()->name.' Terms')
            ->emptyStateDescription(fn () => 'Click "Add '.$this->getOwnerRecord()->name.' Term" to add '.$this->getOwnerRecord()->name.' terms.')
            ->emptyStateIcon('heroicon-o-tag')
            ->heading(fn () => Str::plural($this->getOwnerRecord()->name))
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Term Name'),
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable()
                    ->label('Slug'),
                TextColumn::make('children_count')
                    ->counts('children')
                    ->label('Sub-Terms')
                    ->sortable()
                    ->placeholder('0'),
                TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 50 ? $state : null;
                    })
                    ->label('Description'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(fn () => 'Add '.Str::plural($this->getOwnerRecord()->name))
                    ->modalHeading(fn () => 'Create New '.$this->getOwnerRecord()->name.' Term')
                    ->createAnother(false)
                    ->using(fn (array $data) => $this->createOrRestoreTaxonomy($data, $this->getOwnerRecord()->id)),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('View')
                    ->modalHeading(fn ($record) => 'View '.$this->getOwnerRecord()->name.' Term: '.$record->name),
                EditAction::make()
                    ->label('Edit')
                    ->modalHeading(fn ($record) => 'Edit '.$this->getOwnerRecord()->name.' Term: '.$record->name),
                DeleteAction::make()
                    ->label('Delete')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => 'Delete '.$this->getOwnerRecord()->name.' Term')
                    ->modalDescription(fn ($record) => 'Are you sure you want to delete "'.$record->name.'"? This action cannot be undone.')
                    ->action(fn ($record) => RestoresSoftDeletedTaxonomies::deleteTaxonomyWithChildren($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(fn ($records) => $records->each(fn ($record) => RestoresSoftDeletedTaxonomies::deleteTaxonomyWithChildren($record))),
                ]),
            ]);
    }
}
