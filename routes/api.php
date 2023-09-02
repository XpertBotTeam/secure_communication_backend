<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CallController;
use App\Http\Controllers\API\MessageController;
use App\Http\Controllers\API\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


//authentication

Route::post('register', [AuthController::class,'register']);
Route::post('login', [AuthController::class,'login']);

//calls

Route::get('indexCall', [CallController::class, 'indexCall']);
Route::get('showCall/{id}', [CallController::class, 'showCall']);
Route::post('saveCall', [CallController::class, 'saveCall']);
Route::put('updateCall/{id}', [CallController::class, 'updateCall']);
Route::delete('destroyCall/{id}', [CallController::class, 'destroyCall']);

//messages

//Route::get('indexMessage', [MessageController::class, 'indexMessage']);
//Route::post('saveMessage', [MessageController::class, 'saveMessage']);
//Route::put('updateMessage/{id}', [MessageController::class, 'updateMessage']);
Route::middleware('auth:sanctum')->get('showMessage/{recipientId}', [MessageController::class, 'showMessage']);
Route::middleware('auth:sanctum')->post('sendMessage/{recipientId}', [MessageController::class, 'sendMessage']);
Route::delete('destroyMessage/{id}', [MessageController::class, 'destroyMessage']);
Route::middleware('auth:sanctum')->delete('deleteChatMessages', [MessageController::class,'deleteChatMessages']);


//files
// Route::get('indexFile', [FileController::class, 'indexFile']);
// Route::put('updateFile/{id}', [FileController::class, 'updateFile']);
Route::get('showFiles', [FileController::class, 'showFiles']);
Route::post('uploadFile/{recipientId}', [FileController::class, 'uploadFile']);
Route::delete('destroyFile/{id}', [FileController::class, 'destroyFile']);


Route::middleware('auth:sanctum')->get('messagedUsers', [UserController::class, 'messagedUsers']);
Route::post('messagedUsersByEmail', [UserController::class, 'messagedUsersByEmail']);
Route::middleware('auth:sanctum')->post('addFriend', [UserController::class, 'addFriend']);
Route::middleware('auth:sanctum')->post('acceptFriend', [UserController::class, 'acceptFriend']);
Route::middleware('auth:sanctum')->get('getFriends', [UserController::class, 'getFriends']);


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
