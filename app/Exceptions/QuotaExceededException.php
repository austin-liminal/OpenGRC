<?php

namespace App\Exceptions;

use App\Enums\QuotaType;
use Exception;

class QuotaExceededException extends Exception
{
    protected QuotaType $quotaType;

    protected int $currentUsage;

    protected int $limit;

    public function __construct(
        QuotaType $quotaType,
        int $currentUsage,
        int $limit,
        string $message = ''
    ) {
        $this->quotaType = $quotaType;
        $this->currentUsage = $currentUsage;
        $this->limit = $limit;

        if (empty($message)) {
            $message = sprintf(
                'Daily %s quota exceeded. Used: %d, Limit: %d',
                $quotaType->getLabel(),
                $currentUsage,
                $limit
            );
        }

        parent::__construct($message);
    }

    public function getQuotaType(): QuotaType
    {
        return $this->quotaType;
    }

    public function getCurrentUsage(): int
    {
        return $this->currentUsage;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getRemaining(): int
    {
        return max(0, $this->limit - $this->currentUsage);
    }
}
