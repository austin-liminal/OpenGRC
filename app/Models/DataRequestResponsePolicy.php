<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DataRequestResponsePolicy extends Pivot
{
    public $incrementing = true;

    protected $table = 'data_request_response_policy';

    protected $fillable = [
        'data_request_response_id',
        'policy_id',
        'description',
    ];

    public function dataRequestResponse(): BelongsTo
    {
        return $this->belongsTo(DataRequestResponse::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class)->withTrashed();
    }
}
