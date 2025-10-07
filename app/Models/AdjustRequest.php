<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdjustRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDENTE = 'PENDENTE';
    public const STATUS_APROVADO = 'APROVADO';
    public const STATUS_REJEITADO = 'REJEITADO';

    protected $fillable = [
        'user_id',
        'date',
        'from_ts',
        'to_ts',
        'reason',
        'status',
        'approver_id',
        'decided_at',
        'audit',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'from_ts' => 'datetime',
            'to_ts' => 'datetime',
            'decided_at' => 'datetime',
            'audit' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
