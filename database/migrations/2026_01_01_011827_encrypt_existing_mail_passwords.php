<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration encrypts any existing plaintext mail.password values
     * in the settings table to address security vulnerability where
     * SMTP credentials were stored unencrypted.
     */
    public function up(): void
    {
        // Get the current mail.password setting
        $setting = DB::table('settings')
            ->where('key', 'mail.password')
            ->first();

        if (! $setting || empty($setting->value)) {
            return;
        }

        // The value is stored as JSON, so decode it
        $password = json_decode($setting->value, true);

        if (empty($password)) {
            return;
        }

        // Check if the password is already encrypted by attempting to decrypt it
        try {
            Crypt::decryptString($password);
            // If decryption succeeds, it's already encrypted - nothing to do
            Log::info('Mail password is already encrypted, skipping migration.');

            return;
        } catch (\Exception $e) {
            // Decryption failed, so the password is plaintext and needs encryption
        }

        // Encrypt the plaintext password
        $encryptedPassword = Crypt::encryptString($password);

        // Update the setting with the encrypted value
        DB::table('settings')
            ->where('key', 'mail.password')
            ->update(['value' => json_encode($encryptedPassword)]);

        Log::info('Mail password has been encrypted successfully.');
    }

    /**
     * Reverse the migrations.
     *
     * Note: We cannot reverse this migration as we cannot decrypt
     * the password without knowing the original plaintext value.
     * This is intentional for security reasons.
     */
    public function down(): void
    {
        // Cannot reverse encryption - this is intentional for security
        Log::warning('Cannot reverse mail password encryption migration. This is intentional for security.');
    }
};
