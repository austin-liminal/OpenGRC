<?php

namespace App\Filament\Resources\AuditResource\RelationManagers;

use App\Enums\WorkflowStatus;
use App\Filament\Resources\DataRequestResource;
use App\Http\Controllers\QueueController;
use App\Models\DataRequest;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Enums\ResponseStatus;

class DataRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'DataRequest';

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
                TextColumn::make('auditItem.auditable.code')
                    ->searchable()
                    ->label('Audit Item'),
                TextColumn::make('code')
                    ->searchable()
                    ->toggleable()
                    ->label('Request Code'),
                TextColumn::make('details')
                    ->label('Request Details')
                    ->searchable()
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
                    ->disabled(function () {
                        return $this->getOwnerRecord()->status != WorkflowStatus::INPROGRESS;
                    })
                    ->hidden()
                    ->after(function (DataRequest $record, Tables\Actions\Action $action) {
                        DataRequestResource::createResponses($record);
                    }),
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
                    ->label('Export All Evidence')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->requiresConfirmation()
                    ->modalHeading('Export All Evidence')
                    ->modalDescription('This will generate a PDF for each audit item and zip them for download. You will be notified when the export is ready.')
                    ->action(function ($livewire) {
                        $audit = $this->getOwnerRecord();
                        \App\Jobs\ExportAuditEvidenceJob::dispatch($audit->id);

                        // Ensure queue worker is running
                        $queueController = new QueueController;
                        $wasAlreadyRunning = $queueController->ensureQueueWorkerRunning();

                        $body = $wasAlreadyRunning
                            ? 'The export job has been added to the queue. You will be able to download the ZIP in the Attachments section.'
                            : 'The export job has been queued and a queue worker has been started. You will be able to download the ZIP in the Attachments section.';

                        return Notification::make()
                            ->title('Export Started')
                            ->body($body)
                            ->success()
                            ->send();
                    }),
            ])

            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('View Data Request')
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
