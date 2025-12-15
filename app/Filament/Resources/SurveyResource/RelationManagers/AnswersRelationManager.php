<?php

namespace App\Filament\Resources\SurveyResource\RelationManagers;

use App\Enums\QuestionType;
use App\Models\SurveyAnswer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class AnswersRelationManager extends RelationManager
{
    protected static string $relationship = 'answers';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Answers are typically view-only in the admin panel
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('question.question_text')
                    ->label(__('survey.survey.answers.columns.question'))
                    ->wrap()
                    ->limit(100),
                Tables\Columns\TextColumn::make('question.question_type')
                    ->label(__('survey.survey.answers.columns.type'))
                    ->badge(),
                Tables\Columns\TextColumn::make('display_value')
                    ->label(__('survey.survey.answers.columns.answer'))
                    ->wrap()
                    ->formatStateUsing(function ($state, $record) {
                        $value = $record->answer_value;

                        if ($value === null) {
                            return new HtmlString('<span class="text-gray-400">No answer</span>');
                        }

                        $questionType = $record->question?->question_type;

                        if ($questionType === QuestionType::BOOLEAN) {
                            return $value ? 'Yes' : 'No';
                        }

                        if ($questionType === QuestionType::FILE) {
                            return $record->attachments->count() > 0
                                ? $record->attachments->count().' file(s) attached'
                                : 'No files';
                        }

                        if (is_array($value)) {
                            if (isset($value['value'])) {
                                return $value['value'];
                            }

                            return implode(', ', array_filter($value, fn ($v) => ! is_array($v)));
                        }

                        return (string) $value;
                    }),
                Tables\Columns\TextColumn::make('comment')
                    ->label(__('survey.survey.answers.columns.comment'))
                    ->wrap()
                    ->limit(50)
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('manual_score')
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
                    ->formatStateUsing(fn (?int $state): string => $state !== null ? "{$state}/100" : 'Not scored')
                    ->visible(fn ($livewire) => $livewire->getOwnerRecord()->status->value === 'completed'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('survey.survey.answers.columns.answered_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('score_answer')
                    ->label('Score')
                    ->icon('heroicon-o-calculator')
                    ->color('warning')
                    ->form([
                        Forms\Components\Placeholder::make('answer_preview')
                            ->label('Answer')
                            ->content(fn (SurveyAnswer $record): string => is_array($record->answer_value)
                                ? implode(', ', array_filter($record->answer_value, fn ($v) => !is_array($v)))
                                : (string) ($record->answer_value ?? 'No answer')),
                        Forms\Components\TextInput::make('manual_score')
                            ->label('Risk Score (0-100)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required()
                            ->default(fn (SurveyAnswer $record) => $record->manual_score)
                            ->helperText('0 = No risk (best), 100 = High risk (worst)'),
                    ])
                    ->action(function (SurveyAnswer $record, array $data) {
                        $record->update([
                            'manual_score' => $data['manual_score'],
                            'scored_by' => Auth::id(),
                            'scored_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Answer scored')
                            ->body("Score set to {$data['manual_score']}/100")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (SurveyAnswer $record): bool =>
                        in_array($record->question?->question_type, [QuestionType::TEXT, QuestionType::LONG_TEXT])
                        && $record->question?->risk_weight > 0
                    ),
                Tables\Actions\ViewAction::make()
                    ->modalHeading(fn ($record) => 'Answer Details')
                    ->modalContent(function ($record) {
                        $question = $record->question;
                        $value = $record->answer_value;

                        $html = '<div class="space-y-4">';
                        $html .= '<div><strong>Question:</strong><br>'.e($question->question_text ?? 'Unknown').'</div>';
                        $html .= '<div><strong>Type:</strong> '.($question->question_type?->getLabel() ?? 'Unknown').'</div>';
                        $html .= '<div><strong>Required:</strong> '.($question->is_required ? 'Yes' : 'No').'</div>';

                        if ($question->help_text) {
                            $html .= '<div><strong>Help Text:</strong><br>'.e($question->help_text).'</div>';
                        }

                        $html .= '<hr class="my-4">';
                        $html .= '<div><strong>Answer:</strong><br>';

                        if ($value === null) {
                            $html .= '<span class="text-gray-400">No answer provided</span>';
                        } elseif ($question->question_type === QuestionType::BOOLEAN) {
                            $html .= $value ? 'Yes' : 'No';
                        } elseif ($question->question_type === QuestionType::FILE) {
                            // Show file attachments with download links
                            if ($record->attachments->count() > 0) {
                                $html .= '<div class="space-y-2 mt-2">';
                                foreach ($record->attachments as $attachment) {
                                    $downloadUrl = route('survey-attachment.download', $attachment);
                                    $html .= '<div class="flex items-center gap-2 bg-gray-50 dark:bg-gray-800 p-2 rounded">';
                                    $html .= '<svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                                    $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>';
                                    $html .= '</svg>';
                                    $html .= '<div class="flex-1">';
                                    $html .= '<a href="'.e($downloadUrl).'" class="text-primary-600 hover:text-primary-800 font-medium" target="_blank">';
                                    $html .= e($attachment->file_name);
                                    $html .= '</a>';
                                    $html .= '<span class="text-xs text-gray-500 ml-2">('.$attachment->formatted_file_size.')</span>';
                                    $html .= '</div>';
                                    $html .= '</div>';
                                }
                                $html .= '</div>';
                            } else {
                                $html .= '<span class="text-gray-400">No files uploaded</span>';
                            }
                        } elseif (is_array($value)) {
                            $html .= '<ul class="list-disc list-inside">';
                            foreach ($value as $v) {
                                if (! is_array($v)) {
                                    $html .= '<li>'.e($v).'</li>';
                                }
                            }
                            $html .= '</ul>';
                        } else {
                            $html .= e($value);
                        }

                        $html .= '</div>';

                        // Show comment if present
                        if ($record->comment) {
                            $html .= '<hr class="my-4">';
                            $html .= '<div><strong>Additional Comments:</strong><br>';
                            $html .= '<div class="bg-gray-50 dark:bg-gray-800 p-3 rounded mt-1">'.nl2br(e($record->comment)).'</div>';
                            $html .= '</div>';
                        }

                        $html .= '</div>';

                        return new HtmlString($html);
                    }),
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateHeading('No answers yet')
            ->emptyStateDescription('Answers will appear here once the respondent starts filling out the survey.');
    }
}
