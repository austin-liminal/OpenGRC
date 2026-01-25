<x-filament-panels::page.simple>
    @if($mode === 'login')
        <form wire:submit="login" class="fi-form grid gap-y-6">
            {{ $this->loginForm }}

            <x-filament::button type="submit" class="w-full">
                Sign In
            </x-filament::button>
        </form>

        <div class="mt-4 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Don't have an account?
                <button type="button" wire:click="switchToRegister" class="text-primary-600 hover:text-primary-500 font-medium">
                    Create one
                </button>
            </p>
        </div>
    @elseif($mode === 'register')
        <form wire:submit="register" class="fi-form grid gap-y-6">
            {{ $this->registerForm }}

            <x-filament::button type="submit" class="w-full">
                Create Account
            </x-filament::button>
        </form>

        <div class="mt-4 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Already have an account?
                <button type="button" wire:click="switchToLogin" class="text-primary-600 hover:text-primary-500 font-medium">
                    Sign in
                </button>
            </p>
        </div>
    @elseif($mode === 'set-password')
        <form wire:submit="setPassword" class="fi-form grid gap-y-6">
            {{ $this->setPasswordForm }}

            <x-filament::button type="submit" class="w-full">
                Set Password & Continue
            </x-filament::button>
        </form>
    @endif
</x-filament-panels::page.simple>
