<?php

namespace App\Filament\Admin\Pages\Settings\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;

class SurveySettingsSchema
{
    public static function schema(): array
    {
        return [
            Section::make(__('Survey Invitation Email'))
                ->description(__('Email template sent when a survey is assigned to internal users or external respondents'))
                ->schema([
                    TextInput::make('mail.templates.survey_invitation_subject')
                        ->label(__('Subject'))
                        ->helperText(__('Available variables: {{ $surveyTitle }}'))
                        ->columnSpanFull(),
                    RichEditor::make('mail.templates.survey_invitation_body')
                        ->label(__('Body'))
                        ->helperText(__('Available variables: {{ $name }}, {{ $email }}, {{ $surveyUrl }}, {{ $surveyTitle }}, {{ $dueDate }}, {{ $description }}'))
                        ->disableToolbarButtons([
                            'image',
                            'attachFiles',
                        ])
                        ->columnSpanFull(),
                ]),

            Section::make(__('Vendor Survey Assigned Email'))
                ->description(__('Email template sent when a survey is assigned to a vendor contact'))
                ->schema([
                    TextInput::make('mail.templates.vendor_survey_assigned_subject')
                        ->label(__('Subject'))
                        ->placeholder('New survey assigned: {{ $surveyTitle }}')
                        ->helperText(__('Available variables: {{ $surveyTitle }}'))
                        ->columnSpanFull(),
                    RichEditor::make('mail.templates.vendor_survey_assigned_body')
                        ->label(__('Body'))
                        ->helperText(__('Available variables: {{ $name }}, {{ $vendorName }}, {{ $surveyTitle }}, {{ $dueDate }}, {{ $portalUrl }}'))
                        ->disableToolbarButtons([
                            'image',
                            'attachFiles',
                        ])
                        ->columnSpanFull(),
                ]),

            Section::make(__('Survey Reminder Email'))
                ->description(__('Email template sent as a reminder when a survey is approaching its due date'))
                ->schema([
                    TextInput::make('mail.templates.vendor_survey_reminder_subject')
                        ->label(__('Subject'))
                        ->placeholder('Reminder: {{ $surveyTitle }} due in {{ $daysRemaining }} days')
                        ->helperText(__('Available variables: {{ $surveyTitle }}, {{ $daysRemaining }}'))
                        ->columnSpanFull(),
                    RichEditor::make('mail.templates.vendor_survey_reminder_body')
                        ->label(__('Body'))
                        ->helperText(__('Available variables: {{ $name }}, {{ $surveyTitle }}, {{ $dueDate }}, {{ $daysRemaining }}, {{ $portalUrl }}'))
                        ->disableToolbarButtons([
                            'image',
                            'attachFiles',
                        ])
                        ->columnSpanFull(),
                ]),
        ];
    }
}
