<?php

namespace App\Models;

use App\Enums\VendorRiskRating;
use App\Enums\VendorStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Vendor extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $casts = [
        'status' => VendorStatus::class,
        'risk_rating' => VendorRiskRating::class,
        'logo' => 'array',
    ];

    public function vendorManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_manager_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'status', 'risk_rating', 'vendor_manager_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
