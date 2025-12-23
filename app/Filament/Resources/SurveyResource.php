<?php

namespace App\Filament\Resources;

use App\Enums\SurveyStatus;
use App\Enums\SurveyTemplateStatus;
use App\Enums\SurveyType;
use App\Filament\Resources\SurveyResource\Pages;
use App\Filament\Resources\SurveyResource\RelationManagers;
use App\Mail\SurveyInvitationMail;
use App\Models\Survey;
use App\Models\User;
use App\Services\VendorRiskScoringService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Mail;

class SurveyResource extends Resource
{
    protected static ?string $model = Survey::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationGroup = 'Surveys';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'title';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('survey.survey.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('survey.survey.model.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('survey.survey.model.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Survey Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('survey_template_id')
                            ->label(__('survey.survey.form.template.label'))
                            ->relationship('template', 'title', fn (Builder $query) => $query->where('status', SurveyTemplateStatus::ACTIVE))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => request()->query('template'))
                            ->disabled(fn (?Survey $record): bool => $record !== null)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('title')
                            ->label(__('survey.survey.form.title.label'))
                            ->helperText(__('survey.survey.form.title.helper'))
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->label(__('survey.survey.form.status.label'))
                            ->options(SurveyStatus::class)
                            ->default(SurveyStatus::DRAFT)
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->label(__('Type'))
                            ->options(SurveyType::class)
                            ->default(SurveyType::VENDOR_ASSESSMENT)
                            ->required(),
                        Forms\Components\RichEditor::make('description')
                            ->label(__('survey.survey.form.description.label'))
                            ->helperText(__('survey.survey.form.description.helper'))
                            ->disableToolbarButtons(['attachFiles'])
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Vendor')
                    ->schema([
                        Forms\Components\Select::make('vendor_id')
                            ->label(__('Vendor'))
                            ->relationship('vendor', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Associate this survey with a vendor for TPRM tracking'),
                    ]),
                Forms\Components\Section::make('Respondent Information')
                    ->description(__('survey.survey.form.respondent.description'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('respondent_email')
                            ->label(__('survey.survey.form.respondent_email.label'))
                            ->email()
                            ->maxLength(255)
                            ->helperText(__('survey.survey.form.respondent_email.helper')),
                        Forms\Components\TextInput::make('respondent_name')
                            ->label(__('survey.survey.form.respondent_name.label'))
                            ->maxLength(255),
                        Forms\Components\Select::make('assigned_to_id')
                            ->label(__('survey.survey.form.assigned_to.label'))
                            ->options(User::whereNotNull('name')->pluck('name', 'id'))
                            ->searchable()
                            ->helperText(__('survey.survey.form.assigned_to.helper')),
                        Forms\Components\DatePicker::make('due_date')
                            ->label(__('survey.survey.form.due_date.label'))
                            ->native(false),
                        Forms\Components\DatePicker::make('expiration_date')
                            ->label(__('survey.survey.form.expiration_date.label'))
                            ->helperText(__('survey.survey.form.expiration_date.helper'))
                            ->native(false),
                    ]),
                Forms\Components\Section::make('Survey Link')
                    ->description(fn (?Survey $record): string => $record?->isInternal()
                        ? __('Internal survey - accessible via admin panel')
                        : __('survey.survey.form.link.description'))
                    ->schema([
                        Forms\Components\Placeholder::make('public_url')
                            ->label(fn (?Survey $record): string => $record?->isInternal()
                                ? __('Internal Assessment Link')
                                : __('survey.survey.form.link.label'))
                            ->content(fn (?Survey $record): string => $record === null
                                ? 'Link will be generated after saving'
                                : ($record->isInternal()
                                    ? static::getUrl('respond-internal', ['record' => $record])
                                    : $record->getPublicUrl()))
                            ->visible(fn (?Survey $record): bool => $record !== null),
                    ])
                    ->visible(fn (?Survey $record): bool => $record !== null),
                Forms\Components\Hidden::make('created_by_id')
                    ->default(fn () => auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_title')
                    ->label(__('survey.survey.table.columns.title'))
                    ->searchable(['title'])
                    ->sortable(['title'])
                    ->wrap(),
                Tables\Columns\TextColumn::make('template.title')
                    ->label(__('survey.survey.table.columns.template'))
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-')
                    ->url(fn (Survey $record) => $record->vendor_id ? VendorResource::getUrl('view', ['record' => $record->vendor_id]) : null),
                Tables\Columns\TextColumn::make('respondent_display')
                    ->label(__('survey.survey.table.columns.respondent'))
                    ->wrap(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('survey.survey.table.columns.status'))
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('progress')
                    ->label(__('survey.survey.table.columns.progress'))
                    ->suffix('%')
                    ->color(fn (Survey $record): string => match (true) {
                        $record->progress >= 100 => 'success',
                        $record->progress >= 50 => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('due_date')
                    ->label(__('survey.survey.table.columns.due_date'))
                    ->date()
                    ->sortable()
                    ->color(fn (Survey $record): ?string => $record->isExpired() ? 'danger' : null),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label(__('survey.survey.table.columns.completed_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('risk_score')
                    ->label('Risk Score')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state <= 20 => 'success',
                        $state <= 40 => 'info',
                        $state <= 60 => 'warning',
                        $state <= 80 => 'orange',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (?int $state): string => $state !== null ? "{$state}/100" : '-')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label(__('survey.survey.table.columns.created_by'))
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('survey.survey.table.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(SurveyStatus::class)
                    ->label(__('survey.survey.table.filters.status')),
                Tables\Filters\SelectFilter::make('type')
                    ->options(SurveyType::class)
                    ->label(__('Type')),
                Tables\Filters\SelectFilter::make('vendor_id')
                    ->relationship('vendor', 'name')
                    ->label('Vendor')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('survey_template_id')
                    ->relationship('template', 'title')
                    ->label(__('survey.survey.table.filters.template'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('assigned_to_id')
                    ->relationship('assignedTo', 'name')
                    ->label(__('survey.survey.table.filters.assigned_to'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('copy_link')
                        ->label(fn (Survey $record): string => $record->isInternal()
                            ? __('Open Assessment')
                            : __('survey.survey.actions.copy_link'))
                        ->icon('heroicon-o-link')
                        ->color('gray')
                        ->url(fn (Survey $record): ?string => $record->isInternal()
                            ? static::getUrl('respond-internal', ['record' => $record])
                            : null)
                        ->requiresConfirmation(fn (Survey $record): bool => ! $record->isInternal())
                        ->modalHeading('Survey Link')
                        ->modalDescription(fn (Survey $record) => 'Copy this link to share the survey: '.$record->getPublicUrl())
                        ->modalSubmitActionLabel('Copy to Clipboard')
                        ->action(fn () => null)
                        ->visible(fn (Survey $record): bool => $record->isInternal() || $record->access_token !== null),
                    Tables\Actions\Action::make('mark_complete')
                        ->label(__('survey.survey.actions.mark_complete'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Survey $record) {
                            $record->update([
                                'status' => SurveyStatus::COMPLETED,
                                'completed_at' => now(),
                            ]);
                        })
                        ->visible(fn (Survey $record): bool => ! in_array($record->status, [SurveyStatus::COMPLETED, SurveyStatus::EXPIRED])),
                    Tables\Actions\Action::make('send_invitation')
                        ->label(__('survey.survey.actions.send_invitation'))
                        ->icon('heroicon-o-envelope')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading(__('survey.survey.actions.send_invitation_modal.heading'))
                        ->modalDescription(fn (Survey $record) => __('survey.survey.actions.send_invitation_modal.description', ['email' => $record->respondent_email]))
                        ->modalSubmitActionLabel(__('survey.survey.actions.send_invitation_modal.submit'))
                        ->action(function (Survey $record) {
                            // Update status to SENT regardless of email success
                            $record->update(['status' => SurveyStatus::SENT]);

                            try {
                                Mail::send(new SurveyInvitationMail($record));

                                Notification::make()
                                    ->title(__('survey.survey.notifications.invitation_sent.title'))
                                    ->body(__('survey.survey.notifications.invitation_sent.body', ['email' => $record->respondent_email]))
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title(__('Survey Sent'))
                                    ->body(__('Survey marked as sent but email notification failed: ').$e->getMessage())
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn (Survey $record): bool => ! empty($record->respondent_email) && $record->status === SurveyStatus::DRAFT),
                    Tables\Actions\Action::make('resend_invitation')
                        ->label(__('survey.survey.actions.resend_invitation'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalHeading(__('survey.survey.actions.resend_invitation_modal.heading'))
                        ->modalDescription(fn (Survey $record) => __('survey.survey.actions.resend_invitation_modal.description', ['email' => $record->respondent_email]))
                        ->modalSubmitActionLabel(__('survey.survey.actions.resend_invitation_modal.submit'))
                        ->action(function (Survey $record) {
                            try {
                                Mail::send(new SurveyInvitationMail($record));

                                Notification::make()
                                    ->title(__('survey.survey.notifications.invitation_sent.title'))
                                    ->body(__('survey.survey.notifications.invitation_sent.body', ['email' => $record->respondent_email]))
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title(__('survey.survey.notifications.invitation_failed.title'))
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (Survey $record): bool => ! empty($record->respondent_email) && in_array($record->status, [SurveyStatus::SENT, SurveyStatus::IN_PROGRESS])),
                    Tables\Actions\Action::make('score_survey')
                        ->label(__('Score Survey'))
                        ->icon('heroicon-o-clipboard-document-check')
                        ->color('primary')
                        ->url(fn (Survey $record): string => static::getUrl('score', ['record' => $record]))
                        ->visible(fn (Survey $record): bool => in_array($record->status, [SurveyStatus::PENDING_SCORING, SurveyStatus::COMPLETED])),
                    Tables\Actions\Action::make('recalculate_score')
                        ->label(__('Recalculate Risk Score'))
                        ->icon('heroicon-o-calculator')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalDescription(__('This will recalculate the risk score based on current answers and question weights.'))
                        ->action(function (Survey $record) {
                            $service = new VendorRiskScoringService;
                            $score = $service->calculateSurveyScore($record);

                            if ($record->vendor) {
                                $service->calculateVendorScore($record->vendor);
                            }

                            Notification::make()
                                ->title(__('Risk score recalculated'))
                                ->body(__('New score: :score/100', ['score' => $score]))
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Survey $record): bool => $record->status === SurveyStatus::COMPLETED),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('survey.survey.table.empty_state.heading'))
            ->emptyStateDescription(__('survey.survey.table.empty_state.description'));
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make(__('survey.survey.infolist.section_title'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('display_title')
                            ->label(__('survey.survey.form.title.label')),
                        TextEntry::make('template.title')
                            ->label(__('survey.survey.form.template.label'))
                            ->url(fn (Survey $record) => SurveyTemplateResource::getUrl('view', ['record' => $record->template])),
                        TextEntry::make('status')
                            ->label(__('survey.survey.form.status.label'))
                            ->badge(),
                        TextEntry::make('type')
                            ->label(__('Type'))
                            ->badge(),
                        TextEntry::make('vendor.name')
                            ->label('Vendor')
                            ->placeholder('-')
                            ->url(fn (Survey $record) => $record->vendor_id ? VendorResource::getUrl('view', ['record' => $record->vendor_id]) : null),
                        TextEntry::make('respondent_display')
                            ->label(__('survey.survey.table.columns.respondent')),
                        TextEntry::make('assignedTo.name')
                            ->label(__('survey.survey.form.assigned_to.label'))
                            ->default('-'),
                        TextEntry::make('due_date')
                            ->label(__('survey.survey.form.due_date.label'))
                            ->date()
                            ->placeholder('-'),
                        TextEntry::make('expiration_date')
                            ->label(__('survey.survey.form.expiration_date.label'))
                            ->date()
                            ->placeholder('-')
                            ->color(fn (Survey $record): ?string => $record->isLinkExpired() ? 'danger' : null),
                        TextEntry::make('progress')
                            ->label(__('survey.survey.table.columns.progress'))
                            ->suffix('%'),
                        TextEntry::make('completed_at')
                            ->label(__('survey.survey.table.columns.completed_at'))
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('risk_score')
                            ->label('Risk Score')
                            ->badge()
                            ->color(fn (?int $state): string => match (true) {
                                $state === null => 'gray',
                                $state <= 20 => 'success',
                                $state <= 40 => 'info',
                                $state <= 60 => 'warning',
                                $state <= 80 => 'orange',
                                default => 'danger',
                            })
                            ->formatStateUsing(fn (?int $state): string => $state !== null ? "{$state}/100" : '-'),
                        TextEntry::make('createdBy.name')
                            ->label(__('survey.survey.table.columns.created_by')),
                        TextEntry::make('public_url')
                            ->label(fn (Survey $record): string => $record->isInternal()
                                ? __('Internal Assessment Link')
                                : __('survey.survey.form.link.label'))
                            ->state(fn (Survey $record): string => $record->isInternal()
                                ? static::getUrl('respond-internal', ['record' => $record])
                                : $record->getPublicUrl())
                            ->copyable()
                            ->copyMessage('Link copied!')
                            ->url(fn (Survey $record): ?string => $record->isInternal()
                                ? static::getUrl('respond-internal', ['record' => $record])
                                : null)
                            ->columnSpanFull(),
                        TextEntry::make('description')
                            ->label(__('survey.survey.form.description.label'))
                            ->html()
                            ->columnSpanFull()
                            ->hidden(fn (Survey $record): bool => empty($record->description)),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AnswersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSurveys::route('/'),
            'create' => Pages\CreateSurvey::route('/create'),
            'view' => Pages\ViewSurvey::route('/{record}'),
            'edit' => Pages\EditSurvey::route('/{record}/edit'),
            'score' => Pages\ScoreSurvey::route('/{record}/score'),
            'respond-internal' => Pages\RespondToSurveyInternal::route('/{record}/respond'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
