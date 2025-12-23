<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->unsignedInteger('risk_score')->nullable()->after('risk_rating')
                ->comment('Calculated overall risk score 0-100');
            $table->timestamp('risk_score_calculated_at')->nullable()->after('risk_score');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['risk_score', 'risk_score_calculated_at']);
        });
    }
};
