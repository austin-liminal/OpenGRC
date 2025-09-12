<?php

namespace App\Filament\Resources\AuditResource\RelationManagers;

use App\Enums\Applicability;
use App\Enums\Effectiveness;
use App\Enums\WorkflowStatus;
use App\Models\AuditItem;
use App\Models\Control;
use App\Models\Implementation;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

class AuditItemRelationManager extends RelationManager
{
    protected static string $relationship = 'AuditItems';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Control Information')
                    ->schema([
                        Placeholder::make('control_code')
                            ->label('Control Code')
                            ->content(fn (AuditItem $record): string => $record->control->code),
                        Placeholder::make('control_title')
                            ->label('Control Title')
                            ->content(fn (AuditItem $record): string => $record->control->title),
                        Placeholder::make('control_desc')
                            ->label('Control Description')
                            ->content(fn (AuditItem $record): HtmlString => new HtmlString(optional($record->control)->description ?? ''))
                            ->columnSpanFull(),
                        Placeholder::make('control_discussion')
                            ->label('Control Discussion')
                            ->content(fn (AuditItem $record): HtmlString => new HtmlString(optional($record->control)->discussion ?? ''))
                            ->columnSpanFull(),

                    ])->columns(2)->collapsible(true),

                Forms\Components\Section::make('Evaluation')
                    ->schema([
                        ToggleButtons::make('status')
                            ->label('Status')
                            ->options(WorkflowStatus::class)
                            ->default('Not Started')
                            ->grouped(),
                        ToggleButtons::make('effectiveness')
                            ->label('Effectiveness')
                            ->options(Effectiveness::class)
                            ->default('Not Effective')
                            ->grouped(),
                        ToggleButtons::make('applicability')
                            ->label('Applicability')
                            ->options(Applicability::class)
                            ->default('Applicable')
                            ->grouped(),
                        RichEditor::make('auditor_notes')
                            ->columnSpanFull()
                            ->disableToolbarButtons([
                                'image',
                                'attachFiles',
                            ])
                            ->label('Auditor Notes'),
                    ]),

                Forms\Components\Section::make('Audit Evidence')
                    ->schema([

                        // Todo: This can be replaced with a Repeater component when nested relationships are
                        // supported in Filament - potentially in v4.x
                        Placeholder::make('control.implementations')
                            ->label('Documented Implementations')
                            ->content(fn (AuditItem $record): HtmlString => new HtmlString($this->implementationsTable($record)))
                            ->columnSpanFull()
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Implementations that a related to this control.'),

                        Placeholder::make('data_requests')
                            ->label('Data Requests Issued')
                            ->content(fn (AuditItem $record): HtmlString => new HtmlString($this->dataRequestsTable($record)))
                            ->columnSpanFull()
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Data Requests that have been issued.'),

                    ])->collapsible(true),
            ]);
    }

    protected function implementationsTable(AuditItem $record): HtmlString
    {
        // Assuming $record->dataRequests returns an array or collection of data
        $dataRequests = $record->control->implementations;

        // Start building the table HTML
        $html = '<table class="table-auto w-full border-collapse border border-gray-200">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th class="border px-4 py-2">Code</th>';
        $html .= '<th class="border px-4 py-2">Title</th>';
        $html .= '<th class="border px-4 py-2">Details</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        // Loop through dataRequests and generate table rows
        foreach ($dataRequests as $request) {
            $html .= '<tr>';
            $html .= '<td class="border px-4 py-2">'.e($request->code).'</td>';
            $html .= '<td class="border px-4 py-2">'.'<a target="_blank"   href='.
                route('filament.app.resources.implementations.view', $request->id).
                '>'.e($request->title).'</a></td>';
            $html .= '<td class="border px-4 py-2">'.$request->details.'</td>';
            $html .= '</tr>';
        }

        // Close the table
        $html .= '</tbody>';
        $html .= '</table>';

        // Return the generated HTML as an HtmlString
        return new HtmlString($html);
    }

    protected function dataRequestsTable(AuditItem $record): HtmlString
    {
        // Assuming $record->dataRequests returns an array or collection of data
        $dataRequests = $record->dataRequests;

        // Start building the table HTML
        $html = '<table class="table-auto w-full border-collapse border border-gray-200">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th class="border px-4 py-2">Request</th>';
        $html .= '<th class="border px-4 py-2">Response</th>';
        $html .= '<th class="border px-4 py-2">Status</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        // Loop through dataRequests and generate table rows
        foreach ($dataRequests as $request) {
            $html .= '<tr>';
            $html .= '<td class="border px-4 py-2">'.e($request->details).'</td>';
            $html .= '<td class="border px-4 py-2">';
            foreach ($request->responses as $r) {
                $html .= '<div>'.$r->response.'</div>';
                if (isset($r->attachments)) {
                    foreach ($r->attachments as $attachment) {
                        $html .= '***<a href="#">'.$attachment->description.'</a>';
                    }
                }
            }

            $html .= '</td>';
            $html .= '<td class="border px-4 py-2">'.$request->status.'</td>';
            $html .= '</tr>';
        }

        // Close the table
        $html .= '</tbody>';
        $html .= '</table>';

        // Return the generated HTML as an HtmlString
        return new HtmlString($html);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('auditable.type')
                    ->getStateUsing(function ($record) {
                        return class_basename($record->auditable);
                    }),
                Tables\Columns\TextColumn::make('code')
                    ->getStateUsing(function ($record) {
                        return class_basename($record->auditable->code);
                    })
                    ->label('Code')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHasMorph('auditable', ['App\Models\Control', 'App\Models\Implementation'], function (Builder $query, string $type) use ($search) {
                            $query->where('code', 'like', "%{$search}%");
                        });
                    }),
                Tables\Columns\TextColumn::make('title')
                    ->wrap()
                    ->getStateUsing(function ($record) {
                        return class_basename($record->auditable->title);
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHasMorph('auditable', ['App\Models\Control', 'App\Models\Implementation'], function (Builder $query, string $type) use ($search) {
                            $query->where('title', 'like', "%{$search}%");
                        });
                    }),
                Tables\Columns\TextColumn::make('status')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('applicability')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('effectiveness')->sortable()->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Assess control')
                    ->visible(fn (AuditItem $record): bool => $record->audit->status === WorkflowStatus::INPROGRESS)
                    ->url(fn (AuditItem $record): string => route('filament.app.resources.audit-items.edit', ['record' => $record->id])),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add More Items')
                    ->modalHeading('Associate Existing Control or Implementation')
                    ->modalSubmitActionLabel('Associate')
                    ->createAnother(false)
                    ->form([
                        Select::make('auditable_type')
                            ->label('Type')
                            ->options([
                                'control' => 'Control',
                                'implementation' => 'Implementation',
                            ])
                            ->required()
                            ->live()
                            ->default('control'),
                        Select::make('auditable_id')
                            ->label('Control/Implementation')
                            ->options(function (Forms\Get $get, RelationManager $livewire) {
                                $type = $get('auditable_type');
                                $audit = $livewire->ownerRecord;

                                // Get already associated auditable IDs for this type
                                $existingIds = $audit->auditItems()
                                    ->where('auditable_type', $type === 'control' ? Control::class : Implementation::class)
                                    ->pluck('auditable_id')
                                    ->toArray();

                                if ($type === 'control') {
                                    return Control::whereNotIn('id', $existingIds)
                                        ->get()
                                        ->mapWithKeys(function ($control) {
                                            return [$control->id => $control->code.' - '.$control->title];
                                        });
                                } elseif ($type === 'implementation') {
                                    return Implementation::whereNotIn('id', $existingIds)
                                        ->get()
                                        ->mapWithKeys(function ($implementation) {
                                            return [$implementation->id => $implementation->code.' - '.$implementation->title];
                                        });
                                }

                                return [];
                            })
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data, RelationManager $livewire) {
                        $audit = $livewire->ownerRecord;

                        $auditItem = new AuditItem([
                            'status' => WorkflowStatus::NOTSTARTED,
                            'applicability' => Applicability::APPLICABLE,
                            'effectiveness' => Effectiveness::UNKNOWN,
                            'audit_id' => $audit->id,
                            'user_id' => $audit->manager_id,
                        ]);

                        if ($data['auditable_type'] === 'control') {
                            $auditItem->auditable()->associate(Control::find($data['auditable_id']));
                        } elseif ($data['auditable_type'] === 'implementation') {
                            $auditItem->auditable()->associate(Implementation::find($data['auditable_id']));
                        }

                        $auditItem->save();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_edit_status')
                        ->label('Update Status')
                        ->icon('heroicon-o-pencil-square')
                        ->form([
                            ToggleButtons::make('status')
                                ->label('Status')
                                ->options(WorkflowStatus::class)
                                ->required()
                                ->grouped(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(function (AuditItem $record) use ($data) {
                                $record->update(['status' => $data['status']]);
                            });
                        }),
                    Tables\Actions\BulkAction::make('bulk_edit_applicability')
                        ->label('Update Applicability')
                        ->icon('heroicon-o-check-circle')
                        ->form([
                            ToggleButtons::make('applicability')
                                ->label('Applicability')
                                ->options(Applicability::class)
                                ->required()
                                ->grouped(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(function (AuditItem $record) use ($data) {
                                $record->update(['applicability' => $data['applicability']]);
                            });
                        }),
                    Tables\Actions\BulkAction::make('bulk_edit_effectiveness')
                        ->label('Update Effectiveness')
                        ->icon('heroicon-o-star')
                        ->form([
                            ToggleButtons::make('effectiveness')
                                ->label('Effectiveness')
                                ->options(Effectiveness::class)
                                ->required()
                                ->grouped(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(function (AuditItem $record) use ($data) {
                                $record->update(['effectiveness' => $data['effectiveness']]);
                            });
                        }),
                ]),
            ]);

    }
}
