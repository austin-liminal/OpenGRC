<?php

namespace App\Filament\Resources\DataRequestResponseResource\Pages;

use App\Filament\Resources\DataRequestResponseResource;
use App\Models\Control;
use App\Models\DataRequestResponse;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Resources\Pages\ViewRecord;
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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                        Section::make('Control Details')
                            ->columnSpanFull()
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                Placeholder::make('request.dataRequest.auditItems.names')
                                    ->content(function ($record) {
                                        $titles = $record->dataRequest->auditItems->map(function ($item) {
                                            return $item->auditable ? $item->auditable->title : null;
                                        })->filter()->all();

                                        if (empty($titles) && $record->dataRequest->auditItem?->auditable) {
                                            $titles = [$record->dataRequest->auditItem->auditable->title];
                                        }

                                        return new HtmlString(! empty($titles) ? implode('<br>', $titles) : 'No audit items available');
                                    })
                                    ->label(function ($record) {
                                        $hasControl = $record->dataRequest->auditItems->contains(function ($item) {
                                            return $item->auditable_type === Control::class;
                                        });

                                        if (! $hasControl && $record->dataRequest->auditItem) {
                                            $hasControl = $record->dataRequest->auditItem->auditable_type === Control::class;
                                        }

                                        return $hasControl ? 'Control Name(s)' : 'Implementation Name(s)';
                                    })
                                    ->columnSpanFull(),
                                Placeholder::make('request.dataRequest.auditItems.descriptions')
                                    ->content(function ($record) {
                                        $descriptions = $record->dataRequest->auditItems->map(function ($item) {
                                            if ($item->auditable) {
                                                return '<strong>'.($item->auditable->code ?? $item->auditable->title).':</strong> '.$item->auditable->description;
                                            }

                                            return null;
                                        })->filter()->all();

                                        if (empty($descriptions) && $record->dataRequest->auditItem?->auditable) {
                                            $code = $record->dataRequest->auditItem->auditable->code ?? $record->dataRequest->auditItem->auditable->title;
                                            $descriptions = ['<strong>'.$code.':</strong> '.$record->dataRequest->auditItem->auditable->description];
                                        }

                                        return new HtmlString(! empty($descriptions) ? implode('<br><br>', $descriptions) : 'No descriptions available');
                                    })
                                    ->label('Control Description(s)')
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
