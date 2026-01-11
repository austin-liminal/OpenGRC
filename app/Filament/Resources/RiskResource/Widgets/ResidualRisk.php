<?php

namespace App\Filament\Resources\RiskResource\Widgets;

use App\Filament\Resources\RiskResource;
use App\Models\Risk;
use Filament\Widgets\Widget;

class ResidualRisk extends Widget
{
    protected static bool $isLazy = false;

    protected static string $view = 'filament.widgets.risk-map';

    public array $grid;

    public string $title;

    public string $type = 'residual';

    public string $filterUrl;

    protected static ?int $sort = 2;

    public function mount(string $title = 'Residual Risk'): void
    {
        $risks = Risk::select(['id', 'name', 'residual_likelihood', 'residual_impact'])->get();
        $this->grid = InherentRisk::generateGrid($risks, 'residual');
        $this->title = $title;
        $this->type = 'residual';
        $this->filterUrl = RiskResource::getUrl('index');
    }
}
