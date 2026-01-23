<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class TableDescriptionWidget extends Widget
{
    protected static string $view = 'filament.widgets.table-description-widget';

    protected int|string|array $columnSpan = 'full';

    public string $description = '';
}
