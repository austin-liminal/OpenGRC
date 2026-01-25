<?php

namespace App\Filament\Resources\VendorDocumentResource\Pages;

use App\Enums\VendorDocumentStatus;
use App\Filament\Resources\VendorDocumentResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ViewVendorDocument extends ViewRecord
{
    protected static string $resource = VendorDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->label('Download')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    return Storage::disk(config('filesystems.default'))
                        ->download($this->record->file_path, $this->record->file_name);
                }),
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('review_notes')
                        ->label('Review Notes')
                        ->placeholder('Optional notes about the approval...')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->update([
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
                ->visible(fn () => in_array($this->record->status, [
                    VendorDocumentStatus::PENDING,
                    VendorDocumentStatus::UNDER_REVIEW,
                ])),
            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('review_notes')
                        ->label('Rejection Reason')
                        ->placeholder('Please explain why this document is being rejected...')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->update([
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
                ->visible(fn () => in_array($this->record->status, [
                    VendorDocumentStatus::PENDING,
                    VendorDocumentStatus::UNDER_REVIEW,
                ])),
            EditAction::make(),
        ];
    }
}
