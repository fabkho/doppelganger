<?php

namespace fabkho\doppelganger\Tests\Models;

use fabkho\doppelganger\Traits\Synchronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Organization extends Model
{
    use SoftDeletes, Synchronizable;

    protected $fillable = [
        'name',
        'description',
        'config_settings',  // renamed from settings
        'revenue',
        'is_active',
        'last_audit',
        'status',
        'metadata',
        'employee_count'
    ];

    protected $casts = [
        'config_settings' => 'array',  // renamed from settings
        'metadata' => 'array',
        'is_active' => 'boolean',
        'revenue' => 'decimal:2',
        'last_audit' => 'datetime',
        'employee_count' => 'integer'
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
        });
    }

    public function excludeFromSync(): array
    {
        return [];
    }

    public function getSyncValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive,pending'
        ];
    }

    public function resources(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Resource::class);
    }

    public function settings(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(OrganizationSettings::class, 'organization_id');
    }

    public function location(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(OrganizationLocation::class)->where('is_primary', true);
    }
}
