<?php

namespace App\Filament\Resources\VendorResource\RelationManagers;

use App\Enums\VendorDocumentStatus;
use App\Enums\VendorDocumentType;
use App\Models\VendorDocument;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VendorDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-document-text';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    ->maxLength(1000)
                    ->columnSpanFull(),

                FileUpload::make('file_path')
                    ->label('Document File')
                    ->required()
                    ->disk(config('filesystems.default'))
                    ->directory('vendor-documents')
                    ->visibility('private')
                    ->maxSize(20480)
                    ->storeFileNamesIn('file_name')
                    ->columnSpanFull(),

                Select::make('status')
                    ->label('Status')
                    ->options(VendorDocumentStatus::class)
                    ->required()
                    ->native(false)
                    ->default(VendorDocumentStatus::PENDING),

                DatePicker::make('issue_date')
                    ->label('Issue Date')
                    ->native(false),

                DatePicker::make('expiration_date')
                    ->label('Expiration Date')
                    ->native(false),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

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
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(VendorDocumentStatus::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Document'),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn (VendorDocument $record) => Storage::disk(config('filesystems.default'))
                        ->download($record->file_path, $record->file_name)),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (VendorDocument $record) {
                        $record->update([
                            'status' => VendorDocumentStatus::APPROVED,
                            'reviewed_by' => Auth::id(),
                            'reviewed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Document approved')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (VendorDocument $record) => $record->status === VendorDocumentStatus::PENDING),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('review_notes')
                            ->label('Rejection Reason')
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
                    ->visible(fn (VendorDocument $record) => $record->status === VendorDocumentStatus::PENDING),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
