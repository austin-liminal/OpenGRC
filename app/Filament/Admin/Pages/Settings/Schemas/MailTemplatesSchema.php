<?php

namespace App\Filament\Admin\Pages\Settings\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

class MailTemplatesSchema
{
    public static function schema(): array
    {
        return [
            Section::make(__('Password Reset Email'))
                ->description(__('Email sent when a user requests to reset their password'))
                ->schema([
                    TextInput::make('mail.templates.password_reset_subject')
                        ->label(__('Subject'))
                        ->columnSpanFull(),
                    RichEditor::make('mail.templates.password_reset_body')
                        ->label(__('Body'))
                        ->disableToolbarButtons([
                            'image',
                            'attachFiles',
                        ])
                        ->columnSpanFull(),
                ]),

            Section::make(__('New Account Email'))
                ->description(__('Email sent when a new user account is created'))
                ->schema([
                    TextInput::make('mail.templates.new_account_subject')
                        ->label(__('Subject'))
                        ->columnSpanFull(),
                    RichEditor::make('mail.templates.new_account_body')
                        ->label(__('Body'))
                        ->disableToolbarButtons([
                            'image',
                            'attachFiles',
                        ])
                        ->columnSpanFull(),
                ]),

            Section::make(__('Evidence Request Email'))
                ->description(__('Email sent when evidence is requested from an audit item'))
                ->schema([
                    TextInput::make('mail.templates.evidence_request_subject')
                        ->label(__('Subject'))
                        ->columnSpanFull(),
                    RichEditor::make('mail.templates.evidence_request_body')
                        ->label(__('Body'))
                        ->disableToolbarButtons([
                            'image',
                            'attachFiles',
                        ])
                        ->columnSpanFull(),
                ]),
        ];
    }
}
