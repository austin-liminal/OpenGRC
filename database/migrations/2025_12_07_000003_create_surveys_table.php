<?php

use App\Enums\SurveyStatus;
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
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_template_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->longText('description')->nullable();
            $table->string('status')->default(SurveyStatus::DRAFT->value);
            $table->unsignedInteger('risk_score')->nullable()->comment('Calculated risk score 0-100 after survey completion');
            $table->timestamp('risk_score_calculated_at')->nullable();
            $table->string('respondent_email')->nullable();
            $table->string('respondent_name')->nullable();
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->string('access_token', 64)->unique()->nullable();
            $table->timestamp('completed_at')->nullable();
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
        Schema::dropIfExists('surveys');
    }
};
