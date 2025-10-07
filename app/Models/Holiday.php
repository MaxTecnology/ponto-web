<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'scope',
        'uf',
        'municipio',
        'date',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
