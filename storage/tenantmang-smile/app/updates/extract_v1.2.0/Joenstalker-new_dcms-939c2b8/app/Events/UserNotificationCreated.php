<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserNotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;
    public string $context;
    public array $notification;
    public int $unreadCount;

    public function __construct(int $userId, string $context, array $notification, int $unreadCount)
    {
        $this->userId = $userId;
        $this->context = $context;
        $this->notification = $notification;
        $this->unreadCount = $unreadCount;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'UserNotificationCreated';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'context' => $this->context,
            'notification' => $this->notification,
            'unread_count' => $this->unreadCount,
        ];
    }
}
