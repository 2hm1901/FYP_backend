<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourtCancelled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $courtNumber;
    public $startTime;
    public $endTime;
    public $venueName;
    public $notificationId;

    public function __construct($userId, $courtNumber, $startTime, $endTime, $venueName, $notificationId)
    {
        $this->userId = $userId;
        $this->courtNumber = $courtNumber;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->venueName = $venueName;
        $this->notificationId = $notificationId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'court-cancelled';
    }
}