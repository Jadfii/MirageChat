<?php

namespace App;

use Kra8\Snowflake\HasSnowflakePrimary;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
  /**
   * Primary key in the model migration.
   *
   * @var string
   */
  protected $primaryKey = 'message_id';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'content', 'read_by', 'user_id', 'channel_id'
  ];

  /**
   * The attributes that should be hidden for arrays.
   *
   * @var array
   */
  protected $hidden = [
      '',
  ];

  /**
   * Attributes that can be shown directly to users
   *
   * @var array
   */
  public static $viewable = [
      'message_id', 'user_id', 'channel_id', 'content', 'created_at'
  ];

  /**
   * A message belongs to a user
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function user()
  {
    return $this->belongsTo(User::class);
  }

  /**
   * A message belongs to a channel
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function channel()
  {
    return $this->belongsTo(Channel::class, 'channel_id');
  }

  public static function hasPermission($permission, User $user, Message $message)
  {
      switch ($permission) {
        case 'view': {
          return Channel::hasPermission($permission, $user, $message->channel);
        }
        case 'edit': {
          return $user->id == $message->user_id;
        }
        default: {
          return false;
        }
      }
  }

  public static function isRead(User $user, Message $message)
  {
    return in_array($user->id, json_decode($message->read_by));
  }
}
