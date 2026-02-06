<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'deal_id',
        'reviewer_id',
        'reviewee_id',
        'rating',
        'review_type',
        'comment',
        'is_verified_purchase',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified_purchase' => 'boolean',
    ];

    // Relationships
    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewee()
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

    // Update user's average rating after creating/updating review
    protected static function booted()
    {
        static::created(function ($review) {
            $review->updateUserRating();
        });

        static::updated(function ($review) {
            $review->updateUserRating();
        });

        static::deleted(function ($review) {
            $review->updateUserRating();
        });
    }

    protected function updateUserRating()
    {
        $user = User::find($this->reviewee_id);
        if ($user) {
            $avgRating = Review::where('reviewee_id', $this->reviewee_id)->avg('rating');
            $totalReviews = Review::where('reviewee_id', $this->reviewee_id)->count();
            
            $user->update([
                'average_rating' => round($avgRating, 2),
                'total_reviews' => $totalReviews,
            ]);
        }
    }
}
