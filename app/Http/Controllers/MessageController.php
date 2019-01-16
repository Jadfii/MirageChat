<?php

namespace App\Http\Controllers;

use App\Message;
use App\Channel;
use App\Events\MessageNew;
use App\Events\MessageRemove;
use App\Events\MessageUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct(Request $request)
  {
      $this->middleware('auth:api');
      $this->middleware('message.auth:view', ['except' => ['index', 'store']]);
      $this->middleware('message.auth:edit', ['only' => ['update', 'delete']]);
      $this->middleware('channel.auth:view', ['only' => ['store']]);
  }

  /**
  * Fetch all messages
  *
  * @return Message
  */
  public static function index()
  {
      $user = Auth::guard('api')->user();
      if ($user == null) {
        $user = Auth::user();
      }
      $messages = Message::all();

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
  * Fetch all messages grouped by channel
  *
  * @return Message
  */
  public static function every()
  {
      $user = Auth::guard('api')->user();
      if ($user == null) {
        $user = Auth::user();
      }
      $messages = Message::all();

      foreach ($messages as $key => $value) {
        $messages[$key] = $value::where('message_id', $value->message_id)->get(Message::$viewable)->first();
        $messages[$key]['read'] = $value->isRead($user, $value);
        if (!Message::hasPermission('view', $user, $value)) {
          unset($messages[$key]);
        }
      }

      return $messages;
  }

  /**
  * Fetch single message
  *
  * @return Message
  */
  public static function show(Message $message)
  {
      return $message;
  }

  /**
  * Fetch channel of single message
  *
  * @return Message
  */
  public static function show_channel(Message $message)
  {
      return $message->channel;
  }

  /**
  * Add message to database and broadcast new message
  *
  * @param Request $request
  * @return Response
  */
  public static function store(Request $request)
  {
      $channel = Channel::find($request->channel);
      if ($channel == null or !$channel->exists()) {
        return response()->json("Channel ".$request->input('channel_id')." not found", 404);
      }
      $user = Auth::guard('api')->user();

      $request->validate([
          'content' => 'string|max:2000|present'
       ]);

      $message = Message::create([
        'user_id' => $user->id,
        'channel_id' => $channel->channel_id,
        'content' => $request->input('content'),
        'read_by' => json_encode(array($user->id)),
      ]);

      broadcast(new MessageNew($user, $message));

      $message = $message::where('message_id', $message->message_id)->get(Message::$viewable)->first();
      $message['read'] = true;

      return response()->json($message, 201);
  }

  /**
  * Edit message content
  *
  * @param Request $request
  * @param Message $message
  * @return Response
  */
  public static function update(Request $request, Message $message)
  {
      $message->update([
        'content' => $request->input('content'),
      ]);

      $user = Auth::guard('api')->user();

      $message_obj = $message;
      $message = $message::where('message_id', $message->message_id)->get(Message::$viewable)->first()->toArray();
      $message['read'] = $message_obj->isRead($user, $message_obj);

      broadcast(new MessageUpdate($user, $message));

      return response()->json($message, 200);
  }

  /**
  * 'Read' message - add user to read_by
  *
  * @param Request $request
  * @param Message $message
  * @return Response
  */
  public static function read(Request $request, Message $message)
  {
      $user = Auth::guard('api')->user();

      $read_by = json_decode($message->read_by);
      if (!in_array($user->id, $read_by)) {
        $read_by[] = $user->id;

        $message->update([
          'read_by' => json_encode($read_by),
        ]);
      }

      $message_obj = $message;
      $message = $message::where('message_id', $message->message_id)->get(Message::$viewable)->first();
      $message['read'] = $message->isRead($user, $message_obj);

      return response()->json($message, 200);
  }

  /**
  * Remove message from database
  *
  * @param Request $request
  * @param Message $message
  * @return Response
  */
  public static function delete(Request $request, Message $message)
  {
      $user = Auth::guard('api')->user();
      broadcast(new MessageRemove($user, $message));
      $message->delete();

      return response()->json(null, 204);
  }
}
