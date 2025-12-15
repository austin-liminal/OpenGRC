<?php

namespace App\Filament\Resources;

use App\Enums\VendorDocumentStatus;
use App\Enums\VendorDocumentType;
use App\Filament\Resources\VendorDocumentResource\Pages;
use App\Models\VendorDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VendorDocumentResource extends Resource
{
    protected static ?string $model = VendorDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Vendor Management';

    protected static ?string $navigationLabel = 'Vendor Documents';

    protected static ?int $navigationSort = 3;

    // Hide from navigation - access via Vendor relation manager
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Document Information')
                    ->schema([
                        Forms\Components\Select::make('vendor_id')
                            ->label('Vendor')
                            ->relationship('vendor', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

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
                            ->maxSize(20480)
                            ->storeFileNamesIn('file_name'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(VendorDocumentStatus::class)
                            ->required()
                            ->native(false)
                            ->default(VendorDocumentStatus::PENDING),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Dates')
                    ->schema([
                        Forms\Components\DatePicker::make('issue_date')
                            ->label('Issue Date')
                            ->native(false),

                        Forms\Components\DatePicker::make('expiration_date')
                            ->label('Expiration Date')
                            ->native(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

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

                Tables\Columns\TextColumn::make('uploadedBy.name')
                    ->label('Uploaded By')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vendor_id')
                    ->label('Vendor')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('document_type')
                    ->label('Type')
                    ->options(VendorDocumentType::class),

                Tables\Filters\SelectFilter::make('status')
                    ->options(VendorDocumentStatus::class),

                Tables\Filters\Filter::make('pending_review')
                    ->label('Pending Review')
                    ->query(fn ($query) => $query->where('status', VendorDocumentStatus::PENDING))
                    ->toggle(),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expiring Soon (30 days)')
                    ->query(fn ($query) => $query->expiringSoon())
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (VendorDocument $record) {
                        return Storage::disk(config('filesystems.default'))
                            ->download($record->file_path, $record->file_name);
                    }),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('review_notes')
                            ->label('Review Notes')
                            ->placeholder('Optional notes about the approval...')
                            ->rows(3),
                    ])
                    ->action(function (VendorDocument $record, array $data) {
                        $record->update([
                            'status' => VendorDocumentStatus::APPROVED,
                            'review_notes' => $data['review_notes'] ?? null,
                            'reviewed_by' => Auth::id(),
                            'reviewed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Document approved')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (VendorDocument $record) => in_array($record->status, [
                        VendorDocumentStatus::PENDING,
                        VendorDocumentStatus::UNDER_REVIEW,
                    ])),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('review_notes')
                            ->label('Rejection Reason')
                            ->placeholder('Please explain why this document is being rejected...')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (VendorDocument $record, array $data) {
                        $record->update([
                            'status' => VendorDocumentStatus::REJECTED,
                            'review_notes' => $data['review_notes'],
                            'reviewed_by' => Auth::id(),
                            'reviewed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Document rejected')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn (VendorDocument $record) => in_array($record->status, [
                        VendorDocumentStatus::PENDING,
                        VendorDocumentStatus::UNDER_REVIEW,
                    ])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Document Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('vendor.name')
                            ->label('Vendor'),

                        Infolists\Components\TextEntry::make('document_type')
                            ->label('Type')
                            ->badge(),

                        Infolists\Components\TextEntry::make('name')
                            ->label('Name'),

                        Infolists\Components\TextEntry::make('status')
                            ->badge(),

                        Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull()
                            ->placeholder('No description'),

                        Infolists\Components\TextEntry::make('file_name')
                            ->label('File'),

                        Infolists\Components\TextEntry::make('uploadedBy.name')
                            ->label('Uploaded By')
                            ->placeholder('Unknown'),
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

                Infolists\Components\Section::make('Review Information')
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
                    ->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorDocuments::route('/'),
            'create' => Pages\CreateVendorDocument::route('/create'),
            'view' => Pages\ViewVendorDocument::route('/{record}'),
            'edit' => Pages\EditVendorDocument::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', VendorDocumentStatus::PENDING)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
