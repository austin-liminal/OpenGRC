<?php

namespace App\Filament\Resources\VendorResource\RelationManagers;

use App\Enums\VendorDocumentStatus;
use App\Enums\VendorDocumentType;
use App\Models\VendorDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VendorDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    protected static ?string $icon = 'heroicon-o-document-text';

    public function form(Form $form): Form
    {
        return $form
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
                    ->maxLength(1000)
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('file_path')
                    ->label('Document File')
                    ->required()
                    ->disk(config('filesystems.default'))
                    ->directory('vendor-documents')
                    ->visibility('private')
                    ->maxSize(20480)
                    ->storeFileNamesIn('file_name')
                    ->columnSpanFull(),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(VendorDocumentStatus::class)
                    ->required()
                    ->native(false)
                    ->default(VendorDocumentStatus::PENDING),

                Forms\Components\DatePicker::make('issue_date')
                    ->label('Issue Date')
                    ->native(false),

                Forms\Components\DatePicker::make('expiration_date')
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

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(VendorDocumentStatus::class),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Document'),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn (VendorDocument $record) => Storage::disk(config('filesystems.default'))
                        ->download($record->file_path, $record->file_name)),
                Tables\Actions\Action::make('approve')
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
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('review_notes')
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
