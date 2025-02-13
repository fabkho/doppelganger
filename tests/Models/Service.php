<?php

namespace fabkho\doppelganger\Tests\Models;

use fabkho\doppelganger\Traits\Synchronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes, Synchronizable;

    protected $fillable = [
        'name',
        'resource_id',
        'config',
        'status',
        'last_run',
        'error_count'
    ];

    protected $casts = [
        'config' => 'array',
        'last_run' => 'datetime',
        'error_count' => 'integer'
    ];

    // Optionally exclude specific relationships
    public function excludeFromSync(): array
    {
        return [
            // List any relationships you don't want to sync
        ];
    }

    public function getSyncValidationRules(): array
    {
        return [
            'name' => 'required|string',
            'resource_id' => 'required|integer',
            'status' => 'required|in:running,stopped,failed'
        ];
    }

    public function shouldBeSynchronized(): bool
    {
        return !is_null($this->resource_id);
    }


    public function resource(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}
