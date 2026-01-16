<?php

namespace App\Filament\Resources;

use App\Enums\ResponseStatus;
use App\Filament\Resources\DataRequestResponseResource\Pages;
use App\Models\DataRequestResponse;
use Exception;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class DataRequestResponseResource extends Resource
{
    protected static ?string $model = DataRequestResponse::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
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
                        Section::make(function ($record) {
                            // Check if any audit items are Controls
                            $hasControl = $record->dataRequest->auditItems->contains(function ($item) {
                                return $item->auditable_type === \App\Models\Control::class;
                            });

                            // Fallback to single relationship
                            if (!$hasControl && $record->dataRequest->auditItem) {
                                $hasControl = $record->dataRequest->auditItem->auditable_type === \App\Models\Control::class;
                            }

                            return $hasControl ? 'Control Details' : 'Implementation Details';
                        })
                            ->columnSpanFull()
                            ->collapsible()
                            ->collapsed(function ($record) {
                                // Only collapse for Controls, expand for Implementations
                                $hasControl = $record->dataRequest->auditItems->contains(function ($item) {
                                    return $item->auditable_type === \App\Models\Control::class;
                                });

                                if (!$hasControl && $record->dataRequest->auditItem) {
                                    $hasControl = $record->dataRequest->auditItem->auditable_type === \App\Models\Control::class;
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
                                                    $output .= '<strong>Code:</strong> ' . e($code) . '<br>';
                                                }
                                                if ($title) {
                                                    $output .= '<strong>Title:</strong> ' . e($title) . '<br>';
                                                }
                                                if ($details) {
                                                    $output .= '<strong>Details:</strong> ' . $details;
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
                                                $output .= '<strong>Code:</strong> ' . e($code) . '<br>';
                                            }
                                            if ($title) {
                                                $output .= '<strong>Title:</strong> ' . e($title) . '<br>';
                                            }
                                            if ($details) {
                                                $output .= '<strong>Details:</strong> ' . $details;
                                            }
                                            $items = [$output];
                                        }

                                        return new HtmlString(!empty($items) ? implode('<hr class="my-4">', $items) : 'No details available');
                                    })
                                    ->label('')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Response')
                    ->columnSpanFull()
                    ->schema([
                        RichEditor::make('response')
                            ->maxLength(65535)
                            ->disableToolbarButtons([
                                'image',
                                'attachFiles',
                            ])
                            ->required(function ($get, $record) {
                                if (is_null($record)) {
                                    return false;
                                }
                                $auditManagerId = $record->manager_id ?: 0;
                                $currentUserId = auth()->id();

                                return $currentUserId !== $auditManagerId;
                            }),

                        Repeater::make('attachments')
                            ->relationship('attachments')
                            ->columnSpanFull()
                            ->columns()
                            ->schema([
                                Textarea::make('description')
                                    ->maxLength(1024)
                                    ->required(),
                                FileUpload::make('file_path')
                                    ->label('File')
                                    ->required()
                                    ->preserveFilenames()
                                    ->disk(setting('storage.driver', 'private'))
                                    ->directory('data-request-attachments')
                                    ->storeFileNamesIn('file_name')
                                    ->visibility('private')
                                    ->downloadable()
                                    ->openable()
                                    ->deletable()
                                    ->reorderable()
                                    ->maxSize(10240) // 2MB max (matches PHP upload_max_filesize)
                                    ->getUploadedFileNameForStorageUsing(function ($file) {
                                        $random = Str::random(8);

                                        return $random.'-'.$file->getClientOriginalName();
                                    })
                                    ->deleteUploadedFileUsing(function ($state) {
                                        if ($state) {
                                            Storage::disk(setting('storage.driver', 'private'))->delete($state);
                                        }
                                    }),

                                Hidden::make('uploaded_by')
                                    ->default(Auth::id()),
                                Hidden::make('audit_id')
                                    ->default(function ($livewire) {
                                        $drr = DataRequestResponse::where('id', $livewire->data['id'])->first();

                                        return $drr->dataRequest->audit_id;
                                    }),
                            ]),

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

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('dataRequest.details')
                    ->label('Data Request Details')
                    ->wrap()
                    ->html()
                    ->limit(200),
                Tables\Columns\TextColumn::make('requester.name')
                    ->label('Requester'),
                Tables\Columns\TextColumn::make('requestee.name')
                    ->label('Requestee'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Status'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(ResponseStatus::class)
                    ->label('Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDataRequestResponses::route('/'),
            'create' => Pages\CreateDataRequestResponse::route('/create'),
            'edit' => Pages\EditDataRequestResponse::route('/{record}/edit'),
            'view' => Pages\ViewDataRequestResponse::route('/{record}'),
        ];
    }
}
