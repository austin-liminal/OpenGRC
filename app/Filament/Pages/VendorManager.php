<?php

namespace App\Filament\Pages;

use App\Filament\Resources\VendorResource;
use App\Filament\Widgets\SurveyInfoWidget;
use App\Filament\Widgets\SurveysTableWidget;
use App\Filament\Widgets\SurveyTemplatesTableWidget;
use App\Filament\Widgets\VendorsTableWidget;
use App\Filament\Widgets\VendorStatsWidget;
use Filament\Actions\Action;

class VendorManager extends TabbedPage
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Vendor Management';

    protected static ?string $title = 'Vendor Management';

    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user || ! method_exists($user, 'can')) {
            return false;
        }

        return $user->can('List Vendors') || $user->can('List Surveys');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addVendor')
                ->label(__('Add Vendor'))
                ->icon('heroicon-o-plus')
                ->url(VendorResource::getUrl('create'))
                ->visible(fn () => auth()->check() && auth()->user()->can('Create Vendors')),
            Action::make('settings')
                ->label(__('Settings'))
                ->icon('heroicon-o-ellipsis-vertical')
                ->url('/admin/vendor-portal-settings')
                ->color('gray')
                ->visible(fn () => auth()->user()?->can('Manage Vendor Management')),
        ];
    }

    public function getTabs(): array
    {
        return [
            'vendors' => [
                'label' => __('Vendors'),
                'icon' => 'heroicon-o-building-storefront',
            ],
            'surveys' => [
                'label' => __('Vendor Surveys'),
                'icon' => 'heroicon-o-paper-airplane',
            ],
            'templates' => [
                'label' => __('Survey Templates'),
                'icon' => 'heroicon-o-clipboard-document-list',
            ],
        ];
    }

    public function getWidgets(): array
    {
        return match ($this->activeTab) {
            'surveys' => [SurveysTableWidget::class],
            'templates' => [SurveyTemplatesTableWidget::class],
            default => [VendorsTableWidget::class],
        };
    }

    public function getStatsWidgets(): array
    {
        return match ($this->activeTab) {
            'surveys', 'templates' => [SurveyInfoWidget::class],
            default => [VendorStatsWidget::class],
        };
    }
}
