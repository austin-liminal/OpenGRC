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
                        Section::make('Control Details')
                            ->columnSpanFull()
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                Placeholder::make('request.dataRequest.auditItems.names')
                                    ->content(function ($record) {
                                        // Try many-to-many relationship first
                                        $titles = $record->dataRequest->auditItems->map(function ($item) {
                                            return $item->auditable ? $item->auditable->title : null;
                                        })->filter()->all();

                                        // Fallback to single relationship for backwards compatibility
                                        if (empty($titles) && $record->dataRequest->auditItem?->auditable) {
                                            $titles = [$record->dataRequest->auditItem->auditable->title];
                                        }

                                        return new HtmlString(!empty($titles) ? implode('<br>', $titles) : 'No audit items available');
                                    })
                                    ->label(function ($record) {
                                        // Check if any audit items are Controls
                                        $hasControl = $record->dataRequest->auditItems->contains(function ($item) {
                                            return $item->auditable_type === \App\Models\Control::class;
                                        });

                                        // Fallback to single relationship
                                        if (!$hasControl && $record->dataRequest->auditItem) {
                                            $hasControl = $record->dataRequest->auditItem->auditable_type === \App\Models\Control::class;
                                        }

                                        return $hasControl ? 'Control Name(s)' : 'Implementation Name(s)';
                                    })
                                    ->columnSpanFull(),
                                Placeholder::make('request.dataRequest.auditItems.descriptions')
                                    ->content(function ($record) {
                                        // Try many-to-many relationship first
                                        $descriptions = $record->dataRequest->auditItems->map(function ($item) {
                                            if ($item->auditable) {
                                                return '<strong>' . ($item->auditable->code ?? $item->auditable->title) . ':</strong> ' . $item->auditable->description;
                                            }
                                            return null;
                                        })->filter()->all();

                                        // Fallback to single relationship for backwards compatibility
                                        if (empty($descriptions) && $record->dataRequest->auditItem?->auditable) {
                                            $code = $record->dataRequest->auditItem->auditable->code ?? $record->dataRequest->auditItem->auditable->title;
                                            $descriptions = ['<strong>' . $code . ':</strong> ' . $record->dataRequest->auditItem->auditable->description];
                                        }

                                        return new HtmlString(!empty($descriptions) ? implode('<br><br>', $descriptions) : 'No descriptions available');
                                    })
                                    ->label('Control Description(s)')
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
