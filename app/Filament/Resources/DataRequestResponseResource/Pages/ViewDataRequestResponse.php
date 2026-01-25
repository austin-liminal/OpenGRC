<?php

namespace App\Filament\Resources\DataRequestResponseResource\Pages;

use App\Filament\Resources\DataRequestResponseResource;
use App\Models\Control;
use App\Models\DataRequestResponse;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ViewDataRequestResponse extends ViewRecord
{
    protected static string $resource = DataRequestResponseResource::class;

    /**
     * Get the header actions for the view.
     *
     * @return Action[]
     */
    protected function getHeaderActions(): array
    {
        /** @var DataRequestResponse $record */
        $record = $this->record;

        return [
            Action::make('back')
                ->label('Back to Assessment')
                ->icon('heroicon-m-arrow-left')
                ->url(route('filament.app.resources.audit-items.edit', $record->dataRequest->auditItem->audit_id)),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Evidence Requested')
                    ->columns(2)
                    ->schema([
                        Placeholder::make('request.dataRequest.audit.name')
                            ->content(fn ($record) => $record->dataRequest->audit->title ?? 'No audit name available')
                            ->label('Audit Name'),
                        Placeholder::make('dataRequest.code')
                            ->content(fn ($record) => $record->dataRequest->code ?? 'No code')
                            ->label('Request Code'),
                        Section::make('Data Request Details')
                            ->columnSpanFull()
                            ->schema([
                                Placeholder::make('request.dataRequest.details')
                                    ->content(fn ($record) => new HtmlString($record->dataRequest->details ?? 'No details available'))
                                    ->label('')
                                    ->columnSpanFull(),
                            ]),
                        Section::make(function ($record) {
                            // Check if any audit items are Controls
                            $hasControl = $record->dataRequest->auditItems->contains(function ($item) {
                                return $item->auditable_type === Control::class;
                            });

                            // Fallback to single relationship
                            if (! $hasControl && $record->dataRequest->auditItem) {
                                $hasControl = $record->dataRequest->auditItem->auditable_type === Control::class;
                            }

                            return $hasControl ? 'Control Details' : 'Implementation Details';
                        })
                            ->columnSpanFull()
                            ->collapsible()
                            ->collapsed(function ($record) {
                                // Only collapse for Controls, expand for Implementations
                                $hasControl = $record->dataRequest->auditItems->contains(function ($item) {
                                    return $item->auditable_type === Control::class;
                                });

                                if (! $hasControl && $record->dataRequest->auditItem) {
                                    $hasControl = $record->dataRequest->auditItem->auditable_type === Control::class;
                                }

                                return $hasControl;
                            })
                            ->schema([
                                Placeholder::make('request.dataRequest.auditItems.info')
                                    ->content(function ($record) {
                                        // Try many-to-many relationship first
                                        $items = $record->dataRequest->auditItems->map(function ($item) {
                                            if ($item->auditable) {
                                                $code = $item->auditable->code ?? '';
                                                $title = $item->auditable->title ?? '';
                                                // Implementation uses 'details', Control uses 'description'
                                                $details = $item->auditable->details ?? $item->auditable->description ?? '';

                                                $output = '';
                                                if ($code) {
                                                    $output .= '<strong>Code:</strong> '.e($code).'<br>';
                                                }
                                                if ($title) {
                                                    $output .= '<strong>Title:</strong> '.e($title).'<br>';
                                                }
                                                if ($details) {
                                                    $output .= '<strong>Details:</strong> '.$details;
                                                }

                                                return $output;
                                            }

                                            return null;
                                        })->filter()->all();

                                        // Fallback to single relationship for backwards compatibility
                                        if (empty($items) && $record->dataRequest->auditItem?->auditable) {
                                            $auditable = $record->dataRequest->auditItem->auditable;
                                            $code = $auditable->code ?? '';
                                            $title = $auditable->title ?? '';
                                            // Implementation uses 'details', Control uses 'description'
                                            $details = $auditable->details ?? $auditable->description ?? '';

                                            $output = '';
                                            if ($code) {
                                                $output .= '<strong>Code:</strong> '.e($code).'<br>';
                                            }
                                            if ($title) {
                                                $output .= '<strong>Title:</strong> '.e($title).'<br>';
                                            }
                                            if ($details) {
                                                $output .= '<strong>Details:</strong> '.$details;
                                            }
                                            $items = [$output];
                                        }

                                        return new HtmlString(! empty($items) ? implode('<hr class="my-4">', $items) : 'No details available');
                                    })
                                    ->label('')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Response')
                    ->columnSpanFull()
                    ->schema([
                        Placeholder::make('response')
                            ->content(fn ($record) => new HtmlString($record->response ?? 'No response yet'))
                            ->label('Response'),

                        Repeater::make('attachments')
                            ->relationship('attachments')
                            ->columnSpanFull()
                            ->columns(2)
                            ->schema([
                                TextInput::make('description')
                                    ->disabled(),
                                TextInput::make('file_name')
                                    ->label('File')
                                    ->disabled(),
                            ])
                            ->deletable(false)
                            ->addable(false)
                            ->reorderable(false),
                    ]),
                Section::make('Comments')
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        ViewField::make('comments')
                            ->view('filament.forms.components.inline-comments')
                            ->dehydrated(false),
                    ]),
            ]);
    }
}
