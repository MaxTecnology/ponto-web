<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Punch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'ts_server',
        'ts_client',
        'ip',
        'user_agent',
        'device_info',
        'fingerprint_hash',
        'geo',
        'geo_consent',
        'observacao',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'ts_server' => 'datetime',
            'ts_client' => 'datetime',
            'device_info' => 'array',
            'geo' => 'array',
            'geo_consent' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeWithFingerprint(Builder $query, ?string $hash): Builder
    {
        return $query->when($hash, fn (Builder $builder) => $builder->where('fingerprint_hash', $hash));
    }
}
