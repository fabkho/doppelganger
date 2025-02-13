<?php

namespace fabkho\doppelganger\Tests\Models;

use fabkho\doppelganger\Traits\Synchronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrganizationSettings extends Model
{
    use SoftDeletes, Synchronizable;

    protected $fillable = [
        'organization_id',
        'default_language',
        'timezone',
        'date_format',
        'time_format',
        'currency',
        'notification_settings',
        'branding_settings'
    ];

    protected $casts = [
        'notification_settings' => 'array',
        'branding_settings' => 'array'
    ];

    public function organization(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
