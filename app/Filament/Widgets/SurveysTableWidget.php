<?php

namespace App\Filament\Widgets;

use App\Enums\SurveyStatus;
use App\Filament\Resources\SurveyResource;
use App\Mail\SurveyInvitationMail;
use App\Models\Survey;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Mail;

class SurveysTableWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Survey::query()->with(['template', 'assignedTo', 'createdBy']))
            ->heading(__('survey.manager.tabs.surveys'))
            ->columns([
                Tables\Columns\TextColumn::make('display_title')
                    ->label(__('survey.survey.table.columns.title'))
                    ->searchable(['title'])
                    ->sortable('title')
                    ->wrap(),
                Tables\Columns\TextColumn::make('template.title')
                    ->label(__('survey.survey.table.columns.template'))
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('respondent_display')
                    ->label(__('survey.survey.table.columns.respondent'))
                    ->getStateUsing(fn (Survey $record): string => $record->respondent_name ?? $record->respondent_email ?? $record->assignedTo?->name ?? '-'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('survey.survey.table.columns.status'))
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('progress')
                    ->label(__('survey.survey.table.columns.progress'))
                    ->getStateUsing(fn (Survey $record): string => $record->progress.'%')
                    ->color(fn (Survey $record): string => match (true) {
                        $record->progress === 100 => 'success',
                        $record->progress > 50 => 'warning',
                        $record->progress > 0 => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('due_date')
                    ->label(__('survey.survey.table.columns.due_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label(__('survey.survey.table.columns.completed_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\SelectFilter::make('template_id')
                    ->relationship('template', 'title')
                    ->label(__('survey.survey.table.filters.template')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->url(fn (Survey $record): string => SurveyResource::getUrl('view', ['record' => $record])),
                    Tables\Actions\Action::make('edit')
                        ->label('Edit')
                        ->icon('heroicon-o-pencil')
                        ->url(fn (Survey $record): string => SurveyResource::getUrl('edit', ['record' => $record])),
                    Tables\Actions\Action::make('copy_link')
                        ->label(__('survey.survey.actions.copy_link'))
                        ->icon('heroicon-o-clipboard-document')
                        ->color('gray')
                        ->action(fn () => null)
                        ->extraAttributes(fn (Survey $record): array => [
                            'x-data' => '{}',
                            'x-on:click' => "navigator.clipboard.writeText('{$record->getPublicUrl()}'); \$tooltip('Link copied!')",
                        ])
                        ->visible(fn (Survey $record): bool => $record->access_token !== null),
                    Tables\Actions\Action::make('send_invitation')
                        ->label(__('survey.survey.actions.send_invitation'))
                        ->icon('heroicon-o-envelope')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading(__('survey.survey.actions.send_invitation_modal.heading'))
                        ->modalDescription(fn (Survey $record) => __('survey.survey.actions.send_invitation_modal.description', ['email' => $record->respondent_email]))
                        ->modalSubmitActionLabel(__('survey.survey.actions.send_invitation_modal.submit'))
                        ->action(function (Survey $record) {
                            try {
                                Mail::send(new SurveyInvitationMail($record));

                                $record->update(['status' => SurveyStatus::SENT]);

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
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create')
                    ->label('Create Survey')
                    ->icon('heroicon-o-plus')
                    ->url(SurveyResource::getUrl('create')),
            ])
            ->emptyStateHeading(__('survey.survey.table.empty_state.heading'))
            ->emptyStateDescription(__('survey.survey.table.empty_state.description'))
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Survey $record): string => SurveyResource::getUrl('view', ['record' => $record]));
    }
}
