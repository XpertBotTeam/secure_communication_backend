<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Edujugon\PushNotification\PushNotification;
use Pusher\Pusher;

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
    // public function sendMessage(Request $request, $recipientId)
    // {
    //     // Get the currently logged-in user
    //     $sender = Auth::user();

    //     // Get the message content from the request
    //     $messageContent = $request->input('message');

    //     // Create a new message record in the database
    //     $message = new Message();
    //     $message->SenderID = $sender->UserID;
    //     $message->RecipientID = $recipientId;
    //     $message->Content = $messageContent;
    //     $message->save();
        
    //     $recipient = User::find($recipientId); // Replace "User" with your recipient model
    //     if ($recipient) {
    //         $push = new PushNotification('fcm');
    //         $push->setMessage([
    //             'notification' => [
    //                 'title' => $sender->name,
    //                 'body' => $messageContent,
    //             ],
    //             'to' => $recipient->remember_token, // Replace with the recipient's FCM token
    //         ]);
    //         $push->send();
    //     }
    //     // Return a success response or any relevant data
    //     return response()->json(['message' => 'Message sent to recipient successfully']);

    // }

    

  

    public function sendMessage(Request $request, $recipientId)
{
    // Get the authenticated user
    $sender = Auth::user();

    // Get the message content from the request
    $messageContent = $request->input('message');

    // Create a new message record in the database
    $message = new Message();
    $message->SenderID = $sender->UserID;
    $message->RecipientID = $recipientId;
    $message->Content = $messageContent;
    $message->save();

    // Construct the FCM notification data
    $notificationData = [
        'title' => $sender->name,
        'body' => $messageContent,
    ];

    // Get the recipient's FCM token (replace with your own logic)
    $recipient = User::find($recipientId);
    $recipientFcmToken = $recipient->device_token; // Replace with the recipient's FCM token

    $customHeaders = [
        'Authorization' => 'key=AAAAZByIAcU:APA91bEQi1lGyafydauOlbbudsey5WuubQvu2U83bzayaexnM271Yl5QltpQSQSKpnmmEc4vcBJhIvG7vdXrq_jagUyeThAmonar_hs66KAX21k0TZnRn0T_4lj5vtiI6NHgt0IkuDwX',
        'Content-Type' => 'application/json',
    ];

    // Send the FCM notification
    $response = Http::withOptions(['verify' => false])
        ->withHeaders($customHeaders)
        ->post('https://fcm.googleapis.com/fcm/send', [
            'notification' => $notificationData,
            'to' => $recipientFcmToken,
        ]);

    // Check if the notification was sent successfully
    if ($response->successful()) {
        // Notification sent successfully
        return response()->json(['message' => 'Message sent to recipient successfully']);
    } else {
        // Handle the error (e.g., log it)
        return response()->json(['error' => 'Failed to send FCM notification'], 500);
    }
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


    // function publishToChannel() {
    //     $channelName = 'mychannel'; // Replace with your desired channel name //email lal receiver
    //     $eventName = 'myevent';     // Replace with your desired event name
    //     $messageData = ['message' => 'Hello, Pusher!']; // Replace with your message data // sender email , date/time  and message 
    //     // Replace with your Pusher credentials and cluster information
    //     $pusherAppId = '1663364';
    //     $pusherKey = 'a6633a240c4dff774ec8';
    //     $pusherSecret = 'e19061d4adad37599c1e';
    //     $pusherCluster = 'eu'; // Replace with your cluster information
    
    //     // Initialize a Pusher client with the correct cluster
    //     $pusher = new Pusher($pusherKey, $pusherSecret, $pusherAppId, [
    //         'cluster' => $pusherCluster,
    //         'useTLS' => false, // Use TLS for secure connections
    //     ]);
    
    //     // Publish the message to the specified channel
    //     $pusher->trigger($channelName, $eventName, $messageData);
    // }


    //optimal for web
    public function publishToChannel(Request $request)
    {
        $receiverEmail = $request->input('receiver_email');
        $eventName = $request->input('event_name');
        $senderEmail = $request->input('sender_email');
        $messageText = $request->input('message');
        $dateTime = now();
    
        // Query the sender's and recipient's User models to get their IDs
        $sender = User::where('email', $senderEmail)->first();
        $recipient = User::where('email', $receiverEmail)->first();
    
        // Ensure the sender and recipient exist
        if (!$sender || !$recipient) {
            return response()->json(['message' => 'Sender or recipient not found'], 404);
        }
    
        // Construct your message data with the retrieved User IDs
        $messageData = [
            'receiver_email' => $receiverEmail,
            'event_name' => $eventName,
            'sender_email' => $senderEmail,
            'message' => $messageText,
            'date_time' => $dateTime,

        ];
    
        // Insert the message into your database using the Message model
        $message = new Message();
        $message->Content = $messageText;
        $message->Status = 'Delivered'; // Adjust the status as needed
        $message->SenderID = $sender->UserID;
        $message->RecipientID = $recipient->UserID;
        $message->Timestamp = $dateTime;
        $message->save();
    
        // Replace with your Pusher credentials and cluster information
        $pusherAppId = '1663364';
        $pusherKey = 'a6633a240c4dff774ec8';
        $pusherSecret = 'e19061d4adad37599c1e';
        $pusherCluster = 'eu'; // Replace with your cluster information
    
        // Initialize a Pusher client with the correct cluster
        $pusher = new Pusher($pusherKey, $pusherSecret, $pusherAppId, [
            'cluster' => $pusherCluster,
            'useTLS' => false, // Use TLS for secure connections
        ]);
    
        // Use the receiver's email as the channel name and the provided event name
        $channelName = $receiverEmail;
        $pusher->trigger($channelName, $eventName, $messageData);
    
        return response()->json(['message' => 'Message sent and saved successfully']);
    }



    public function chat(Request $request)
    {
        // Get the user's message from the request
        $userMessage = $request->input('message');

        // Set up your OpenAI API request payload
        $payload = [
            'model' => 'gpt-3.5-turbo-0613', // Replace with your specific model
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful assistant.',
                ],
                [
                    'role' => 'user',
                    'content' => $userMessage,
                ],
            ],
        ];

        // Make a request to the OpenAI API
        $apiKey = env('OPENAI_API_KEY');
        $response = Http::withoutVerifying()
            ->withToken($apiKey)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.openai.com/v1/chat/completions', $payload);

        $responseData = $response->json();

        // Extract and return the assistant's response
        $assistantResponse = $responseData['choices'][0]['message']['content'];

        return response()->json(['message' => $assistantResponse]);
    }
   // channelname=email receiver
   // message=data{sender email, datetime, message}

}