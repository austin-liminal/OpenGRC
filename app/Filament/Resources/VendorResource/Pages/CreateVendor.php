<?php

namespace App\Filament\Resources\VendorResource\Pages;

use App\Enums\VendorRiskRating;
use App\Enums\VendorStatus;
use App\Filament\Resources\VendorResource;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\HtmlString;

class CreateVendor extends CreateRecord
{
    use HasWizard;

    protected static string $resource = VendorResource::class;

    protected function getSteps(): array
    {
        return [
            Step::make(__('Vendor Information'))
                ->icon('heroicon-o-building-storefront')
                ->description(__('Basic vendor details'))
                ->schema([
                    Section::make(__('Vendor Details'))
                        ->description(__('Enter the basic information about this vendor.'))
                        ->schema([
                            TextInput::make('name')
                                ->label(__('Vendor Name'))
                                ->required()
                                ->maxLength(255)
                                ->placeholder(__('e.g., Acme Corporation'))
                                ->helperText(__('The official name of the vendor or third-party organization.')),
                            Textarea::make('description')
                                ->label(__('Description'))
                                ->maxLength(65535)
                                ->rows(3)
                                ->placeholder(__('Brief description of what this vendor provides...'))
                                ->helperText(__('Describe the products or services this vendor provides to your organization.')),
                            TextInput::make('url')
                                ->label(__('Website URL'))
                                ->url()
                                ->maxLength(512)
                                ->placeholder(__('https://example.com'))
                                ->helperText(__('The vendor\'s primary website.')),
                            Select::make('vendor_manager_id')
                                ->label(__('Vendor Relationship Manager'))
                                ->relationship('vendorManager', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->helperText(__('The internal user responsible for managing this vendor relationship.')),
                        ])
                        ->columns(1),

                    Section::make(__('Additional Information'))
                        ->description(__('Optional notes and logo.'))
                        ->collapsible()
                        ->collapsed()
                        ->schema([
                            Textarea::make('notes')
                                ->label(__('Internal Notes'))
                                ->maxLength(65535)
                                ->rows(3)
                                ->placeholder(__('Any additional notes about this vendor...'))
                                ->helperText(__('Private notes visible only to your team.')),
                            FileUpload::make('logo')
                                ->label(__('Vendor Logo'))
                                ->disk(config('filesystems.default'))
                                ->directory('vendor-logos')
                                ->image()
                                ->imageResizeMode('cover')
                                ->imageCropAspectRatio('1:1')
                                ->imageResizeTargetWidth('200')
                                ->imageResizeTargetHeight('200')
                                ->visibility('private')
                                ->maxSize(1024)
                                ->helperText(__('Optional. Upload a logo for easy identification (max 1MB).')),
                        ])
                        ->columns(1),
                ]),

            Step::make(__('Vendor Contact'))
                ->icon('heroicon-o-user-circle')
                ->description(__('Primary contact information'))
                ->schema([
                    Section::make(__('Vendor Contact'))
                        ->description(__('Enter the primary contact information for this vendor.'))
                        ->schema([
                            TextInput::make('contact_name')
                                ->label(__('Contact Name'))
                                ->maxLength(255)
                                ->placeholder(__('e.g., John Smith'))
                                ->helperText(__('Main point of contact at the vendor.')),
                            TextInput::make('contact_email')
                                ->label(__('Contact Email'))
                                ->email()
                                ->maxLength(255)
                                ->placeholder(__('john@example.com'))
                                ->helperText(__('Email address for vendor communications.')),
                            TextInput::make('contact_phone')
                                ->label(__('Contact Phone'))
                                ->tel()
                                ->maxLength(255)
                                ->placeholder(__('(555) 123-4567'))
                                ->helperText(__('Phone number for vendor contact.')),
                            Textarea::make('address')
                                ->label(__('Physical Address'))
                                ->rows(3)
                                ->placeholder(__('123 Main St, City, State, ZIP'))
                                ->helperText(__('Vendor\'s physical address.')),
                        ])
                        ->columns(1),
                ]),

            Step::make(__('Risk Assessment'))
                ->icon('heroicon-o-shield-exclamation')
                ->description(__('Evaluate vendor risk'))
                ->schema([
                    Section::make(__('Organizational Risk Rating'))
                        ->description(__('Assess the potential impact this vendor could have on your organization.'))
                        ->schema([
                            Placeholder::make('risk_explanation')
                                ->label('')
                                ->content(new HtmlString('
                                    <div class="prose prose-sm dark:prose-invert max-w-none">
                                        <p>The <strong>Organizational Risk Rating</strong> represents the potential impact this vendor could have on your organization <em>before</em> any controls or mitigations are applied.</p>
                                        <p class="mt-2">Consider factors such as:</p>
                                        <ul class="mt-1">
                                            <li><strong>Data Access</strong> - Does this vendor have access to sensitive data (PII, financial, health)?</li>
                                            <li><strong>System Access</strong> - Does the vendor connect to your internal systems or networks?</li>
                                            <li><strong>Business Criticality</strong> - How dependent is your organization on this vendor?</li>
                                            <li><strong>Regulatory Impact</strong> - Could a vendor incident affect your compliance status?</li>
                                        </ul>
                                    </div>
                                ')),
                            Select::make('risk_rating')
                                ->label(__('Organizational Risk Rating'))
                                ->enum(VendorRiskRating::class)
                                ->options(collect(VendorRiskRating::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()]))
                                ->required()
                                ->default(VendorRiskRating::MEDIUM->value)
                                ->helperText(__('Select the risk level based on the potential impact to your organization.'))
                                ->live(),
                            Placeholder::make('risk_guidance')
                                ->label('')
                                ->content(function (Get $get) {
                                    $rating = $get('risk_rating');
                                    if (! $rating) {
                                        return '';
                                    }

                                    $guidance = match ($rating) {
                                        VendorRiskRating::CRITICAL->value => '<div class="p-3 bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800 rounded-lg"><p class="text-danger-700 dark:text-danger-400 font-medium">Critical Risk</p><p class="text-sm text-danger-600 dark:text-danger-500 mt-1">This vendor requires immediate and thorough due diligence. Consider comprehensive security assessments, on-site audits, and continuous monitoring.</p></div>',
                                        VendorRiskRating::HIGH->value => '<div class="p-3 bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800 rounded-lg"><p class="text-danger-700 dark:text-danger-400 font-medium">High Risk</p><p class="text-sm text-danger-600 dark:text-danger-500 mt-1">This vendor should undergo detailed security questionnaires and regular assessments. Consider requesting SOC 2 reports or similar certifications.</p></div>',
                                        VendorRiskRating::MEDIUM->value => '<div class="p-3 bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-800 rounded-lg"><p class="text-warning-700 dark:text-warning-400 font-medium">Medium Risk</p><p class="text-sm text-warning-600 dark:text-warning-500 mt-1">Standard vendor assessment is recommended. Send a security questionnaire and review annually.</p></div>',
                                        VendorRiskRating::LOW->value => '<div class="p-3 bg-info-50 dark:bg-info-900/20 border border-info-200 dark:border-info-800 rounded-lg"><p class="text-info-700 dark:text-info-400 font-medium">Low Risk</p><p class="text-sm text-info-600 dark:text-info-500 mt-1">Basic due diligence is sufficient. Consider a simplified questionnaire or self-attestation.</p></div>',
                                        VendorRiskRating::VERY_LOW->value => '<div class="p-3 bg-success-50 dark:bg-success-900/20 border border-success-200 dark:border-success-800 rounded-lg"><p class="text-success-700 dark:text-success-400 font-medium">Very Low Risk</p><p class="text-sm text-success-600 dark:text-success-500 mt-1">Minimal assessment needed. Document the vendor relationship and review periodically.</p></div>',
                                        default => '',
                                    };

                                    return new HtmlString($guidance);
                                }),
                        ])
                        ->columns(1),

                    Section::make(__('Vendor Status'))
                        ->description(__('Set the current status of this vendor relationship.'))
                        ->schema([
                            Select::make('status')
                                ->label(__('Status'))
                                ->enum(VendorStatus::class)
                                ->options(collect(VendorStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()]))
                                ->required()
                                ->default(VendorStatus::PENDING->value)
                                ->helperText(__('New vendors typically start as "Pending" until assessment is complete.')),
                        ])
                        ->columns(1),
                ]),

            Step::make(__('Summary'))
                ->icon('heroicon-o-check-circle')
                ->description(__('Review and create'))
                ->schema([
                    Section::make(__('Review Vendor Information'))
                        ->description(__('Please review the information below before creating the vendor.'))
                        ->schema([
                            Placeholder::make('summary_name')
                                ->label(__('Vendor Name'))
                                ->content(fn (Get $get) => $get('name') ?: '-'),
                            Placeholder::make('summary_description')
                                ->label(__('Description'))
                                ->content(fn (Get $get) => $get('description') ?: '-'),
                            Placeholder::make('summary_url')
                                ->label(__('Website'))
                                ->content(fn (Get $get) => $get('url') ?: '-'),
                            Placeholder::make('summary_contact')
                                ->label(__('Contact'))
                                ->content(fn (Get $get) => $get('contact_name') ?: '-'),
                            Placeholder::make('summary_risk')
                                ->label(__('Risk Rating'))
                                ->content(fn (Get $get) => $get('risk_rating') ?: '-'),
                            Placeholder::make('summary_status')
                                ->label(__('Status'))
                                ->content(fn (Get $get) => $get('status') ?: '-'),
                        ])
                        ->columns(2),

                    Section::make(__('Next Steps'))
                        ->icon('heroicon-o-arrow-right-circle')
                        ->schema([
                            Placeholder::make('next_steps')
                                ->label('')
                                ->content(new HtmlString('
                                    <div class="prose prose-sm dark:prose-invert max-w-none">
                                        <p>After creating this vendor, you can:</p>
                                        <ol class="mt-2">
                                            <li><strong>Send a Vendor Survey</strong> - Assess the vendor\'s security posture by sending them a questionnaire from the Vendor Manager page.</li>
                                            <li><strong>Add Vendor Contacts</strong> - Add the vendor\'s team members who will respond to surveys and manage the relationship.</li>
                                            <li><strong>Upload Documents</strong> - Attach relevant documents like contracts, certifications, or audit reports.</li>
                                            <li><strong>Link Applications</strong> - Associate any applications or systems provided by this vendor.</li>
                                        </ol>
                                        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Click <strong>Create</strong> below to add this vendor to your organization.</p>
                                    </div>
                                ')),
                        ]),
                ]),
        ];
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('Vendor created successfully');
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.app.pages.vendor-manager');
    }
}
