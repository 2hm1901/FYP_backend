<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Notification;

class BookingStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $venueName;
    public $bookingDate;
    public $status;
    public $notificationId;

    public function __construct($userId, $venueName, $bookingDate, $status)
    {
        $this->userId = $userId;
        $this->venueName = $venueName;
        $this->bookingDate = $bookingDate;
        $this->status = $status;

        // Lưu thông báo vào database
        $message = $status === 'accepted'
            ? "{$venueName} đã chấp nhận yêu cầu đặt sân vào ngày {$bookingDate} của bạn"
            : "{$venueName} đã từ chối yêu cầu đặt sân vào ngày {$bookingDate} của bạn";

        $notification = Notification::create([
            'user_id' => $userId,
            'message' => $message,
        ]);
        $this->notificationId = $notification->id;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'booking-status-updated';
    }

    public function broadcastWith()
    {
        return [
            'notificationId' => $this->notificationId,
            'venueName' => $this->venueName,
            'bookingDate' => $this->bookingDate,
            'status' => $this->status,
        ];
    }
}