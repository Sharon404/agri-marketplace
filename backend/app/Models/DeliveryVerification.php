<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'logistics_job_id',
        'verified_by_id', // admin/agent user
        'verification_type', // farmer_signature, buyer_receipt, photos, etc.
        'verification_data', // JSON with details
        'verified_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'verification_data' => 'array',
            'verified_at' => 'datetime',
        ];
    }

    public function logisticsJob(): BelongsTo
    {
        return $this->belongsTo(LogisticsJob::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_id');
    }

    /**
     * Check if verification is complete.
     */
    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    /**
     * Mark as verified.
     */
    public function verify(array $data = []): bool
    {
        if (!$this->isVerified()) {
            $this->update([
                'verification_data' => $data,
                'verified_at' => now(),
            ]);
            return true;
        }
        return false;
    }
}