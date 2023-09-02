<?php
namespace App\Http\Controllers\API;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;

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

    public function uploadFile(Request $request,$recipientId)
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
}
