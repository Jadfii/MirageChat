<?php

namespace App;

use Kra8\Snowflake\HasSnowflakePrimary;
use App\Notifications\ResetPassword;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasSnowflakePrimary, Notifiable;
    protected $table = "users";

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username', 'email', 'password', 'api_token'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Options attributes to verify against.
     *
     * @var array
     */
    public $options = [
        'desktop_notifications', 'message_sounds'
    ];

    /**
     * Attributes that can be shown directly to the current user
     *
     * @var array
     */
    public static $viewable = [
        'id', 'username', 'email', 'status', 'api_token'
    ];

    /**
     * Attributes to be shown publicly
     *
     * @var array
     */
    public static $profile = [
        'id', 'username', 'status'
    ];

    /**
     * A user can have many messages
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messages()
    {
      return $this->hasMany(Message::class);
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }

    public function getProfile() {
        return array(
            "id" => $this->id,
            "username" => $this->username,
            "email" => $this->email,
            "status" => $this->status,
        );
    }

    public function createAvatar() {
      // Create user avatar (download from API and save)
      if (!Storage::exists(storage_path('app/public/avatars/'.$this->id.'.png'))) {
        Storage::put('public/avatars/'.$this->id.'.png', file_get_contents('https://api.adorable.io/avatars/256/'.$this->id.'.png'));
      }
    }
}
