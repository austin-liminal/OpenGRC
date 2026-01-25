<?php

namespace App\Filament\Admin\Pages\Settings\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

class VendorPortalMailSchema
{
    public static function schema(): array
    {
        return [
            Section::make(__('Vendor Invitation Email'))
                ->description(__('Email sent when inviting a new vendor contact to the portal'))
                ->schema([
                    TextInput::make('mail.templates.vendor_invitation_subject')
                        ->label(__('Subject'))
                        ->placeholder('You have been invited to {{ $vendorName }} Vendor Portal')
                        ->helperText(__('Available variables: {{ $vendorName }}'))
                        ->columnSpanFull(),
                    RichEditor::make('mail.templates.vendor_invitation_body')
                        ->label(__('Body'))
                        ->helperText(__('Available variables: {{ $name }}, {{ $email }}, {{ $vendorName }}, {{ $magicLinkUrl }}'))
                        ->disableToolbarButtons([
                            'image',
                            'attachFiles',
                        ])
                        ->columnSpanFull(),
                ]),

            Section::make(__('Vendor Magic Link Email'))
                ->description(__('Email sent when a vendor requests a login link'))
                ->schema([
                    TextInput::make('mail.templates.vendor_magic_link_subject')
                        ->label(__('Subject'))
                        ->placeholder('Your login link for {{ $vendorName }} Vendor Portal')
                        ->helperText(__('Available variables: {{ $vendorName }}'))
                        ->columnSpanFull(),
                    RichEditor::make('mail.templates.vendor_magic_link_body')
                        ->label(__('Body'))
                        ->helperText(__('Available variables: {{ $name }}, {{ $magicLinkUrl }}, {{ $expiresAt }}'))
                        ->disableToolbarButtons([
                            'image',
                            'attachFiles',
                        ])
                        ->columnSpanFull(),
                ]),

            Section::make(__('Vendor Document Expiring Email'))
                ->description(__('Email sent when a vendor document is approaching its expiration date'))
                ->schema([
                    TextInput::make('mail.templates.vendor_document_expiring_subject')
                        ->label(__('Subject'))
                        ->placeholder('Document expiring: {{ $documentTitle }}')
                        ->helperText(__('Available variables: {{ $documentTitle }}'))
                        ->columnSpanFull(),
                    RichEditor::make('mail.templates.vendor_document_expiring_body')
                        ->label(__('Body'))
                        ->helperText(__('Available variables: {{ $name }}, {{ $documentTitle }}, {{ $expirationDate }}, {{ $daysRemaining }}'))
                        ->disableToolbarButtons([
                            'image',
                            'attachFiles',
                        ])
                        ->columnSpanFull(),
                ]),
        ];
    }
}
