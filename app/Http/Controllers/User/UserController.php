<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Venue;

class UserController extends Controller
{
    //API lấy thông tin chủ sân từ venue_id
    public function getOwnerInfo(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'venue_id' => 'required|exists:venues,id',
            ]);

            $venueId = $request->venue_id;

            // Truy vấn venue để lấy owner
            $venue = Venue::where('id', $venueId)
                ->with(['owner', 'owner.bankAccount']) // Load thông tin owner và bankAccount
                ->first();

            if (!$venue) {
                return response()->json([
                    'success' => false,
                    'message' => 'Venue not found',
                ], 404);
            }

            if (!$venue->owner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Owner not found for this venue',
                ], 404);
            }

            // Trả về thông tin owner và bankAccount với cấu trúc rõ ràng
            return response()->json([
                'success' => true,
                'message' => 'Owner info retrieved successfully',
                'data' => [
                    'id' => $venue->owner->id,
                    'username' => $venue->owner->username,
                    'email' => $venue->owner->email,
                    'phone_number' => $venue->owner->phone_number,
                    'user_type' => $venue->owner->user_type,
                    'avatar' => $venue->owner->avatar,
                    'bankAccount' => $venue->owner->bankAccount ? [
                        'id' => $venue->owner->bankAccount->id,
                        'account_number' => $venue->owner->bankAccount->account_number,
                        'bank_name' => $venue->owner->bankAccount->bank_name,
                        'qr_code' => $venue->owner->bankAccount->qr_code,
                    ] : null,
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    //API lấy thông tin người dùng từ user_id
    public function getUser(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
            ]);

            $userId = $request->user_id;
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'User info retrieved successfully',
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
