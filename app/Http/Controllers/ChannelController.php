<?php

namespace App\Http\Controllers;

use App\Channel;
use App\Message;
use App\Events\ChannelNew;
use App\Events\ChannelRemove;
use App\Events\ChannelUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChannelController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct(Request $request)
  {
      $this->middleware('auth:api');
      $this->middleware('channel.auth:view', ['except' => ['index', 'store']]);
      $this->middleware('channel.auth:edit', ['only' => ['update', 'delete']]);
  }

  /**
  * Get all channels the user is a member of
  *
  * @return Response
  */
  public static function index()
  {
    $user = Auth::guard('api')->user();
    if ($user == null) {
      $user = Auth::user();
    }

    $channels = array();
    foreach (Channel::all(Channel::$viewable) as $key => $value) {
      if (Channel::hasPermission('view', $user, $value)) {
        $channels[] = $value;
      }
    }

    return $channels;
  }

  /**
  * Get channel
  *
  * @param Channel $channel
  * @return Response
  */
  public static function show(Channel $channel)
  {
      return $channel;
  }

  /**
  * Get all messages in channel
  *
  * @param Channel $channel
  * @return Response
  */
  public static function show_messages(Channel $channel)
  {
      $user = Auth::guard('api')->user();
      if ($user == null) {
        $user = Auth::user();
      }
      $messages = Message::where('channel_id', $channel->channel_id)->get();

      foreach ($messages as $key => $value) {
        $messages[$key] = $value::where('message_id', $value->message_id)->get(Message::$viewable)->first();
        $messages[$key]['read'] = $value->isRead($user, $value);
        if (!Message::hasPermission('view', $user, $value)) {
          unset($messages[$key]);
        }
      }

      return json_decode(json_encode($messages), true);
  }

  /**
  * Add channel to database
  *
  * @param Request $request
  * @return Response
  */
  public static function store(Request $request)
  {
      $user = Auth::guard('api')->user();

      $snowflake = resolve('\Kra8\Snowflake\Snowflake');
      //$snowflake->workerId = 2;
      //$snowflake->datacenterId = 2;
      $channel_id = $snowflake->next();

      $request->validate([
          'name' => 'required|string|min:1',
      ]);

      $members = $request->input('members');
      $members = Channel::createMembersArray($request, $members);
      $members[] = $user->id;

      $channel = Channel::create([
        'channel_id' => $channel_id,
        'name' => $request->input('name'),
        'user_id' => $user->id,
        'members' => json_encode($members),
      ]);

      broadcast(new ChannelNew($user, $channel::select($channel::$viewable)->where('channel_id', $channel->channel_id)->get()->first()->toArray()));

      return response()->json($channel::select($channel::$viewable)->where('channel_id', $channel->channel_id)->get()->first(), 201);
  }

  /**
  * Update channel
  *
  * @param Request $request
  * @param Channel $channel
  * @return Response
  */
  public static function update(Request $request, Channel $channel)
  {
      $user = Auth::guard('api')->user();

      $old_members = json_decode($channel->members);

      $members = $request->input('members');
      $members = Channel::createMembersArray($request, $members);
      if (!is_array($members)) {
        return $members;
      }
      $members[] = $user->id;

      foreach ($members as $key => $value) {
        $members[$key] = (string)$value;
      }

      $channel->update([
        'name' => $request->input('name'),
        'members' => json_encode($members),
      ]);

      broadcast(new ChannelUpdate($user, $channel::select($channel::$viewable)->where('channel_id', $channel->channel_id)->get()->first()->toArray(), $old_members));

      return response()->json($channel::select($channel::$viewable)->where('channel_id', $channel->channel_id)->get()->first(), 200);
  }

  /**
  * Leave channel
  *
  * @param Request $request
  * @param Channel $channel
  * @return Response
  */
  public static function leave(Request $request, Channel $channel)
  {
      $user = Auth::guard('api')->user();

      $old_members = json_decode($channel->members);

      $members = $old_members;
      unset($members[array_search($user->id, $members)]);
      $members = Channel::createMembersArray($request, $members);

      $channel->update([
        'members' => json_encode($members),
      ]);

      broadcast(new ChannelUpdate($user, $channel::select($channel::$viewable)->where('channel_id', $channel->channel_id)->get()->first()->toArray(), $old_members));

      return response()->json($channel::select($channel::$viewable)->where('channel_id', $channel->channel_id)->get()->first(), 200);
  }

  /**
  * 'Read' channel - add user to read_by for all messages in channel
  *
  * @param Request $request
  * @param Channel $channel
  * @return Response
  */
  public static function read(Request $request, Channel $channel)
  {
      $user = Auth::guard('api')->user();

      $messages = Message::where('channel_id', $channel->channel_id)->get();

      foreach ($messages as $key => $value) {
        $read_by = json_decode($value->read_by);
        if (!in_array($user->id, $read_by)) {
          $read_by[] = $user->id;

          $value->update([
            'read_by' => json_encode($read_by),
          ]);
        }

        $messages[$key] = $value::where('message_id', $value->message_id)->get(Message::$viewable)->first();
        $messages[$key]['read'] = $value->isRead($user, $value);
      }

      return response()->json($messages, 200);
  }

  /**
  * Remove channel from database
  *
  * @param Request $request
  * @return Response
  */
  public static function delete(Request $request, Channel $channel)
  {
      $user = Auth::guard('api')->user();
      broadcast(new ChannelRemove($user, $channel::select(['channel_id', 'user_id'])->where('channel_id', $channel->channel_id)->get()->first()->toArray()));
      $channel->delete();

      return response()->json(null, 204);
  }
}
