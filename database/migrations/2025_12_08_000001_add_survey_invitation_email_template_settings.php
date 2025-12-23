<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $settings = [
            [
                'key' => 'mail.templates.survey_invitation_subject',
                'value' => 'Survey Invitation: {{ $surveyTitle }}',
            ],
            [
                'key' => 'mail.templates.survey_invitation_body',
                'value' => '<h1>Survey Invitation</h1><p>Hello, {{ $name }}!</p><p>You have been invited to complete a survey: <strong>{{ $surveyTitle }}</strong></p>@if($description){!! $description !!}@endif<p>Please click the link below to access the survey:</p><p><a href="{{ $surveyUrl }}">{{ $surveyUrl }}</a></p>@if($dueDate)<p><strong>Due Date:</strong> {{ $dueDate }}</p>@endif<p>Thank you for your participation.</p>',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                ['value' => json_encode($setting['value'])]
            );
        }

        // Clear the settings cache so new values are picked up
        Cache::forget(config('settings.cache_key', 'settings_'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', [
                'mail.templates.survey_invitation_subject',
                'mail.templates.survey_invitation_body',
            ])
            ->delete();
    }
};
