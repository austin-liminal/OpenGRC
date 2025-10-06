<?php

namespace App\Filament\Resources\ProgramResource\Pages;

use App\Filament\Resources\ProgramResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

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
            Actions\EditAction::make(),
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
                        TextEntry::make('description')
                            ->label(__('programs.form.description'))
                            ->columnSpanFull()
                            ->markdown()
                            ->hidden(fn ($record) => empty($record->description)),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\StatsOverview::make(['program' => $this->record]),
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 3;
    }
}
