<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessHour extends Model
{
    protected $primaryKey = 'weekday';

    public $incrementing = false;

    protected $fillable = ['weekday', 'is_open', 'opens_at', 'closes_at', 'break_starts_at', 'break_ends_at'];

    protected function casts(): array
    {
        return ['is_open' => 'boolean'];
    }
}
