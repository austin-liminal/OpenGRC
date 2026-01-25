<?php

namespace App\Filament\Vendor\Resources;

use App\Enums\VendorDocumentStatus;
use App\Enums\VendorDocumentType;
use App\Filament\Vendor\Resources\DocumentResource\Pages\CreateDocument;
use App\Filament\Vendor\Resources\DocumentResource\Pages\EditDocument;
use App\Filament\Vendor\Resources\DocumentResource\Pages\ListDocuments;
use App\Filament\Vendor\Resources\DocumentResource\Pages\ViewDocument;
use App\Models\VendorDocument;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DocumentResource extends Resource
{
    protected static ?string $model = VendorDocument::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Documents';

    protected static ?string $modelLabel = 'Document';

    protected static ?string $pluralModelLabel = 'Documents';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        $vendorUser = Auth::guard('vendor')->user();

        return parent::getEloquentQuery()
            ->where('vendor_id', $vendorUser?->vendor_id);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Document Information')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('document_type')
                            ->label('Document Type')
                            ->options(VendorDocumentType::class)
                            ->required()
                            ->native(false),

                        TextInput::make('name')
                            ->label('Document Name')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(1000),

                        FileUpload::make('file_path')
                            ->label('Document File')
                            ->required()
                            ->disk(config('filesystems.default'))
                            ->directory('vendor-documents')
                            ->visibility('private')
                            ->maxSize(20480) // 20MB
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'image/jpeg',
                                'image/png',
                            ])
                            ->storeFileNamesIn('file_name'),
                    ])
                    ->columns(1),

                Section::make('Dates')
                    ->columnSpanFull()
                    ->schema([
                        DatePicker::make('issue_date')
                            ->label('Issue Date')
                            ->native(false),

                        DatePicker::make('expiration_date')
                            ->label('Expiration Date')
                            ->native(false)
                            ->after('issue_date'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('expiration_date')
                    ->label('Expires')
                    ->date()
                    ->sortable()
                    ->color(fn (VendorDocument $record) => match (true) {
                        $record->isExpired() => 'danger',
                        $record->isExpiringSoon() => 'warning',
                        default => null,
                    }),

                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('document_type')
                    ->label('Type')
                    ->options(VendorDocumentType::class),

                SelectFilter::make('status')
                    ->options(VendorDocumentStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (VendorDocument $record) => in_array($record->status, [
                        VendorDocumentStatus::DRAFT,
                        VendorDocumentStatus::REJECTED,
                    ])),
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (VendorDocument $record) => route('vendor.document.download', $record))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Document Information')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('document_type')
                            ->label('Type')
                            ->badge(),

                        TextEntry::make('name')
                            ->label('Name'),

                        TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull()
                            ->placeholder('No description provided'),

                        TextEntry::make('status')
                            ->badge(),

                        TextEntry::make('file_name')
                            ->label('File'),
                    ])
                    ->columns(2),

                Section::make('Dates')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('issue_date')
                            ->label('Issue Date')
                            ->date()
                            ->placeholder('Not specified'),

                        TextEntry::make('expiration_date')
                            ->label('Expiration Date')
                            ->date()
                            ->color(fn (VendorDocument $record) => match (true) {
                                $record->isExpired() => 'danger',
                                $record->isExpiringSoon() => 'warning',
                                default => null,
                            })
                            ->placeholder('No expiration'),

                        TextEntry::make('created_at')
                            ->label('Uploaded')
                            ->dateTime(),
                    ])
                    ->columns(3),

                Section::make('Review Status')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('reviewedBy.name')
                            ->label('Reviewed By')
                            ->placeholder('Not yet reviewed'),

                        TextEntry::make('reviewed_at')
                            ->label('Reviewed At')
                            ->dateTime()
                            ->placeholder('Not yet reviewed'),

                        TextEntry::make('review_notes')
                            ->label('Review Notes')
                            ->columnSpanFull()
                            ->placeholder('No notes'),
                    ])
                    ->columns(2)
                    ->visible(fn (VendorDocument $record) => $record->reviewed_at !== null),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocuments::route('/'),
            'create' => CreateDocument::route('/create'),
            'view' => ViewDocument::route('/{record}'),
            'edit' => EditDocument::route('/{record}/edit'),
        ];
    }
}
