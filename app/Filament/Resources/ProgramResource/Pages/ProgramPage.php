<?php

namespace App\Filament\Resources\ProgramResource\Pages;

use App\Filament\Resources\ProgramResource;
use App\Filament\Widgets\StatsOverview;
use App\Models\Control;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;

class ProgramPage extends ViewRecord
{
    protected static string $resource = ProgramResource::class;

    protected string $view = 'filament.resources.program-resource.pages.program-page';

    public function getTitle(): string
    {
        return $this->record->name;
    }

    public function getBreadcrumbs(): array
    {
        return [
            ProgramResource::getUrl() => 'Programs',
            $this->record->name,
            'View',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit')
                ->icon('heroicon-m-pencil')
                ->size(Size::Small)
                ->color('primary')
                ->button(),
            ActionGroup::make([
                Action::make('download_ssp')
                    ->label('Download SSP')
                    ->size(Size::Small)
                    ->color('primary')
                    ->action(function () {
                        $program = $this->record;
                        $program->load(['programManager', 'standards']);

                        // Get all controls for the program
                        $controls = $program->getAllControls();

                        // Get control IDs and eager load relationships
                        $controlIds = $controls->pluck('id')->toArray();
                        $controls = Control::whereIn('id', $controlIds)
                            ->with(['implementations', 'standard'])
                            ->get();

                        $pdf = Pdf::loadView('reports.ssp', [
                            'program' => $program,
                            'controls' => $controls,
                        ]);

                        return response()->streamDownload(
                            function () use ($pdf) {
                                echo $pdf->output();
                            },
                            "SSP-{$program->name}-".date('Y-m-d').'.pdf',
                            ['Content-Type' => 'application/pdf']
                        );
                    }),
            ])
                ->label('Reports')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size(Size::Small)
                ->color('primary')
                ->button(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Program Details'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('programs.form.name')),
                        TextEntry::make('programManager.name')
                            ->label(__('programs.form.program_manager'))
                            ->default('Not assigned'),
                        TextEntry::make('scope_status')
                            ->label(__('programs.form.scope_status'))
                            ->badge(),
                        TextEntry::make('last_audit_date')
                            ->label(__('programs.table.last_audit_date'))
                            ->date('M d, Y')
                            ->placeholder('No audits yet'),
                        TextEntry::make('department')
                            ->label('Department')
                            ->formatStateUsing(function ($record) {
                                return ProgramResource::getTaxonomyTerm($record, 'department')?->name ?? 'Not assigned';
                            }),
                        TextEntry::make('scope')
                            ->label('Scope')
                            ->formatStateUsing(function ($record) {
                                return ProgramResource::getTaxonomyTerm($record, 'scope')?->name ?? 'Not assigned';
                            }),
                    ])
                    ->columns(2),
                Section::make(__('programs.form.description'))
                    ->schema([
                        TextEntry::make('description')
                            ->label('')
                            ->markdown()
                            ->placeholder('No description provided'),
                    ])
                    ->collapsible()
                    ->hidden(fn ($record) => empty($record->description)),
            ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverview::make(['program' => $this->record]),
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }
}
