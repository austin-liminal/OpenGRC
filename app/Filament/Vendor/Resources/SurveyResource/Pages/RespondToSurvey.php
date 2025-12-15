<?php

namespace App\Filament\Vendor\Resources\SurveyResource\Pages;

use App\Enums\QuestionType;
use App\Enums\SurveyStatus;
use App\Filament\Vendor\Resources\SurveyResource;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Services\VendorRiskScoringService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class RespondToSurvey extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = SurveyResource::class;

    protected static string $view = 'filament.vendor.pages.respond-to-survey';

    public Survey|Model|int|string|null $record = null;

    public ?array $data = [];

    public function mount(int|string $record): void
    {
        // Use Filament's record resolution method instead of direct find
        $this->record = $this->resolveRecord($record);

        // Verify vendor access
        $vendorUser = Auth::guard('vendor')->user();
        if ($this->record->vendor_id !== $vendorUser?->vendor_id) {
            abort(403);
        }

        // Check if survey can be responded to
        if (! in_array($this->record->status, [SurveyStatus::SENT, SurveyStatus::IN_PROGRESS])) {
            Notification::make()
                ->title('Survey cannot be modified')
                ->body('This survey has already been completed or is not available for response.')
                ->warning()
                ->send();

            $this->redirect(SurveyResource::getUrl('view', ['record' => $this->record]));

            return;
        }

        // Load existing answers
        $this->loadExistingAnswers();
    }

    protected function resolveRecord(int|string $key): Model
    {
        // Bypass the resource's getEloquentQuery and find directly
        // We'll verify access manually in mount()
        return Survey::findOrFail($key);
    }

    protected function loadExistingAnswers(): void
    {
        $data = [];

        foreach ($this->record->template->questions as $question) {
            $answer = $this->record->answers()
                ->where('survey_question_id', $question->id)
                ->first();

            if ($answer) {
                $data["question_{$question->id}"] = $answer->answer_value;
                $data["comment_{$question->id}"] = $answer->comment;
            }
        }

        $this->data = $data;
        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        $questions = $this->record->template->questions()->orderBy('sort_order')->get();

        $schema = [];

        foreach ($questions as $index => $question) {
            $schema[] = $this->buildQuestionField($question, $index + 1);
        }

        return $form
            ->schema([
                Forms\Components\Section::make($this->record->template->title)
                    ->description($this->record->template->description)
                    ->schema($schema),
            ])
            ->statePath('data');
    }

    protected function buildQuestionField(SurveyQuestion $question, int $number): Forms\Components\Fieldset
    {
        $fieldName = "question_{$question->id}";
        $commentName = "comment_{$question->id}";

        $fields = [];

        // Main question field based on type
        $field = match ($question->question_type) {
            QuestionType::TEXT => Forms\Components\TextInput::make($fieldName)
                ->label("Q{$number}: {$question->question_text}")
                ->helperText($question->help_text)
                ->required($question->is_required)
                ->maxLength(1000),

            QuestionType::LONG_TEXT => Forms\Components\Textarea::make($fieldName)
                ->label("Q{$number}: {$question->question_text}")
                ->helperText($question->help_text)
                ->required($question->is_required)
                ->rows(4)
                ->maxLength(10000),

            QuestionType::BOOLEAN => Forms\Components\Radio::make($fieldName)
                ->label("Q{$number}: {$question->question_text}")
                ->helperText($question->help_text)
                ->required($question->is_required)
                ->options([
                    'yes' => 'Yes',
                    'no' => 'No',
                ])
                ->inline(),

            QuestionType::SINGLE_CHOICE => Forms\Components\Radio::make($fieldName)
                ->label("Q{$number}: {$question->question_text}")
                ->helperText($question->help_text)
                ->required($question->is_required)
                ->options($this->getOptionsFromQuestion($question)),

            QuestionType::MULTIPLE_CHOICE => Forms\Components\CheckboxList::make($fieldName)
                ->label("Q{$number}: {$question->question_text}")
                ->helperText($question->help_text)
                ->required($question->is_required)
                ->options($this->getOptionsFromQuestion($question)),

            QuestionType::FILE => Forms\Components\FileUpload::make($fieldName)
                ->label("Q{$number}: {$question->question_text}")
                ->helperText($question->help_text)
                ->required($question->is_required)
                ->disk(config('filesystems.default'))
                ->directory('survey-attachments')
                ->visibility('private')
                ->maxSize(10240), // 10MB

            default => Forms\Components\TextInput::make($fieldName)
                ->label("Q{$number}: {$question->question_text}")
                ->helperText($question->help_text)
                ->required($question->is_required),
        };

        $fields[] = $field->columnSpanFull();

        // Add comment field if allowed
        if ($question->allow_comments) {
            $fields[] = Forms\Components\Textarea::make($commentName)
                ->label('Additional Comments')
                ->placeholder('Add any additional context or notes...')
                ->rows(2)
                ->maxLength(2000)
                ->columnSpanFull();
        }

        return Forms\Components\Fieldset::make("Question {$number}")
            ->schema($fields)
            ->columnSpanFull();
    }

    protected function getOptionsFromQuestion(SurveyQuestion $question): array
    {
        $options = $question->options ?? [];
        $result = [];

        foreach ($options as $option) {
            $label = $option['label'] ?? $option;
            $result[$label] = $label;
        }

        return $result;
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Update status to in progress if it was sent
        if ($this->record->status === SurveyStatus::SENT) {
            $this->record->update(['status' => SurveyStatus::IN_PROGRESS]);
        }

        // Save each answer
        foreach ($this->record->template->questions as $question) {
            $fieldName = "question_{$question->id}";
            $commentName = "comment_{$question->id}";

            $answerValue = $data[$fieldName] ?? null;
            $comment = $data[$commentName] ?? null;

            SurveyAnswer::updateOrCreate(
                [
                    'survey_id' => $this->record->id,
                    'survey_question_id' => $question->id,
                ],
                [
                    'answer_value' => $answerValue,
                    'comment' => $comment,
                ]
            );
        }

        Notification::make()
            ->title('Progress saved')
            ->body('Your responses have been saved. You can continue later.')
            ->success()
            ->send();
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        // Validate required questions
        $missingRequired = [];
        foreach ($this->record->template->questions as $question) {
            if ($question->is_required) {
                $fieldName = "question_{$question->id}";
                $value = $data[$fieldName] ?? null;

                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    $missingRequired[] = $question->question_text;
                }
            }
        }

        if (! empty($missingRequired)) {
            Notification::make()
                ->title('Required questions not answered')
                ->body('Please answer all required questions before submitting.')
                ->danger()
                ->send();

            return;
        }

        // Save all answers
        foreach ($this->record->template->questions as $question) {
            $fieldName = "question_{$question->id}";
            $commentName = "comment_{$question->id}";

            $answerValue = $data[$fieldName] ?? null;
            $comment = $data[$commentName] ?? null;

            SurveyAnswer::updateOrCreate(
                [
                    'survey_id' => $this->record->id,
                    'survey_question_id' => $question->id,
                ],
                [
                    'answer_value' => $answerValue,
                    'comment' => $comment,
                ]
            );
        }

        // Update survey status to completed
        $this->record->update([
            'status' => SurveyStatus::COMPLETED,
            'completed_at' => now(),
        ]);

        // Calculate risk score
        $scoringService = new VendorRiskScoringService();
        $riskScore = $scoringService->calculateSurveyScore($this->record);

        // Also update vendor's overall risk score
        if ($this->record->vendor) {
            $scoringService->calculateVendorScore($this->record->vendor);
        }

        Notification::make()
            ->title('Survey submitted')
            ->body('Thank you! Your survey response has been submitted successfully.')
            ->success()
            ->send();

        $this->redirect(SurveyResource::getUrl('view', ['record' => $this->record]));
    }

    public function getTitle(): string
    {
        return 'Respond to Survey';
    }

    public function getSubheading(): ?string
    {
        return $this->record->template->title ?? '';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to Surveys')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(SurveyResource::getUrl('index')),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Save Progress')
                ->icon('heroicon-o-bookmark')
                ->color('gray')
                ->action('save'),
            Actions\Action::make('submit')
                ->label('Submit Survey')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->action('submit')
                ->requiresConfirmation()
                ->modalHeading('Submit Survey')
                ->modalDescription('Are you sure you want to submit this survey? You will not be able to make changes after submission.')
                ->modalSubmitActionLabel('Yes, submit survey'),
        ];
    }
}
