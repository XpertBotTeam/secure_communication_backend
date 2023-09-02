<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Friend extends Model
{
    use HasFactory;

    protected $table = 'friends'; // Specify the table name if it's different from the model's plural lowercase name.

    protected $fillable = [
        'user_id',       // The ID of the user initiating the friendship
        'friend_id',     // The ID of the user being added as a friend
        'status',        // Friendship status (e.g., 'pending', 'accepted', 'blocked')
    ];

    /**
     * Define relationships between the Friend model and User model.
     */

    // The user who initiated the friendship (the "sender").
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // The user who received the friendship request (the "recipient").
    public function friend()
    {
        return $this->belongsTo(User::class, 'friend_id');
    }

    /**
     * Define additional methods or scopes for working with friendships.
     */

    // Example: Scope to retrieve accepted friendships.
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    // Example: Scope to retrieve pending friendships.
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    

}
