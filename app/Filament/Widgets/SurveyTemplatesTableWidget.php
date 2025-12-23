<?php

namespace App\Filament\Widgets;

use App\Enums\SurveyTemplateStatus;
use App\Filament\Resources\SurveyResource;
use App\Filament\Resources\SurveyTemplateResource;
use App\Models\SurveyTemplate;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class SurveyTemplatesTableWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(SurveyTemplate::query()->withCount(['questions', 'surveys']))
            ->heading(__('survey.manager.tabs.templates'))
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('survey.template.table.columns.title'))
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('survey.template.table.columns.status'))
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('questions_count')
                    ->label(__('survey.template.table.columns.questions_count'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('surveys_count')
                    ->label(__('survey.template.table.columns.surveys_count'))
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_public')
                    ->label(__('survey.template.table.columns.is_public'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label(__('survey.template.table.columns.created_by'))
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('survey.template.table.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(SurveyTemplateStatus::class)
                    ->label(__('survey.template.table.filters.status')),
                Tables\Filters\TernaryFilter::make('is_public')
                    ->label(__('survey.template.table.filters.is_public')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->url(fn (SurveyTemplate $record): string => SurveyTemplateResource::getUrl('view', ['record' => $record])),
                    Tables\Actions\Action::make('edit')
                        ->label('Edit')
                        ->icon('heroicon-o-pencil')
                        ->url(fn (SurveyTemplate $record): string => SurveyTemplateResource::getUrl('edit', ['record' => $record])),
                    Tables\Actions\Action::make('create_survey')
                        ->label(__('survey.template.actions.create_survey'))
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->url(fn (SurveyTemplate $record): string => SurveyResource::getUrl('create', ['template' => $record->id]))
                        ->visible(fn (SurveyTemplate $record): bool => $record->status === SurveyTemplateStatus::ACTIVE),
                    Tables\Actions\Action::make('duplicate')
                        ->label(__('survey.template.actions.duplicate'))
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->action(function (SurveyTemplate $record) {
                            $newTemplate = $record->replicate();
                            $newTemplate->title = $record->title.' (Copy)';
                            $newTemplate->status = SurveyTemplateStatus::DRAFT;
                            $newTemplate->created_by_id = auth()->id();
                            $newTemplate->save();

                            foreach ($record->questions as $question) {
                                $newQuestion = $question->replicate();
                                $newQuestion->survey_template_id = $newTemplate->id;
                                $newQuestion->save();
                            }

                            return redirect(SurveyTemplateResource::getUrl('edit', ['record' => $newTemplate]));
                        }),
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
                    ->label('Create Template')
                    ->icon('heroicon-o-plus')
                    ->url(SurveyTemplateResource::getUrl('create')),
            ])
            ->emptyStateHeading(__('survey.template.table.empty_state.heading'))
            ->emptyStateDescription(__('survey.template.table.empty_state.description'))
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (SurveyTemplate $record): string => SurveyTemplateResource::getUrl('view', ['record' => $record]));
    }
}
