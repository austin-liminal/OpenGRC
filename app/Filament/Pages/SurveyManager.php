<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\SurveyInfoWidget;
use App\Filament\Widgets\SurveysTableWidget;
use App\Filament\Widgets\SurveyTemplatesTableWidget;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class SurveyManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Surveys';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.survey-manager';

    #[Url]
    public string $activeTab = 'surveys';

    public static function canAccess(): bool
    {
        return auth()->user()->can('List Surveys') || auth()->user()->can('List SurveyTemplates');
    }

    public static function getNavigationLabel(): string
    {
        return __('survey.manager.navigation.label');
    }

    public function getTitle(): string
    {
        return __('survey.manager.title');
    }

    /**
     * Get the info widget for the page.
     */
    public function getInfoWidgets(): array
    {
        return [
            SurveyInfoWidget::class,
        ];
    }

    /**
     * Get the table widget based on active tab.
     */
    public function getTableWidgets(): array
    {
        return match ($this->activeTab) {
            'templates' => [SurveyTemplatesTableWidget::class],
            default => [SurveysTableWidget::class],
        };
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }
}
