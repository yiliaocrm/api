<?php

namespace App\Events\Web;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 扫码登录事件
 * @package App\Events
 */
class ScanQRCodeLoginEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $uuid;
    public string $token;


    public function __construct(string $uuid, string $token)
    {
        $this->uuid  = $uuid;
        $this->token = $token;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn(): Channel|array
    {
        return new Channel('auth.' . $this->uuid);
    }

    public function broadcastWith(): array
    {
        return [
            'access_token' => $this->token,
            'token_type'   => 'bearer'
        ];
    }
}
