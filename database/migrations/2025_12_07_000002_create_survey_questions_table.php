<?php

use App\Enums\QuestionType;
use App\Enums\RiskImpact;
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
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_template_id')->constrained()->onDelete('cascade');
            $table->text('question_text');
            $table->string('question_type')->default(QuestionType::TEXT->value);
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->string('help_text')->nullable();
            $table->boolean('allow_comments')->default(true);
            $table->unsignedInteger('risk_weight')->default(0)->comment('Importance weight 0-100 for risk calculation');
            $table->string('risk_impact')->default(RiskImpact::NEUTRAL->value)->comment('Whether positive answers reduce or increase risk');
            $table->json('option_scores')->nullable()->comment('JSON mapping of option values to risk scores for choice questions');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_questions');
    }
};
