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
        // Create pivot table for many-to-many relationship
        Schema::create('audit_item_data_request', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('data_request_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['audit_item_id', 'data_request_id']);
        });

        // Migrate existing data from audit_item_id column
        DB::table('data_requests')->whereNotNull('audit_item_id')->orderBy('id')->each(function ($dataRequest) {
            DB::table('audit_item_data_request')->insert([
                'audit_item_id' => $dataRequest->audit_item_id,
                'data_request_id' => $dataRequest->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        // Make audit_item_id nullable (keep for backward compatibility during transition)
        Schema::table('data_requests', function (Blueprint $table) {
            $table->foreignId('audit_item_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore audit_item_id values from pivot table (take first relationship)
        DB::table('audit_item_data_request')
            ->select('data_request_id', DB::raw('MIN(audit_item_id) as audit_item_id'))
            ->groupBy('data_request_id')
            ->orderBy('data_request_id')
            ->each(function ($pivot) {
                DB::table('data_requests')
                    ->where('id', $pivot->data_request_id)
                    ->update(['audit_item_id' => $pivot->audit_item_id]);
            });

        // Make audit_item_id non-nullable again
        Schema::table('data_requests', function (Blueprint $table) {
            $table->foreignId('audit_item_id')->nullable(false)->change();
        });

        Schema::dropIfExists('audit_item_data_request');
    }
};
