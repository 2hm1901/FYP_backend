<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\GetOwnerInfoRequest;
use App\Http\Requests\User\GetUserRequest;
use App\Http\Resources\User\OwnerResource;
use App\Http\Resources\User\UserListResource;
use App\Http\Resources\User\UserResource;
use App\Services\User\UserServiceInterface;

/**
 * @OA\Tag(
 *     name="User",
 *     description="Quản lý thông tin người dùng"
 * )
 */
class UserController extends Controller
{
    protected $userService;

    public function __construct(UserServiceInterface $userService)
    {
        $this->userService = $userService;
    }

    /**
     * API lấy thông tin chủ sân từ venue_id
     * 
     * @OA\Post(
     *     path="/api/venue-owner",
     *     summary="Lấy thông tin chủ sân từ venue_id",
     *     description="API lấy thông tin chi tiết chủ sân dựa vào ID sân",
     *     operationId="getOwnerInfo",
     *     tags={"User"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"venue_id"},
     *             @OA\Property(property="venue_id", type="integer", description="ID của sân")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lấy thông tin thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Owner info retrieved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy thông tin"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server"
     *     )
     * )
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
     * 
     * @OA\Get(
     *     path="/api/getUser",
     *     summary="Lấy thông tin người dùng theo user_id",
     *     description="API lấy thông tin chi tiết người dùng dựa vào ID",
     *     operationId="getUser",
     *     tags={"User"},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="ID của người dùng",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lấy thông tin thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User info retrieved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy người dùng"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server"
     *     )
     * )
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
     * 
     * @OA\Get(
     *     path="/api/admin/users",
     *     summary="Lấy danh sách tất cả người dùng",
     *     description="API lấy tất cả người dùng kèm thông tin rating",
     *     operationId="getAllUsers",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lấy danh sách thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server"
     *     )
     * )
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
     * 
     * @OA\Delete(
     *     path="/api/admin/users/{id}",
     *     summary="Xoá người dùng",
     *     description="API xoá người dùng",
     *     operationId="deleteUser",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của người dùng cần xoá",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Xoá thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Đã xóa người dùng thành công")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy người dùng"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server"
     *     )
     * )
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
