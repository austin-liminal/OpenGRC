<?php

use App\Enums\SurveyTemplateStatus;
use App\Enums\SurveyType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('survey_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('status')->default(SurveyTemplateStatus::DRAFT->value);
            $table->string('type')->default(SurveyType::VENDOR_ASSESSMENT->value);
            $table->boolean('is_public')->default(false);
            $table->foreignId('created_by_id')->constrained('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_templates');
    }
};
