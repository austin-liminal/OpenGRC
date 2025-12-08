<?php

namespace App\Filament\Resources\VendorResource\RelationManagers;

use App\Enums\SurveyStatus;
use App\Enums\SurveyTemplateStatus;
use App\Filament\Resources\SurveyResource;
use App\Mail\SurveyInvitationMail;
use App\Models\Survey;
use App\Models\SurveyTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class SurveysRelationManager extends RelationManager
{
    protected static string $relationship = 'surveys';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('survey_template_id')
                    ->label(__('Survey Template'))
                    ->options(SurveyTemplate::where('status', SurveyTemplateStatus::ACTIVE)->pluck('title', 'id'))
                    ->searchable()
                    ->required()
                    ->disabled(fn (?Survey $record): bool => $record !== null),
                Forms\Components\TextInput::make('respondent_email')
                    ->label(__('Respondent Email'))
                    ->email()
                    ->required(),
                Forms\Components\TextInput::make('respondent_name')
                    ->label(__('Respondent Name')),
                Forms\Components\DatePicker::make('due_date')
                    ->label(__('Due Date'))
                    ->native(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('display_title')
                    ->label(__('survey.survey.table.columns.title'))
                    ->sortable(['title']),
                Tables\Columns\TextColumn::make('template.title')
                    ->label(__('survey.survey.table.columns.template'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('respondent_display')
                    ->label(__('survey.survey.table.columns.respondent'))
                    ->wrap(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('survey.survey.table.columns.status'))
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('progress')
                    ->label(__('survey.survey.table.columns.progress'))
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label(__('survey.survey.table.columns.due_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('survey.survey.table.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(SurveyStatus::class),
            ])
            ->headerActions([
                Tables\Actions\Action::make('send_survey')
                    ->label(__('Send Survey'))
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('survey_template_id')
                            ->label(__('Survey Template'))
                            ->options(SurveyTemplate::where('status', SurveyTemplateStatus::ACTIVE)->pluck('title', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('respondent_email')
                            ->label(__('Respondent Email'))
                            ->email()
                            ->required()
                            ->helperText(__('The email address to send the survey to')),
                        Forms\Components\TextInput::make('respondent_name')
                            ->label(__('Respondent Name'))
                            ->helperText(__('Name of the person completing the survey')),
                        Forms\Components\DatePicker::make('due_date')
                            ->label(__('Due Date'))
                            ->native(false),
                    ])
                    ->action(function (array $data) {
                        $survey = Survey::create([
                            'survey_template_id' => $data['survey_template_id'],
                            'vendor_id' => $this->ownerRecord->id,
                            'respondent_email' => $data['respondent_email'],
                            'respondent_name' => $data['respondent_name'] ?? null,
                            'due_date' => $data['due_date'] ?? null,
                            'status' => SurveyStatus::DRAFT,
                            'created_by_id' => auth()->id(),
                        ]);

                        try {
                            Mail::send(new SurveyInvitationMail($survey));
                            $survey->update(['status' => SurveyStatus::SENT]);

                            Notification::make()
                                ->title(__('Survey Sent'))
                                ->body(__('Survey invitation sent to :email', ['email' => $data['respondent_email']]))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title(__('Failed to Send Survey'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => SurveyResource::getUrl('view', ['record' => $record])),
                Tables\Actions\Action::make('resend_invitation')
                    ->label(__('Resend'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function (Survey $record) {
                        try {
                            Mail::send(new SurveyInvitationMail($record));

                            Notification::make()
                                ->title(__('Survey Sent'))
                                ->body(__('Survey invitation sent to :email', ['email' => $record->respondent_email]))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title(__('Failed to Send Survey'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Survey $record): bool => ! empty($record->respondent_email) && in_array($record->status, [SurveyStatus::SENT, SurveyStatus::IN_PROGRESS])),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
