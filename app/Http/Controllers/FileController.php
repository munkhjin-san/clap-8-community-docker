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
    public function removeTempPath(Request $request){
        $validatedData = $request->validate([
            'u_path' => 'required',
        ]);
        $path = storage_path('app/managed_files/temp_download/' . $request->u_path);

        if (\File::exists($path)) \File::deleteDirectory($path);
    }
    public function downloadRequest(Request $request){

        $now = (string)now()->timestamp;
        $path = substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(8/strlen($x)) )),1,8);

        $u_id = (string)Auth::id() . '_' . $now . $path;
        $state = [
            "success" => true,
            "path" => $u_id
        ];
        return response()->json($state);
    }
    public function downloadManagedFiles(Request $request){

       
        $validatedData = $request->validate([
            'q' => 'required',
            'u_path' => 'required',
        ]);

        $q = $request->q;
        if(count($q) == 1 && $q[0]['folder'] == 0){

            $path = storage_path('app/managed_files/') . $q[0]['record_id'] . '/' . $q[0]['path']. '.' . $q[0]['extension'];
            if (File::exists($path)) {  
                return response()->download($path);
            }else{
                throw ValidationException::withMessages(['field_name' => 'This value is incorrect']);
            }
            return response()->json(1);
        }else{

        
            $u_path = $request->u_path;
            


            // $zip->addEmptyDir('root');
            $folders = array_filter($q, function($k) {
                return $k['folder'] == 1;
            },);
            $folder_id = array_column($folders, 'id');
            $files = array_filter($q, function($k) {
                return $k['folder'] == 0;
            },);

            $r_path = $u_path;
            $path = storage_path('app/managed_files/temp_download/' . $r_path);
            File::makeDirectory($path, 0705, true, true);

            $folders = appFolderRecord::whereIn('id', $folder_id)->get();
            if($folders){
                foreach($folders as $folder){
                    File::makeDirectory($path.'/'.$folder->path, 0705, true, true);
                    if(!$folder->files->isEmpty()){
                        foreach($folder->files as $obj){
                            Storage::disk('s3')->copy(storage_path('app/managed_files/') . $obj->record_id . '/' . $obj->path .  '.' .  $obj->extension, $path.'/'.$folder->path. '/' .  $obj->name . '.' . $obj->extension);
                        } 
                    }
                    if(!$folder->sub_directories->isEmpty()){
                        $arrayData = $this->recursive_folder_structure($path.'/'.$folder->path, $folder);
                    }                  
                }
            }
            if($files){
                foreach($files as $obj){
                    Storage::disk('s3')->copy(storage_path('app/managed_files/'). $obj['record_id'] . '/' . $obj['path'] .  '.' .  $obj['extension'], $path . '/' .  $obj['name'] . '.' . $obj['extension']);
                }
            }

            $zipname = 'file.zip';
            $zip = new ZipArchive;
            $zip->open(storage_path('app/managed_files/temp_download/') . $r_path . '/' . $zipname, ZipArchive::CREATE);

            $rootPath = realpath(storage_path('app/managed_files/temp_download/' . $r_path));

            $files_list = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath), RecursiveIteratorIterator::LEAVES_ONLY);

            foreach ($files_list as $name => $file)
            {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);

                if (!$file->isDir())
                {
                    // Add current file to archive
                    $zip->addFile($filePath, $relativePath);
                }else {
                    if($relativePath !== false)
                        $zip->addEmptyDir($relativePath);
                }
            }

            $zip->close();           

            return response()->download(storage_path('app/managed_files/temp_download/'. $r_path . '/' . $zipname));
        }
        

        
    }
    private function recursive_folder_structure($path, $folder) {

        
        if(!$folder->files->isEmpty()){
            foreach($folder->files as $obj){
                Storage::disk('s3')->copy(storage_path('app/managed_files/') . $obj->record_id . '/' . $obj->path .  '.' .  $obj->extension, $path . '/' .  $obj->name . '.' . $obj->extension);
            } 
        }
        if(!$folder->sub_directories->isEmpty()){
            foreach($folder->sub_directories as $folder){
                File::makeDirectory($path.'/'.$folder->path, 0705, true, true);
                $arrayData = $this->recursive_folder_structure($path.'/'.$folder->path, $folder);
            }
        }
        return;
    }
    private function recursive_download($zip, $q) {


        foreach($q as $item){
            if($item['folder'] == 1){
                $zip->addEmptyDir($item['path']);

                // $inner_files = appFileRecord::where('recycle_flag', 0)->where('record_id', $item['record_id'])->where('parent_id', $item['id'])->get();
                // $inner_folders = appFolderRecord::where('recycle_flag', 0)->where('record_id', $item['record_id'])->where('parent_id', $item['id'])->get();
            }else{
                $zip->addFile(storage_path('app/managed_files/') . $item['record_id'] . '/' . $item['path'] . '.' . $item['extension'], $item['name'] . '.'. $item['extension']);
            }
        }
        return $zip;
    }
    public function getFileList(Request $request){

        $res = [];
        
            $folders = appFolderRecord::where('recycle_flag', '=', $request->recycle_flag)->where('record_id', '=', $request->rec_id)->where('parent_id', '=', $request->par_id)->with('user')->get();
            $files = appFileRecord::where('recycle_flag', '=', $request->recycle_flag)->where('record_id', '=', $request->rec_id)->where('parent_id', '=', $request->par_id)->with('user')->get();
            $parent_data = null;
            if($request->par_id == 0){
                $board = boardRecord::find($request->rec_id);
                if($board->private_flag == 0 || $board->private_flag == 3){
                    $parent_data = ["id" => 0, "path" => $board->title];
                }else if($board->private_flag == 1){
                    $user = boardToUser::where('record_id', $board->id)->where('deleted_status', 0)->where('user_id', '!=', Auth::id())->first();
                    if($user){
                        $parent_data = ["id" => 0, "path" => $user->user->name];
                    }
                }
                
            }else{
                $p_data = appFolderRecord::find($request->par_id);
                $parent_data = ["id" => $p_data->id, "path" => $p_data->path];
            }
        $res['folders'] = $folders;
        $res['files'] = $files;
        $res['parent'] = $parent_data;

        return response()->json($res);
    }
    public function getRecycleList(Request $request){
        $res = [];
        
            $folders = appFolderRecord::where('recycle_flag', '=', 1)->where('record_id', '=', $request->rec_id)->where('parent_id', '=', $request->par_id)->with('user')->get();
            $files = appFileRecord::where('recycle_flag', '=', 1)->where('record_id', '=', $request->rec_id)->where('parent_id', '=', $request->par_id)->with('user')->get();
        
        $res['folders'] = $folders;
        $res['files'] = $files;
        $parent_data = ["id" => 0, "path" => 'ゴミ箱'];

        return response()->json($res);
    }
    public function createNewFolder(Request $request){
        
        if(empty($request->name)){
            $f_path = 'newFolder';
        }else{
            $f_path = $request->name;
        }   
        $exist_folders = appFolderRecord::where('recycle_flag', 0)->where('record_id', $request->record_id)->where('parent_id', $request->active_folder)->where('path', $f_path)->count();
        if($exist_folders > 0){
            $flag = true;
            $key = 1;
            while($flag) {
                $defaults = appFolderRecord::where('recycle_flag', 0)->where('record_id', $request->record_id)->where('parent_id', $request->active_folder)->where('path', $f_path . '(' . $key . ')')->count();
                if($defaults == 0){
                    $flag = false;
                }else{
                    $key = $key + 1;
                }
            }
           
            $f_path = $f_path . '(' . $key . ')';
            
            
        }
        
        $folder = new appFolderRecord;      
        $folder->path = $f_path;
        $folder->user_id = Auth::id();
        $folder->parent_id = $request->active_folder;
        $folder->record_id = $request->record_id;
        $folder->color = $request->color;
        $folder->save();
        return response()->json($folder);
    }
    public function editFolder(Request $request){
        
        if($request->item['folder'] == 1){
            $folder = appFolderRecord::find($request->item['id']);
            if($folder){

                if(empty($request->name)){
                    $f_path = '新しいフォルダ';
                }else{
                    $f_path = $request->name;
                }            
                $exist_folders = appFolderRecord::where('id', '!=', $folder->id)->where('recycle_flag', 0)->where('record_id', $folder->record_id)->where('parent_id', $folder->parent_id)->where('path', $f_path)->count();
               
                if($exist_folders > 0){
                    return response()->json('duplicates');
                }
                $folder->path = $f_path;
                $folder->color = $request->color;
                $folder->timestamps = false;
                $folder->save();
                return response()->json($folder);
            }
        }else if($request->item['folder'] == 0){
            $file = appFileRecord::find($request->item['id']);
            if($file){     
                $f_name = $request->name;
                $exist_file = appFileRecord::where('id', '!=', $file->id)->where('recycle_flag', 0)->where('record_id', $file->record_id)->where('parent_id', $file->parent_id)->where('name', $f_name)->count();
               
                if($exist_file > 0){
                    return response()->json('duplicates');
                }           
                $file->name = $f_name;                      
                $file->save();
                return response()->json($file);
            }
        }

        
    }
    public function restoreItems(Request $request){
        foreach($request->list as $item){
            if($item['folder'] == 1){
                $targetFolder = appFolderRecord::find($item['id']);
                $f_name = $targetFolder->path;
                $exist_file = appFolderRecord::where('recycle_flag', 0)->where('record_id',$targetFolder->record_id)->where('parent_id', 0)->where('path', $f_name)->count();
                
                if($exist_file > 0){                    
                    $flag = true;
                    $key = 1;
                    while($flag) {
                        $defaults = appFolderRecord::where('recycle_flag', 0)->where('record_id',$targetFolder->record_id)->where('parent_id', 0)->where('path', $f_name . '(' . $key . ')')->count();
                        if($defaults == 0){
                            $flag = false;
                        }else{
                            $key = $key + 1;
                        }
                    }            
                    $f_name = $f_name . '(' . $key . ')';               
                }
                $targetFolder->update(['recycle_flag' => 0, 'updated_by' => Auth::id(), 'path' => $f_name,  'parent_id' => 0, 'deleted_at' => null]);
                if(!$targetFolder->files->isEmpty()){
                    foreach($targetFolder->files as $file){
                        $file->update(['recycle_flag' => 0, 'updated_by' => Auth::id(), 'deleted_at' => null]);
                    }
                }
                if(!$targetFolder->sub_directories->isEmpty()){
                    foreach($targetFolder->sub_directories as $folder){
                        $arrayData = $this->recursive_restore($folder);
                    }
                    
                    // return response()->json($targetFolder->sub_directories);
                }else{
                    // return response()->json('empty');
                }
            }else if($item['folder'] == 0){
                $targetFolder = appFileRecord::find($item['id']);
                if($targetFolder){
                    $f_name = $targetFolder->name;
                    $exist_file = appFileRecord::where('recycle_flag', 0)->where('record_id',$targetFolder->record_id)->where('parent_id', 0)->where('name', $f_name)->count();
                    
                    if($exist_file > 0){                    
                        $flag = true;
                        $key = 1;
                        while($flag) {
                            $defaults = appFileRecord::where('recycle_flag', 0)->where('record_id',$targetFolder->record_id)->where('parent_id', 0)->where('name', $f_name . '(' . $key . ')')->count();
                            if($defaults == 0){
                                $flag = false;
                            }else{
                                $key = $key + 1;
                            }
                        }            
                        $f_name = $f_name . '(' . $key . ')';               
                    }
                    $old_id = $targetFolder->parent_id;
                    $targetFolder->update(['recycle_flag' => 0, 'updated_by' => Auth::id(), 'name' => $f_name, 'parent_id' => 0, 'deleted_at' => null]);
                    
                }else{
                    $returnData = array(
                        'status' => 'error',
                        'message' => 'An error occurred!'
                    );
                    return response()->json($returnData, 500); 
                }
            }

        }
        return response()->json('success');
        // $targetFolder = appFolderRecord::find($request->id);       
        // $targetFolder->update(['recycle_flag' => 0, 'updated_by' => Auth::id()]);
        // if(!$targetFolder->files->isEmpty()){
        //     foreach($targetFolder->files as $file){
        //         $file->update(['recycle_flag' => 0]);
        //     }
        // }
        // if(!$targetFolder->sub_directories->isEmpty()){
        //     $arrayData = $this->recursive_restore($targetFolder->sub_directories);
        //     return response()->json($targetFolder->sub_directories);
        // }else{
        //     return response()->json('empty');
        // }
    }
    private function recursive_restore($folder) {
            $folder->update(['recycle_flag' => 0, 'updated_by' => Auth::id(), 'deleted_at' => null]);
            if(!$folder->files->isEmpty()){
                foreach($folder->files as $file){
                    $file->update(['recycle_flag' => 0, 'updated_by' => Auth::id(), 'deleted_at' => null]);
                }
            }
            if(!$folder->sub_directories->isEmpty()){
                foreach($folder->sub_directories as $folder){
                    $res = $this->recursive_restore($folder);
                }               
            }else{
                $res = [];
            }
        return;
    }
    public function restoreFile(Request $request){
                
        $targetFolder = appFileRecord::find($request->id);
        if($targetFolder){
            $old_id = $targetFolder->parent_id;
            $targetFolder->update(['recycle_flag' => 0, 'updated_by' => Auth::id(), 'parent_id' => 0]);
            return response()->json('success');
        }else{
            $returnData = array(
                'status' => 'error',
                'message' => 'An error occurred!'
            );
            return response()->json($returnData, 500); 
        }
    }
    public function deleteFolder(Request $request){
        foreach($request->list as $targetFolder){

            $targetFolder = appFolderRecord::find($request->id);
            $old_id = $targetFolder->parent_id;
            $targetFolder->update(['recycle_flag' => 1, 'updated_by' => Auth::id(), 'old_parent_id' => $old_id, 'parent_id' => 0, 'deleted_at' => now()]);
            if(!$targetFolder->files->isEmpty()){
                foreach($targetFolder->files as $file){
                    $file->update(['recycle_flag' => 1, 'deleted_at' => now()]);
                }
            }
            if(!$targetFolder->sub_directories->isEmpty()){
                $arrayData = $this->recursive_delete($targetFolder->sub_directories);
                return response()->json($targetFolder->sub_directories);
            }else{
                return response()->json('empty');
            }
        }

        
    }
    public function deleteFile(Request $request){
        
        foreach($request->list as $item){
            if($item['folder'] == 1){
                $targetFolder = appFolderRecord::find($item['id']);
                $old_id = $targetFolder->parent_id;
                $targetFolder->update(['recycle_flag' => 1, 'updated_by' => Auth::id(), 'old_parent_id' => $old_id, 'parent_id' => 0, 'deleted_at' => now()]);
                if(!$targetFolder->files->isEmpty()){
                    foreach($targetFolder->files as $file){
                        $file->update(['recycle_flag' => 1, 'updated_by' => Auth::id(), 'old_parent_id' => $old_id,'deleted_at' => now()]);
                    }
                }
                if(!$targetFolder->sub_directories->isEmpty()){
                    foreach($targetFolder->sub_directories as $folder){
                        $arrayData = $this->recursive_delete($folder);
                    }   
                }else{
                }
            }else if($item['folder'] == 0){
                $targetFolder = appFileRecord::find($item['id']);
                if($targetFolder){
                    $old_id = $targetFolder->parent_id;
                    $targetFolder->update(['recycle_flag' => 1, 'updated_by' => Auth::id(), 'old_parent_id' => $old_id, 'parent_id' => 0, 'deleted_at' => now()]);
                    
                }else{
                    $returnData = array(
                        'status' => 'error',
                        'message' => 'An error occurred!'
                    );
                    return response()->json($returnData, 500); 
                }
            }

        }
        return response()->json('success');
    }
    private function recursive_delete($folder) {
        $folder->update(['recycle_flag' => 1, 'updated_by' => Auth::id(), 'deleted_at' => now()]);
        if(!$folder->files->isEmpty()){
            foreach($folder->files as $file){
                $file->update(['recycle_flag' => 1, 'updated_by' => Auth::id(), 'deleted_at' => now()]);
            }
        }
        if(!$folder->sub_directories->isEmpty()){
            foreach($folder->sub_directories as $folder){
                $res = $this->recursive_delete($folder);
            }
            
        }else{
            $res = [];
        }        
        return;
    }
    public function movePasteItems(Request $request){
        // return response()->json($request);
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        if(empty($auth_user_id)){               
            return response()->json("loggedOut");
        }
        if($request->data){
            foreach($request->data['objects'] as $obj){
                if($obj['folder'] == 0){

                    $file = appFileRecord::findOrFail($obj['id']);
                    $file->record_id = $request->data['target_record_id']; 
                    $file->parent_id = $request->data['target_parent_id'];  
                    $file->updated_by = $auth_user_id;
                    $file->save();
                    
                    
                }else if($obj['folder'] == 1){
                    $source_folder = appFolderRecord::findOrFail($obj['id']);                    
                    $source_folder->updated_by = $auth_user_id;
                    $source_folder->parent_id = $request->data['target_parent_id'];  
                    $source_folder->record_id = $request->data['target_record_id']; 
                    $source_folder->save();                    
                }
            }
        }
        return response()->json($request);
    }
    private function recursive_move($folder, $src) {
            $auth_user_id = Auth::id();
            
       
            $folder->updated_by = $auth_user_id;  
            $folder->record_id = $src->data['target_record_id']; 
            $folder->save();
            if(!$folder->files->isEmpty()){
                foreach($folder->files as $file){


                    
                    $source_path = $file->path . '.' .$file->extension;
                    $target_path = $file->path . '.' .$file->extension;
                    $new_path = storage_path('app/managed_files/' . $src->data['target_record_id']);
                    $old_path = storage_path('app/managed_files/' . $src->data['source_record_id']); 
                    File::isDirectory($new_path) or File::makeDirectory($new_path, 0705, true, true);
                    if($file->mime_type == 'image'){
                        $thumb_path_new = $new_path . '/thumb';    
                        File::isDirectory($thumb_path_new) or File::makeDirectory($thumb_path_new, 0705, true, true);                                
                        File::move($old_path . '/thumb' . '/' . $file->path . '_thumb_50' . '.' .  $file->extension,$new_path . '/thumb' . '/' . $file->path . '_thumb_50' . '.' . $file->extension);
                        File::move($old_path . '/thumb' . '/' . $file->path . '_thumb_100' . '.' .  $file->extension,$new_path . '/thumb' . '/' . $file->path . '_thumb_100' . '.' . $file->extension);
                    } 
                                
                    File::move($old_path . '/' . $source_path ,$new_path . '/' . $target_path );

                    $file->record_id = $src->data['target_record_id'];                            
                    $file->updated_by = $auth_user_id;                           
                    $file->save(); 
                }
            }
            
            if(!$folder->sub_directories->isEmpty()){                
                foreach($folder->sub_directories as $folder){
                    $arrayData = $this->recursive_move($folder, $src);
                }
                return response()->json($arrayData);
                
            }
        return;
        
        
    }
    public function copyPasteItems(Request $request){
        // return response()->json($request);
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        if(empty($auth_user_id)){               
            return response()->json("loggedOut");
        }
        if($request->data){
            foreach($request->data['objects'] as $obj){
                if($obj['folder'] == 0){
                    $rand = substr(str_shuffle('0123456789'),1,6);
                    $now = now()->timestamp;
                    $stamp = date("YmdHis", $now);
                    $file_path = $stamp . '_' . $rand;
                    $source_path = $obj['path'] . '.' .$obj['extension'];
                    $target_path = $file_path . '.' .$obj['extension'];
                    // $new_path = storage_path('app/managed_files/' . $request->data['target_record_id']);
                    // $old_path = storage_path('app/managed_files/' . $request->data['source_record_id']); 
                    $new_path = 'managed_files/' . $request->data['target_record_id'];
                    $old_path = 'managed_files/' . $request->data['source_record_id']; 
                    // File::isDirectory($new_path) or File::makeDirectory($new_path, 0705, true, true); 
                    if (!Storage::disk('s3')->exists($new_path)) {
                        Storage::disk('s3')->makeDirectory($new_path);
                    }
                    if($obj['mime_type'] == 'image'){
                        $thumb_path_new = $new_path . '/thumb';   
                        if (!Storage::disk('s3')->exists($thumb_path_new)) {
                            Storage::disk('s3')->makeDirectory($thumb_path_new);
                        }  
                        Storage::disk('s3')->copy($old_path . '/thumb' . '/' . $obj['path'] . '_thumb_50' . '.' .  $obj['extension'], $new_path . '/thumb' . '/' . $file_path . '_thumb_50' . '.' . $obj['extension']);
                        Storage::disk('s3')->copy($old_path . '/thumb' . '/' . $obj['path'] . '_thumb_100' . '.' .  $obj['extension'], $new_path . '/thumb' . '/' . $file_path . '_thumb_100' . '.' . $obj['extension']);
                       
                    } 
                               
                    Storage::disk('s3')->copy($old_path . '/' . $source_path , $new_path . '/' . $target_path ); 
                    $f_name = $obj['name'] . '(コピー)';
                    $exist_file = appFileRecord::where('recycle_flag', 0)->where('record_id',$request->data['target_record_id'])->where('parent_id', $request->data['target_parent_id'])->where('name', $f_name)->count();
                    if($exist_file > 0){
                        $flag = true;
                        $key = 1;
                        while($flag) {
                            $defaults = appFileRecord::where('recycle_flag', 0)->where('record_id',$request->data['target_record_id'])->where('parent_id', $request->data['target_parent_id'])->where('name', $f_name . '(' . $key . ')')->count();
                            if($defaults == 0){
                                $flag = false;
                            }else{
                                $key = $key + 1;
                            }
                        }            
                        $f_name = $f_name . '(' . $key . ')';               
                        
                    }
                    $newFile = new appFileRecord;
                    $newFile->record_id = $request->data['target_record_id']; 
                    $newFile->name = $f_name;
                    $newFile->extension = $obj['extension'];
                    $newFile->path = $file_path;
                    $newFile->user_id = $auth_user_id;
                    $newFile->mime_type = $obj['mime_type'];
                    $newFile->parent_id = $request->data['target_parent_id'];         
                    $newFile->size = $obj['size'];
                    $newFile->save(); 
                    // return response()->json('bb');
                }else if($obj['folder'] == 1){
                    $f_name = $obj['path'] . '(コピー)';
                    $exist_file = appFolderRecord::where('recycle_flag', 0)->where('record_id',$request->data['target_record_id'])->where('parent_id', $request->data['target_parent_id'])->where('path', $f_name)->count();
                    if($exist_file > 0){
                        $flag = true;
                        $key = 1;
                        while($flag) {
                            $defaults = appFolderRecord::where('recycle_flag', 0)->where('record_id',$request->data['target_record_id'])->where('parent_id', $request->data['target_parent_id'])->where('path', $f_name . '(' . $key . ')')->count();
                            if($defaults == 0){
                                $flag = false;
                            }else{
                                $key = $key + 1;
                            }
                        }            
                        $f_name = $f_name . '(' . $key . ')';               
                        
                    }
                    $source_folder = appFolderRecord::findOrFail($obj['id']);
                    $clone_folder = new appFolderRecord;
                    $clone_folder->user_id = $auth_user_id;
                    $clone_folder->parent_id = $request->data['target_parent_id'];  
                    $clone_folder->record_id = $request->data['target_record_id']; 
                    $clone_folder->path = $f_name;
                    $clone_folder->color = $obj['color'];
                    $clone_folder->save();
                    if(!$source_folder->files->isEmpty()){
                        foreach($source_folder->files as $file){
                            $rand = substr(str_shuffle('0123456789'),1,6);
                            $now = now()->timestamp;
                            $stamp = date("YmdHis", $now);
                            $file_path = $stamp . '_' . $rand;
                            $source_path = $file->path . '.' .$file->extension;
                            $target_path = $file_path . '.' .$file->extension;
                            $new_path = 'managed_files/' . $request->data['target_record_id'];
                            $old_path = 'managed_files/' . $request->data['source_record_id']; 

                            if (!Storage::disk('s3')->exists($new_path)) {
                                Storage::disk('s3')->makeDirectory($new_path);
                            }
                            if($file->mime_type == 'image'){
                                $thumb_path_new = $new_path . '/thumb';                                                               
                                if (!Storage::disk('s3')->exists($thumb_path_new)) {
                                    Storage::disk('s3')->makeDirectory($thumb_path_new);
                                }                                      
                                Storage::disk('s3')->copy($old_path . '/thumb' . '/' . $file->path . '_thumb_50' . '.' .  $file->extension, $new_path . '/thumb' . '/' . $file_path . '_thumb_50' . '.' . $file->extension);
                                Storage::disk('s3')->copy($old_path . '/thumb' . '/' . $file->path . '_thumb_100' . '.' .  $file->extension, $new_path . '/thumb' . '/' . $file_path . '_thumb_100' . '.' . $file->extension);
                            } 
                                        
                            Storage::disk('s3')->copy($old_path . '/' . $source_path , $new_path . '/' . $target_path );
                            $f_name = $file->name;
                            $exist_file = appFileRecord::where('recycle_flag', 0)->where('record_id',$request->data['target_record_id'])->where('parent_id', $clone_folder->id)->where('name', $f_name)->count();
                            if($exist_file > 0){
                                $flag = true;
                                $key = 1;
                                while($flag) {
                                    $defaults = appFileRecord::where('recycle_flag', 0)->where('record_id',$request->data['target_record_id'])->where('parent_id', $clone_folder->id)->where('name', $f_name . '(' . $key . ')')->count();
                                    if($defaults == 0){
                                        $flag = false;
                                    }else{
                                        $key = $key + 1;
                                    }
                                }            
                                $f_name = $f_name . '(' . $key . ')';               
                                
                            }
                            $clone_file = new appFileRecord;
                            $clone_file->record_id = $request->data['target_record_id']; 
                            $clone_file->name = $f_name;
                            $clone_file->extension = $file->extension;
                            $clone_file->path = $file_path;
                            $clone_file->user_id = $auth_user_id;
                            $clone_file->mime_type = $file->mime_type;
                            $clone_file->parent_id = $clone_folder->id;         
                            $clone_file->size = $file->size;
                            $clone_file->save(); 
                        }
                    }
                    if(!$source_folder->sub_directories->isEmpty()){
                        foreach($source_folder->sub_directories as $folder){
                            $arrayData = $this->recursive_copy($folder, $request, $clone_folder);
                        }                        
                        
                    }

                    
                }
            }
        }
        return response()->json($request);
    }
    private function recursive_copy($folder, $src, $parent) { 
        $auth_user_id = Auth::id();        
        $clone_folder = new appFolderRecord;
        $clone_folder->user_id = $auth_user_id;
        $clone_folder->parent_id = $parent->id;  
        $clone_folder->record_id = $src->data['target_record_id']; 
        $clone_folder->path = $folder->path;
        $clone_folder->color = $folder->color;
        $clone_folder->save();
        if(!$folder->files->isEmpty()){
            foreach($folder->files as $file){
                $rand = substr(str_shuffle('0123456789'),1,6);
                $now = now()->timestamp;
                $stamp = date("YmdHis", $now);
                $file_path = $stamp . '_' . $rand;
                $source_path = $file->path . '.' .$file->extension;
                $target_path = $file_path . '.' .$file->extension;
                $new_path = storage_path('app/managed_files/' . $src->data['target_record_id']);
                $old_path = storage_path('app/managed_files/' . $src->data['source_record_id']); 
                if (!Storage::disk('s3')->exists($new_path)) {
                    Storage::disk('s3')->makeDirectory($new_path);
                }
                if($file->mime_type == 'image'){
                    $thumb_path_new = $new_path . '/thumb';                                                               
                    if (!Storage::disk('s3')->exists($thumb_path_new)) {
                        Storage::disk('s3')->makeDirectory($thumb_path_new);
                    }                                      
                    Storage::disk('s3')->copy($old_path . '/thumb' . '/' . $file->path . '_thumb_50' . '.' .  $file->extension, $new_path . '/thumb' . '/' . $file_path . '_thumb_50' . '.' . $file->extension);
                    Storage::disk('s3')->copy($old_path . '/thumb' . '/' . $file->path . '_thumb_100' . '.' .  $file->extension, $new_path . '/thumb' . '/' . $file_path . '_thumb_100' . '.' . $file->extension);
                } 
                            
                Storage::disk('s3')->copy($old_path . '/' . $source_path , $new_path . '/' . $target_path );

                $f_name = $file->name;
                $exist_file = appFileRecord::where('recycle_flag', 0)->where('record_id',$src->data['target_record_id'])->where('parent_id', $clone_folder->id)->where('name', $f_name)->count();
                if($exist_file > 0){
                    $flag = true;
                    $key = 1;
                    while($flag) {
                        $defaults = appFileRecord::where('recycle_flag', 0)->where('record_id',$src->data['target_record_id'])->where('parent_id', $clone_folder->id)->where('name', $f_name . '(' . $key . ')')->count();
                        if($defaults == 0){
                            $flag = false;
                        }else{
                            $key = $key + 1;
                        }
                    }            
                    $f_name = $f_name . '(' . $key . ')';               
                    
                }
                $clone_file = new appFileRecord;
                $clone_file->record_id = $src->data['target_record_id']; 
                $clone_file->name = $f_name;
                $clone_file->extension = $file->extension;
                $clone_file->path = $file_path;
                $clone_file->user_id = $auth_user_id;
                $clone_file->mime_type = $file->mime_type;
                $clone_file->parent_id = $clone_folder->id;         
                $clone_file->size = $file->size;
                $clone_file->save(); 
            }
        }
        if(!$folder->sub_directories->isEmpty()){
            foreach($folder->sub_directories as $folder){
                $arrayData = $this->recursive_copy($folder, $src, $clone_folder);
            }
            return response()->json($arrayData);
            
        }
        return;        
    }
    public function moveToFolder(Request $request){
       
        if($request->move_items){
            // return response()->json($request->move_items);
            $target_id = $request->target_folder_id;
            foreach($request->move_items as $item){
                if($item['folder'] == 1){
                    
                    $moveFolder = appFolderRecord::find($item['id']);
                    if($moveFolder){
                        $f_name = $moveFolder->path;
                        $exist_file = appFolderRecord::where('recycle_flag', 0)->where('record_id', $moveFolder->record_id)->where('parent_id', $target_id)->where('path', $f_name)->count();
                        if($exist_file > 0){
                            $flag = true;
                            $key = 1;
                            while($flag) {
                                $defaults = appFolderRecord::where('recycle_flag', 0)->where('record_id', $moveFolder->record_id)->where('parent_id', $target_id)->where('path', $f_name . '(' . $key . ')')->count();
                                if($defaults == 0){
                                    $flag = false;
                                }else{
                                    $key = $key + 1;
                                }
                            }            
                            $f_name = $f_name . '(' . $key . ')';               
                            
                        }
                        $moveFolder->path = $f_name;
                        $moveFolder->parent_id = $target_id;
                        $moveFolder->save();
                        
                        
                    }                    
                }else if($item['folder'] == 0){
                    $moveFolder = appFileRecord::find($item['id']);
                    if($moveFolder){
                        $f_name = $moveFolder->name;
                        $exist_file = appFileRecord::where('recycle_flag', 0)->where('record_id', $moveFolder->record_id)->where('parent_id', $target_id)->where('name', $f_name)->count();
                        if($exist_file > 0){
                            $flag = true;
                            $key = 1;
                            while($flag) {
                                $defaults = appFileRecord::where('recycle_flag', 0)->where('record_id', $moveFolder->record_id)->where('parent_id', $target_id)->where('name', $f_name . '(' . $key . ')')->count();
                                if($defaults == 0){
                                    $flag = false;
                                }else{
                                    $key = $key + 1;
                                }
                            }            
                            $f_name = $f_name . '(' . $key . ')';               
                            
                        }
                        $moveFolder->name = $f_name; 
                        $moveFolder->parent_id = $target_id;
                        $moveFolder->save();
                        
                    } 
                }else{
                    $returnData = array(
                        'status' => 'error',
                        'message' => 'An error occurred!'
                    );
                    return response()->json($returnData, 500); 
                }
                
            } 
            return response()->json('success');  

        }else{
            $returnData = array(
                'status' => 'error',
                'message' => 'An error occurred!'
            );
            return response()->json($returnData, 500); 
        }

       
        
        // if($request->type == 'folder'){
        //     $moveFolder = appFolderRecord::find($request->source_id);
        //     if($moveFolder){
        //         $moveFolder->parent_id = $request->target_id;
        //         $moveFolder->save();
        //         return response()->json('success');
        //     }
        // }else if($request->type == 'file'){
        //     $moveFile = appFileRecord::find($request->source_id);
        //     if($moveFile){
        //         $moveFile->parent_id = $request->target_id;
        //         $moveFile->save();
        //         return response()->json('success');
        //     }
        // }
        
    }
    public function uploadNewFile(Request $request){

        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        if(empty($auth_user_id)){               
            return response()->json("loggedOut");
        }
        $board_id_int = (int)$request->board_id;
        $folder_id_int = (int)$request->folder_id;
        $files_list = [];
        foreach($request->file() as $file ){
            $mime_type = $file->getMimeType();
            $mime_type_array = explode('/',$mime_type);
            $file_type = $mime_type_array[0];
            $file_extension = $file->getClientOriginalExtension();
            $path = storage_path('app/managed_files/');     
            $file_name_full = $file->getClientOriginalName(); 
            $path_info = pathinfo($file_name_full);
            $file_name = $path_info['filename'];
            $file_size = $file->getSize();

            $rand = substr(str_shuffle('0123456789'),1,6);
            $now = now()->timestamp;
            $stamp = date("YmdHis", $now);
            $file_path = $stamp . '_' . $rand;


            if($file_type == 'image' && $file_extension !== 'svg'){
                $img = Image::make($file)->orientate();
                $set_path = $file_path . '.' . $file_extension;
                $stream = $img->stream();
                if (!Storage::disk('s3')->exists('managed_files/' . $request->board_id . '/' .  $set_path)) {
                    Storage::disk('s3')->makeDirectory('managed_files/' . $request->board_id . '/' .  $set_path);
                }
                Storage::disk('s3')->put('managed_files/' . $request->board_id . '/' .  $set_path, $stream);
                // if($file_size > 1000000){
                    
                //     $img->save($path .'/'. $set_path, 30);
                    
                    
                // }else{
                    
                //     $img->save($path .'/'. $set_path);  
                // }
                $thumbnail = Image::make($file)->orientate()->fit(50, 50, function ($constraint) {
                    $constraint->upsize();
                });
                // $thumb_path = $path . $request->board_id . '/thumb';
                // File::isDirectory($thumb_path) or File::makeDirectory($thumb_path, 0705, true, true);  
                $thumb_stream = $thumbnail->stream();
                // $thumbnail->save($thumb_path .'/'. $file_path . '_thumb_50.' . $file_extension);

                $thumbnail_big = Image::make($file)->orientate()->fit(100, 100, function ($constraint) {
                    $constraint->upsize();
                });
                $thumbBig_stream = $thumbnail_big->stream();
                // $thumbnail_big->save($thumb_path .'/'. $file_path . '_thumb_100.' . $file_extension);

                // $thumbnail_temp_path = $thumb_path .'/'. $file_path . '_thumb_50.' . $file_extension;

                // $thumbnail_btemp_path = $thumb_path .'/'. $file_path . '_thumb_100.' . $file_extension;

                $thumb_path_s3 = 'managed_files/' . $request->board_id . '/thumb';
                if (!Storage::disk('s3')->exists($thumb_path_s3)) {
                    Storage::disk('s3')->makeDirectory($thumb_path_s3);
                }
                $thumb_set_path =  $file_path . '_thumb_50.' . $file_extension;
                $thumb_bset_path = $file_path . '_thumb_100.' . $file_extension;   
                Storage::disk('s3')->put($thumb_path_s3 . '/' .  $thumb_set_path, $thumb_stream);
                Storage::disk('s3')->put($thumb_path_s3 . '/' .  $thumb_bset_path, $thumbBig_stream);
            }else if($file_type == 'image' && $file_extension == 'svg'){
                if (!Storage::disk('s3')->exists('managed_files/' . $request->board_id)) {
                    Storage::disk('s3')->makeDirectory('managed_files/' . $request->board_id);
                } 
                // File::isDirectory($path) or File::makeDirectory($path, 0705, true, true);   
                $set_path = $file_path . '.' . $file_extension; 
                Storage::disk('s3')->put( 'managed_files/' . $request->board_id . '/' .  $set_path, file_get_contents($file));
                $thumb_path = 'managed_files/' . $request->board_id . '/thumb';
                if (!Storage::disk('s3')->exists($thumb_path_s3)) {
                    Storage::disk('s3')->makeDirectory($thumb_path_s3);
                }
                // File::isDirectory($thumb_path) or File::makeDirectory($thumb_path, 0705, true, true);  
                Storage::disk('s3')->copy('managed_files/' . $request->board_id . '/' . $set_path, 'managed_files/' . $request->board_id . '/thumb' . '/' . $file_path . '_thumb_50' . '.' . $file_extension);
                Storage::disk('s3')->copy('managed_files/' . $request->board_id . '/' . $set_path, 'managed_files/' . $request->board_id . '/thumb' . '/' . $file_path . '_thumb_100' . '.' . $file_extension);
            }   
            else{
                if (!Storage::disk('s3')->exists('managed_files/' . $request->board_id)) {
                    Storage::disk('s3')->makeDirectory('managed_files/' . $request->board_id);
                }                            
                // File::isDirectory($path) or File::makeDirectory($path, 0705, true, true);   
                $set_path = $file_path . '.' . $file_extension;    
                Storage::disk('s3')->put( 'managed_files/' . $request->board_id . '/' .  $set_path, file_get_contents($file));      

            }

            $f_name = $file_name;
            $exist_file = appFileRecord::where('recycle_flag', 0)->where('record_id', $board_id_int)->where('parent_id', $folder_id_int)->where('name', $f_name)->count();
            if($exist_file > 0){
                $flag = true;
                $key = 1;
                while($flag) {
                    $defaults = appFileRecord::where('recycle_flag', 0)->where('record_id', $board_id_int)->where('parent_id', $folder_id_int)->where('name', $f_name . '(' . $key . ')')->count();
                    if($defaults == 0){
                        $flag = false;
                    }else{
                        $key = $key + 1;
                    }
                }            
                $f_name = $f_name . '(' . $key . ')';               
                
            }
            $sizeAfter = Storage::disk('s3')->size('managed_files/' . $request->board_id . '/' .  $set_path);
            $newFile = new appFileRecord;
            $newFile->record_id = $board_id_int;
            $newFile->name = $f_name;
            $newFile->extension = $file_extension;
            $newFile->path = $file_path;
            $newFile->user_id = $auth_user_id;
            $newFile->mime_type = $file_type;
            $newFile->parent_id = $folder_id_int;          
           
            $newFile->size = $sizeAfter;
            $newFile->save(); 
            $files_list[] = $newFile->id;
        }
        
        $rebound = array(
            "uploaded_new_files" => $board_id_int
        );
        $options = array(
            'cluster' => config('app.pusher_cluster'),
            'useTLS' => true
        );    
        $pusher = new Pusher(
            config('app.pusher_key'),
            config('app.pusher_secret'),
            config('app.pusher_id'),
            $options
        );    
        $pusher->trigger('my-channel', 'my-event', $rebound);
        return response()->json($files_list);
    }
    public function folderStructure(Request $request){
        // if($request->pattern == 0){
            $folders = appFolderRecord::where('recycle_flag', '=', 0)->where('record_id', '=', $request->rec_id)->where('parent_id', '=', $request->par_id)->get();
            $parent = appFolderRecord::where('recycle_flag', '=', 0)->where('record_id', '=', $request->rec_id)->where('id', '=', $request->par_id)->select('id', 'path', 'parent_id')->first();
            if($request->par_id == 0){
                $root = [
                    "id" => 0,
                    "path" => null
                ];
                $data = [
                    "current" => $root,
                    "folders" => $folders
                ];
            }else{
                $data = [
                    "current" => $parent,
                    "folders" => $folders
                ];
            }
            
            return response()->json($data);
        // }else if($request->pattern == 1){
        //     $currentFolder = appFolderRecord::where('recycle_flag', '=', 0)->where('record_id', '=', $request->rec_id)->where('id', '=', $request->par_id)->select('id', 'path', 'parent_id')->first();
            
        //     $folders = appFolderRecord::where('recycle_flag', '=', 0)->where('record_id', '=', $request->rec_id)->where('parent_id', '=', $currentFolder->parent_id)->get();
        //     $parent = appFolderRecord::where('recycle_flag', '=', 0)->where('record_id', '=', $request->rec_id)->where('id', '=', $currentFolder->parent_id)->select('id', 'path')->first();
        //     if($request->par_id == 0 || $currentFolder->parent_id == 0){
        //         $root = [
        //             "id" => 0,
        //             "path" => null
        //         ];
        //         $data = [
        //             "parent" => $root,
        //             "folders" => $folders
        //         ];
        //     }else{
        //         $data = [
        //             "parent" => $parent,
        //             "folders" => $folders
        //         ];
        //     }
        //     return response()->json($data);
        // }
        
    }
    public function getQuota(Request $request){
        $all_files_size = appFileRecord::where('recycle_flag', '=', 0)->where('record_id', '=', $request->board_id)->pluck('size')->toArray();
        $sum = array_sum(array_map('intval', $all_files_size));
        return response()->json($sum);
    }
    public function importFromBoard(Request $request){        
        $auth_user_id = Auth::id();
        if($request['files']){      
            foreach($request['files'] as $file){
                $message_record = messageRecord::findOrFail($file['message_id']);                
                $message_file_path = storage_path('app/shared_files/' . $message_record->record_id . '/' . $file['id'] . '_' . $file['user_id'] . '_' . $file['message_id'] . '.' . $file['extension']);
                
                if(File::exists($message_file_path)){
                    $rand = substr(str_shuffle('0123456789'),1,6);
                    $now = now()->timestamp;
                    $stamp = date("YmdHis", $now);
                    $file_path = $stamp . '_' . $rand;
                    $target_file_path = storage_path('app/managed_files/' . $request['record_id'] . '/' . $file_path . '.' . $file['extension']);
                    $target_path_import = storage_path('app/managed_files/' . $request['record_id']);
          
                    File::isDirectory($target_path_import) or File::makeDirectory($target_path_import, 0705, true, true);
                    File::isDirectory(storage_path('app/managed_files/' . $request['record_id'] . '/thumb')) or File::makeDirectory(storage_path('app/managed_files/' . $request['record_id'] . '/thumb'), 0705, true, true);  
                    if($file['mime_type'] == 'image' && $file['extension'] !== 'svg'){
                        $thumbnail = Image::make($message_file_path)->fit(50, 50, function ($constraint) {
                            $constraint->upsize();
                        });
                        $thumbnail->save($target_path_import . '/thumb' .'/'. $file_path . '_thumb_50.' . $file['extension']);
        
                        $thumbnail_big = Image::make($message_file_path)->fit(100, 100, function ($constraint) {
                            $constraint->upsize();
                        });                      
                          
                        $thumbnail_big->save($target_path_import . '/thumb' .'/'. $file_path . '_thumb_100.' . $file['extension']);
                    }else if($file['mime_type'] == 'image' && $file['extension'] == 'svg'){                         
                        Storage::disk('s3')->copy($message_file_path, $target_path_import . '/thumb' .'/' . $file_path . '_thumb_50' . '.' . $file['extension']);
                        Storage::disk('s3')->copy($message_file_path, $target_path_import . '/thumb' .'/' . $file_path . '_thumb_100' . '.' . $file['extension']);
                    }
                    Storage::disk('s3')->copy($message_file_path, $target_file_path);

                    $f_name = pathinfo($file['name'], PATHINFO_FILENAME);
                    
                    $exist_file = appFileRecord::where('recycle_flag', 0)->where('record_id',$request['record_id'])->where('parent_id', $request['to'])->where('name', $f_name)->count();
                    // return response()->json($exist_file);
                    if($exist_file > 0){                    
                        $flag = true;
                        $key = 1;
                        while($flag) {
                            $defaults = appFileRecord::where('recycle_flag', 0)->where('record_id',$request['record_id'])->where('parent_id', $request['to'])->where('name', $f_name . '(' . $key . ')')->count();
                            if($defaults == 0){
                                $flag = false;
                            }else{
                                $key = $key + 1;
                            }
                        }            
                        $f_name = $f_name . '(' . $key . ')';               
                    }
                    $new_file = new appFileRecord;
                    $new_file->user_id = $auth_user_id;
                    $new_file->parent_id = $request['to'];
                    $new_file->record_id = $request['record_id'];
                    $new_file->path = $file_path;
                    $new_file->name = $f_name;
                    $new_file->size = $file['size'];
                    $new_file->extension = $file['extension'];
                    $new_file->mime_type = $file['mime_type'];
                    $new_file->save();
                    return response()->json($new_file);
                }              
        
                
            }
        }



   

    }
    public function getSearch(Request $request){ 
        $res = [];
        
        $folders = appFolderRecord::where('recycle_flag', '=', $request->recycle_flag)
        ->where('record_id', '=', $request->record_id)
        // ->where('parent_id', '=', $request->parent_id)
        ->where('path', 'LIKE', '%' . $request->keyword . '%')
        ->with('user')->get();
        $files = appFileRecord::where('recycle_flag', '=', $request->recycle_flag)
        ->where('record_id', '=', $request->record_id)
        // ->where('parent_id', '=', $request->parent_id)
        ->where(DB::raw("CONCAT_WS('', name, ' ', extension)"), 'LIKE', '%' . $request->keyword . '%')
        ->with('user')->get();
    
        $res['folders'] = $folders;
        $res['files'] = $files;
        return response()->json($res);
    }
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
    public function removeDeletedFiles(Request $request){  
        $month_ago = Carbon::today()->subDays(30); 
        $files = appFileRecord::where('recycle_flag', 1)->whereDate('deleted_at', '<', $month_ago)->get();    
        foreach($files as $file){
            $file->delete();
            $file->save();
            File::delete(storage_path('app/managed_files/' . $file->record_id . '/' . $file->path . '.' . $file->extension));
            File::delete(storage_path('app/managed_files/' . $file->record_id . '/thumb' . '/' . $file->path . '_thumb_100.' . $file->extension));
            File::delete(storage_path('app/managed_files/' . $file->record_id . '/thumb' . '/' . $file->path . '_thumb_50.' . $file->extension));
            
        }
        $folders = appFolderRecord::where('recycle_flag', '=', 1)->whereDate('deleted_at', '<', $month_ago)->get();
        foreach($folders as $folder){
            $folder->delete();
            $folder->save();
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


        $allFiles = messageFile::where('board_id', $request->board_id)->where('removed_at', null)->when($time_condition, function ($query) use ($timeLimit) {
            $query->where('created_at', '>=',  $timeLimit );
        })->with('user')->orderBy('created_at', 'desc')
        ->with('unsignedUsers')
        ->get();
        return response()->json($allFiles);
    }

}
