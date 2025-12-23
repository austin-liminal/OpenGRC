<?php

namespace App\Filament\Vendor\Resources;

use App\Enums\VendorDocumentStatus;
use App\Enums\VendorDocumentType;
use App\Filament\Vendor\Resources\DocumentResource\Pages;
use App\Models\VendorDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DocumentResource extends Resource
{
    protected static ?string $model = VendorDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Document Information')
                    ->schema([
                        Forms\Components\Select::make('document_type')
                            ->label('Document Type')
                            ->options(VendorDocumentType::class)
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('name')
                            ->label('Document Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(1000),

                        Forms\Components\FileUpload::make('file_path')
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

                Forms\Components\Section::make('Dates')
                    ->schema([
                        Forms\Components\DatePicker::make('issue_date')
                            ->label('Issue Date')
                            ->native(false),

                        Forms\Components\DatePicker::make('expiration_date')
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
                Tables\Columns\TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('expiration_date')
                    ->label('Expires')
                    ->date()
                    ->sortable()
                    ->color(fn (VendorDocument $record) => match (true) {
                        $record->isExpired() => 'danger',
                        $record->isExpiringSoon() => 'warning',
                        default => null,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('document_type')
                    ->label('Type')
                    ->options(VendorDocumentType::class),

                Tables\Filters\SelectFilter::make('status')
                    ->options(VendorDocumentStatus::class),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (VendorDocument $record) => in_array($record->status, [
                        VendorDocumentStatus::DRAFT,
                        VendorDocumentStatus::REJECTED,
                    ])),
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (VendorDocument $record) => route('vendor.document.download', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Document Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('document_type')
                            ->label('Type')
                            ->badge(),

                        Infolists\Components\TextEntry::make('name')
                            ->label('Name'),

                        Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull()
                            ->placeholder('No description provided'),

                        Infolists\Components\TextEntry::make('status')
                            ->badge(),

                        Infolists\Components\TextEntry::make('file_name')
                            ->label('File'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Dates')
                    ->schema([
                        Infolists\Components\TextEntry::make('issue_date')
                            ->label('Issue Date')
                            ->date()
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('expiration_date')
                            ->label('Expiration Date')
                            ->date()
                            ->color(fn (VendorDocument $record) => match (true) {
                                $record->isExpired() => 'danger',
                                $record->isExpiringSoon() => 'warning',
                                default => null,
                            })
                            ->placeholder('No expiration'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Uploaded')
                            ->dateTime(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Review Status')
                    ->schema([
                        Infolists\Components\TextEntry::make('reviewedBy.name')
                            ->label('Reviewed By')
                            ->placeholder('Not yet reviewed'),

                        Infolists\Components\TextEntry::make('reviewed_at')
                            ->label('Reviewed At')
                            ->dateTime()
                            ->placeholder('Not yet reviewed'),

                        Infolists\Components\TextEntry::make('review_notes')
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
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'view' => Pages\ViewDocument::route('/{record}'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
