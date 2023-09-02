<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];


    protected $primaryKey = 'UserID';

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'SenderID');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'RecipientID');
    }

    public function sentFiles()
    {
        return $this->hasMany(File::class, 'SenderID');
    }

    public function receivedFiles()
    {
        return $this->hasMany(File::class, 'RecipientID');
    }

    public function sentCalls()
    {
        return $this->hasMany(Call::class, 'SenderID');
    }

    public function receivedCalls()
    {
        return $this->hasMany(Call::class, 'RecipientID');
    }

    public function friends()
{
    return $this->belongsToMany(User::class, 'friends', 'user_id', 'friend_id')
        ->withPivot('status')
        ->wherePivot('status', 'accepted');
}

public function acceptedFriends()
{
    return $this->belongsToMany(User::class, 'friends', 'user_id', 'friend_id')
        ->wherePivot('status', 'accepted');
}

public function friendNames()
{
    return $this->acceptedFriends->pluck('name'); // Assuming 'name' is the name column in your users table
}

public function refreshToken()
    {
        $this->tokens()->delete(); // Revoke existing tokens
        return $this->createToken('authToken')->plainTextToken; // Generate and return a new token
    }

}
