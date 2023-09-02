<?php

namespace App\Http\Controllers\API;

use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
class CallController extends Controller
{
    public function indexCall()
    {
        $calls = Call::all();
        return response()->json($calls);
    }

    public function showCall($id)
    {
        $call = Call::findOrFail($id);
        return response()->json($call);
    }

    public function saveCall(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'CallType' => 'required|in:Voice,Video',
            // Add more validation rules for other fields
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed'], 400);
        }

        $call = Call::create($request->all());
        return response()->json($call, 201);
    }

    public function updateCall(Request $request, $id)
    {
        $call = Call::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'CallType' => 'required|in:Voice,Video',
            // Add more validation rules for other fields
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed'], 400);
        }

        $call->update($request->all());
        return response()->json($call, 200);
    }

    public function destroyCall($id)
    {
        $call = Call::findOrFail($id);
        $call->delete();
        return response()->json(null, 204);
    }
}
