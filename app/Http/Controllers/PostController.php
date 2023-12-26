<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\KnowledgeRecord;
use App\Models\NiceRecord;
use App\Models\ChallengeRecord;
use App\Models\TagRecord;
use App\Models\FileRecord;
use App\Models\ClapRecord;
use App\Models\KnowledgeUseTag;
use App\Models\ChallengeUseTag;
use App\Models\NiceUseTag;
use App\Models\SearchHistoryRecord;
use App\Models\CommentRecord;
use App\Models\UserLastRecord;
use Illuminate\Support\Facades\Mail;
use App\Mail\Comment;
use Illuminate\Support\Facades\File; 
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use App\Events\MessageSent;
use DB;
use Auth;


class PostController extends Controller
{
    public function post_delete_file(Request $request){
        $validatedData = $request->validate([
            'list' => 'required',
        ]);
        $result = $this->delete_file_execute($request->list, $request->path);
        return $result;

    }
    private function delete_file_execute($list, $path){
        $files = FileRecord::whereIn('id', $list)->get();
        foreach($files as $file){
            Storage::disk('local')->delete($path . '/' . $file->id . '_' . $file->user_id . '_' . $file->path . '.' . $file->extension);
            $file->update(["deleted_flag" => 1]);
        }
        return $files;
    }
    public function delete_post(Request $request){
        $nameSpace = '\\App\\Models\\'; 

        $model = $nameSpace . ucfirst($request->path) . 'Record'; 
        $record = $model::findOrFail($request->id);
        // if($record->app_type == 3 || $record->app_type == 4){
        //     $record->to_users()->detach();
        // }
        $files = $record->files()->get();
        $ids = [];
        foreach($files as $file){
            $ids[] = $file->id;
        }
        if(count($ids)){
            $result = $this->delete_file_execute($ids, '/post_files');
        }    

        
        $record->tags()->detach();
        $record->delete();
        return response()->json($record->id);
    }
    public function get_posts(Request $request){    
        
        $nameSpace = '\\App\\Models\\'; 

        $model = $nameSpace . ucfirst($request->path) . 'Record'; 
        $params = $request['query'];
        $search_tags = [];
        $skip = $request->skip;
        $has_id = array_key_exists('id', $params) && $params['id'] !== null;
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
        ->withCount('comments')
        ->with('claps')
        ->when($request->path == 'challenge' || $request->path == 'nice', function ($query) {
            $query->with('to_users');
        })
        ->when($request->path == 'challenge', function ($query) {
            $query->with('challenge_awards')->with('result_files');
        })
        ->orderBy('created_at', 'desc')
        ->when(!$has_id, function ($query) use($skip) {
            $query->skip($skip);
            
        })
        ->take(10)
        ->get();

        return response()->json($qr);


    }
    public function post_get_tags(Request $request){
        $super = $request->super;
        $key = $request->key;
        $special = $request->special ? $request->special : [];
        $tag_text = TagRecord::where('deleted_flag','=', 0)
        ->when(!$super, function ($query) use ($key) {
            $query->where('text', 'LIKE', '%' . $key . '%');
        })
        ->orderBy('hits', 'desc')->orderBy('created_at', 'desc')
        ->when(empty($key), function ($query) use ($key) {
            
            $query->take(10);
        })
        ->get([
            'id',
            'text'
        ]);
        if(count($special)){
            $special_tags = TagRecord::whereIn('text', $special)->get();
            foreach($special_tags as $s_tag){
                $tag_text->prepend($s_tag);
            }
            return response()->json($tag_text);
        }else{
            return response()->json($tag_text);
        }
    
       
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
        ]);
        return response()->json($all_users);
    }
    public function prepare_sharing_files(Request $request ){  
        $path = $request['path'];
        $ids = [];
        foreach($request->list as $file){
            $exists = Storage::disk('local')->exists($file['path']);
            if($exists){
                $file_path = date("YmdHis") . md5(uniqid());                 
                $fileRecord = new fileRecord;
                $fileRecord->path =  $file_path;
                $fileRecord->name = $file['record']['name'];       
                $fileRecord->mime_type = $file['record']['mime_type'];       
                $fileRecord->extension = $file['record']['extension'];
                $fileRecord->size = $file['record']['size'];
                $fileRecord->user_id = Auth::id();
                $fileRecord->save();
                $set_path = $path . '/' . $fileRecord->id . '_' . $fileRecord->user_id . '_' . $file_path . '.' . $fileRecord->extension;
                Storage::disk('local')->copy( $file['path'], $set_path );
                $ids[] = $fileRecord;  
            }
            
        }
        return response()->json($ids);   
        
        
    }
    public function post_file_upload(Request $request ){    
        $ids = [];
        $path = $request['path'];
        // return response()->json($path);
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
    public function post_add_record(Request $request ){  
        


        if(!empty($request->title)){

            $validatedData = $request->validate([
                'path' => 'required',
            ]);
            $nameSpace = '\\App\\Models\\'. ucfirst($request->path) . 'Record'; 

            $record = $request->edit_id ? $nameSpace::findOrFail($request->edit_id) : new $nameSpace; 
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
            if($request->path !== 'knowledge'){
                $record->to_users()->sync($request->to_users);
            }            
            $tagIds = [];
            foreach ($request->tags as $text) {
                $tag = TagRecord::firstOrCreate(['text' => $text]);

                $tagIds[] = $tag->id;
                $increment = $tag->increment('hits');
            }
            $record->tags()->sync($tagIds);


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
            //             $Address = User::where('id','=', $to_user)->select('email')->pluck('email')->first();
            //             if(!empty($mailAddress)){
            //                 Mail::to($mailAddress)->send(new multiMail($title, $subject_inner, $body, $url, $param01, $param02, $param03, $param04));
            //             }
            //         }                      
            //     }

            // }
            // return response()->json($record);  


                // return response()->json($request->file_ids);
                $record->files()->sync($request->file_ids);
                if(!$request->edit_id){
                    $list = UserLastRecord::where('user_id', '=', Auth::id())->where('deleted_flag', '=', 0)->update([
                        'last_' . $request->path => $record->id
                    ]);
                }
                
                
            $rebound = array(
                "new_post_from" => Auth::id(),
                "app_name" => $request->path,
                "record_id" => $record->id
            );
            event(new MessageSent($rebound));
            
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
    public function challenge_charge_to(Request $request){

        $auth_user = Auth::user();
        $auth_user_id = Auth::id();

        $validatedData = $request->validate([
            'charge_bet' => 'required',
            'record_id' => 'required'
        ]);


        $record = ChallengeRecord::findOrFail($request->record_id);
        $record->challenge_awards()->attach(Auth::id(), ['award_bet' => $request->charge_bet, 'created_at' => now(), 'updated_at' => now()]);
        return response()->json();

        // $award = new challengeAward;
        // $award->record_id = $request->record_id;
        // $award->award_bet = $request->charge_bet;
        // $award->user_id = $auth_user_id;
        // $award->save();
            
        // $user = $auth_user;
        // $user->award_charge = $user->award_charge - $request->charge_bet;
        // $user->save();

        // #20201202_0013 Tumur　通知機能追加
        // $challenge = ChallengeRecord::find($request->record_id);
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
    public function get_post_comments(Request $request){
        $validatedData = $request->validate([
            'app_name' => 'required',
            'record_id' => 'required'
        ]);
        $comments = CommentRecord::where('record_id', $request->record_id)->where('app_name', $request->app_name)->where('deleted_flag', 0)->with('user')->get();
        return response()->json($comments);  
    }
    public function post_comment_add(Request $request){
        $validatedData = $request->validate([
            'app_name' => 'required',
            'record_id' => 'required',
            'message' => 'required'
        ]);
        $comment = new CommentRecord;
        $comment->app_name = $request->app_name;
        $comment->record_id = $request->record_id;
        $comment->messages = $request->message;
        $comment->user_id = Auth::id();
        $comment->emoji_flag = $request->emoji_flag;
        $comment->save();

        $nameSpace = '\\App\\Models\\'; 
        $model = $nameSpace . ucfirst($request->app_name) . 'Record'; 
        $owner = $model::where('id', '=', $request->record_id)->first();
        $owner_id = $owner->user_id;
        $current_commenters_id = commentRecord::where('deleted_flag', '=', 0)->where('app_name', '=', $request->app_name)->where('record_id', '=', $request->record_id)->where('id', '!=', $comment->id)->where('user_id', '!=', Auth::id())->where('user_id', '!=', $owner_id)->pluck('user_id');
        $current_commenters_id_unique = [];
        foreach($current_commenters_id as $id){
            if(!(in_array($id, $current_commenters_id_unique))){
                $current_commenters_id_unique[] = $id;
            }
        }
        if($request->app_name == 'challenge' || $request->app_name == 'nice'){           
            
            $to_users = $owner->to_users()->get();
            foreach($to_users as $to_user){
                if(!(in_array($to_user['id'], $current_commenters_id_unique)) && $to_user['id'] > 105 && $to_user['id'] !== Auth::id()){
                    $current_commenters_id_unique[] = $to_user['id'];
                }
            }
        }
        
        if($owner_id !== Auth::id()){
            $current_commenters_id_unique[] = $owner_id;
        }          

        $app_name_list = [
            "nice" => 'ナイス',
            "knowledge" => 'ナレッジ',
            "challenge" => 'チャレンジ'
        ];
        $app_name_title = $app_name_list[$request->app_name];
        $from_name = Auth::user()->name . 'さんから、' . $app_name_title .'へコメントが届きました。'; 
        $comment_body = $comment->messages;
        $content = <<<EOD
        $from_name
        
        コメント内容：
        $comment_body
        EOD;
        
        $subject ='【' . $app_name_title . 'へコメントが届きました】 ' . $owner->title;     

        $mail_list = User::where('retire', 0)->whereNotNull('email')->whereIn('id', $current_commenters_id_unique)->where('id', '!=', Auth::id())->pluck('email')->toArray();
        foreach($mail_list as $mail){
            Mail::to($mail)->send(new Comment($subject, $content, $comment->id, $request->app_name, $owner->id));                                  
        
        }         
                
        return response()->json();  
    }
    public function post_add_clap(Request $request){
        $validatedData = $request->validate([
            'app_name' => 'required',
            'record_id' => 'required',
            'action' => 'required'
        ]);
        $existingRecord = ClapRecord::where([
            'record_id' => $request->record_id,
            'from_user' => Auth::id(),
            'app_name' => $request->app_name
        ])->first();
        
        if ($existingRecord) {
            // If a record exists, delete it
            $existingRecord->delete();
        } else {
            // If no record exists, create a new one
            ClapRecord::create([
                'record_id' => $request->record_id,
                'from_user' => Auth::id(),
                'app_name' => $request->app_name
            ]);
        }
        return response()->json(); 
    }
    public function post_comment_edit(Request $request){
        $validatedData = $request->validate([
            'id' => 'required',
            'message' => 'required'
        ]);
        $comment = CommentRecord::findOrFail($request->id)->update([
            "messages" => $request->message
        ]);
        return response()->json();  
    }
    public function post_comment_delete(Request $request){
        $validatedData = $request->validate([
            'id' => 'required',
        ]);
        $comment = CommentRecord::findOrFail($request->id)->update([
            "deleted_flag" => 1
        ]);
        return response()->json();  
    }
    public function post_status_update(Request $request){
        $validatedData = $request->validate([
            'id' => 'required',
            'status' => 'required'
        ]);
        $record = ChallengeRecord::findOrFail($request->id);
        
        $fileIds = $request->resultFiles;
        $pivotValues = [];
        foreach ($fileIds as $fileId) {
            $pivotValues[$fileId] = ['result_flag' => 1];
        }
        $record->result_files()->sync($pivotValues);
        // $record->update([
        //     "status_flag" => $request->status,
        //     "result" => $request->result
        // ]);
        $record->status_flag = $request->status;
        $record->result = $request->result;
        $record->save();
        return response()->json($record);  
    }
    public function post_get_challenge_users(Request $request){
        $other_users = User::where('retire', 0)->where('deleted_flag', 0)->where('id', '>', 105)->select('id', 'name', 'icon_id')->get();
        return response()->json($other_users); 
    }
    public function post_get_nice_users(Request $request){
        $other_users = User::where('retire', 0)->where('deleted_flag', 0)->where('id', '!=', Auth::id())->where('id', '>', 99)->select('id', 'name', 'icon_id')->get();
        return response()->json($other_users); 
    }
    public function get_post_badge(Request $request){
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        $result = [];
        if(!empty($auth_user_id)){
            $list = UserLastRecord::where('user_id', '=', $auth_user_id)->where('deleted_flag', '=', 0)->first();
            $kn = KnowledgeRecord::latest('created_at')->first();
            $nc = NiceRecord::latest('created_at')->first();
            $ch = ChallengeRecord::latest('created_at')->first();
            if(empty($list)){
                $newls = new UserLastRecord;
                $newls->user_id = $auth_user_id;
                $newls->last_knowledge = $kn->id;
                $newls->last_nice = $nc->id;
                $newls->last_challenge = $ch->id;
                $newls->save();
                $list = $newls;
            }
            
            $kn_from = $list->last_knowledge;            
            $kn_to = $kn->id;
            $kn_difference = KnowledgeRecord::whereBetween('id', [$kn_from, $kn_to])->count(); 
            if($kn_difference > 0){
                $kn_difference = $kn_difference - 1;
            }
            $result[0] =  $kn_difference;

            
            
            $nc_from = $list->last_nice;            
            $nc_to = $nc->id;
            $nc_difference = NiceRecord::whereBetween('id', [$nc_from, $nc_to])->count(); 
            if($nc_difference > 0){
                $nc_difference = $nc_difference - 1;
            }
            $result[1] =  $nc_difference;

            
            $ch_from = $list->last_challenge;            
            $ch_to = $ch->id;
            $ch_difference = ChallengeRecord::whereBetween('id', [$ch_from, $ch_to])->count(); 
            if($ch_difference > 0){
                $ch_difference = $ch_difference - 1;
            }
            $result[2] =  $ch_difference;

            return response()->json($result);
        }
        
    }
    public function update_post_badge(Request $request){
        $auth_user = Auth::user();
        $auth_user_id = Auth::id();
        if(!empty($auth_user_id)){
            $last_update = UserLastRecord::where('user_id', '=', $auth_user_id)->where('deleted_flag', '=', 0)->first();
            $nameSpace = '\\App\\Models\\'; 
            $model = $nameSpace . ucfirst($request->which) . 'Record'; 
            $rec = $model::latest('created_at')->first();
            if(!empty($rec)){                
                if(!empty($last_update)){
                    $last_update['last_' . $request->which] = $rec->id;
                    $last_update->save();
                }
            }
            $update = $this->get_post_badge($request);
            return $update;         
            
        }
        
    }
    public function get_featured_tags(Request $request){
        $nameSpace = '\\App\\Models\\'; 
        $model = $nameSpace . ucfirst($request->app_name) . 'UseTag';     
        if($request->pattern === 'first' || $request->pattern === 'reset'){
            
            $use_tags = $model::where('deleted_flag', 0)
            ->whereHas('app_record', function($q){
                $q->where('deleted_flag', 0);
            })
            ->pluck('tag_id')->toArray();
            $unique_list = array_unique($use_tags);
            $tags = TagRecord::where('deleted_flag', 0)->whereIn('id', $unique_list)->orderBy('hits', 'desc')->take($request->offset * 50)->get();
            $indexed = array_count_values($use_tags);
            $tags->map(function ($item) use($indexed) {
                $num = 0;
                if($indexed[$item->id]){
                    $num = $indexed[$item->id];
                }
                $item['occurrence'] = $num;
            });
            $tags = $tags->sortBy('occurrence');
            return response()->json($tags);

        }else if($request->pattern === 'afterSearch'){
            $nameSpace = '\\App\\Models\\'; 
            $model = $nameSpace . ucfirst($request->app_name) . 'Record';  
            $tag_list = $request->tags;

            $query = $model::query()->where(DB::raw('deleted_flag'), '=', '0'); 
            if(!empty($request->key_list)){
                foreach($request->key_list as $key){ 
                    if($request->app_name == 'challenge'){
                        $query->where(DB::raw("CONCAT_WS('', title, ' ', content,' ',content_rule, ' ', content_goal, ' ', key_users, ' ', key_tags, ' ', result)"), 'LIKE', '%' . $key . '%');
                    }else if($request->app_name == 'nice'){
                        $query->where(DB::raw("CONCAT_WS('', title, ' ', content, ' ', key_users, ' ', key_tags)"), 'LIKE', '%' . $key . '%');
                    }else if($request->app_name == 'knowledge'){
                        $query->where(DB::raw("CONCAT_WS('', title, ' ', content, ' ', key_users, ' ', key_tags)"), 'LIKE', '%' . $key . '%');
                    }
                    
                }
            }
            
            $tag_texts = TagRecord::whereIn('id', $tag_list)->pluck('text')->toArray();
            $tag_hit = TagRecord::whereIn('id', $tag_list)->increment('hits');
            if(count($tag_texts)){    
                foreach($tag_texts as $tag){ 
                    
                    $query->whereHas('tags', function ($q) use($tag){
                        $q->where('text', $tag);
                    });
                }
            }
                   
                    
            $result = $query->with('tags')->get();
            $used_tags = [];
            
            foreach($result as $record){
                $list = $record['tags'];
                foreach($list as $tag){
                    
                    // if(!in_array($tag['tag_records'], $used_tags)){
                        $used_tags[] = $tag['id'];
                    // }
                    
                }
            }

            $unique_list = array_unique($used_tags);
            $tags = TagRecord::where('deleted_flag', 0)->whereIn('id', $unique_list)->orderBy('hits', 'desc')->take($request->offset * 50)->get();
            $indexed = array_count_values($used_tags);
            $tags->map(function ($item) use($indexed) {
                $num = 0;
                if($indexed[$item->id]){
                    $num = $indexed[$item->id];
                }
                $item['occurrence'] = $num;
            });
            return response()->json($tags);
            
            
        }
        


        
    }
    public function post_advanced_search(Request $request){
        
        $path = $request->app_name;
        $nameSpace = '\\App\\Models\\'; 
        $model = $nameSpace . ucfirst($request->app_name) . 'Record';  
        $tag_list = $request->tags;
        $tag_texts = TagRecord::whereIn('id', $tag_list)->pluck('text')->toArray();
        if($request->key_word){
            $history = SearchHistoryRecord::where('deleted_flag', 0)->where('user_id', Auth::id())->where('content', $request->key_word)->first();
            if(!$history){
                $new_history = new SearchHistoryRecord;
                $new_history->content = $request->key_word;
                $new_history->user_id = Auth::id();
                $new_history->save();
            }else if($history){
                $history->touch();
            }
        }                
            
        $query = $model::query();
        if(count($tag_texts)){    
            foreach($tag_texts as $tag){ 
                $query->whereHas('tags', function ($query) use ($tag) {
                    $query->where('text', $tag);
                });                       
            }
        }                
        foreach($request->key_list as $key){ 
            $query->when(($path == 'knowledge' || $path == 'nice'), function($q) use($key){
                $q->where(DB::raw("CONCAT_WS('', title, ' ', content, ' ', key_users, ' ', key_tags)"), 'LIKE', '%' . $key . '%');
            });
            $query->when($path == 'challenge', function($q) use($key){
                $q->where(DB::raw("CONCAT_WS('', title, ' ', content,' ',content_rule, ' ', content_goal, ' ', key_users, ' ', key_tags, ' ', result)"), 'LIKE', '%' . $key . '%');
            });           
        }                   
            
        $q_result = $query->with('user')
        ->when($path == 'challenge' || $path == 'nice', function ($query) {
            $query->with('to_users');
        })
        ->with('tags')
        ->with('files')
        ->orderBy('created_at', $request->order)
        ->paginate(10);
        
        return response()->json($q_result);
           
        
       
    }
    public function get_history(Request $request){
        if($request->key == ''){
            $list = SearchHistoryRecord::where('deleted_flag', 0)->where('user_id', Auth::id())->orderBy('updated_at', 'desc')->take(8)->get();
            return response()->json($list);
        }else{
            $list = SearchHistoryRecord::where('deleted_flag', 0)->where('user_id', Auth::id())->where('content', 'LIKE', '%' . $request->key . '%')->orderBy('updated_at', 'desc')->take(8)->get();
            return response()->json($list);
        }
        
    }
}
