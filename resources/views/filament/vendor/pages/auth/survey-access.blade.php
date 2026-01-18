<x-filament-panels::page.simple>
    @if($mode === 'login')
        <x-filament-panels::form wire:submit="login">
            {{ $this->loginForm }}

            <x-filament::button type="submit" class="w-full">
                Sign In
            </x-filament::button>
        </x-filament-panels::form>

        <div class="mt-4 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Don't have an account?
                <button type="button" wire:click="switchToRegister" class="text-primary-600 hover:text-primary-500 font-medium">
                    Create one
                </button>
            </p>
        </div>
    @elseif($mode === 'register')
        <x-filament-panels::form wire:submit="register">
            {{ $this->registerForm }}

            <x-filament::button type="submit" class="w-full">
                Create Account
            </x-filament::button>
        </x-filament-panels::form>

        <div class="mt-4 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Already have an account?
                <button type="button" wire:click="switchToLogin" class="text-primary-600 hover:text-primary-500 font-medium">
                    Sign in
                </button>
            </p>
        </div>
    @elseif($mode === 'set-password')
        <x-filament-panels::form wire:submit="setPassword">
            {{ $this->setPasswordForm }}

            <x-filament::button type="submit" class="w-full">
                Set Password & Continue
            </x-filament::button>
        </x-filament-panels::form>
    @endif
</x-filament-panels::page.simple>
