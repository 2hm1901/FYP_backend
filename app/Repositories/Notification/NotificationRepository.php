<?php

namespace App\Repositories\Notification;

use App\Models\Notification;

class NotificationRepository implements NotificationRepositoryInterface
{
    /**
     * Tạo thông báo mới
     */
    public function create(array $data)
    {
        return Notification::create($data);
    }

    /**
     * Xoá thông báo theo user_id
     */
    public function deleteNotificationsByUserId($userId)
    {
        return Notification::where('user_id', $userId)->delete();
    }
} 