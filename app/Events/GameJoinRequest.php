<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameJoinRequest implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $creatorId;
    public $requesterId;
    public $requesterName;
    public $gameId;
    public $notificationId;

    public function __construct($creatorId, $requesterId, $requesterName, $gameId, $notificationId)
    {
        $this->creatorId = $creatorId;
        $this->requesterId = $requesterId;
        $this->requesterName = $requesterName;
        $this->gameId = $gameId;
        $this->notificationId = $notificationId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->creatorId);
    }

    public function broadcastAs()
    {
        return 'game-join-request';
    }
} 