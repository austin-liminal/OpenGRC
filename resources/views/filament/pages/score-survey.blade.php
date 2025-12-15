<x-filament-panels::page>
    {{-- Survey Info Infolist --}}
    {{ $this->surveyInfolist }}

    {{-- Auto-Scored Questions Infolist --}}
    {{ $this->autoScoredInfolist }}

    {{-- Manual Scoring Form --}}
    @if($this->hasManualScoringQuestions())
        <x-filament::section>
            <x-slot name="heading">Manual Scoring Required</x-slot>
            <x-slot name="description">
                The following open-ended questions require manual scoring. Review each answer and assign a risk score from 0 (no risk) to 100 (high risk).
            </x-slot>

            <form wire:submit="saveAndCalculate">
                {{ $this->form }}
            </form>
        </x-filament::section>
    @else
        <x-filament::section>
            <x-slot name="heading">Manual Scoring</x-slot>
            {{ $this->form }}
        </x-filament::section>
    @endif

    {{-- Score Breakdown Infolist --}}
    {{ $this->breakdownInfolist }}
</x-filament-panels::page>
