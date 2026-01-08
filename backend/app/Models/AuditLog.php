<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action', // e.g., created_deal, updated_listing, verified_user
        'model_type', // e.g., App\Models\Deal
        'model_id',
        'old_values', // JSON of old data
        'new_values', // JSON of new data
        'ip_address',
        'user_agent',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the model that was audited.
     */
    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * Log an action.
     */
    public static function log(User $user, string $action, Model $model, array $oldValues = [], array $newValues = [], string $notes = ''): self
    {
        return static::create([
            'user_id' => $user->id,
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'notes' => $notes,
        ]);
    }
}