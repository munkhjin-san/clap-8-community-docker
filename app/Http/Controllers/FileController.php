<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\appFileRecord;
use App\Models\appFolderRecord;
use App\Models\boardRecord;
use App\Models\boardToUser;
use App\Models\messageRecord;
use App\Models\messageFile;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File; 
use Intervention\Image\Facades\Image;
use Carbon\Carbon;
use DB;
use App\Events\Message;
use Illuminate\Support\Str;
use ZipArchive;
use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;
use Illuminate\Validation\ValidationException;
use Pusher\Pusher;
class FileController extends Controller
{   
    public function cdnExtract(Request $request){    
        

        try {
            $p1 = storage_path('app/managed_files/'. $request->board_id . '/' . $request->path);
            return response()->file($p1);
        } catch (FileNotFoundException $exception) {
            abort(404);
        }

    }
    public function cdnSubExtract(Request $request){     
        

        try {
            $p1 = storage_path('app/managed_files/' . $request->board_id . '/' . $request->sub_folder . '/' . $request->path);  
            return response()->file($p1);
        } catch (FileNotFoundException $exception) {
            abort(404);
        }

    }
    public function cdnExtractDocs(Request $request){     
        if($request->user_id){
            $user = User::findOrFail($request->user_id);
            if($request->keyword == $user->file_key){
                try {
                    $p1 = storage_path('app/managed_files/'. $request->board_id . '/' . $request->path);  
                    return response()->file($p1);
                } catch (FileNotFoundException $exception) {
                    abort(404);
                }
            }else{
                abort(404);
            }
        }
        
        

    }
    public function fetchFileList(Request $request){  
        $validatedData = $request->validate([
            'board_id' => 'required',
        ]);

    
           
        $targetBoard = boardRecord::findOrFail($request->board_id);
        $usercheck = $targetBoard->board_to_users()->where('user_id','=', Auth::id())->first();
        $timeLimit = $usercheck->joined_at; 
        $messageFrom = $targetBoard->message_from;     
        $time_condition = $messageFrom == 0 && $timeLimit;


        $allFiles = messageFile::where('board_id', $request->board_id)->when($time_condition, function ($query) use ($timeLimit) {
            $query->where('created_at', '>=',  $timeLimit );
        })->with('user')->orderBy('created_at', 'desc')
        ->with('unsignedUsers')
        ->get();
        return response()->json($allFiles);
    }

}
