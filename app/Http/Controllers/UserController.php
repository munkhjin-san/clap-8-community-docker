<?php

declare(strict_types=1);

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Icons;

use App\Models\UserAlbum;
use App\Models\TagRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File; 
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Events\MessageSent;
use App\Services\SharedService;
class UserController extends Controller{
    protected $sharedService;
    
    public function __construct(SharedService $sharedService)
    {
        $this->sharedService = $sharedService;
    }

    public function index(Request $request){
       
        


        $user_id = $request->id;
        if(empty($user_id)){
            return redirect('/user/'. Auth::id());
        }
        $data = $this->sharedService->getUserState($request->id, Auth::user());
        return view('user', ['data' => $data]);
            
        

        

    }
    public function saveSignature(Request $request){
        $auth_id = Auth::id();
        $user = User::with('linked')->findOrFail($auth_id);
        $unique_number = rand(1000, 9999); 
        $current_timestamp = time(); 
        $new_a_path = $current_timestamp . $unique_number; 
        $set_path = $user->id . '_' . $new_a_path . '.png';
        File::isDirectory(storage_path('app/user_signatures')) or File::makeDirectory(storage_path('app/user_signatures'), 0755, true, true);
        Storage::disk('local')->putFileAs(
            '/user_signatures', $request->sign, $set_path
        );  
        Storage::disk('local')->delete('user_signatures/' . $user->id . '_' . $user->sign_path . '.png');
        
        $user->sign_path = $new_a_path;
        $user->save();
        return response()->json($user);
    }
    
    public function croppedUp(Request $request) {    
        $user = Auth::user();
        $auth_user_id = Auth::id();

        $img = Image::read($request->file('croppedImage'))->scale(200);      
        
        $size_variants = [200, 120, 80, 45, 30, 25, 20, 15];
        if (!Storage::disk('local')->exists('profile_icon')) {
            Storage::disk('local')->makeDirectory('profile_icon');
        }

        $old_icon = Icons::where('user_id', $auth_user_id)->where('use_of', 'profile')->first();

        $orgImage = json_decode($request->get('orgImage'), true);
        $imageUrl = $orgImage['url'];

        $imageData = base64_decode(preg_replace('#^data:\w+/\w+;base64,#i', '', $imageUrl));
        $org_img = Image::read($imageData);
        
        $icon = new Icons;
                
        $icon->mime_type = 'image';
        $icon->extension = 'jpg';       
        $icon->user_id = $user->id;
        $icon->profile_id = $user->id;
        $icon->use_of = "profile";
        $icon->save();
        $org_path = $icon->id . '_' . $user->id . '_x.jpg';
        $path_for_org = storage_path('app/profile_icon/'.$org_path);
        $org_img->save($path_for_org);
        foreach($size_variants as $size){
            $img_rsz = $img->resize($size, $size);
            $set_path = $icon->id . '_' . $user->id . '_' . $size . '.jpg';
            $temp_path = storage_path('app/profile_icon/'.$set_path);
            $img_rsz->save($temp_path);
            if($old_icon){
                Storage::disk('local')->delete('profile_icon/' . $old_icon->id . '_' . $auth_user_id . '_' . $size . '.jpg');
               
            }

        }
        if($old_icon){
            $old_icon->delete();
        }
        $user->update(['icon_id' => $icon->id]);
        return response()->json();
       
    }
    public function userIconCreate(Request $request) { 
        $create = $this->sharedService->createUserDefaultIcon(Auth::user());
        if($create){
            return response()->json("success");
        }        
        return response()->json();        
    }
    public function get_albums(Request $request) {
        $tag_id = $request->tag_id;
        $usersWithAlbums = User::whereHas('user_album.tags', function ($query) use ($tag_id) {
            $query->where('tag_id', $tag_id);
        })->with(['user_album' => function ($query) use ($tag_id) {
            $query->select('id', 'path', 'name', 'mime_type', 'user_id', 'extension', 'title')
                  ->whereHas('tags', function ($subQuery) use ($tag_id) {
                      $subQuery->where('tag_id', $tag_id);
                  });
        }])->select('id', 'name', 'icon_id')->get();

        return response()->json($usersWithAlbums);
    }
    public function profileEdit(Request $request) { 

             
            $user = Auth::user();       
            $user->phone_number = $request->phone_number;
            $user->work_email = $request->work_email;
           
            $user->motto = $request->motto;
            $user->enjoy = $request->enjoy;
            $user->recommend = $request->recommend;
            $user->intro = $request->intro;
            
            $user->save();
            return response()->json($user);
        
    }
    private function tempToServer($request){
        $path = $request->path;
        foreach($request->uploadedImages as $filetemp){
            if (is_string($filetemp) && Storage::disk('local')->exists($filetemp)) {
                $fileContent = Storage::disk('local')->get($filetemp);
                $file_path = date("YmdHis") . md5(uniqid());   
                $fileInfo = pathinfo($filetemp);
                $file_extension = $fileInfo['extension'];
                $file_real_name = $fileInfo['basename'];            
                $mime_type = mime_content_type(storage_path('app/' . $filetemp));;
                $mime_type_array = explode('/',$mime_type);
                $file_type = $mime_type_array[0];
                
                $album = new UserAlbum;
                $album->user_id = Auth::id();
                $album->path = $file_path;
                $album->name = $file_real_name;
                $album->created_by = Auth::id();
                $album->mime_type = $file_type;
                $album->extension = $file_extension;
                $album->intro_flag = $request['intro_flag'];
                $album->save();
                $set_path = "{$album->id}_{$album->user_id}_{$file_path}.{$file_extension}";
                // $width = 280;
                // $thumbnail_path = "{$album->id}_{$album->user_id}_{$file_path}_thumbnail.webp";
                File::isDirectory(storage_path('app') . $path) or File::makeDirectory(storage_path('app') . '/' . $path, 0755, true, true);
                if($file_type == 'image' && $file_extension !== 'svg'){

                    $img = Image::read($fileContent);
                    
                    $img->encodeByExtension($file_extension, 30)->save(storage_path('app') . $path .'/'. $set_path);
                    // $thumbnail = $img->encode('webp')->resize($width, null, function($constraint) {
                    //     $constraint->aspectRatio();
                    //     $constraint->upsize();
                    // });  
                    // $thumbnail->save(storage_path('app') . $path .'/'. $thumbnail_path, 100);
                }else{
                    

                    file_put_contents(storage_path('app/' . $path . '/' . $set_path), $fileContent);

                } 
                Storage::disk('local')->delete($filetemp);
                return $album->id;
            }
        }
    }
    public function userFileUpload(Request $request){
        $path = $request->path;
        $originalFileName = $request->file('file')->getClientOriginalName();
        $originalMimeType = $request->file('file')->getMimeType();
        $mime_type_array = explode('/',$originalMimeType);
        $file_type = $mime_type_array[0];
        if($file_type == 'video' || $file_type == 'image'){
            $tempFileName = $request->file('file')->storeAs('temp_upload', $originalFileName, 'local');
            return response()->json($tempFileName);
        }else{
            return 'notimage';
        }   
        
    }
    public function save_intro(Request $request){
        $id = $request->id ? $request->id : $this->tempToServer($request);
        $tagIds = [];
        foreach ($request->tags as $text) {
            $tag = TagRecord::firstOrCreate(['text' => $text]);
            $tagIds[] = $tag->id;
        }
        $user_album = UserAlbum::findOrFail($id);
        $user_album->title = $request->title;
        $user_album->tags()->sync($tagIds);
        $user_album->save();
        return response()->json($user_album);
    }
    private function cancelFile($request){
        $canceledImageIds = $request->canceledImageIds ?? [];
        if (count($canceledImageIds) > 0) {
            $existingAlbums = UserAlbum::whereIn('id', $canceledImageIds)->get();

            foreach ($existingAlbums as $album) {
                $set_path = $album->id . '_' . $album->user_id . '_' . $album->path . '.' . $album->extension;
                // $thumbnail_path = $album->id . '_' . $album->user_id . '_' . $album->path . '_thumbnail.webp';
                Storage::disk('local')->delete($request->path . '/' . $set_path);
                // Storage::disk('local')->delete($request->path . '/' . $thumbnail_path);
                $album->delete();
            }
        } 
        return 'saved';
    }
    public function passChange(Request $request) {    
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'current' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);
        $validator->setCustomMessages([
            'min' => '8文字以上に設定してください。',
            'confirmed' => '新しいパスワードが確認と一致していません。',
            'current_password' => '現在のパスワードが間違っています。',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }    
        $user->password = bcrypt($request->password);
        $user->save();
    
        return response()->json(['message' => 'passwordUpdatedSuccessfully'], 200);
    }  
    public function generate_key(Request $request){

        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        $result = implode($pass);
        Auth::user()->file_key = $result;
        Auth::user()->save();
        return response()->json($result);  
        
    }    
    public function deleteMov(Request $request){
        if(!empty($request)){
            $user_id_int = $request->delete_id;
            $intro_record = userAlbum::findOrFail($request->mov_id);  
            $path = '/user_album' . '/' . $user_id_int . '/' . $intro_record->id . '_' . $user_id_int;
            if(!empty($intro_record)){
                $intro_record->tags()->detach();
                $intro_record->delete();
                Storage::disk('local')->delete($path . '_' . $intro_record->path . '.' . $intro_record->extension);
                // Storage::disk('local')->delete($path . '_' . $intro_record->path . '_thumbnail.webp');
                return response()->json('saved');  
            }
        }
    }
   
    private function delete_file_execute($request){
        if($request->file_id){
            $file = UserAlbum::findOrFail($request->file_id);
            Storage::disk('local')->delete($request->path . '/' . $file->id . '_' . $file->user_id . '_' . $file->path . '.' . $file->extension);
            $file->delete();
        }else{
            Storage::disk('local')->delete($request->path);
        }
        return 'deleted';
    }
    public function user_file_delete(Request $request){
        $request->validate([
            'path' => 'required',
        ]);
        $result = $this->delete_file_execute($request);
        return $result;
    }
    public function profile_get_update_user (Request $request){
        $today = Carbon::now()->format('Y-m-d');
        $list = User::where('id', '=', $request->id)->where('id', '>', 105)
        ->where('deleted_flag','=', 0)->with('positions')->with('offices')->with('icons')->with(['user_album' => function($q){
            $q->where('deleted_flag','=', 0)->with('tags');
        }])
        ->with(['weathers' => function($q) use($today){
            $q->where('type_id', 43)->where('date', $today);
        }])->with(['days_weathers' => function($q) use($today){
            $q->where('type_id', 43)->where('deleted_flag', 0)
            ->where('date', '<', $today)
            ->orderBy('date', 'desc')
            ->limit(5);
        }])->with('portfolio')->with('linked')
        ->first();         

        return response()->json($list);
        
    }  
    public function setColor (Request $request){     
        $request->validate([
            'value' => 'required',
        ]);   
        try {
            $request->user()->update(['color' => $request->value]);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(['message' => 'commonError']);
        }
    }
    
    public function getClaps(Request $request){
        
        if(!empty($request)){
            $user = User::with([
                'portfolio' => function ($q) {
                    $q->withCount('claps');
                },
                'post' => function ($q) {
                    $q->withCount('claps');
                },
                'post_recieved' => function ($q) {
                    $q->withCount('claps');
                },
                'comment' => function ($q) {
                    $q->withCount('claps');
                }
            ])->findOrFail($request->id);
            $post = $user->post->sum('claps_count');

            $post_to = $user->post_recieved->sum('claps_count');

            $post_claps = $post + $post_to;
            
            $portfolio_claps = $user->portfolio->sum('claps_count');

            $comment_claps = $user->comment->sum('claps_count');

            $sum = $post_claps + $portfolio_claps + $comment_claps;

            $claps = [
                "sum" => $sum,
            ]; 

            return response()->json($claps);
        }
    }
    public function set_active_linked_account(Request $request){
        $request->validate([
            'id' => 'required',
        ]);

        if($request->id == Auth::id()){
            Auth::user()->linked()->update(['active' => 0]);
        }else{
            $target = Auth::user()->linked()->where('link_id', $request->id)->exists();
            if($target){
                Auth::user()->linked()->whereNot('link_id', $request->id)->update(['active' => 0]);
                Auth::user()->linked()->where('link_id', $request->id)->update(['active' => 1]);
            }           
        }
        
        $updated = $this->profile_get_update_user(new Request (["id" =>  Auth::id()]));
        $rebound = array(
            array(
                "event" => "switch:".Auth::id(),
                "data" => array("to" => $request->id)
            )            
        );

        // event(new MessageSent($rebound));
        return response()->json(["socket" => $rebound, "user" => $updated->original]);

        
    }
    
}
