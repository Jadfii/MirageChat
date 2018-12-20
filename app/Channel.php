<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Channel extends Model
{
  /**
   * Indicates if the IDs are auto-incrementing.
   *
   * @var bool
   */
  public $incrementing = false;

  /**
   * Primary key in the model migration.
   *
   * @var string
   */
  protected $primaryKey = 'channel_id';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'channel_id', 'name', 'user_id', 'members'
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
      'channel_id', 'name', 'user_id', 'members'
  ];

  /**
   * A channel belongs to a user
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public static function createMembersArray(Request $request, $members)
  {
      $members_new = array();
      if (is_array($members)) {
        foreach ($members as $key => $value) {
          $user = User::where('id', $value)->first();
          if ($user !== null and $user->exists()) {
            $members_new[] = $user->id;
          } else {
            return response()->json("User ".$value." not found", 404);
          }
        }
      } else {
        $user = User::where('id', $members)->first();
        if ($user->exists()) {
          $members_new = array($user->id);
        } else {
          return response()->json("User ".$value." not found", 404);
        }
      }

      return $members_new;
  }

  public static function isMember(User $user, Channel $channel)
  {
      return in_array($user->id, json_decode($channel->members));
  }

  public static function isOwner(User $user, Channel $channel)
  {
      return $user->id == $channel->user_id;
  }

  public static function hasPermission($permission, User $user, Channel $channel)
  {
      switch ($permission) {
        case 'view': {
          return Channel::isMember($user, $channel);
        }
        case 'edit': {
          return Channel::isOwner($user, $channel);
        }
        default: {
          return false;
        }
      }
  }
}
