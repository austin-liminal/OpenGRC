<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasTaxonomyFields;
use App\Filament\Exports\ProgramExporter;
use App\Filament\Resources\ProgramResource\Pages;
use App\Filament\Resources\ProgramResource\RelationManagers;
use App\Models\Program;
use Filament\Forms;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Table;

class ProgramResource extends Resource
{
    use HasTaxonomyFields;

    protected static ?string $model = Program::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = null;

    protected static ?string $navigationGroup = null;

    public static function getNavigationLabel(): string
    {
        return __('navigation.resources.program');
    }

    public static function getNavigationGroup(): string
    {
        return __('navigation.groups.foundations');
    }

    public static function getModelLabel(): string
    {
        return __('programs.labels.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('programs.labels.plural');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('programs.form.name'))
                    ->columnSpanFull()
                    ->required()
                    ->maxLength(255),
                RichEditor::make('description')
                    ->label(__('programs.form.description'))
                    ->fileAttachmentsDisk(setting('storage.driver', 'private'))
                    ->fileAttachmentsVisibility('private')
                    ->fileAttachmentsDirectory('ssp-uploads')
                    ->columnSpanFull(),
                Forms\Components\Select::make('program_manager_id')
                    ->label(__('programs.form.program_manager'))
                    ->relationship('programManager', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('scope_status')
                    ->label(__('programs.form.scope_status'))
                    ->options([
                        'In Scope' => __('programs.scope_status.in_scope'),
                        'Out of Scope' => __('programs.scope_status.out_of_scope'),
                        'Pending Review' => __('programs.scope_status.pending_review'),
                    ])
                    ->required(),
                self::taxonomySelect('Department', 'department')
                    ->nullable()
                    ->columnSpan(1),
                self::taxonomySelect('Scope', 'scope')
                    ->nullable()
                    ->columnSpan(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Program $record): string => Pages\ProgramPage::getUrl(['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('programs.table.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('programManager.name')
                    ->label(__('programs.table.program_manager'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_audit_date')
                    ->label(__('programs.table.last_audit_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('scope_status')
                    ->label(__('programs.table.scope_status'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('taxonomy_department')
                    ->label('Department')
                    ->getStateUsing(function (Program $record) {
                        return self::getTaxonomyTerm($record, 'department')?->name ?? 'Not assigned';
                    })
                    ->sortable(query: function ($query, string $direction): void {
                        $departmentParent = \Aliziodev\LaravelTaxonomy\Models\Taxonomy::where('slug', 'department')->whereNull('parent_id')->first();
                        if (! $departmentParent) {
                            return;
                        }

                        $query->leftJoin('taxonomables as dept_taxonomables', function ($join) {
                            $join->on('programs.id', '=', 'dept_taxonomables.taxonomable_id')
                                ->where('dept_taxonomables.taxonomable_type', '=', 'App\\Models\\Program');
                        })
                            ->leftJoin('taxonomies as dept_taxonomies', function ($join) use ($departmentParent) {
                                $join->on('dept_taxonomables.taxonomy_id', '=', 'dept_taxonomies.id')
                                    ->where('dept_taxonomies.parent_id', '=', $departmentParent->id);
                            })
                            ->orderBy('dept_taxonomies.name', $direction)
                            ->select('programs.*');
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('taxonomy_scope')
                    ->label('Scope')
                    ->getStateUsing(function (Program $record) {
                        return self::getTaxonomyTerm($record, 'scope')?->name ?? 'Not assigned';
                    })
                    ->sortable(query: function ($query, string $direction): void {
                        $scopeParent = \Aliziodev\LaravelTaxonomy\Models\Taxonomy::where('slug', 'scope')->whereNull('parent_id')->first();
                        if (! $scopeParent) {
                            return;
                        }

                        $query->leftJoin('taxonomables as scope_taxonomables', function ($join) {
                            $join->on('programs.id', '=', 'scope_taxonomables.taxonomable_id')
                                ->where('scope_taxonomables.taxonomable_type', '=', 'App\\Models\\Program');
                        })
                            ->leftJoin('taxonomies as scope_taxonomies', function ($join) use ($scopeParent) {
                                $join->on('scope_taxonomables.taxonomy_id', '=', 'scope_taxonomies.id')
                                    ->where('scope_taxonomies.parent_id', '=', $scopeParent->id);
                            })
                            ->orderBy('scope_taxonomies.name', $direction)
                            ->select('programs.*');
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('programs.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('programs.table.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department')
                    ->label('Department')
                    ->options(function () {
                        $taxonomy = self::getParentTaxonomy('department');

                        if (! $taxonomy) {
                            return [];
                        }

                        return \Aliziodev\LaravelTaxonomy\Models\Taxonomy::where('parent_id', $taxonomy->id)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->query(function ($query, array $data) {
                        if (! $data['value']) {
                            return;
                        }

                        $query->whereHas('taxonomies', function ($query) use ($data) {
                            $query->where('taxonomy_id', $data['value']);
                        });
                    }),
                Tables\Filters\SelectFilter::make('scope')
                    ->label('Scope')
                    ->options(function () {
                        $taxonomy = self::getParentTaxonomy('scope');

                        if (! $taxonomy) {
                            return [];
                        }

                        return \Aliziodev\LaravelTaxonomy\Models\Taxonomy::where('parent_id', $taxonomy->id)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->query(function ($query, array $data) {
                        if (! $data['value']) {
                            return;
                        }

                        $query->whereHas('taxonomies', function ($query) use ($data) {
                            $query->where('taxonomy_id', $data['value']);
                        });
                    }),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(ProgramExporter::class)
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(ProgramExporter::class)
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray'),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\StandardsRelationManager::class,
            RelationManagers\ControlsRelationManager::class,
            RelationManagers\RisksRelationManager::class,
            RelationManagers\AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrograms::route('/'),
            'create' => Pages\CreateProgram::route('/create'),
            'view' => Pages\ProgramPage::route('/{record}'),
            'edit' => Pages\EditProgram::route('/{record}/edit'),
        ];
    }
}
