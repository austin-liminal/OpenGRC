<?php

namespace App\Filament\Resources;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use App\Enums\DocumentType;
use App\Filament\Exports\PolicyExporter;
use App\Filament\Resources\PolicyResource\Pages;
use App\Filament\Resources\PolicyResource\RelationManagers;
use App\Models\Policy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PolicyResource extends Resource
{
    protected static ?string $model = Policy::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Policies';

    protected static ?string $navigationGroup = 'Entities';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Core Information Section
                Forms\Components\Section::make('Document Information')
                    ->columns(4)
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->label('Policy Code')
                            ->placeholder('e.g., POL-001')
                            ->helperText('Unique identifier for this policy'),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Policy Name')
                            ->placeholder('e.g., Information Security Policy')
                            ->columnSpan(3),

                        Forms\Components\Select::make('document_type')
                            ->label('Document Type')
                            ->options(DocumentType::class)
                            ->default(DocumentType::Policy)
                            ->required()
                            ->helperText('Type of document (Policy, Procedure, Standard, etc.)'),

                        Forms\Components\Select::make('status_id')
                            ->label('Status')
                            ->options(fn () => Taxonomy::where('slug', 'policy-status')->first()?->children()->pluck('name', 'id') ?? collect())
                            ->searchable()
                            ->required()
                            ->helperText('Current status of the policy'),

                        Forms\Components\Select::make('scope_id')
                            ->label('Scope')
                            ->options(fn () => Taxonomy::where('slug', 'policy-scope')->first()?->children()->pluck('name', 'id') ?? collect())
                            ->searchable()
                            ->helperText('Organizational scope of this policy'),

                        Forms\Components\Select::make('department_id')
                            ->label('Department')
                            ->options(fn () => Taxonomy::where('slug', 'department')->first()?->children()->pluck('name', 'id') ?? collect())
                            ->searchable()
                            ->helperText('Department responsible for this policy'),

                        Forms\Components\Select::make('owner_id')
                            ->label('Policy Owner')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('User responsible for this policy'),

                        Forms\Components\DatePicker::make('effective_date')
                            ->label('Effective Date')
                            ->helperText('Date when this policy becomes effective'),

                        Forms\Components\DatePicker::make('retired_date')
                            ->label('Retired Date')
                            ->helperText('Date when this policy was retired (only for retired/superseded policies)'),
                    ]),

                // Policy Content Section
                Forms\Components\Section::make('Policy Content')
                    ->schema([
                        Forms\Components\RichEditor::make('policy_scope')
                            ->label('Policy Scope')
                            ->columnSpanFull()
                            ->helperText('Define the scope and applicability of this policy'),

                        Forms\Components\RichEditor::make('purpose')
                            ->label('Purpose')
                            ->columnSpanFull()
                            ->helperText('Explain the purpose and objectives of this policy'),

                        Forms\Components\RichEditor::make('body')
                            ->label('Policy Body')
                            ->columnSpanFull()
                            ->helperText('Main content and requirements of the policy'),
                    ]),

                // Document Upload Section
                Forms\Components\Section::make('Policy Document')
                    ->schema([
                        Forms\Components\FileUpload::make('document_path')
                            ->label('Upload Policy Document')
                            ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(10240)
                            ->directory('policies')
                            ->columnSpanFull()
                            ->helperText('Upload a policy document instead of filling in the fields above (PDF, DOC, DOCX - max 10MB)'),
                    ])
                    ->collapsed()
                    ->description('Optionally upload a policy document file'),

                // Revision History Section
                Forms\Components\Section::make('Revision History')
                    ->schema([
                        Forms\Components\Repeater::make('revision_history')
                            ->label('')
                            ->schema([
                                Forms\Components\TextInput::make('version')
                                    ->label('Version')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., 1.0, 2.1'),

                                Forms\Components\DatePicker::make('date')
                                    ->label('Date')
                                    ->required(),

                                Forms\Components\TextInput::make('author')
                                    ->label('Author')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Author name'),

                                Forms\Components\Textarea::make('changes')
                                    ->label('Changes')
                                    ->required()
                                    ->rows(3)
                                    ->placeholder('Describe the changes made in this version')
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Add Revision')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['version'] ?? null)
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->description('Track version history and changes to this policy'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->label('Code')
                    ->toggleable()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state?->getLabel() ?? DocumentType::Other->getLabel())
                    ->color(fn ($state) => $state?->getColor() ?? DocumentType::Other->getColor())
                    ->icon(fn ($state) => $state?->getIcon() ?? DocumentType::Other->getIcon())
                    ->sortable(),

                Tables\Columns\TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->toggleable()
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'In Review' => 'info',
                        'Awaiting Feedback' => 'warning',
                        'Pending Approval' => 'warning',
                        'Approved' => 'success',
                        'Archived' => 'gray',
                        'Superseded', 'Retired' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('scope.name')
                    ->label('Scope')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updater.name')
                    ->label('Updated By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('document_type')
                    ->label('Document Type')
                    ->options(DocumentType::class),

                Tables\Filters\SelectFilter::make('status_id')
                    ->label('Status')
                    ->options(fn () => Taxonomy::where('slug', 'policy-status')->first()?->children()->pluck('name', 'id') ?? collect())
                    ->searchable(),

                Tables\Filters\SelectFilter::make('scope_id')
                    ->label('Scope')
                    ->options(fn () => Taxonomy::where('slug', 'policy-scope')->first()?->children()->pluck('name', 'id') ?? collect())
                    ->searchable(),

                Tables\Filters\SelectFilter::make('department_id')
                    ->label('Department')
                    ->options(fn () => Taxonomy::where('slug', 'department')->first()?->children()->pluck('name', 'id') ?? collect())
                    ->searchable(),

                Tables\Filters\SelectFilter::make('owner_id')
                    ->label('Policy Owner')
                    ->relationship('owner', 'name')
                    ->searchable(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(PolicyExporter::class)
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('updateDocumentType')
                        ->label('Update Document Type')
                        ->icon('heroicon-o-document-text')
                        ->form([
                            Forms\Components\Select::make('document_type')
                                ->label('Document Type')
                                ->options(DocumentType::class)
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            $records->each->update(['document_type' => $data['document_type']]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('updateStatus')
                        ->label('Update Status')
                        ->icon('heroicon-o-flag')
                        ->form([
                            Forms\Components\Select::make('status_id')
                                ->label('Status')
                                ->options(fn () => Taxonomy::where('slug', 'policy-status')->first()?->children()->pluck('name', 'id') ?? collect())
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            $records->each->update(['status_id' => $data['status_id']]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('updateDepartment')
                        ->label('Update Department')
                        ->icon('heroicon-o-building-office')
                        ->form([
                            Forms\Components\Select::make('department_id')
                                ->label('Department')
                                ->options(fn () => Taxonomy::where('slug', 'department')->first()?->children()->pluck('name', 'id') ?? collect())
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            $records->each->update(['department_id' => $data['department_id']]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('updateOwner')
                        ->label('Update Owner')
                        ->icon('heroicon-o-user')
                        ->form([
                            Forms\Components\Select::make('owner_id')
                                ->label('Policy Owner')
                                ->relationship('owner', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            $records->each->update(['owner_id' => $data['owner_id']]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('updateScope')
                        ->label('Update Scope')
                        ->icon('heroicon-o-globe-alt')
                        ->form([
                            Forms\Components\Select::make('scope_id')
                                ->label('Scope')
                                ->options(fn () => Taxonomy::where('slug', 'policy-scope')->first()?->children()->pluck('name', 'id') ?? collect())
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            $records->each->update(['scope_id' => $data['scope_id']]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    ExportBulkAction::make()
                        ->exporter(PolicyExporter::class)
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray'),

                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),

            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Header Section
                Infolists\Components\Section::make('Policy Overview')
                    ->schema([
                        Infolists\Components\Split::make([
                            Infolists\Components\Grid::make(2)
                                ->schema([
                                    Infolists\Components\Group::make([
                                        Infolists\Components\TextEntry::make('code')
                                            ->label('Policy Code')
                                            ->badge()
                                            ->color('primary')
                                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                                        Infolists\Components\TextEntry::make('name')
                                            ->label('Policy Name')
                                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                            ->weight('bold'),

                                        Infolists\Components\TextEntry::make('document_type')
                                            ->label('Document Type')
                                            ->badge()
                                            ->formatStateUsing(fn ($state) => $state?->getLabel() ?? DocumentType::Other->getLabel())
                                            ->color(fn ($state) => $state?->getColor() ?? DocumentType::Other->getColor())
                                            ->icon(fn ($state) => $state?->getIcon() ?? DocumentType::Other->getIcon()),
                                    ]),

                                    Infolists\Components\Group::make([
                                        Infolists\Components\TextEntry::make('status.name')
                                            ->label('Status')
                                            ->badge()
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

                                        Infolists\Components\TextEntry::make('scope.name')
                                            ->label('Scope')
                                            ->badge()
                                            ->color('info')
                                            ->placeholder('Not specified'),
                                    ]),
                                ]),
                        ])->from('md'),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('department.name')
                                    ->label('Department')
                                    ->icon('heroicon-o-building-office')
                                    ->placeholder('Not assigned'),

                                Infolists\Components\TextEntry::make('document_path')
                                    ->label('Document')
                                    ->formatStateUsing(fn ($state) => $state ? 'Document Uploaded' : 'No Document')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'gray')
                                    ->icon(fn ($state) => $state ? 'heroicon-o-document-check' : 'heroicon-o-document'),
                            ]),
                    ])
                    ->collapsible(),

                // Policy Content
                Infolists\Components\Section::make('Policy Content')
                    ->schema([
                        Infolists\Components\TextEntry::make('policy_scope')
                            ->label('Policy Scope')
                            ->html()
                            ->placeholder('No scope defined')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('purpose')
                            ->label('Purpose')
                            ->html()
                            ->placeholder('No purpose defined')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('body')
                            ->label('Policy Body')
                            ->html()
                            ->placeholder('No body content')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),

                // Revision History
                Infolists\Components\Section::make('Revision History')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('revision_history')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('version')
                                    ->label('Version')
                                    ->badge()
                                    ->color('primary'),

                                Infolists\Components\TextEntry::make('date')
                                    ->label('Date')
                                    ->date('n/j/Y'),

                                Infolists\Components\TextEntry::make('author')
                                    ->label('Author')
                                    ->icon('heroicon-o-user'),

                                Infolists\Components\TextEntry::make('changes')
                                    ->html()
                                    ->label('Changes'),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->visible(fn ($record) => $record->revision_history && count($record->revision_history) > 0),

                // Metadata
                Infolists\Components\Section::make('Metadata')
                    ->schema([
                        Infolists\Components\TextEntry::make('creator.name')
                            ->label('Created By')
                            ->icon('heroicon-o-user'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('M d, Y H:i'),

                        Infolists\Components\TextEntry::make('updater.name')
                            ->label('Last Updated By')
                            ->icon('heroicon-o-user'),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime('M d, Y H:i')
                            ->since(),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ControlsRelationManager::class,
            RelationManagers\ImplementationsRelationManager::class,
            RelationManagers\RisksRelationManager::class,
            RelationManagers\ExceptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPolicies::route('/'),
            'create' => Pages\CreatePolicy::route('/create'),
            'view' => Pages\ViewPolicy::route('/{record}'),
            'view-details' => Pages\ViewPolicyDetails::route('/{record}/details'),
            'edit' => Pages\EditPolicy::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->name.' ('.$record->code.')';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'name', 'policy_scope', 'purpose'];
    }
}
