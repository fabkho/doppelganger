<?php

namespace fabkho\doppelganger\Tests\Models;

use fabkho\doppelganger\Traits\Synchronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Resource extends Model
{
    use SoftDeletes, Synchronizable;

    protected $fillable = [
        'name',
        'organization_id',
        'configuration',
        'is_public',
        'last_accessed',
        'usage_count'
    ];

    protected $casts = [
        'configuration' => 'array',
        'is_public' => 'boolean',
        'last_accessed' => 'datetime',
        'usage_count' => 'integer'
    ];

    // Optionally exclude specific relationships
    public function excludeFromSync(): array
    {
        return [
            // List any relationships you don't want to sync
        ];
    }

    /**
     * Get validation rules for synchronization.
     *
     * @return array
     */
    public function getSyncValidationRules(): array
    {
        return [
            'name' => 'required|string',
            'organization_id' => 'required|integer',
            'is_public' => 'boolean'
        ];
    }

    /**
     * Determine if the model should be synchronized.
     *
     * @return bool
     */
    public function shouldBeSynchronized(): bool
    {
        // Only sync resources that belong to an organization
        return !is_null($this->organization_id);
    }

    public function organization(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function services(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Service::class);
    }
}
