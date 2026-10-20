<?php

namespace App\Models;

use App\Enums\PaymentReviewStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentProof extends Model
{
    protected $fillable = [
        'appointment_id',
        'image_path',
        'uploaded_at',
        'reviewed_by_admin_id',
        'reviewed_at',
        'review_status',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'review_status' => PaymentReviewStatus::class,
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_admin_id');
    }
}
