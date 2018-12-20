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

class ChannelUpdate implements ShouldBroadcast
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
     * Members of channel before the channel update
     *
     * @var Array
     */
    public $old_members;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, $channel, $old_members)
    {
      $this->user = $user::select($user::$profile)->where('id', $user->id)->get()->first()->toArray();
      $this->channel = $channel;
      $this->old_members = $old_members;
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'user' => $this->user,
            'channel' => $this->channel,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        $members = array_unique(array_merge(json_decode($this->channel["members"]), $this->old_members));
        $channels = array();
        foreach ($members as $key => $value) {
            $channels[] = 'private-users.'.$value;
        }
        return $channels;
    }
}
