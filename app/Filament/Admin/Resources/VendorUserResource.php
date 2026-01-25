<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\VendorUserResource\Pages\CreateVendorUser;
use App\Filament\Admin\Resources\VendorUserResource\Pages\EditVendorUser;
use App\Filament\Admin\Resources\VendorUserResource\Pages\ListVendorUsers;
use App\Filament\Admin\Resources\VendorUserResource\Pages\ViewVendorUser;
use App\Mail\VendorInvitationMail;
use App\Mail\VendorMagicLinkMail;
use App\Models\VendorUser;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class VendorUserResource extends Resource
{
    protected static ?string $model = VendorUser::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Vendor Users';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 11;

    protected static ?string $modelLabel = 'Vendor User';

    protected static ?string $pluralModelLabel = 'Vendor Users';

    public static function getNavigationGroup(): string
    {
        return __('navigation.groups.system');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('vendor_id')
                    ->label('Vendor')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
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
                Placeholder::make('status')
                    ->label('Account Status')
                    ->content(fn (?VendorUser $record): string => $record?->hasPassword() ? 'Active' : 'Pending Activation')
                    ->visibleOn(['view', 'edit']),
                Placeholder::make('last_login_at')
                    ->label('Last Login')
                    ->content(fn (?VendorUser $record): string => $record?->last_login_at?->format('Y-m-d H:i:s') ?? 'Never')
                    ->visibleOn(['view', 'edit']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_primary')
                    ->label('Primary')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('has_password')
                    ->label('Activated')
                    ->state(fn (VendorUser $record): bool => $record->hasPassword())
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),
                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('vendor')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_primary')
                    ->label('Primary Contact'),
                TernaryFilter::make('activated')
                    ->label('Activated')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('password'),
                        false: fn (Builder $query) => $query->whereNull('password'),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ActionGroup::make([
                    Action::make('resend_invitation')
                        ->label('Resend Invitation')
                        ->icon('heroicon-o-envelope')
                        ->color('primary')
                        ->visible(fn (VendorUser $record): bool => ! $record->hasPassword())
                        ->requiresConfirmation()
                        ->action(fn (VendorUser $record) => static::resendInvitation($record)),
                    Action::make('send_magic_link')
                        ->label('Send Magic Link')
                        ->icon('heroicon-o-link')
                        ->color('info')
                        ->visible(fn (VendorUser $record): bool => $record->hasPassword())
                        ->requiresConfirmation()
                        ->action(fn (VendorUser $record) => static::sendMagicLink($record)),
                    Action::make('reset_password')
                        ->label('Send Password Reset')
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        ->visible(fn (VendorUser $record): bool => $record->hasPassword())
                        ->requiresConfirmation()
                        ->action(fn (VendorUser $record) => static::sendPasswordReset($record)),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    BulkAction::make('resend_invitations')
                        ->label('Resend Invitations')
                        ->icon('heroicon-o-envelope')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if (! $record->hasPassword()) {
                                    static::resendInvitation($record, false);
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->title("Sent {$count} invitation(s)")
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendorUsers::route('/'),
            'create' => CreateVendorUser::route('/create'),
            'view' => ViewVendorUser::route('/{record}'),
            'edit' => EditVendorUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function resendInvitation(VendorUser $record, bool $notify = true): void
    {
        try {
            Mail::send(new VendorInvitationMail($record));

            if ($notify) {
                Notification::make()
                    ->title('Invitation sent to '.$record->email)
                    ->success()
                    ->send();
            }
        } catch (Exception $e) {
            if ($notify) {
                Notification::make()
                    ->title('Failed to send invitation')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        }
    }

    public static function sendMagicLink(VendorUser $record): void
    {
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
    }

    public static function sendPasswordReset(VendorUser $record): void
    {
        try {
            $status = Password::broker('vendor_users')->sendResetLink(
                ['email' => $record->email]
            );

            if ($status === Password::RESET_LINK_SENT) {
                Notification::make()
                    ->title('Password reset sent to '.$record->email)
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Failed to send password reset')
                    ->body(__($status))
                    ->danger()
                    ->send();
            }
        } catch (Exception $e) {
            Notification::make()
                ->title('Failed to send password reset')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
