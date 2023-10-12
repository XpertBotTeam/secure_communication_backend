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

    // Retrieve user IDs from the Message model
    $userIds = Message::where('SenderID', $userId)
        ->orWhere('RecipientID', $userId)
        ->distinct()
        ->pluck('SenderID', 'RecipientID')
        ->flatten()
        ->reject(function ($id) use ($userId) {
            return $id == $userId;
        });

    // Retrieve user information (name and email) based on the user IDs
    $messagedUsers = User::whereIn('UserID', $userIds)
        ->select('UserID', 'name', 'email') // Adjust the columns as needed
        ->get();

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

    // public function addFriend(Request $request)
    // {
    //     // Validate the incoming request data
    //     $request->validate([
    //         'friend_email' => 'required|email|exists:users,email', // Ensure the friend's email exists
    //     ]);
    
    //     // Get the authenticated user
    //     $user = $request->user();
    
    //     // Find the user associated with the provided email
    //     $friend = User::where('email', $request->input('friend_email'))->first();
    
    //     // Check if the friendship already exists
    //     if (!$user->friends()->where('friend_id', $friend->UserID)->exists()) {
    //         // Add the friend to the user's list of friends with a pending status
    //         $friendship = new Friend([
    //             'user_id' => $user->UserID,
    //             'friend_id' => $friend->UserID,
    //             'status' => 'pending', // You can set the initial status as needed
    //         ]);
    
    //         $friendship->save();
    
    //         // You can customize the response based on your application's needs
    //         return response()->json(['message' => 'Friend request sent successfully']);
    //     }
    
    //     // Friendship already exists, return an error message or handle it as needed
    //     return response()->json(['message' => 'Friendship already exists'], 400);
    // }

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
    
        // Check if there is an existing accepted friendship between user and friend
        $existingFriendship = Friend::where(function ($query) use ($user, $friend) {
            $query->where('user_id', $user->UserID)
                ->where('friend_id', $friend->UserID)
                ->where('status', 'accepted');
        })->orWhere(function ($query) use ($user, $friend) {
            $query->where('user_id', $friend->UserID)
                ->where('friend_id', $user->UserID)
                ->where('status', 'accepted');
        })->first();
    
        if ($existingFriendship) {
            // Friendship already exists and is accepted, return an error message
            return response()->json(['message' => 'You are already friends with this user'], 400);
        }
    
        // Check if there's an existing pending friend request from the user to the friend
        $existingRequest = Friend::where('user_id', $user->UserID)
            ->where('friend_id', $friend->UserID)
            ->where('status', 'pending')
            ->first();
    
        if (!$existingRequest) {
            // Add the friend to the user's list of friends with a pending status
            $friendship = new Friend([
                'user_id' => $user->UserID,
                'friend_id' => $friend->UserID,
                'status' => 'pending', // You can set the initial status as needed
            ]);
    
            $friendship->save();
    
            // You can customize the response based on your application's needs
            return response()->json(['message' => 'Friend request sent successfully']);
        }
    
        // Pending friend request already exists, return an error message
        return response()->json(['message' => 'A pending friend request already exists'], 400);
    }
    

    

    public function acceptFriend(Request $request)
{
    // Validate the incoming request data
    $request->validate([
        'friend_email' => 'required|email|exists:users,email', // Ensure the friend's email exists
    ]);

    // Get the authenticated user (friend)
    $friend = $request->user();

    // Find the user associated with the provided email (initiator)
    $initiator = User::where('email', $request->input('friend_email'))->first();

    // Find the pending friendship record initiated by the initiator
    $friendship = Friend::where('user_id', $initiator->UserID)
        ->where('friend_id', $friend->UserID)
        ->where('status', 'pending')
        ->first();

    if (!$friendship) {
        // Handle the case where the friendship request does not exist or is already accepted
        return response()->json(['message' => 'Friendship request not found or already accepted'], 404);
    }

    // Update the friendship status to 'accepted' for the friend (authenticated user)
    $friendship->update(['status' => 'accepted']);

    // You can customize the response based on your application's needs
    return response()->json(['message' => 'Friendship request accepted successfully']);
}

    


public function getFriends(Request $request)
{
    // Get the authenticated user
    $user = $request->user();

    // Fetch friends where the status is "accepted" based on both user_id and friend_id
    $friends = Friend::where(function ($query) use ($user) {
        $query->where('user_id', $user->UserID)
            ->orWhere('friend_id', $user->UserID);
    })
    ->where('status', 'accepted')
    ->with(['user', 'friend']) // Load user and friend details
    ->get();

    // Extract the names and emails of friends
    $friendDetails = $friends->map(function ($friendship) use ($user) {
        // Determine whether the authenticated user is the initiator or the friend in the relationship
        $isInitiator = $friendship->user_id == $user->UserID;

        // Get the friend's details based on the role in the relationship
        $friend = $isInitiator ? $friendship->friend : $friendship->user;

        return [
            'name' => $friend->name,
            'email' => $friend->email,
            'id' => $friend->UserID,
        ];
    });

    // You can customize the response format and data as needed
    return response()->json(['friends' => $friendDetails]);
}



public function getFriendRequests(Request $request)
{
    // Get the authenticated user (friend)
    $user = $request->user();

    // Fetch friend requests with status "pending" where the friend is the authenticated user
    $friendRequests = Friend::where('friend_id', $user->UserID)
        ->where('status', 'pending')
        ->with('user') // Load the user who initiated the request
        ->get();

    // Extract details of the users who sent the requests
    $requestDetails = $friendRequests->map(function ($request) {
        return [
            'name' => $request->user->name,
            'email' => $request->user->email,
        ];
    });

    // Return the friend requests or an empty array if there are none
    return response()->json(['friend_requests' => $requestDetails]);
}


    

}
