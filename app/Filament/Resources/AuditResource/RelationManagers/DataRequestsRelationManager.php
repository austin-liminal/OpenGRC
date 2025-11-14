<?php

namespace App\Filament\Resources\AuditResource\RelationManagers;

use App\Enums\ResponseStatus;
use App\Enums\WorkflowStatus;
use App\Filament\Resources\DataRequestResource;
use App\Http\Controllers\QueueController;
use App\Models\DataRequest;
use App\Models\User;
use App\Notifications\DropdownNotification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DataRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'DataRequest';

    protected $listeners = ['refreshComponent' => '$refresh'];

    public function getTablePollInterval(): ?string
    {
        // Poll every 5 seconds to update button state
        return '5s';
    }

    public function form(Form $form): Form
    {
        return DataRequestResource::getEditForm($form);
    }

    /**
     * @throws \Exception
     */
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->toggleable()
                    ->toggledHiddenByDefault()
                    ->label('ID'),
                TextColumn::make('auditItems')
                    ->label('Audit Item(s)')
                    ->wrap()
                    ->state(function (DataRequest $record) {
                        // Try the many-to-many relationship first (for new data requests)
                        $codes = $record->auditItems->pluck('auditable.code')->filter()->all();

                        // Fallback to single relationship for backwards compatibility
                        if (empty($codes) && $record->auditItem?->auditable) {
                            $codes = [$record->auditItem->auditable->code];
                        }

                        return ! empty($codes) ? implode(', ', $codes) : '-';
                    })
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('auditItems.auditable', function ($q) use ($search) {
                            $q->where('code', 'like', "%{$search}%");
                        })->orWhereHas('auditItem.auditable', function ($q) use ($search) {
                            $q->where('code', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('code')
                    ->searchable()
                    ->toggleable()
                    ->label('Request Code'),
                TextColumn::make('details')
                    ->label('Request Details')
                    ->searchable()
                    ->html()
                    ->wrap(),
                TextColumn::make('responses.status')
                    ->label('Responses')
                    ->badge(),
                TextColumn::make('assignedTo')
                    ->label('Assigned To')
                    ->state(function (DataRequest $record) {
                        return $record->responses->first()?->requestee->name;
                    }),
                TextColumn::make('responses')
                    ->label('Due Date')
                    ->date()
                    ->state(function (DataRequest $record) {
                        return $record->responses->sortByDesc('due_at')->first()?->due_at;
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(ResponseStatus::class)
                    ->label('Status')
                    ->query(function ($query, $state) {
                        if ($state['value'] ?? null) {
                            return $query->whereHas('responses', function ($query) use ($state) {
                                $query->where('status', $state['value']);
                            });
                        }
                    }),
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->options(function () {
                        return User::whereHas('todos')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->label('Assigned To')
                    ->query(function ($query, $state) {
                        if ($state['value'] ?? null) {
                            return $query->whereHas('responses', function ($query) use ($state) {
                                $query->where('requestee_id', $state['value']);
                            });
                        }
                    }),
                Tables\Filters\SelectFilter::make('code')
                    ->options(DataRequest::whereNotNull('code')->pluck('code', 'code')->toArray())
                    ->label('Request Code'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create Data Request')
                    ->modalHeading('Create New Data Request')
                    ->disabled(function () {
                        return $this->getOwnerRecord()->status != WorkflowStatus::INPROGRESS;
                    })
                    ->form([
                        Select::make('audit_items')
                            ->label('Audit Item(s)')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                $audit = $this->getOwnerRecord();

                                return $audit->auditItems()
                                    ->with('auditable')
                                    ->get()
                                    ->mapWithKeys(function ($item) {
                                        $label = $item->auditable
                                            ? $item->auditable->code.' - '.$item->auditable->title
                                            : 'Item #'.$item->id;

                                        return [$item->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->required()
                            ->helperText('Select one or more audit items for this data request'),
                        TextInput::make('code')
                            ->label('Request Code')
                            ->maxLength(255)
                            ->helperText('Optional. If left blank, will default to Request-{id} after creation.')
                            ->nullable(),
                        RichEditor::make('details')
                            ->label('Request Details')
                            ->disableToolbarButtons([
                                'image',
                                'attachFiles',
                            ])
                            ->required()
                            ->columnSpanFull()
                            ->helperText('Describe what information or evidence is being requested'),
                        Select::make('assigned_to_id')
                            ->label('Assign To')
                            ->options(User::whereNotNull('name')->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->required()
                            ->helperText('User responsible for responding to this request'),
                        DatePicker::make('due_at')
                            ->label('Due Date')
                            ->required()
                            ->helperText('When should this request be completed?'),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by_id'] = auth()->id();
                        $data['audit_id'] = $this->getOwnerRecord()->id;

                        return $data;
                    })
                    ->using(function (array $data, string $model): DataRequest {
                        // Extract audit items and due date before creating the data request
                        $auditItems = $data['audit_items'];
                        $dueAt = $data['due_at'];
                        unset($data['audit_items'], $data['due_at']);

                        // Create the data request
                        $dataRequest = $model::create($data);

                        // Set the code if not provided
                        if (empty($dataRequest->code)) {
                            $dataRequest->code = 'Request-'.$dataRequest->id;
                            $dataRequest->save();
                        }

                        // Attach the audit items
                        $dataRequest->auditItems()->attach($auditItems);

                        // Create the response
                        DataRequestResource::createResponses($dataRequest, $dueAt);

                        return $dataRequest;
                    })
                    ->successNotificationTitle('Data Request Created')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Data Request Created')
                            ->body('The data request has been created and assigned successfully.')
                    ),
                Tables\Actions\Action::make('import_irl')
                    ->label('Import IRL')
                    ->color('primary')
                    ->disabled(function () {
                        return $this->getOwnerRecord()->manager_id != auth()->id();
                    })
                    ->hidden(function () {
                        return $this->getOwnerRecord()->manager_id != auth()->id();
                    })
                    ->action(function () {
                        $audit = $this->getOwnerRecord();

                        return redirect()->route('filament.app.resources.audits.import-irl', $audit);
                    }),
                Tables\Actions\Action::make('ExportAuditEvidence')
                    ->label(function () {
                        $audit = $this->getOwnerRecord();
                        $isExporting = \Cache::has("audit_{$audit->id}_exporting");

                        return $isExporting ? 'Export In Progress...' : 'Export All Evidence';
                    })
                    ->icon(function () {
                        $audit = $this->getOwnerRecord();
                        $isExporting = \Cache::has("audit_{$audit->id}_exporting");

                        return $isExporting ? 'heroicon-m-arrow-path' : 'heroicon-m-arrow-down-tray';
                    })
                    ->disabled(function () {
                        $audit = $this->getOwnerRecord();

                        return \Cache::has("audit_{$audit->id}_exporting");
                    })
                    ->color(function () {
                        $audit = $this->getOwnerRecord();
                        $isExporting = \Cache::has("audit_{$audit->id}_exporting");

                        return $isExporting ? 'warning' : 'primary';
                    })
                    ->extraAttributes(function () {
                        $audit = $this->getOwnerRecord();
                        $isExporting = \Cache::has("audit_{$audit->id}_exporting");

                        return $isExporting ? ['class' => 'animate-pulse'] : [];
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Export All Evidence')
                    ->modalDescription('This will generate a PDF for each audit item and zip them for download. You will be notified when the export is ready.')
                    ->action(function ($livewire) {
                        $audit = $this->getOwnerRecord();

                        // Check cache before dispatching
                        if (\Cache::has("audit_{$audit->id}_exporting")) {
                            return Notification::make()
                                ->title('Export Already In Progress')
                                ->body('An export is already running for this audit. Please wait for it to complete.')
                                ->warning()
                                ->send();
                        }

                        \App\Jobs\ExportAuditEvidenceJob::dispatch($audit->id, auth()->id());

                        // Ensure queue worker is running
                        $queueController = new QueueController;
                        $wasAlreadyRunning = $queueController->ensureQueueWorkerRunning();

                        $body = $wasAlreadyRunning
                            ? 'The export job has been added to the queue. You will be able to download the ZIP in the Attachments section.'
                            : 'The export job has been queued and a queue worker has been started. You will be able to download the ZIP in the Attachments section.';

                        Notification::make()
                            ->title('Export Started')
                            ->body($body)
                            ->success()
                            ->send();
                        auth()->user()->notify(new DropdownNotification(
                            title: 'Evidence Export Started',
                            body: 'Your evidence export is in-progress. This will take several minutes depending on the amount of evidence you collected.',
                            icon: 'heroicon-o-bell',
                            color: 'success'
                        ));

                        // Use JavaScript to refresh the page
                        $this->js('window.location.reload()');
                    }),
            ])

            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('View Data Request')
                    ->modalFooterActions(fn ($record) => DataRequestResource::getModalFooterActions($record))
                    ->disabled(function () {
                        return $this->getOwnerRecord()->status != WorkflowStatus::INPROGRESS;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->disabled(function () {
                        return $this->getOwnerRecord()->status != WorkflowStatus::INPROGRESS;
                    })
                    ->visible(function () {
                        return $this->getOwnerRecord()->status == WorkflowStatus::INPROGRESS;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulk_assign_requestee')
                        ->label('Bulk Assign Requestee')
                        ->icon('heroicon-o-user-plus')
                        ->color('primary')
                        ->form([
                            Select::make('requestee_id')
                                ->label('Assign to User')
                                ->options(User::pluck('name', 'id')->toArray())
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (array $data, \Illuminate\Database\Eloquent\Collection $records) {
                            $requesteeId = $data['requestee_id'];
                            $updatedCount = 0;

                            foreach ($records as $dataRequest) {
                                $response = $dataRequest->responses->first();
                                if ($response) {
                                    $response->update(['requestee_id' => $requesteeId]);
                                    $updatedCount++;
                                }
                            }

                            Notification::make()
                                ->title('Bulk Assignment Complete')
                                ->body("Successfully assigned {$updatedCount} data request responses.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Bulk Assign Requestee')
                        ->modalDescription('This will assign the selected user as the requestee for the first response of each selected data request.')
                        ->disabled(function () {
                            return $this->getOwnerRecord()->status != WorkflowStatus::INPROGRESS;
                        }),
                    Tables\Actions\BulkAction::make('bulk_update_status')
                        ->label('Bulk Update Status')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->form([
                            Select::make('status')
                                ->label('Status')
                                ->options(ResponseStatus::class)
                                ->required(),
                        ])
                        ->action(function (array $data, \Illuminate\Database\Eloquent\Collection $records) {
                            $status = $data['status'];
                            $updatedCount = 0;

                            foreach ($records as $dataRequest) {
                                $response = $dataRequest->responses->first();
                                if ($response) {
                                    $response->update(['status' => $status]);
                                    $updatedCount++;
                                }
                            }

                            Notification::make()
                                ->title('Bulk Status Update Complete')
                                ->body("Successfully updated status for {$updatedCount} data request responses.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Bulk Update Status')
                        ->modalDescription('This will update the status for the first response of each selected data request.')
                        ->disabled(function () {
                            return $this->getOwnerRecord()->status != WorkflowStatus::INPROGRESS;
                        }),
                ]),
            ]);
    }
}
