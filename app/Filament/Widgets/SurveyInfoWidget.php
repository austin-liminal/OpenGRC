<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class SurveyInfoWidget extends Widget
{
    protected static string $view = 'filament.widgets.survey-info-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;
}
