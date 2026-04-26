<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserAccessChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;
    public string $action;
    public string $message;
    public bool $shouldLogout;

    public function __construct(int $userId, string $action, string $message, bool $shouldLogout = false)
    {
        $this->userId = $userId;
        $this->action = $action;
        $this->message = $message;
        $this->shouldLogout = $shouldLogout;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'UserAccessChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'action' => $this->action,
            'message' => $this->message,
            'should_logout' => $this->shouldLogout,
        ];
    }
}
