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
        'username', 'email', 'password', 'api_token', 'google2fa_secret', 'options',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token'
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
        'id', 'username', 'email', 'status', 'api_token', 'google2fa_secret', 'options',
    ];

    /**
     * The attributes that are allowed to be edited by user
     *
     * @var array
     */
    public $editable = [
        'username', 'email', 'password', 'options',
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

    /**
     * Send password reset notification for user
     *
     * @param string  $token
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }

    /**
     * Get user profile attributes
     *
     * @return array
     */
    public function getProfile()
    {
        return array(
            "id" => $this->id,
            "username" => $this->username,
            "email" => $this->email,
            "status" => $this->status,
        );
    }

    /**
     * Create user avatar from avatar api
     *
     */
    public function createAvatar()
    {
      // Create user avatar (download from API and save)
      if (!Storage::exists(storage_path('app/public/avatars/'.$this->id.'.png'))) {
        Storage::put('public/avatars/'.$this->id.'.png', file_get_contents('https://api.adorable.io/avatars/256/'.$this->id.'.png'));
      }
    }

    /**
     * Ecrypt the user's google_2fa secret.
     *
     * @param  string  $value
     * @return string
     */
    public function set2faSecretAttribute($value)
    {
         $this->attributes['google2fa_secret'] = encrypt($value);
    }

    /**
     * Decrypt the user's google_2fa secret.
     *
     * @param  string  $value
     * @return string
     */
    public function get2faSecretAttribute($value)
    {
        return decrypt($value);
    }
}
