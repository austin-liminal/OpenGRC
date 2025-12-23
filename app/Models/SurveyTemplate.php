<?php

namespace App\Models;

use App\Enums\SurveyTemplateStatus;
use App\Enums\SurveyType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SurveyTemplate extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'status',
        'type',
        'created_by_id',
    ];

    protected $casts = [
        'status' => SurveyTemplateStatus::class,
        'type' => SurveyType::class,
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('sort_order');
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'description', 'status', 'type'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
