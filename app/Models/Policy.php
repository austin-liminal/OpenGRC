<?php

namespace App\Models;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use Aliziodev\LaravelTaxonomy\Traits\HasTaxonomy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Policy
 *
 * Represents an organizational policy in the GRC system.
 * Note: This is a Policy document model, not to be confused with Laravel authorization policies.
 *
 * @package App\Models
 */
class Policy extends Model
{
    use HasFactory, HasTaxonomy, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'policy_scope',
        'purpose',
        'body',
        'document_path',
        'scope_id',
        'department_id',
        'status_id',
        'owner_id',
        'effective_date',
        'retired_date',
        'revision_history',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'effective_date' => 'date',
        'retired_date' => 'date',
        'revision_history' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    /**
     * Get the user who created this policy.
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this policy.
     *
     * @return BelongsTo
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the taxonomy scope for this policy.
     *
     * @return BelongsTo
     */
    public function scope(): BelongsTo
    {
        return $this->belongsTo(Taxonomy::class, 'scope_id');
    }

    /**
     * Get the department taxonomy term for this policy.
     *
     * @return BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Taxonomy::class, 'department_id');
    }

    /**
     * Get the status taxonomy term.
     *
     * @return BelongsTo
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Taxonomy::class, 'status_id');
    }

    /**
     * Get the owner (user) of this policy.
     *
     * @return BelongsTo
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the controls associated with this policy.
     *
     * @return BelongsToMany
     */
    public function controls(): BelongsToMany
    {
        return $this->belongsToMany(Control::class, 'control_policy')
            ->withTimestamps();
    }

    /**
     * Get the implementations associated with this policy.
     *
     * @return BelongsToMany
     */
    public function implementations(): BelongsToMany
    {
        return $this->belongsToMany(Implementation::class, 'implementation_policy')
            ->withTimestamps();
    }

    /**
     * Get the risks associated with this policy.
     *
     * @return BelongsToMany
     */
    public function risks(): BelongsToMany
    {
        return $this->belongsToMany(Risk::class, 'policy_risk')
            ->withTimestamps();
    }

    /**
     * Get the scope name accessor.
     *
     * @return string|null
     */
    public function getScopeNameAttribute(): ?string
    {
        return $this->scope?->name;
    }

    /**
     * Get the status name accessor.
     *
     * @return string|null
     */
    public function getStatusNameAttribute(): ?string
    {
        return $this->status?->name;
    }

    /**
     * Scope a query to filter by status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $statusName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $statusName)
    {
        return $query->whereHas('status', function ($q) use ($statusName) {
            $q->where('name', $statusName);
        });
    }

    /**
     * Scope a query to filter by department.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $departmentId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope a query to filter by scope.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $scopeName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByScope($query, string $scopeName)
    {
        return $query->whereHas('scope', function ($q) use ($scopeName) {
            $q->where('name', $scopeName);
        });
    }
}
