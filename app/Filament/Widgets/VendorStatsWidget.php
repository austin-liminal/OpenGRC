<?php

namespace App\Filament\Widgets;

use App\Enums\SurveyStatus;
use App\Enums\VendorRiskRating;
use App\Enums\VendorStatus;
use App\Models\Survey;
use App\Models\Vendor;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VendorStatsWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalVendors = Vendor::count();
        $activeVendors = Vendor::where('status', VendorStatus::ACCEPTED)->count();
        $pendingVendors = Vendor::where('status', VendorStatus::PENDING)->count();

        // Risk rating breakdown
        $highRiskVendors = Vendor::whereIn('risk_rating', [VendorRiskRating::HIGH, VendorRiskRating::CRITICAL])->count();
        $mediumRiskVendors = Vendor::where('risk_rating', VendorRiskRating::MEDIUM)->count();
        $lowRiskVendors = Vendor::whereIn('risk_rating', [VendorRiskRating::LOW, VendorRiskRating::VERY_LOW])->count();

        // Survey stats
        $totalSurveys = Survey::count();
        $pendingSurveys = Survey::whereIn('status', [SurveyStatus::SENT, SurveyStatus::IN_PROGRESS])->count();
        $completedSurveys = Survey::where('status', SurveyStatus::COMPLETED)->count();

        return [
            Stat::make(__('Total Vendors'), $totalVendors)
                ->description(__(':active active, :pending pending', ['active' => $activeVendors, 'pending' => $pendingVendors]))
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('primary'),

            Stat::make(__('High Risk Vendors'), $highRiskVendors)
                ->description(__(':medium medium, :low low risk', ['medium' => $mediumRiskVendors, 'low' => $lowRiskVendors]))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($highRiskVendors > 0 ? 'danger' : 'success'),

            Stat::make(__('Vendor Surveys'), $totalSurveys)
                ->description(__(':pending awaiting response, :completed completed', ['pending' => $pendingSurveys, 'completed' => $completedSurveys]))
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color($pendingSurveys > 0 ? 'warning' : 'success'),
        ];
    }
}
