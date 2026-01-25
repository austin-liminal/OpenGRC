<?php

namespace App\Filament\Resources\RiskResource\Widgets;

use App\Filament\Resources\RiskResource;
use App\Models\Risk;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class InherentRisk extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.risk-map';

    public array $grid;

    public string $title;

    public string $type = 'inherent';

    public string $filterUrl;

    protected static ?int $sort = 2;

    public function mount(string $title = 'Inherent Risk'): void
    {
        $risks = Risk::select(['id', 'name', 'inherent_likelihood', 'inherent_impact'])
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->get();
        $this->grid = self::generateGrid($risks, 'inherent');
        $this->title = $title;
        $this->type = 'inherent';
        $this->filterUrl = RiskResource::getUrl('index');
    }

    public static function generateGrid(Collection $risks, string $type): array
    {
        $grid = array_fill(0, 5, array_fill(0, 5, []));

        foreach ($risks as $risk) {
            if ($type === 'inherent') {
                $likelihoodIndex = $risk->inherent_likelihood - 1;
                $impactIndex = $risk->inherent_impact - 1;
            } else {
                $likelihoodIndex = $risk->residual_likelihood - 1;
                $impactIndex = $risk->residual_impact - 1;
            }

            if (isset($grid[$impactIndex][$likelihoodIndex])) {
                $grid[$impactIndex][$likelihoodIndex][] = $risk;
            }
        }

        return $grid;
    }
}
