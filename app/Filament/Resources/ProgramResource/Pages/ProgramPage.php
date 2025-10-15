<?php

namespace App\Filament\Resources\ProgramResource\Pages;

use App\Filament\Resources\ProgramResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\ActionSize;

class ProgramPage extends ViewRecord
{
    protected static string $resource = ProgramResource::class;

    protected static string $view = 'filament.resources.program-resource.pages.program-page';

    public function getTitle(): string
    {
        return $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit')
                ->icon('heroicon-m-pencil')
                ->size(ActionSize::Small)
                ->color('primary')
                ->button(),
            ActionGroup::make([
                Action::make('download_ssp')
                    ->label('Download SSP')
                    ->size(ActionSize::Small)
                    ->color('primary')
                    ->action(function () {
                        $program = $this->record;
                        $program->load(['programManager', 'standards']);

                        // Get all controls for the program
                        $controls = $program->getAllControls();

                        // Get control IDs and eager load relationships
                        $controlIds = $controls->pluck('id')->toArray();
                        $controls = \App\Models\Control::whereIn('id', $controlIds)
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
                ->size(ActionSize::Small)
                ->color('primary')
                ->button(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
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
                                $department = $record->taxonomies()
                                    ->whereHas('parent', function ($query) {
                                        $query->where('name', 'Department');
                                    })
                                    ->first();

                                return $department?->name ?? 'Not assigned';
                            }),
                        TextEntry::make('scope')
                            ->label('Scope')
                            ->formatStateUsing(function ($record) {
                                $scope = $record->taxonomies()
                                    ->whereHas('parent', function ($query) {
                                        $query->where('name', 'Scope');
                                    })
                                    ->first();

                                return $scope?->name ?? 'Not assigned';
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
                    ->collapsed()
                    ->hidden(fn ($record) => empty($record->description)),
            ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\StatsOverview::make(['program' => $this->record]),
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }
}
