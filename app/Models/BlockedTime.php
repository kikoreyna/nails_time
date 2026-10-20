<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedTime extends Model
{
    protected $fillable = ['date', 'starts_at', 'ends_at', 'reason'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}
