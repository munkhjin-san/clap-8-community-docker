<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\KnowledgeRecord;
use App\Models\NiceRecord;
use App\Models\ChallengeRecord;
use App\Models\TagRecord;
use App\Models\FileRecord;
use Illuminate\Support\Facades\File; 
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Auth;


class PostController extends Controller
{
    public function post_delete_file(Request $request){
        $validatedData = $request->validate([
            'list' => 'required',
        ]);
        $result = $this->delete_file_execute($request->list);
        return $result;

    }
    private function delete_file_execute($list){
        $files = FileRecord::whereIn('id', $list)->get();
        foreach($files as $file){
            Storage::disk('local')->delete('post_files/' . $file->id . '_' . $file->user_id . '_' . $file->path . '.' . $file->extension);
            $file->update(["deleted_flag" => 1]);
        }
        return $files;
    }
    public function get_posts(Request $request){    
        
        $nameSpace = '\\App\\Models\\'; 

        $model = $nameSpace . ucfirst($request->path) . 'Record'; 
        $params = $request['query'];
        $search_tags = [];
        if(array_key_exists('search_tags', $params) && $params['search_tags']){
            $parts = explode('|', $params['search_tags']);
            if (count($parts)) {
                for ($i = 0; $i < count($parts); $i++) {
                    $search_tags[] = $parts[$i];
                }               
            }
        }
        
     
        $records = $model::query();

        $qr = $records->where('deleted_flag', 0)
        ->when($params, function ($query) use($params, $search_tags) {
            $query->when(array_key_exists('id', $params) && $params['id'], function ($query) use($params) {
                $query->where('id', $params['id']);
            });
            
            $query->when($search_tags, function ($query) use ($search_tags) {
                $query->whereHas('tags', function ($query) use ($search_tags) {
                    $query->whereIn('text', $search_tags);
                    foreach ($search_tags as $tag) {
                        $query->orWhere('text', 'LIKE', '%' . $tag . '%');
                    }
                });
            });
            
        })
        
        ->with('user')
        ->with('tags')
        ->with('files')
        ->when($request->path == 'challenge' || $request->path == 'nice', function ($query) {
            $query->with('to_users');
        })
        ->when($request->path == 'challenge', function ($query) {
            $query->with('challenge_awards');
        })
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json($qr);


    }
    public function post_get_tags(Request $request){
        $super = $request->super;
        $key = $request->key;
        $tag_text = TagRecord::where('deleted_flag','=', 0)
        ->when(!$super, function ($query) use ($key) {
            $query->where('text', 'LIKE', '%' . $key . '%');
        })
        ->orderBy('hits', 'desc')->orderBy('created_at', 'desc')
        ->when($super, function ($query) use ($key) {
            $query->take(10);
        })
        ->get([
            'id',
            'text'
        ]);
        return response()->json($tag_text);
    }
    public function post_get_users(Request $request){
        
        $self_include = $request->self;
        $all_users = User::where('name', 'LIKE', '%' . $request->key . '%')
        ->when(!$self_include, function ($query) {
            $query->where('id', '!=',  Auth::id() );
        })
        ->get([
            'id',
            'name',
            'icon_id',
            'a_path',
            'a_version'
        ]);
        return response()->json($all_users);
    }
    public function post_create(Request $request){
        
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();   
        

  
        return response()->json($request);
            if(!empty($request->to_users)){

                $to_users = $request->to_users;

                foreach($to_users as $to_user){

                    $knowlegeToUser = new knowledgeToUser;
                    $knowlegeToUser->record_id = $knowledge->id;
                    $knowlegeToUser->user_id = $to_user;
                    $knowlegeToUser->save();

                }

            }

            if(!empty($request->tags)){

                $tags = $request->tags;

                foreach($tags as $tag){
                    $existTag = tagRecord::where('text', '=', $tag)->first();
                    if(empty($existTag)){
                        $tagRecord = new tagRecord;
                        $tagRecord->text = $tag;
                        $tagRecord->save();
                        $knowledgeUseTag = new knowledgeUseTag;
                        $knowledgeUseTag->record_id = $knowledge->id;
                        $knowledgeUseTag->tag_id = $tagRecord->id;
                        $knowledgeUseTag->save();
                        
                    }else{
                        $knowledgeUseTag = new knowledgeUseTag;
                        $knowledgeUseTag->record_id = $knowledge->id;
                        $knowledgeUseTag->tag_id = $existTag->id;
                        $knowledgeUseTag->save();
                    }
                    

                    

                }

            }
            $keyTags = knowledgeUseTag::where('record_id', '=', $knowledge->id)->where('deleted_flag', '=', 0)->with(['tag_records' => function($q){
                $q->where('deleted_flag','=', 0)->select('id', 'text');
            }])->get();
            
                $tags = [];
                foreach($keyTags as $keyTag){
                    $tags[] = $keyTag->tag_records->text;
                }
                $new_tag_list = implode(",",$tags);
                $knowledge->key_tags = $new_tag_list;
                $knowledge->save();

            if(!empty($file_id_array)){

                foreach($file_id_array as $file_id){

                    //ファイル使用中間テーブル保存処理
                    $knowlegeUseFile = new knowledgeUseFile;
                    $knowlegeUseFile->record_id = $knowledge->id;
                    $knowlegeUseFile->file_id = $file_id;
                    $knowlegeUseFile->save();

                }

            }
            if($request->forwarded_files){
                $root_path = base_path();
                $replaced = Str::replaceLast('public_html/', '', Str::replaceLast('app', '', $root_path));
                foreach($request->forwarded_files as $file){  
                    $file_path = date("YmdHis") . md5(uniqid());  
                    $path_managed_files = $replaced . 'managed_files/' . $file['source_board_id'] . '/';   
                    $fileRecord = new fileRecord;
                    $fileRecord->path =  $file_path;
                    $fileRecord->name = $file['name'];
                    $fileRecord->mime_type = $file['mime_type'];
                    $fileRecord->extension = $file['extension'];
                    $fileRecord->size = $file['size'];
                    $fileRecord->user_id = $auth_user_id;
                    $fileRecord->save();
                    $knowlegeUseFile = new knowledgeUseFile;
                    $knowlegeUseFile->record_id = $knowledge->id;
                    $knowlegeUseFile->file_id = $fileRecord->id;
                    $knowlegeUseFile->save();                    
                    $path = 'root/post_files/'; 
                    $set_path = $fileRecord->id . '_' . $fileRecord->user_id . '_' . $file_path . '.' . $fileRecord->extension;

                    File::copy($path_managed_files . $file['path'] . '.' . $file['extension'], $replaced . $path . $set_path);    

                }
            }
            if($request->shared_temp_files){
                $root_path = base_path();
                $replaced = Str::replaceLast('public_html/', '', Str::replaceLast('app', '', $root_path));
                
                foreach($request->shared_temp_files as $file){  
                    $file_path = date("YmdHis") . md5(uniqid());   
                    $fileRecord = new fileRecord;
                    $fileRecord->path =  $file_path;
                    $fileRecord->name = $file['name'];
                    $fileRecord->mime_type = $file['mime_type'];
                    $fileRecord->extension = $file['extension'];
                    $fileRecord->size = $file['size'];
                    $fileRecord->user_id = $auth_user_id;
                    $fileRecord->save();
                    $knowlegeUseFile = new knowledgeUseFile;
                    $knowlegeUseFile->record_id = $knowledge->id;
                    $knowlegeUseFile->file_id = $fileRecord->id;
                    $knowlegeUseFile->save();
                    $path = 'root/post_files/'; 
                    $set_path = $fileRecord->id . '_' . $fileRecord->user_id . '_' . $file_path . '.' . $fileRecord->extension;
                    File::copy($replaced . 'shared_files/' . $file['source_board_id'] . '/' . $file['id'] . '_' . $file['user_id'] . '_' . $file['message_id'] . '.' . $file['extension'], $replaced . $path .  $set_path);
                    

                }
            }
            Auth::user()->user_last_record->last_knowledge = $knowledge->id;
            Auth::user()->user_last_record->save();
            event(new Message("added_new_record"));
            return response()->json($request->shared_temp_files);
  
        
    }
    public function post_file_upload(Request $request ){    
        $ids = [];
        foreach($request->file() as $file ){
            $file_path = date("YmdHis") . md5(uniqid());           
            $file_extension = $file->getClientOriginalExtension();
            $file_real_name = $file->getClientOriginalName();            
            $mime_type = $file->getMimeType();
            $mime_type_array = explode('/',$mime_type);
            $file_type = $mime_type_array[0];            
            $file_size = $file->getSize();  
            
            $fileRecord = new fileRecord;
            $fileRecord->path =  $file_path;
            $fileRecord->name = $file_real_name;
            $fileRecord->mime_type = $file_type;
            $fileRecord->extension = $file_extension;
            
            $fileRecord->user_id = Auth::id();
            $fileRecord->save();
            $set_path = $fileRecord->id . '_' . $fileRecord->user_id . '_' . $file_path . '.' . $fileRecord->extension;

            $path = '/post_files';
            if($file_type == 'image' && $file_extension !== 'svg'){
                $img = Image::make($file)->orientate();
                    
                File::isDirectory(storage_path('app') . '/' . $path) or File::makeDirectory(storage_path('app') . '/' . $path, 0755, true, true);                      
                $img->save(storage_path('app') . '/' . $path .'/'. $set_path, 30);  
                
            }else{
                Storage::disk('local')->putFileAs(
                    $path, $file, $set_path
                );
            }
            $sizeAfter = File::size(storage_path('app/post_files/' . $set_path));
        
            $fileRecord->size = $sizeAfter;
            $fileRecord->save(); 
            $ids[] = $fileRecord;
            

            
                    
        }
        return response()->json($ids);      

        

    }
    public function post_add_record(Request $request ){  
        


        if(!empty($request->title)){

            $validatedData = $request->validate([
                'path' => 'required',
            ]);
            $nameSpace = '\\App\\Models\\'. ucfirst($request->path) . 'Record'; 

            $record = new $nameSpace; 
            $record->user_id = Auth::id();
            $record->title = $request->title;
            if($request->path == 'challenge'){
                $record->content_rule = $request->content_rule;
                $record->content_goal = $request->content_goal;
                $record->date_start = $request->date_start;
                $record->date_end = $request->date_end;
                $record->award_entry = $request->award_entry;
            }else{
                $record->content = $request->content;
            }
            
            $record->referrer = $request->referrer;           

            $record->save();

            $record->to_users()->attach($request->to_users);
            $tagIds = [];
            foreach ($request->tags as $text) {
                $tag = TagRecord::firstOrCreate(['text' => $text]);
                $tagIds[] = $tag->id;
            }
            $record->tags()->attach($tagIds);
            // return response()->json($record); 

            // if(!empty($request->to_users)){

            //     $to_users = $request->to_users;

            //     foreach($to_users as $to_user){

            //         $challengeToUser = new challengeToUser;
            //         $challengeToUser->record_id = $challenge->id;
            //         $challengeToUser->user_id = $to_user;
            //         $challengeToUser->save();
            //         #20201202_0013 Tumur　通知機能追加
            //         $param01 = $challenge->id;
            //         $param02 = null;
            //         $param03 = null;
            //         $param04 = null;
            //         $subject_inner = null;
            //         $title ='【チャレンジのプレイヤーに選ばれました】 ' . $challenge->title;  
            //         $url = 'https://clap-glowd.com/app/public/challenge?id=' . $challenge->id;
            //         $body = 'プレイヤーに選ばれたチャレンジがあります。'; 
                    
            //         if($to_user !== $auth_user_id){
            //             $mailAddress = User::where('id','=', $to_user)->select('email')->pluck('email')->first();
            //             if(!empty($mailAddress)){
            //                 Mail::to($mailAddress)->send(new multiMail($title, $subject_inner, $body, $url, $param01, $param02, $param03, $param04));
            //             }
            //         }                      
            //     }

            // }
            // return response()->json($record);  


                // return response()->json($request->file_ids);
                $record->files()->attach($request->file_ids);
                // foreach($file_id_array as $file_id){

                //     //ファイル使用中間テーブル保存処理
                //     $challengeUseFile = new challengeUseFile;
                //     $challengeUseFile->record_id = $challenge->id;
                //     $challengeUseFile->file_id = $file_id;
                //     $challengeUseFile->save();

                // }

            
            return response()->json($record);
            if($request->forwarded_files){
                $root_path = base_path();
                $replaced = Str::replaceLast('public_html/', '', Str::replaceLast('app', '', $root_path));
                foreach($request->forwarded_files as $file){  
                    $file_path = date("YmdHis") . md5(uniqid());  
                    $path_managed_files = $replaced . 'managed_files/' . $file['source_board_id'] . '/';   
                    $fileRecord = new fileRecord;
                    $fileRecord->path =  $file_path;
                    $fileRecord->name = $file['name'];
                    $fileRecord->mime_type = $file['mime_type'];
                    $fileRecord->extension = $file['extension'];
                    $fileRecord->size = $file['size'];
                    $fileRecord->user_id = $auth_user_id;
                    $fileRecord->save();
                    $challengeUseFile = new challengeUseFile;
                    $challengeUseFile->record_id = $challenge->id;
                    $challengeUseFile->file_id = $fileRecord->id;
                    $challengeUseFile->save();                    
                    $path = 'root/post_files/'; 
                    $set_path = $fileRecord->id . '_' . $fileRecord->user_id . '_' . $file_path . '.' . $fileRecord->extension;

                    File::copy($path_managed_files . $file['path'] . '.' . $file['extension'], $replaced . $path . $set_path);    

                }
            }
            if($request->shared_temp_files){
                $root_path = base_path();
                $replaced = Str::replaceLast('public_html/', '', Str::replaceLast('app', '', $root_path));
                
                foreach($request->shared_temp_files as $file){  
                    $file_path = date("YmdHis") . md5(uniqid());   
                    $fileRecord = new fileRecord;
                    $fileRecord->path =  $file_path;
                    $fileRecord->name = $file['name'];
                    $fileRecord->mime_type = $file['mime_type'];
                    $fileRecord->extension = $file['extension'];
                    $fileRecord->size = $file['size'];
                    $fileRecord->user_id = $auth_user_id;
                    $fileRecord->save();
                    $challengeUseFile = new challengeUseFile;
                    $challengeUseFile->record_id = $challenge->id;
                    $challengeUseFile->file_id = $fileRecord->id;
                    $challengeUseFile->save();
                    $path = 'root/post_files/'; 
                    $set_path = $fileRecord->id . '_' . $fileRecord->user_id . '_' . $file_path . '.' . $fileRecord->extension;
                    File::copy($replaced . 'shared_files/' . $file['source_board_id'] . '/' . $file['id'] . '_' . $file['user_id'] . '_' . $file['message_id'] . '.' . $file['extension'], $replaced . $path .  $set_path);
                    

                }
            }
            Auth::user()->user_last_record->last_challenge = $challenge->id;
            Auth::user()->user_last_record->save();
            event(new Message("added_new_record"));
            $add_record_id = $challenge->id;
            return response()->json();
        }
    }
    public function postBetMessage(Request $request){

        $auth_user = Auth::user();
        $auth_user_id = Auth::id();

        $validatedData = $request->validate([
            'charge_bet' => 'required',
            'record_id' => 'required'
        ]);




        $award = new challengeAward;
        $award->record_id = $request->record_id;
        $award->award_bet = $request->charge_bet;
        $award->user_id = $auth_user_id;
        $award->save();
            
        $user = $auth_user;
        $user->award_charge = $user->award_charge - $request->charge_bet;
        $user->save();

        // #20201202_0013 Tumur　通知機能追加
        // $challenge = challengeRecord::find($request->record_id);
        // $to_users = challengeToUser::where('record_id', '=', $request->record_id)->where('deleted_flag', '=', 0)->where('user_id', '!=', $auth_user_id)->pluck('user_id');
        // $param01 = $challenge->id;
        // $param02 = null;
        // $param03 = null;
        // $param04 = null;
        // $subject_inner = null;
        // $title ='【チャレンジにチャージされました】 ' . $challenge->title;  
        // $url = 'https://clap-glowd.com/app/public/challenge?id=' . $challenge->id;
        // $body = $auth_user->name . 'さんが、あなたのチャレンジにチャージしました。'; 
        
        // foreach($to_users as $to_user){
        //     $mailAddress = User::where('id','=', $to_user)->select('email')->pluck('email')->first();
        //     if(!empty($mailAddress)){
        //         Mail::to($mailAddress)->send(new multiMail($title, $subject_inner, $body, $url, $param01, $param02, $param03, $param04));
        //     }
        // }
                
            
        return response()->json();  
        

    }
}
