<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable(); // Null until user sets password via magic link
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vendor_id', 'is_primary']);
        });

        // Add primary_contact_id to vendors table
        Schema::table('vendors', function (Blueprint $table) {
            $table->foreignId('primary_contact_id')->nullable()->after('vendor_manager_id')->constrained('vendor_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('primary_contact_id');
        });

        Schema::dropIfExists('vendor_users');
    }
};
