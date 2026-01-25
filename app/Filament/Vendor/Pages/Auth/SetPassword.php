<?php

namespace App\Filament\Vendor\Pages\Auth;

use App\Models\VendorUser;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SetPassword extends SimplePage
{
    protected string $view = 'filament-panels::pages.auth.password-reset.reset-password';

    public ?array $data = [];

    public function mount(): void
    {
        $user = Filament::auth()->user();

        if (! $user instanceof VendorUser || $user->hasPassword()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required()
                    ->rule(Password::default())
                    ->same('passwordConfirmation')
                    ->validationAttribute('password'),
                TextInput::make('passwordConfirmation')
                    ->label('Confirm Password')
                    ->password()
                    ->required()
                    ->dehydrated(false),
            ])
            ->statePath('data');
    }

    public function getFormActions(): array
    {
        return [
            Action::make('setPassword')
                ->label('Set Password')
                ->submit('setPassword'),
        ];
    }

    public function setPassword(): void
    {
        $data = $this->form->getState();

        $user = Filament::auth()->user();

        if ($user instanceof VendorUser) {
            $user->update([
                'password' => Hash::make($data['password']),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);

            Notification::make()
                ->title('Password Set')
                ->body('Your password has been set successfully.')
                ->success()
                ->send();
        }

        redirect()->intended(Filament::getUrl());
    }

    public function getTitle(): string
    {
        return 'Set Your Password';
    }

    public function getHeading(): string
    {
        return 'Set Your Password';
    }

    public function getSubheading(): ?string
    {
        return 'Please create a password to secure your account.';
    }
}
