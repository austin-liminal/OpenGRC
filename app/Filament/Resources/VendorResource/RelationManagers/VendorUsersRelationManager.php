<?php

namespace App\Filament\Resources\VendorResource\RelationManagers;

use App\Mail\VendorInvitationMail;
use App\Mail\VendorMagicLinkMail;
use App\Models\VendorUser;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class VendorUsersRelationManager extends RelationManager
{
    protected static string $relationship = 'vendorUsers';

    protected static ?string $title = 'Portal Users';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Toggle::make('is_primary')
                    ->label('Primary Contact')
                    ->helperText('Primary contacts receive all vendor communications'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_primary')
                    ->label('Primary')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (VendorUser $record): string => $record->hasPassword() ? 'Active' : 'Pending')
                    ->color(fn (string $state): string => $state === 'Active' ? 'success' : 'warning'),
                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_primary')
                    ->label('Primary Contact'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Invite User')
                    ->after(function (VendorUser $record) {
                        try {
                            Mail::send(new VendorInvitationMail($record));
                            Notification::make()
                                ->title('Invitation sent to '.$record->email)
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('User created but email failed')
                                ->body($e->getMessage())
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    Action::make('resend_invitation')
                        ->label('Resend Invitation')
                        ->icon('heroicon-o-envelope')
                        ->color('primary')
                        ->visible(fn (VendorUser $record): bool => ! $record->hasPassword())
                        ->requiresConfirmation()
                        ->action(function (VendorUser $record) {
                            try {
                                Mail::send(new VendorInvitationMail($record));
                                Notification::make()
                                    ->title('Invitation sent to '.$record->email)
                                    ->success()
                                    ->send();
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('Failed to send invitation')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Action::make('send_magic_link')
                        ->label('Send Magic Link')
                        ->icon('heroicon-o-link')
                        ->color('info')
                        ->visible(fn (VendorUser $record): bool => $record->hasPassword())
                        ->requiresConfirmation()
                        ->action(function (VendorUser $record) {
                            try {
                                Mail::send(new VendorMagicLinkMail($record));
                                Notification::make()
                                    ->title('Magic link sent to '.$record->email)
                                    ->success()
                                    ->send();
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('Failed to send magic link')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Action::make('make_primary')
                        ->label('Make Primary')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->visible(fn (VendorUser $record): bool => ! $record->is_primary)
                        ->requiresConfirmation()
                        ->action(function (VendorUser $record) {
                            // Remove primary from other users
                            $record->vendor->vendorUsers()->update(['is_primary' => false]);
                            $record->update(['is_primary' => true]);

                            // Update vendor's primary contact
                            $record->vendor->update(['primary_contact_id' => $record->id]);

                            Notification::make()
                                ->title($record->name.' is now the primary contact')
                                ->success()
                                ->send();
                        }),
                    DeleteAction::make()
                        ->label('Revoke Access'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Revoke Access'),
                ]),
            ]);
    }
}
