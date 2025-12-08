<?php

namespace App\Filament\Resources\SurveyTemplateResource\RelationManagers;

use App\Enums\SurveyStatus;
use App\Filament\Resources\SurveyResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SurveysRelationManager extends RelationManager
{
    protected static string $relationship = 'surveys';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return SurveyResource::form($form);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('display_title')
                    ->label(__('survey.survey.table.columns.title'))
                    ->sortable(['title']),
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
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => SurveyResource::getUrl('view', ['record' => $record])),
                Tables\Actions\EditAction::make()
                    ->url(fn ($record) => SurveyResource::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
