<?php

namespace App\Filament\Resources;

use App\Enums\QuestionType;
use App\Enums\RiskImpact;
use App\Enums\SurveyTemplateStatus;
use App\Enums\SurveyType;
use App\Filament\Resources\SurveyTemplateResource\Pages;
use App\Filament\Resources\SurveyTemplateResource\RelationManagers;
use App\Models\SurveyTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SurveyTemplateResource extends Resource
{
    protected static ?string $model = SurveyTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Surveys';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'title';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('survey.template.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('survey.template.model.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('survey.template.model.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Template Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label(__('survey.template.form.title.label'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('description')
                            ->label(__('survey.template.form.description.label'))
                            ->disableToolbarButtons(['attachFiles'])
                            ->columnSpanFull(),
                        Forms\Components\Select::make('status')
                            ->label(__('survey.template.form.status.label'))
                            ->options(SurveyTemplateStatus::class)
                            ->default(SurveyTemplateStatus::DRAFT)
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->label(__('Survey Type'))
                            ->options(SurveyType::class)
                            ->default(SurveyType::VENDOR_ASSESSMENT)
                            ->required()
                            ->helperText(__('The type of survey this template is used for')),
                        Forms\Components\Hidden::make('created_by_id')
                            ->default(fn () => auth()->id()),
                    ]),
                Forms\Components\Section::make('Questions')
                    ->description(__('survey.template.form.questions.description'))
                    ->schema([
                        Forms\Components\Repeater::make('questions')
                            ->relationship()
                            ->label('')
                            ->orderColumn('sort_order')
                            ->reorderable()
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(fn (array $state): ?string => $state['question_text'] ?? 'New Question')
                            ->schema([
                                Forms\Components\TextInput::make('question_text')
                                    ->label(__('survey.template.form.questions.question_text'))
                                    ->required()
                                    ->maxLength(1000)
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('question_type')
                                    ->label(__('survey.template.form.questions.question_type'))
                                    ->options(QuestionType::class)
                                    ->default(QuestionType::TEXT)
                                    ->required()
                                    ->live(onBlur: false)
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('options', [])),
                                Forms\Components\Toggle::make('is_required')
                                    ->label(__('survey.template.form.questions.is_required'))
                                    ->default(false),
                                Forms\Components\Toggle::make('allow_comments')
                                    ->label(__('survey.template.form.questions.allow_comments'))
                                    ->helperText(__('survey.template.form.questions.allow_comments_helper'))
                                    ->default(true),
                                Forms\Components\TextInput::make('help_text')
                                    ->label(__('survey.template.form.questions.help_text'))
                                    ->maxLength(500)
                                    ->columnSpanFull(),
                                Forms\Components\Repeater::make('options')
                                    ->label(__('survey.template.form.questions.options'))
                                    ->schema([
                                        Forms\Components\TextInput::make('label')
                                            ->label('Option Label')
                                            ->required(),
                                    ])
                                    ->columns(1)
                                    ->addActionLabel('Add Option')
                                    ->visible(function (Forms\Get $get): bool {
                                        $type = $get('question_type');
                                        if ($type instanceof QuestionType) {
                                            $type = $type->value;
                                        }

                                        return in_array($type, [
                                            QuestionType::SINGLE_CHOICE->value,
                                            QuestionType::MULTIPLE_CHOICE->value,
                                        ]);
                                    })
                                    ->minItems(function (Forms\Get $get): int {
                                        $type = $get('question_type');
                                        if ($type instanceof QuestionType) {
                                            $type = $type->value;
                                        }

                                        return in_array($type, [
                                            QuestionType::SINGLE_CHOICE->value,
                                            QuestionType::MULTIPLE_CHOICE->value,
                                        ]) ? 2 : 0;
                                    })
                                    ->columnSpanFull(),
                                Forms\Components\Fieldset::make('Risk Scoring')
                                    ->schema([
                                        Forms\Components\TextInput::make('risk_weight')
                                            ->label('Risk Weight')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->suffix('%')
                                            ->helperText('Importance of this question (0-100). 0 = not scored.'),
                                        Forms\Components\Select::make('risk_impact')
                                            ->label('Risk Impact')
                                            ->options(RiskImpact::class)
                                            ->default(RiskImpact::NEUTRAL)
                                            ->helperText('How does a "Yes" answer affect risk?'),
                                        Forms\Components\KeyValue::make('option_scores')
                                            ->label('Option Scores')
                                            ->keyLabel('Option Value')
                                            ->valueLabel('Risk Score (0-100)')
                                            ->helperText('Map each option to a risk score. 0 = no risk, 100 = maximum risk.')
                                            ->visible(function (Forms\Get $get): bool {
                                                $type = $get('question_type');
                                                if ($type instanceof QuestionType) {
                                                    $type = $type->value;
                                                }

                                                return in_array($type, [
                                                    QuestionType::SINGLE_CHOICE->value,
                                                    QuestionType::MULTIPLE_CHOICE->value,
                                                ]);
                                            })
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Add Question'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('questions_count')
                    ->label(__('survey.template.table.columns.questions_count'))
                    ->counts('questions')
                    ->sortable(),
                Tables\Columns\TextColumn::make('surveys_count')
                    ->label(__('survey.template.table.columns.surveys_count'))
                    ->counts('surveys')
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
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('survey.template.table.columns.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(SurveyTemplateStatus::class)
                    ->label(__('survey.template.table.filters.status')),
                Tables\Filters\SelectFilter::make('type')
                    ->options(SurveyType::class)
                    ->label(__('Type')),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
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
            ->emptyStateHeading(__('survey.template.table.empty_state.heading'))
            ->emptyStateDescription(__('survey.template.table.empty_state.description'));
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make(__('survey.template.infolist.section_title'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('title')
                            ->label(__('survey.template.form.title.label'))
                            ->columnSpanFull(),
                        TextEntry::make('status')
                            ->label(__('survey.template.form.status.label'))
                            ->badge(),
                        TextEntry::make('type')
                            ->label(__('Type'))
                            ->badge(),
                        TextEntry::make('createdBy.name')
                            ->label(__('survey.template.table.columns.created_by')),
                        TextEntry::make('description')
                            ->label(__('survey.template.form.description.label'))
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SurveysRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSurveyTemplates::route('/'),
            'create' => Pages\CreateSurveyTemplate::route('/create'),
            'view' => Pages\ViewSurveyTemplate::route('/{record}'),
            'edit' => Pages\EditSurveyTemplate::route('/{record}/edit'),
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
