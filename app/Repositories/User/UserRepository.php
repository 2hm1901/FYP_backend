<?php

namespace App\Repositories\User;

use App\Models\User;

class UserRepository implements UserRepositoryInterface
{
    /**
     * Lấy thông tin user theo id
     */
    public function getUserById($id)
    {
        return User::find($id);
    }

    /**
     * Lấy tất cả users kèm thông tin reviews
     */
    public function getAllUsersWithReviews()
    {
        return User::with('reviews')->get();
    }

    /**
     * Xoá user
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        
        // Xóa các liên kết liên quan
        $user->reviews()->delete();
        
        // Xoá user
        return $user->delete();
    }
} 