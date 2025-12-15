<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            // General Settings
            ['key' => 'vendor_portal.enabled', 'value' => json_encode(true)],
            ['key' => 'vendor_portal.name', 'value' => json_encode('Vendor Portal')],

            // Risk Scoring Thresholds
            ['key' => 'vendor_portal.risk_threshold_very_low', 'value' => json_encode(20)],
            ['key' => 'vendor_portal.risk_threshold_low', 'value' => json_encode(40)],
            ['key' => 'vendor_portal.risk_threshold_medium', 'value' => json_encode(60)],
            ['key' => 'vendor_portal.risk_threshold_high', 'value' => json_encode(80)],

            // Magic Link Settings
            ['key' => 'vendor_portal.magic_link_expiry_hours', 'value' => json_encode(24)],

            // Session Settings
            ['key' => 'vendor_portal.session_timeout_minutes', 'value' => json_encode(120)],

            // Vendor Email Templates
            ['key' => 'mail.templates.vendor_invitation_subject', 'value' => json_encode('You have been invited to the Vendor Portal')],
            ['key' => 'mail.templates.vendor_invitation_body', 'value' => json_encode('<p>Hello {{ $name }},</p><p>You have been invited to access the Vendor Portal for {{ $vendorName }}.</p><p>Click the link below to set up your account:</p><p><a href="{{ $magicLinkUrl }}">Access Vendor Portal</a></p><p>This link will expire in 24 hours.</p>')],

            ['key' => 'mail.templates.vendor_magic_link_subject', 'value' => json_encode('Your login link for the Vendor Portal')],
            ['key' => 'mail.templates.vendor_magic_link_body', 'value' => json_encode('<p>Hello {{ $name }},</p><p>Click the link below to access the Vendor Portal:</p><p><a href="{{ $magicLinkUrl }}">Login to Vendor Portal</a></p><p>This link will expire at {{ $expiresAt }}.</p>')],

            ['key' => 'mail.templates.vendor_survey_assigned_subject', 'value' => json_encode('New survey assigned: {{ $surveyTitle }}')],
            ['key' => 'mail.templates.vendor_survey_assigned_body', 'value' => json_encode('<p>Hello {{ $name }},</p><p>A new survey has been assigned to {{ $vendorName }}:</p><p><strong>{{ $surveyTitle }}</strong></p><p>Due Date: {{ $dueDate }}</p><p><a href="{{ $portalUrl }}">Access Vendor Portal</a></p>')],

            ['key' => 'mail.templates.vendor_survey_reminder_subject', 'value' => json_encode('Reminder: {{ $surveyTitle }} due in {{ $daysRemaining }} days')],
            ['key' => 'mail.templates.vendor_survey_reminder_body', 'value' => json_encode('<p>Hello {{ $name }},</p><p>This is a reminder that the following survey is due soon:</p><p><strong>{{ $surveyTitle }}</strong></p><p>Due Date: {{ $dueDate }} ({{ $daysRemaining }} days remaining)</p><p><a href="{{ $portalUrl }}">Complete Survey</a></p>')],

            ['key' => 'mail.templates.vendor_document_expiring_subject', 'value' => json_encode('Document expiring: {{ $documentTitle }}')],
            ['key' => 'mail.templates.vendor_document_expiring_body', 'value' => json_encode('<p>Hello {{ $name }},</p><p>The following document is expiring soon:</p><p><strong>{{ $documentTitle }}</strong></p><p>Expiration Date: {{ $expirationDate }} ({{ $daysRemaining }} days remaining)</p><p>Please upload an updated version at your earliest convenience.</p>')],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }

    public function down(): void
    {
        $keys = [
            'vendor_portal.enabled',
            'vendor_portal.name',
            'vendor_portal.risk_threshold_very_low',
            'vendor_portal.risk_threshold_low',
            'vendor_portal.risk_threshold_medium',
            'vendor_portal.risk_threshold_high',
            'vendor_portal.magic_link_expiry_hours',
            'vendor_portal.session_timeout_minutes',
            'mail.templates.vendor_invitation_subject',
            'mail.templates.vendor_invitation_body',
            'mail.templates.vendor_magic_link_subject',
            'mail.templates.vendor_magic_link_body',
            'mail.templates.vendor_survey_assigned_subject',
            'mail.templates.vendor_survey_assigned_body',
            'mail.templates.vendor_survey_reminder_subject',
            'mail.templates.vendor_survey_reminder_body',
            'mail.templates.vendor_document_expiring_subject',
            'mail.templates.vendor_document_expiring_body',
        ];

        DB::table('settings')->whereIn('key', $keys)->delete();
    }
};
