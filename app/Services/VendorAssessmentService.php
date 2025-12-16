<?php

namespace App\Services;

use App\Enums\SurveyStatus;
use App\Enums\SurveyTemplateStatus;
use App\Filament\Resources\SurveyResource;
use App\Mail\SurveyInvitationMail;
use App\Models\Survey;
use App\Models\SurveyTemplate;
use App\Models\Vendor;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

class VendorAssessmentService
{
    /**
     * Get the form schema for the Assess Risk action.
     */
    public static function getAssessRiskFormSchema(): array
    {
        return [
            Forms\Components\Radio::make('assessment_type')
                ->label(__('Assessment Type'))
                ->options([
                    'internal' => __('Internal Assessment - Complete the assessment yourself'),
                    'external' => __('Send Survey - Send to vendor contact to complete'),
                ])
                ->default('internal')
                ->required()
                ->live(),
            Forms\Components\Select::make('survey_template_id')
                ->label(__('Survey Template'))
                ->options(SurveyTemplate::where('status', SurveyTemplateStatus::ACTIVE)->pluck('title', 'id'))
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('respondent_email')
                ->label(__('Respondent Email'))
                ->email()
                ->required(fn (Forms\Get $get) => $get('assessment_type') === 'external')
                ->visible(fn (Forms\Get $get) => $get('assessment_type') === 'external')
                ->helperText(__('The email address to send the survey to')),
            Forms\Components\TextInput::make('respondent_name')
                ->label(__('Respondent Name'))
                ->visible(fn (Forms\Get $get) => $get('assessment_type') === 'external')
                ->helperText(__('Name of the person completing the survey')),
            Forms\Components\DatePicker::make('due_date')
                ->label(__('Due Date'))
                ->native(false),
        ];
    }

    /**
     * Handle the Assess Risk action for a vendor.
     *
     * @return \Illuminate\Http\RedirectResponse|null
     */
    public static function handleAssessRisk(Vendor $vendor, array $data)
    {
        $isInternal = $data['assessment_type'] === 'internal';

        $survey = Survey::create([
            'survey_template_id' => $data['survey_template_id'],
            'vendor_id' => $vendor->id,
            'respondent_email' => $isInternal ? null : $data['respondent_email'],
            'respondent_name' => $isInternal ? null : ($data['respondent_name'] ?? null),
            'assigned_to_id' => $isInternal ? auth()->id() : null,
            'due_date' => $data['due_date'] ?? null,
            'status' => $isInternal ? SurveyStatus::DRAFT : SurveyStatus::SENT,
            'created_by_id' => auth()->id(),
        ]);

        if ($isInternal) {
            Notification::make()
                ->title(__('Assessment Created'))
                ->body(__('Internal assessment created. Click to begin.'))
                ->success()
                ->send();

            return redirect(SurveyResource::getUrl('respond-internal', ['record' => $survey]));
        }

        try {
            Mail::send(new SurveyInvitationMail($survey));

            Notification::make()
                ->title(__('Survey Sent'))
                ->body(__('Survey invitation sent to :email', ['email' => $data['respondent_email']]))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('Survey Created'))
                ->body(__('Survey created but email notification failed: ').$e->getMessage())
                ->warning()
                ->send();
        }

        return null;
    }
}
