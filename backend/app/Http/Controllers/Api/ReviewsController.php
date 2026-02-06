<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Review;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReviewsController extends Controller
{
    /**
     * Get reviews for a user
     */
    public function index($userId)
    {
        $reviews = Review::with(['reviewer', 'deal.product'])
            ->where('reviewee_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($reviews);
    }

    /**
     * Create a review for a completed deal
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'deal_id' => 'required|exists:deals,id',
            'rating' => 'required|integer|min:1|max:5',
            'review_type' => 'required|in:quality,delivery,communication,overall',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = auth()->user();
        $deal = Deal::findOrFail($request->deal_id);

        // Verify user is part of the deal
        if ($deal->farmer_id !== $user->id && $deal->buyer_id !== $user->id) {
            return response()->json(['error' => 'You can only review deals you are part of'], 403);
        }

        // Verify deal is completed
        if ($deal->status !== 'completed') {
            return response()->json(['error' => 'You can only review completed deals'], 422);
        }

        // Determine reviewee (the other party)
        $revieweeId = $user->id === $deal->farmer_id ? $deal->buyer_id : $deal->farmer_id;

        // Check if user already reviewed this deal
        $existingReview = Review::where('deal_id', $request->deal_id)
            ->where('reviewer_id', $user->id)
            ->first();

        if ($existingReview) {
            return response()->json(['error' => 'You have already reviewed this deal'], 422);
        }

        DB::beginTransaction();
        try {
            $review = Review::create([
                'deal_id' => $request->deal_id,
                'reviewer_id' => $user->id,
                'reviewee_id' => $revieweeId,
                'rating' => $request->rating,
                'review_type' => $request->review_type,
                'comment' => $request->comment,
                'is_verified_purchase' => true,
            ]);

            // Create notification for reviewee
            Notification::create([
                'user_id' => $revieweeId,
                'type' => 'new_review',
                'title' => 'New Review Received',
                'message' => "{$user->name} left a {$request->rating}-star review for deal #{$deal->id}",
                'priority' => 'normal',
                'action_url' => "/profile/reviews",
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Review submitted successfully',
                'review' => $review->load(['reviewer', 'reviewee']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to submit review: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update a review
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = auth()->user();
        $review = Review::findOrFail($id);

        // Verify user is the reviewer
        if ($review->reviewer_id !== $user->id) {
            return response()->json(['error' => 'You can only update your own reviews'], 403);
        }

        $review->update([
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'message' => 'Review updated successfully',
            'review' => $review->load(['reviewer', 'reviewee']),
        ]);
    }

    /**
     * Delete a review
     */
    public function destroy($id)
    {
        $user = auth()->user();
        $review = Review::findOrFail($id);

        // Verify user is the reviewer
        if ($review->reviewer_id !== $user->id) {
            return response()->json(['error' => 'You can only delete your own reviews'], 403);
        }

        $review->delete();

        return response()->json(['message' => 'Review deleted successfully']);
    }

    /**
     * Get review statistics for a user
     */
    public function statistics($userId)
    {
        $stats = [
            'total_reviews' => Review::where('reviewee_id', $userId)->count(),
            'average_rating' => Review::where('reviewee_id', $userId)->avg('rating'),
            'rating_distribution' => [
                '5_star' => Review::where('reviewee_id', $userId)->where('rating', 5)->count(),
                '4_star' => Review::where('reviewee_id', $userId)->where('rating', 4)->count(),
                '3_star' => Review::where('reviewee_id', $userId)->where('rating', 3)->count(),
                '2_star' => Review::where('reviewee_id', $userId)->where('rating', 2)->count(),
                '1_star' => Review::where('reviewee_id', $userId)->where('rating', 1)->count(),
            ],
            'reviews_by_type' => [
                'quality' => Review::where('reviewee_id', $userId)->where('review_type', 'quality')->avg('rating'),
                'delivery' => Review::where('reviewee_id', $userId)->where('review_type', 'delivery')->avg('rating'),
                'communication' => Review::where('reviewee_id', $userId)->where('review_type', 'communication')->avg('rating'),
                'overall' => Review::where('reviewee_id', $userId)->where('review_type', 'overall')->avg('rating'),
            ],
        ];

        return response()->json($stats);
    }
}
