<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\boardRecord;
use App\Models\messageFile;
use App\Models\User;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Pusher\Pusher;
class FileController extends Controller
{   
    private function active_user(){
        $sub = Auth::user()->linked()->where('main_id', Auth::id())->wherePivot('active', 1)->first();
        if($sub){
            return $sub;
        }else{
            return Auth::user();
        }
    }


    public function fetchFileList(Request $request){ 
        $active_user = $this->active_user(); 
        $validatedData = $request->validate([
            'board_id' => 'required',
        ]);

    
           
        $targetBoard = boardRecord::findOrFail($request->board_id);
        $usercheck = $targetBoard->board_to_users()->where('user_id','=', $active_user->id)->first();
        $timeLimit = $usercheck->created_at; 
        // $messageFrom = $targetBoard->message_from;     
        $time_condition = $timeLimit;


        $allFiles = messageFile::where('board_id', $request->board_id)
        ->when($time_condition, function ($query) use ($timeLimit) {
            $query->where('created_at', '>=',  $timeLimit );
        })->with('user')->orderBy('created_at', 'desc')
        ->with('unsignedUsers')
        ->get();
        return response()->json($allFiles);
    }

}
