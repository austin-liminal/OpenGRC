<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Application extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'owner_id',
        'type',
        'description',
        'status',
        'url',
        'notes',
        'vendor_id',
    ];

    protected $casts = [
        'type' => ApplicationType::class,
        'status' => ApplicationStatus::class,
        'logo' => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'status', 'owner_id', 'vendor_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
