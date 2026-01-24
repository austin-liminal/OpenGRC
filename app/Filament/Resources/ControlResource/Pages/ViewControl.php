<?php

namespace App\Filament\Resources\ControlResource\Pages;

use App\Filament\Resources\ControlResource;
use App\Http\Controllers\AiController;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewControl extends ViewRecord
{
    protected static string $resource = ControlResource::class;

    public ?string $aiSuggestion = null;

    public function getTitle(): string
    {
        return 'Control Details ('.$this->getRecord()->code.')';
    }

    public function getBreadcrumbs(): array
    {
        return [
            ControlResource::getUrl() => 'Controls',
            $this->getRecord()->code,
            'View',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('Get Suggestions')
                ->label('Get AI Suggestions')
                ->hidden(function () {
                    return setting('ai.enabled') != true;
                })
                ->mountUsing(function () {
                    $this->aiSuggestion = AiController::getControlSuggestions($this->record)->toHtml();
                })
                ->modalDescription(fn () => new \Illuminate\Support\HtmlString($this->aiSuggestion ?? 'Loading...'))
                ->modalSubmitAction(false)
                ->closeModalByEscaping(true),
            Actions\Action::make('Check Implementations')
                ->label('Check Implementations')
                ->hidden(function () {
                    return setting('ai.enabled') != true;
                })
                ->mountUsing(function () {
                    $this->aiSuggestion = AiController::getImplementationCheck($this->record)->toHtml();
                })
                ->modalDescription(fn () => new \Illuminate\Support\HtmlString($this->aiSuggestion ?? 'Loading...'))
                ->modalSubmitAction(false)
                ->closeModalByEscaping(true),
        ];
    }
}
