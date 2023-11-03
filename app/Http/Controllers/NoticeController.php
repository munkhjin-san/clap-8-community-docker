<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NoticeRecord;
use App\Models\NoticeFile;
use App\Models\User;
use Illuminate\Support\Facades\File; 
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\Notice;
use Auth;
use DB;

class NoticeController extends Controller
{
    public function get_notices(Request $request){
        $key = $request->keyword;
        $notices = NoticeRecord::where('deleted_flag', 0)
        ->when($key, function($q) use($key){
            $q->where(DB::raw("CONCAT_WS('', title, ' ', body)"), 'LIKE', '%' . $key . '%');
        })
        ->orderBy('created_at', 'desc')->with('files')->with('readers')->paginate(20);        
        return response()->json($notices);
    }
    public function get_notice(Request $request){
        $notice = NoticeRecord::where('id', $request->id)->where('deleted_flag', 0)->with('files')->with('readers')->first();        
        $data = !empty($notice) ? $notice : null;
        return response()->json($data);
    }
    public function generate_readers(){
        $notices = NoticeRecord::where('deleted_flag', 0)->whereNotNull('read_users')->get();
        // echo($notices);
        $userExist = User::pluck('id')->toArray();
        $modelCollection = collect($userExist);
        foreach($notices as $notice){
            // $read_list = array_map("intval", explode(",", $notice->read_users));
            // $notice->readers()->sync($read_list);

            $list = explode(',', $notice->read_users);
            if(!empty($list)){
                $filteredSecondArray = collect($list)->filter(function ($item) use ($modelCollection) {
                    return $modelCollection->contains($item);
                })->toArray();
                // $message->reactedUsers()->sync($filteredSecondArray);     
                echo('exists');  
                $notice->readers()->sync($filteredSecondArray);         
            }   
        }
        return;
    }
    public function read_notice(Request $request){
        $record = NoticeRecord::findOrFail($request->record_id);
        if (!$record->readers->contains(Auth::id())) {
            $record->readers()->attach(Auth::id());
            return response()->json('success');
        }
        return response()->json($data);
    }
    public function get_notice_badge(Request $request){
        $notice = NoticeRecord::where('deleted_flag', 0)->where('created_at', '>', '2023-10-01')->where('user_id', '!=', Auth::id())
        ->whereDoesntHave('readers', function ($query) {
            $query->where('users.id', Auth::id());
        })->count();
        return response()->json($notice);
    }
    public function notice_file_upload(Request $request ){    
        $ids = [];
        $path = '/notice_temp';

        // return response()->json($path);
        foreach($request->file() as $file ){         
            $file_extension = $file->getClientOriginalExtension();
            $file_real_name = $file->getClientOriginalName();            
            $mime_type = $file->getMimeType();
            $mime_type_array = explode('/',$mime_type);
            $file_type = $mime_type_array[0];            
            $file_size = $file->getSize();  
            
            $fileRecord = new NoticeFile;
            $fileRecord->name = $file_real_name;
            $fileRecord->mime_type = $file_type;
            $fileRecord->extension = $file_extension;
            
            $fileRecord->user_id = Auth::id();
            $fileRecord->save();
            $set_path = $fileRecord->id . '.' . $fileRecord->extension;

            
            if($file_type == 'image' && $file_extension !== 'svg'){
                $img = Image::make($file)->orientate();
                    
                File::isDirectory(storage_path('app') . $path) or File::makeDirectory(storage_path('app') . '/' . $path, 0755, true, true);                      
                $img->save(storage_path('app') . $path .'/'. $set_path, 30);  
                
            }else{
                Storage::disk('local')->putFileAs(
                    $path, $file, $set_path
                );
            }
            $sizeAfter = File::size(storage_path('app' . $path . '/' . $set_path));
        
            $fileRecord->size = $sizeAfter;
            $fileRecord->save(); 
            $ids[] = $fileRecord;          

            
                    
        }
        return response()->json($ids);             

    }
    public function notice_add_record(Request $request){
        if($request->editId){
            $record = NoticeRecord::findOrFail($request->editId);   
            $removes = NoticeFile::where('record_id', $request->editId)->where('deleted_flag', 0)->whereNotIn('id', $request->exist_files)->get();
            foreach($removes as $remove){
                Storage::disk('local')->delete('notice_files/' . $remove->id . '_' . $remove->user_id . '_' . $remove->record_id . '.' . $remove->extension);
                $remove->delete();
            }

        }else{
            $record = new NoticeRecord;
        }
        $record->user_id = Auth::id();
        $record->title = $request->title;
        $record->body = $request->body;
        $record->save();
        foreach($request->new_files as $add){
            $fileRecord = NoticeFile::find($add['id'])->update(['record_id' => $record->id]);
            $destPath = $add['id'] . '_' . $add['user_id'] . '_' . $record->id . '.' . $add['extension'];
            Storage::disk('local')->move('notice_temp/' .  $add['id'] . '.' .$add['extension'], 'notice_files/' . $destPath);
        }
        if(!$request->editId){
            $mails = User::where('partner_flag','=', 0)->where('id', '>', 105)->where('deleted_flag', '=', 0)->where('retire', '=', 0)->where('email', '!=', '')->where('hide_flag', '=', 0)->whereNotNull('email')->pluck('email')->toArray();
     
            foreach($mails as $to){
                Mail::to($to)->send(new Notice($record->id, $record->title));
            }
        }
        return response()->json($record);   
        
    }
    public function notice_delete(Request $request){
        $record = NoticeRecord::findOrFail($request->id)->delete();   
        $removes = NoticeFile::where('record_id', $request->id)->where('deleted_flag', 0)->get();
        foreach($removes as $remove){
            Storage::disk('local')->delete('notice_files/' . $remove->id . '_' . $remove->user_id . '_' . $remove->record_id . '.' . $remove->extension);
            $remove->delete();
        }
        return response()->json($record);   
    }
}
