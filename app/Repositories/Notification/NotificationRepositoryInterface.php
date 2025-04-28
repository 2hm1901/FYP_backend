<?php

namespace App\Repositories\Notification;

interface NotificationRepositoryInterface
{
    public function create(array $data);
    public function deleteNotificationsByUserId($userId);
} 