<?php

namespace App\Events;

use App\User;
//use App\Channel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ChannelNew implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * User that created the channel
     *
     * @var User
     */
    public $user;

    /**
     * Channel details
     *
     * @var Channel
     */
    public $channel;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, $channel)
    {
      $this->user = $user::select($user::$profile)->where('id', $user->id)->get()->first()->toArray();
      $this->channel = $channel;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        $channels = array();
        foreach (json_decode($this->channel["members"]) as $key => $value) {
            $channels[] = 'private-users.'.$value;
        }
        return $channels;
    }
}
