<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use App\Models\Friend;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    //need fix
    public function messagedUsers(Request $request)
    {
        $userId = $request->user()->UserID; // Get the authenticated user's ID
        $messagedUsers = Message::where('SenderID', $userId)
                                ->orWhere('RecipientID', $userId)
                                ->distinct()
                                ->pluck('SenderID', 'RecipientID')
                                ->flatten()
                                ->reject(function ($id) use ($userId) {
                                    return $id == $userId;
                                })
                                ->map(function ($id) {
                                    return ['UserID' => $id];
                                });

        
        return response()->json($messagedUsers);
    }


    public function messagedUsersByEmail(Request $request)
    {
        // Validate the input email address
        $request->validate([
            'email' => 'required|email',
        ]);
    
        // Find the user associated with the email
        $user = User::where('email', $request->input('email'))->first();
    
        if (!$user) {
            throw ValidationException::withMessages(['email' => 'User not found']);
        }
    
        // Retrieve the messaged users for that user
        $messagedUsers = User::where(function ($query) use ($user) {
            $query->where('UserID', '!=', $user->UserID)
                  ->where(function ($query) use ($user) {
                      $query->whereHas('sentMessages', function ($query) use ($user) {
                          $query->where('RecipientID', $user->UserID);
                      })->orWhereHas('receivedMessages', function ($query) use ($user) {
                          $query->where('SenderID', $user->UserID);
                      });
                  });
        })->distinct()->pluck('UserID');
        
    
        // Return the list of messaged users
        return response()->json(['messaged_users' => $messagedUsers]);
    }

    public function addFriend(Request $request)
{
    // Validate the incoming request data
    $request->validate([
        'friend_email' => 'required|email|exists:users,email', // Ensure the friend's email exists
    ]);

    // Get the authenticated user
    $user = $request->user();

    // Find the user associated with the provided email
    $friend = User::where('email', $request->input('friend_email'))->first();

    // Check if the friendship already exists
    if (!$user->friends()->where('friend_id', $friend->UserID)->exists()) {
        // Add the friend to the user's list of friends
        $friendship = new Friend([
            'user_id' => $user->UserID,
            'friend_id' => $friend->UserID,
            'status' => 'pending', // You can set the initial status as needed
        ]);

        $friendship->save();

        // Create a reverse friend request so that the other user also sees the sender as a friend
        $reverseFriendship = new Friend([
            'user_id' => $friend->UserID,
            'friend_id' => $user->UserID,
            'status' => 'pending', // You can set the initial status as needed
        ]);

        $reverseFriendship->save();

        // You can customize the response based on your application's needs
        return response()->json(['message' => 'Friend request sent successfully']);
    }

    // Friendship already exists, return an error message or handle it as needed
    return response()->json(['message' => 'Friendship already exists'], 400);
}



public function acceptFriend(Request $request)
{
    // Validate the incoming request data
    $request->validate([
        'friend_email' => 'required|email|exists:users,email', // Ensure the friend's email exists
    ]);

    // Get the authenticated user
    $user = $request->user();

    // Find the user associated with the provided email
    $friend = User::where('email', $request->input('friend_email'))->first();

    // Find the pending friendship record
    $friendship = Friend::where('user_id', $friend->UserID)
        ->where('friend_id', $user->UserID)
        ->where('status', 'pending')
        ->first();

    if (!$friendship) {
        // Handle the case where the friendship request does not exist or is already accepted
        return response()->json(['message' => 'Friendship request not found or already accepted'], 404);
    }

    // Update the friendship status to 'accepted' for both users
    $friendship->update(['status' => 'accepted']);

    // Also, update the reverse friendship status
    $reverseFriendship = Friend::where('user_id', $user->UserID)
        ->where('friend_id', $friend->UserID)
        ->where('status', 'pending')
        ->first();

    if ($reverseFriendship) {
        $reverseFriendship->update(['status' => 'accepted']);
    }

    // You can customize the response based on your application's needs
    return response()->json(['message' => 'Friendship request accepted successfully']);
}
public function getFriends(Request $request)
{
    // Get the authenticated user
    $user = $request->user();

    // Fetch friends with status "accepted" along with their names
    $friends = $user->acceptedFriends->pluck('name');

    // You can customize the response format and data as needed
    return response()->json(['friends' => $friends]);
}



}
