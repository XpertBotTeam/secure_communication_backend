<?php

namespace App\Http\Controllers\API;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

use App\Models\User;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

class FileController extends Controller
{
    // public function indexFile()
    // {
    //     $files = File::all();
    //     return response()->json($files);
    // }
    // public function showFile($id)
    // {
    //     $file = File::findOrFail($id);
    //     return response()->json($file);
    // }

    public function showFiles($recipientId)
    {
        // Get the currently authenticated user (sender)
        $sender = auth()->user();

        // Query the database to retrieve files shared between sender and recipient
        $files = File::where(function ($query) use ($sender, $recipientId) {
            $query->where('SenderID', $sender->id)
                ->where('RecipientID', $recipientId)
                ->orWhere(function ($query) use ($sender, $recipientId) {
                    $query->where('SenderID', $recipientId)
                            ->where('RecipientID', $sender->id);
                });
        })
        ->orderBy('created_at', 'asc')
        ->get();

        // Return the list of files as a JSON response
        return response()->json(['files' => $files]);
    }

    public function uploadFiles(Request $request,$recipientId)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,docx,mp3,mp4,xls,xlsx,jpg,jpeg,png,gif,bmp,avi,mkv,wmv,mov,flv,3gp,webm,ogg,ogv,ts',
        ]);        
    
        $file = $request->file('file');
    
        // Store the file in a designated directory (e.g., storage/app/public)
        $path = $file->store('public/files');
    
        // Save the file metadata in the database
        $fileModel = new File([
            'FileName' => $file->getClientOriginalName(),
            'FileSize' => $file->getSize(),
            'FileType' => $file->getClientOriginalExtension(),
            'FileContent' => $path,
            'SenderID' => auth()->id(),
            'RecipientID' => $recipientId, // You can set this later based on your app's logic
        ]);
    
        $fileModel->save();
    
        return redirect()->back()->with('success', 'File uploaded successfully');
    }

    public function updateFile(Request $request, $id)
    {
        $file = File::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'FileName' => 'required',
            'FileSize' => 'required|integer',
            'FileType' => 'required|in:Media,Document,Other',
            'FileContent' => 'required|binary',
            // Add more validation rules for other fields
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed'], 400);
        }

        $file->update($request->all());
        return response()->json($file, 200);
    }

    public function destroyFile($id)
    {
        $file = File::findOrFail($id);
        $file->delete();
        return response()->json(null, 204);
    }


    public function uploadFile(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,pdf,mp4,avi,mov,wmv|max:204800', // Adjust the allowed file types and maximum size as needed
        ]);

        // Handle the file upload
        if ($request->hasFile('file')) {
            $uploadedFile = $request->file('file');
            $fileName = time() . '_' . uniqid() . '.' . $uploadedFile->getClientOriginalExtension();


            // Determine the directory based on file type (e.g., images or videos)
            $fileType = $uploadedFile->getClientOriginalExtension();
            $directory = in_array($fileType, ['jpeg', 'png', 'pdf']) ? 'uploads/images' : 'uploads/videos';

            // Store the file in the appropriate directory
            $path = $uploadedFile->storeAs($directory, $fileName, 'secure');

            // Create a new file record in the database
            $file = new File([
                'FileName' => $fileName,
                'FileSize' => $uploadedFile->getSize(),
                'FileType' => $fileType,
                'FileContent' => $path, // Store the file path for later retrieval
                'SenderID' => auth()->user()->id, // Assuming you have authentication
                'RecipientID' => $recipientId, // Set the recipient ID as needed
            ]);

            $file->save();

            return response()->json(['message' => 'File uploaded successfully'], 201);
        } else {
            return response()->json(['message' => 'No file provided'], 400);
        }
    }



}
