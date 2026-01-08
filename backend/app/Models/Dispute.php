<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'deal_id',
        'raised_by_id', // farmer or buyer user
        'dispute_type', // quality_issue, delivery_delay, payment_issue, etc.
        'description',
        'status', // open, investigating, resolved, closed
        'resolution',
        'resolved_by_id', // admin/agent user
        'resolved_at',
        'evidence_files', // JSON array of file paths
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'evidence_files' => 'array',
        ];
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }

    /**
     * Check if dispute is resolved.
     */
    public function isResolved(): bool
    {
        return in_array($this->status, ['resolved', 'closed']);
    }

    /**
     * Resolve the dispute.
     */
    public function resolve(User $resolvedBy, string $resolution): bool
    {
        if (!$this->isResolved()) {
            $this->update([
                'status' => 'resolved',
                'resolution' => $resolution,
                'resolved_by_id' => $resolvedBy->id,
                'resolved_at' => now(),
            ]);
            return true;
        }
        return false;
    }
}