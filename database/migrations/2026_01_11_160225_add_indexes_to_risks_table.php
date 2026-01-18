<?php

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
        Schema::table('risks', function (Blueprint $table) {
            // Composite index for inherent risk widget and filtering
            $table->index(['inherent_likelihood', 'inherent_impact'], 'risks_inherent_idx');
            // Composite index for residual risk widget and filtering
            $table->index(['residual_likelihood', 'residual_impact'], 'risks_residual_idx');
            // Index for sorting by residual_risk (default table sort)
            $table->index('residual_risk', 'risks_residual_risk_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('risks', function (Blueprint $table) {
            $table->dropIndex('risks_inherent_idx');
            $table->dropIndex('risks_residual_idx');
            $table->dropIndex('risks_residual_risk_idx');
        });
    }
};
