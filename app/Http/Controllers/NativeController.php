<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NativeUser;

class NativeController extends Controller
{
    public function set_fmc_token(Request $request){
        $exist_check = NativeUser::where('user_id', $request->user()->id)->where('device_id', $request->id)->where('model', $request->model)->first();
        if(empty($exist_check)){
            $newUser = NativeUser::create([
                'user_id' => $request->user()->id,
                'device_id' => $request->id, 
                'model' => $request->model,
                'fcm_token' => $request->token
            ]);
            return response()->json($newUser);
        }else{
            $exist_check->update(['fcm_token' => $request->token]);
            return response()->json($exist_check);
        }
    }
    public function logout(Request $request){
        $clean_token = NativeUser::where('fcm_token', $request->fcm_token)->delete();
        $clean_device = NativeUser::where('device_id', $request->device_id)->delete();
        $revoke = $request->user()->currentAccessToken()->delete();
        return $revoke;
        return response()->json($revoke);
    }
}
