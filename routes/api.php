<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NativeController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/sanctum/token', function (Request $request) {
    $request->validate([
        'login' => 'required',
        'password' => 'required',
        'device_name' => 'required',
    ]);
    // $user = User::where('id', 604)->first();

    // return $user;
 
    $user = User::where('login', $request->login)->first();
 
    if (! $user || ! Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }
 
    return $user->createToken($request->device_name)->plainTextToken;
});
// Route::middleware('auth:sanctum')->post('/logout', function (Request $request){

//     $revoke = $request->user()->currentAccessToken()->delete();
//     return $revoke;
// });
Route::middleware('auth:sanctum')->post('/logout', [NativeController::class, 'logout']);
Route::middleware('auth:sanctum')->post('/chat_add_api', [BoardController::class, 'chatAdd']); 
Route::group(["middleware"=>"auth:sanctum"],function(){
    Route::post('/chat_delete_api', [BoardController::class, 'chatDelete']);
    Route::get('/profile_get_update_user', [UserController::class, 'profile_get_update_user']);
    Route::get('/board_possible_users', [BoardController::class, 'board_possible_users']);
    Route::post('/icon_up_api', [BoardController::class, 'getIconUp']); 
    Route::post('/chat_create', [BoardController::class, 'create_new_board']);
    Route::get('/chat_list', [BoardController::class, 'getAllMessage']); 
    Route::get('/get_messages', [BoardController::class, 'getCommentList']);
    Route::post('/set_fmc_token', [NativeController::class, 'set_fmc_token']);
});