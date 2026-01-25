<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Filament\Resources\PolicyResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewPolicy extends ViewRecord
{
    protected static string $resource = PolicyResource::class;

    protected string $view = 'filament.resources.policy-resource.pages.view-policy-document';

    public function getTitle(): string|Htmlable
    {
        return $this->getRecord()->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_details')
                ->label('View Details')
                ->url(fn () => route('filament.app.resources.policies.view-details', $this->getRecord()))
                ->icon('heroicon-o-eye'),
        ];
    }
}
