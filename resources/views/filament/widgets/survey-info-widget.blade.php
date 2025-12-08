<x-filament-widgets::widget>
    <x-filament::section
        icon="heroicon-o-information-circle"
        icon-color="info"
        collapsible
        persist-collapsed
        id="survey-manager-info"
    >
        <x-slot name="heading">
            {{ __('survey.manager.info.heading') }}
        </x-slot>

        <div class="prose prose-sm max-w-none dark:prose-invert">
            <ol class="list-decimal list-inside space-y-2">
                <li><strong>{{ __('survey.manager.info.step1_title') }}</strong> - {{ __('survey.manager.info.step1_desc') }}</li>
                <li><strong>{{ __('survey.manager.info.step2_title') }}</strong> - {{ __('survey.manager.info.step2_desc') }}</li>
                <li><strong>{{ __('survey.manager.info.step3_title') }}</strong> - {{ __('survey.manager.info.step3_desc') }}</li>
                <li><strong>{{ __('survey.manager.info.step4_title') }}</strong> - {{ __('survey.manager.info.step4_desc') }}</li>
            </ol>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
