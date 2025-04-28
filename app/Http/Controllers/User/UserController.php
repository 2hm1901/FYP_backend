<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\GetOwnerInfoRequest;
use App\Http\Requests\User\GetUserRequest;
use App\Http\Resources\User\OwnerResource;
use App\Http\Resources\User\UserListResource;
use App\Http\Resources\User\UserResource;
use App\Services\User\UserServiceInterface;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserServiceInterface $userService)
    {
        $this->userService = $userService;
    }

    /**
     * API lấy thông tin chủ sân từ venue_id
     */
    public function getOwnerInfo(GetOwnerInfoRequest $request)
    {
        try {
            $venueId = $request->venue_id;
            $owner = $this->userService->getOwnerInfoByVenueId($venueId);

            return response()->json([
                'success' => true,
                'message' => 'Owner info retrieved successfully',
                'data' => new OwnerResource($owner),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * API lấy thông tin người dùng từ user_id
     */
    public function getUser(GetUserRequest $request)
    {
        try {
            $userId = $request->user_id;
            $user = $this->userService->getUserById($userId);

            return response()->json([
                'success' => true,
                'message' => 'User info retrieved successfully',
                'data' => new UserResource($user)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * API lấy tất cả người dùng kèm thông tin rating
     */
    public function getAllUsers()
    {
        try {
            $users = $this->userService->getAllUsersWithRatings();

            return response()->json([
                'success' => true,
                'data' => UserListResource::collection($users)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API xoá người dùng
     */
    public function deleteUser($id)
    {
        try {
            $this->userService->deleteUser($id);

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa người dùng thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
