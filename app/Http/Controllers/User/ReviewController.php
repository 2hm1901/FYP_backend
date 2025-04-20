<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class ReviewController extends Controller
{
    public function createReview(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reviewer_id' => 'required|exists:users,id',
                'reviewed_id' => 'required',
                'reviewed_type' => 'required|in:user,venue',
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $reviewer = User::find($request->reviewer_id);

            // Kiểm tra xem đã có đánh giá từ người này cho đối tượng này chưa
            $existingReview = Review::where('reviewer_id', $request->reviewer_id)
                ->where('reviewed_id', $request->reviewed_id)
                ->where('reviewed_type', $request->reviewed_type)
                ->first();

            if ($existingReview) {
                // Nếu đã tồn tại, cập nhật đánh giá
                $existingReview->update([
                    'rating' => $request->rating,
                    'comment' => $request->comment,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Cập nhật đánh giá thành công',
                    'data' => $existingReview
                ]);
            }

            // Nếu chưa tồn tại, tạo đánh giá mới
            $review = Review::create([
                'reviewer_id' => $request->reviewer_id,
                'reviewer_name' => $reviewer->username,
                'reviewed_id' => $request->reviewed_id,
                'reviewed_type' => $request->reviewed_type,
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đánh giá thành công',
                'data' => $review
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getReviews(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reviewed_id' => 'required',
                'reviewed_type' => 'required|in:user,venue',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $reviews = Review::where('reviewed_id', $request->reviewed_id)
                ->where('reviewed_type', $request->reviewed_type)
                ->orderBy('created_at', 'desc')
                ->get();

            $averageRating = $reviews->avg('rating');

            return response()->json([
                'success' => true,
                'data' => [
                    'reviews' => $reviews,
                    'average_rating' => round($averageRating, 1)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
} 