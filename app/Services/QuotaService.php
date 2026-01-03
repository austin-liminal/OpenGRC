<?php

namespace App\Services;

use App\Enums\QuotaType;
use App\Exceptions\QuotaExceededException;
use Illuminate\Support\Facades\Cache;

class QuotaService
{
    /**
     * Check if a quota type has remaining capacity.
     *
     * @throws QuotaExceededException
     */
    public static function check(QuotaType $quotaType, int $requiredAmount = 0): bool
    {
        $limit = self::getLimit($quotaType);

        // If limit is 0 or negative, quota is disabled
        if ($limit <= 0) {
            return true;
        }

        $currentUsage = self::getUsage($quotaType);

        if (($currentUsage + $requiredAmount) > $limit) {
            throw new QuotaExceededException($quotaType, $currentUsage, $limit);
        }

        return true;
    }

    /**
     * Check quota without throwing exception - returns remaining capacity.
     */
    public static function checkAvailable(QuotaType $quotaType): int
    {
        $limit = self::getLimit($quotaType);

        // If limit is 0 or negative, quota is unlimited
        if ($limit <= 0) {
            return PHP_INT_MAX;
        }

        $currentUsage = self::getUsage($quotaType);

        return max(0, $limit - $currentUsage);
    }

    /**
     * Check if quota has capacity without throwing exception.
     */
    public static function hasCapacity(QuotaType $quotaType, int $amount = 0): bool
    {
        try {
            return self::check($quotaType, $amount);
        } catch (QuotaExceededException) {
            return false;
        }
    }

    /**
     * Record usage against a quota type.
     */
    public static function record(QuotaType $quotaType, int $amount): int
    {
        $cacheKey = $quotaType->getCacheKey();
        $ttl = self::getSecondsUntilEndOfDay();

        // Initialize if not exists
        if (! Cache::has($cacheKey)) {
            Cache::put($cacheKey, 0, $ttl);
        }

        // Increment usage
        Cache::increment($cacheKey, $amount);

        $newTotal = self::getUsage($quotaType);

        // Log the usage
        AppLogger::info(
            'quota',
            'UsageRecorded',
            sprintf('Quota usage recorded: %s', $quotaType->getLabel()),
            [
                'quota_type' => $quotaType->value,
                'amount' => $amount,
                'new_total' => $newTotal,
                'limit' => self::getLimit($quotaType),
                'remaining' => self::checkAvailable($quotaType),
            ]
        );

        return $newTotal;
    }

    /**
     * Get current usage for a quota type.
     */
    public static function getUsage(QuotaType $quotaType): int
    {
        return (int) Cache::get($quotaType->getCacheKey(), 0);
    }

    /**
     * Get the configured limit for a quota type.
     */
    public static function getLimit(QuotaType $quotaType): int
    {
        return (int) env($quotaType->getEnvKey(), $quotaType->getDefaultLimit());
    }

    /**
     * Get usage statistics for a quota type.
     */
    public static function getStats(QuotaType $quotaType): array
    {
        $usage = self::getUsage($quotaType);
        $limit = self::getLimit($quotaType);
        $remaining = max(0, $limit - $usage);
        $percentage = $limit > 0 ? round(($usage / $limit) * 100, 2) : 0;

        return [
            'quota_type' => $quotaType->value,
            'label' => $quotaType->getLabel(),
            'usage' => $usage,
            'limit' => $limit,
            'remaining' => $remaining,
            'percentage_used' => $percentage,
            'is_exceeded' => $usage >= $limit && $limit > 0,
            'resets_at' => now()->endOfDay()->toIso8601String(),
        ];
    }

    /**
     * Get all quota statistics.
     */
    public static function getAllStats(): array
    {
        $stats = [];
        foreach (QuotaType::cases() as $quotaType) {
            $stats[$quotaType->value] = self::getStats($quotaType);
        }

        return $stats;
    }

    /**
     * Calculate seconds until end of day (midnight).
     */
    protected static function getSecondsUntilEndOfDay(): int
    {
        return now()->diffInSeconds(now()->endOfDay());
    }

    /**
     * Reset quota usage for a specific type (for testing or admin override).
     */
    public static function reset(QuotaType $quotaType): void
    {
        Cache::forget($quotaType->getCacheKey());

        AppLogger::warning(
            'quota',
            'UsageReset',
            sprintf('Quota usage reset: %s', $quotaType->getLabel()),
            [
                'quota_type' => $quotaType->value,
            ]
        );
    }
}
