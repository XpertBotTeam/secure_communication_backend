<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    // public function indexMessage()
    // {
    //     $messages = Message::all();
    //     return response()->json($messages);
    // }

    // public function showMessages($id)
    // {
    //     $message = Message::findOrFail($id);
    //     return response()->json($message);
    // }

    

    //ignore this function
    // public function saveMessage(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'Content' => 'required',
    //         'Status' => 'required|in:Read,Delivered,Seen',
    //         // Add more validation rules for other fields
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['error' => 'Validation failed'], 400);
    //     }

    //     $message = Message::create($request->all());
    //     return response()->json($message, 201);
    // }


    public function showMessage($recipientId)
    {
        // Get the currently logged-in user
        $user = Auth::user();

    // Find messages between the logged-in user and the specified recipient
    $messages = Message::where(function ($query) use ($user, $recipientId) {
        $query->where('SenderID', $user->UserID)
              ->where('RecipientID', $recipientId)
              ->orWhere(function ($query) use ($user, $recipientId) {
                  $query->where('SenderID', $recipientId)
                        ->where('RecipientID', $user->UserID);
              });
    })
    ->orderBy('created_at', 'asc') // Optional: Order messages by timestamp
    ->get();

    return response()->json($messages);
    }

    //Create message:
    public function sendMessage(Request $request, $recipientId)
    {
        // Get the currently logged-in user
        $sender = Auth::user();

        // Get the message content from the request
        $messageContent = $request->input('message');

        // Create a new message record in the database
        $message = new Message();
        $message->SenderID = $sender->UserID;
        $message->RecipientID = $recipientId;
        $message->Content = $messageContent;
        $message->save();
        broadcast(new NewMessage($message))->toOthers();
        // Return a success response or any relevant data
        return response()->json(['message' => 'Message sent to recipient successfully']);
    }

    public function getMessages($recipientId)
    {
        // Authenticate the user if not already authenticated (e.g., in a WebSocket middleware)
        Auth::loginUsingId($recipientId);

        // Subscribe the user to their private channel
        Redis::subscribe(['private-user.' . $recipientId], function ($message) {
            // Handle incoming messages, e.g., broadcast to the client
            broadcast(new NewMessage(json_decode($message)));
        });

        // Return a response (usually, WebSocket connections stay open)
        // You may not need to return a response in this context
        return response()->json(['message' => 'WebSocket connection established']);
    }

//I dont think this is relevant for a chat app, it is better to not edit a message
    public function updateMessage(Request $request, $id)
    {
        $message = Message::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'Content' => 'required',
            'Status' => 'required|in:Read,Delivered,Seen',
            // Add more validation rules for other fields
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed'], 400);
        }

        $message->update($request->all());
        return response()->json($message, 200);
    }

//delete one message( gets deleted for both sender and recipient))
    public function destroyMessage($id)
    {
        $message = Message::findOrFail($id);
        $message->delete();
        return response()->json(null, 204);
    }
//delete all the chat between users bas kamen it will get delete 3end tnayneton
    public function deleteChatMessages()
    {
        // Get the currently logged-in user
        $user = Auth::user();

        try {
            Message::where('SenderID', $user->UserID)
                ->orWhere('RecipientID', $user->UserID)
                ->delete();
        
            return response()->json(['message' => 'All chat messages deleted successfully']);
        } catch (\Exception $e) {
            // Log the error or return an error response
            return response()->json(['error' => 'Failed to delete chat messages'], 500);
        }
        
    }


}