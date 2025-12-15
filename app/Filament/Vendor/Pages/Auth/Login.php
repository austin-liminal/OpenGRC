<?php

namespace App\Filament\Vendor\Pages\Auth;

use App\Models\VendorUser;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction(),
        ];
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label(__('filament-panels::pages/auth/login.form.actions.authenticate.label'))
            ->submit('authenticate');
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        // Update last login
        if ($user instanceof VendorUser) {
            $user->update(['last_login_at' => now()]);
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'email' => $data['email'],
            'password' => $data['password'],
        ];
    }

    public function getMagicLinkAction(): Action
    {
        return Action::make('magicLink')
            ->label('Send Magic Link')
            ->color('gray')
            ->outlined()
            ->form([
                TextInput::make('magic_link_email')
                    ->label('Email')
                    ->email()
                    ->required(),
            ])
            ->action(function (array $data) {
                $this->sendMagicLink($data['magic_link_email']);
            });
    }

    protected function sendMagicLink(string $email): void
    {
        try {
            $this->rateLimit(3, 300); // 3 attempts per 5 minutes
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title('Too many requests')
                ->body('Please wait before requesting another magic link.')
                ->danger()
                ->send();

            return;
        }

        $vendorUser = VendorUser::where('email', $email)->first();

        if (! $vendorUser) {
            // Don't reveal if user exists or not
            Notification::make()
                ->title('Magic Link Sent')
                ->body('If an account exists with this email, a magic link has been sent.')
                ->success()
                ->send();

            return;
        }

        $expiryHours = setting('vendor_portal.magic_link_expiry_hours', 24);

        $url = URL::temporarySignedRoute(
            'vendor.magic-login',
            now()->addHours($expiryHours),
            ['vendorUser' => $vendorUser->id]
        );

        // TODO: Send the magic link email
        // Mail::to($vendorUser->email)->send(new VendorMagicLinkMail($vendorUser, $url));

        Notification::make()
            ->title('Magic Link Sent')
            ->body('If an account exists with this email, a magic link has been sent.')
            ->success()
            ->send();
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }
}
