<?php

namespace App\Filament\Admin\Pages\Settings\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;

class MailTemplatesSchema
{
    public static function schema(): array
    {
        return [
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
        ];
    }
}
