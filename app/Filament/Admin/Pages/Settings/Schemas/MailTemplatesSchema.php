<?php

namespace App\Filament\Admin\Pages\Settings\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;

class MailTemplatesSchema
{
    public static function schema(): array
    {
        return [
            Section::make('Internal User Templates')
                ->schema([
                    TextInput::make('mail.templates.password_reset_subject')
                        ->label('Password Reset Subject')
                        ->columnSpanFull(),
                    RichEditor::make('mail.templates.password_reset_body')
                        ->label('Password Reset Body')
                        ->disableToolbarButtons([
                            'image',
                            'attachFiles',
                        ])
                        ->columnSpanFull(),
                    TextInput::make('mail.templates.new_account_subject')
                        ->label('New Account Subject')
                        ->columnSpanFull(),
                    RichEditor::make('mail.templates.new_account_body')
                        ->label('New Account Body')
                        ->disableToolbarButtons([
                            'image',
                            'attachFiles',
                        ])
                        ->columnSpanFull(),
                    TextInput::make('mail.templates.evidence_request_subject')
                        ->label('Evidence Request Subject')
                        ->columnSpanFull(),
                    RichEditor::make('mail.templates.evidence_request_body')
                        ->label('Evidence Request Body')
                        ->disableToolbarButtons([
                            'image',
                            'attachFiles',
                        ])
                        ->columnSpanFull(),
                    TextInput::make('mail.templates.survey_invitation_subject')
                        ->label('Survey Invitation Subject')
                        ->helperText('Available variables: {{ $surveyTitle }}')
                        ->columnSpanFull(),
                    RichEditor::make('mail.templates.survey_invitation_body')
                        ->label('Survey Invitation Body')
                        ->helperText('Available variables: {{ $name }}, {{ $email }}, {{ $surveyUrl }}, {{ $surveyTitle }}, {{ $dueDate }}, {{ $description }}')
                        ->disableToolbarButtons([
                            'image',
                            'attachFiles',
                        ])
                        ->columnSpanFull(),
                ]),

            Section::make('Vendor Portal Templates')
                ->schema([
                    TextInput::make('mail.templates.vendor_invitation_subject')
                        ->label('Vendor Invitation Subject')
                        ->placeholder('You have been invited to {{ $vendorName }} Vendor Portal')
                        ->helperText('Available variables: {{ $vendorName }}')
                        ->columnSpanFull(),
                    RichEditor::make('mail.templates.vendor_invitation_body')
                        ->label('Vendor Invitation Body')
                        ->helperText('Available variables: {{ $name }}, {{ $email }}, {{ $vendorName }}, {{ $magicLinkUrl }}')
                        ->disableToolbarButtons([
                            'image',
                            'attachFiles',
                        ])
                        ->columnSpanFull(),

                    TextInput::make('mail.templates.vendor_magic_link_subject')
                        ->label('Vendor Magic Link Subject')
                        ->placeholder('Your login link for {{ $vendorName }} Vendor Portal')
                        ->helperText('Available variables: {{ $vendorName }}')
                        ->columnSpanFull(),
                    RichEditor::make('mail.templates.vendor_magic_link_body')
                        ->label('Vendor Magic Link Body')
                        ->helperText('Available variables: {{ $name }}, {{ $magicLinkUrl }}, {{ $expiresAt }}')
                        ->disableToolbarButtons([
                            'image',
                            'attachFiles',
                        ])
                        ->columnSpanFull(),

                    TextInput::make('mail.templates.vendor_survey_assigned_subject')
                        ->label('Vendor Survey Assigned Subject')
                        ->placeholder('New survey assigned: {{ $surveyTitle }}')
                        ->helperText('Available variables: {{ $surveyTitle }}')
                        ->columnSpanFull(),
                    RichEditor::make('mail.templates.vendor_survey_assigned_body')
                        ->label('Vendor Survey Assigned Body')
                        ->helperText('Available variables: {{ $name }}, {{ $vendorName }}, {{ $surveyTitle }}, {{ $dueDate }}, {{ $portalUrl }}')
                        ->disableToolbarButtons([
                            'image',
                            'attachFiles',
                        ])
                        ->columnSpanFull(),

                    TextInput::make('mail.templates.vendor_survey_reminder_subject')
                        ->label('Vendor Survey Reminder Subject')
                        ->placeholder('Reminder: {{ $surveyTitle }} due in {{ $daysRemaining }} days')
                        ->helperText('Available variables: {{ $surveyTitle }}, {{ $daysRemaining }}')
                        ->columnSpanFull(),
                    RichEditor::make('mail.templates.vendor_survey_reminder_body')
                        ->label('Vendor Survey Reminder Body')
                        ->helperText('Available variables: {{ $name }}, {{ $surveyTitle }}, {{ $dueDate }}, {{ $daysRemaining }}, {{ $portalUrl }}')
                        ->disableToolbarButtons([
                            'image',
                            'attachFiles',
                        ])
                        ->columnSpanFull(),

                    TextInput::make('mail.templates.vendor_document_expiring_subject')
                        ->label('Vendor Document Expiring Subject')
                        ->placeholder('Document expiring: {{ $documentTitle }}')
                        ->helperText('Available variables: {{ $documentTitle }}')
                        ->columnSpanFull(),
                    RichEditor::make('mail.templates.vendor_document_expiring_body')
                        ->label('Vendor Document Expiring Body')
                        ->helperText('Available variables: {{ $name }}, {{ $documentTitle }}, {{ $expirationDate }}, {{ $daysRemaining }}')
                        ->disableToolbarButtons([
                            'image',
                            'attachFiles',
                        ])
                        ->columnSpanFull(),
                ]),
        ];
    }
}
