<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\KnowledgeRecord;
use App\Models\NiceRecord;
use App\Models\ChallengeRecord;
use App\Models\PostRecord;
use App\Models\TagRecord;
use App\Models\FileRecord;
use App\Models\ClapRecord;
use App\Models\SearchHistoryRecord;
use App\Models\CommentRecord;
use Illuminate\Support\Facades\Mail;
use App\Mail\Comment;
use Illuminate\Support\Facades\File; 
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;
use App\Events\MessageSent;
use App\Jobs\PostStatusChangeNotification;
use App\Services\BadgeService;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    protected $badgeService;
    public function __construct(
        BadgeService $badgeService, 
    ){
        $this->badgeService = $badgeService;
    } 
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
            Storage::disk('local')->delete("$path/{$file->id}_{$file->user_id}_{$file->path}.{$file->extension}");
            Storage::disk('local')->delete("$path/thumbnail/{$file->id}_{$file->user_id}_{$file->path}_thumbnail.webp");
            $file->delete();
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
        
        $has_user = array_key_exists('member', $params) && $params['member'] !== null;
        $user = $has_user ? User::where('name', $params['member'])->first() : null;
        $target_users = $user ? [$user->id] : [];
        $records = PostRecord::query();
        $path = $request->path;
        $app_type = $params['app_type'] ?? null;
        $qr = $records->when($params, function ($query) use($params, $search_tags, $target_users, $path, $app_type) {
            $query->when(array_key_exists('id', $params) && $params['id'], function ($query) use($params) {
                $query->where('id', $params['id']);
            });
            $query->when($target_users, function($query) use ($target_users, $path) {
                $query->when($path == 'post', function ($q) use ($target_users) {
                    $q->where(function ($q) use ($target_users) {
                        foreach($target_users as $user_id){
                            $q->where(function ($q) use ($user_id) {
                                $q->where(function ($q) {
                                    $q->where('app_type', 0)
                                      ->orWhere('app_type', 2);
                                })->whereHas('to_users', function ($query) use ($user_id) {
                                    $query->where('users.id', $user_id);
                                });
                            })
                            ->orWhere(function ($q) use ($target_users) {
                                $q->where('app_type', 1)
                                  ->whereHas('user', function ($query) use ($target_users) {
                                      $query->whereIn('id', $target_users);
                                  });
                            });
                            $q->orWhereIn('user_id', $target_users);
                        }
                    });
                });           
            });
            $query->when($search_tags, function ($query) use ($search_tags) {
                $query->whereHas('tags', function ($query) use ($search_tags) {
                    $query->whereIn('text', $search_tags);
                    foreach ($search_tags as $tag) {
                        $query->orWhere('text', 'LIKE', "%{$tag}%");
                    }
                });
            });

            $query->when(!is_null($app_type), function ($query) use ($app_type) {
                $query->where('app_type', $app_type);
            });

            $query->when($app_type == 2, function ($q) {
                $q->orderByRaw('status_flag != 0')
                ->orderBy('created_at', 'desc');
            });
            
        })
        ->with([
            'user',
            'tags',
            'files',
            'receipts',
            'claps',
            'to_users',
            'grants',
            'entries' => fn ($query) => $query->withCount('comments')->withCount('claps')->with('claps')->orderBy('created_at', 'desc'),    
            'awards',
            'result_files',
            'emotedUsers'
        ])
        ->withCount('comments')
        ->when(!$has_id, function ($query) use($skip) {
            $query->skip($skip);
            
        })
        ->orderBy('updated_at', 'desc')
        ->take(10)        
        ->get();
        return response()->json($qr);


    }
    private function post_refresh(PostRecord $post)
    {
        $post->refresh();
        $post->load([
            'user',
            'tags',
            'files',
            'receipts',
            'claps',
            'to_users',
            'grants',
            'awards',
            'result_files',
            'emotedUsers',
            'entries' => function ($query) {
                $query->withCount('comments')
                    ->withCount('claps')
                    ->with('claps')
                    ->orderBy('created_at', 'desc');
            },
        ]);
        $post->loadCount('comments');

        return $post;
    }
    public function post_get_suggested_tags(Request $request){
        $super = $request->super;
        $key = $request->key;
        $special = $request->special ? $request->special : [];
        $tag_text = TagRecord::where('deleted_flag','=', 0)
        ->where(function ($query) use ($key) {
            $length = mb_strlen($key, 'UTF-8');
    
            for ($i = 0; $i < $length - 1; $i++) {
                $substring = mb_substr($key, $i, 2, 'UTF-8');
                $query->orWhere('text', 'LIKE', $substring);
            }
        })  
        // ->where('text', 'LIKE', '%' . $key . '%')
        ->orderBy('hits', 'desc')
        ->orderBy('created_at', 'desc')
        ->get([
            'id',
            'text'
        ]);
        return response()->json($tag_text);       
    
       
    }
    public function post_get_tags(Request $request){
        $super = $request->super;
        $key = $request->key;
        $special = $request->special ?? [];
        $tag_text = TagRecord::where('deleted_flag','=', 0)
        ->when(!$super, function ($query) use ($key) {
            $query->where('text', 'LIKE', '%' . $key . '%');
        })
        ->orderBy('hits', 'desc')->orderBy('created_at', 'desc')
        ->when(empty($key), function ($query) use ($key) {
            
            $query->take(40);
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
            'icon_path', 'icon_bg',
        ]);
        return response()->json($all_users);
    }
    public function prepare_sharing_files(Request $request ){  
        $path = $request['path'];
        $ids = [];
        foreach($request->list as $file){
            $source_file_path = $file['path'];
            if(strpos($source_file_path, "/cdn") !== false) {
                $source_file_path = str_replace("/cdn", "", $source_file_path);
            }
            $exists = Storage::disk('local')->exists($source_file_path);
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
                
                $thumbnail_path = '/thumbnail/' . $fileRecord->id . '_' . $fileRecord->user_id . '_' . $file_path . '_thumbnail.webp';
                $height = 130;
                $file_type = $file['record']['mime_type'];
                if($file_type == 'image' && $file['record']['extension'] !== 'svg'){
                    $img = Image::read(storage_path('app') . $source_file_path);
                        
                    File::isDirectory(storage_path('app') . $path) or File::makeDirectory(storage_path('app') . '/' . $path, 0755, true, true);                      
                    $img->save(storage_path('app') . $set_path, 30);  
                    $thumbnail = $img->scale(height: 130);  
                    $thumbnail->toWebp()->save(storage_path('app') . $path . $thumbnail_path);
                }else{
                    Storage::disk('local')->copy( $source_file_path, $set_path );
                }
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
            $set_path = $fileRecord->id . '_' . $fileRecord->user_id . '_' . $file_path . '.' . $file_extension;
            $thumbnail_path = 'thumbnail/' . $fileRecord->id . '_' . $fileRecord->user_id . '_' . $file_path . '_thumbnail.webp';
            $height = 130;
            if($file_type == 'image' && $file_extension !== 'svg'){
                $img = Image::read($file);
                if (method_exists($img, 'strip')) {
                    $img->strip();
                }    
                File::isDirectory(storage_path('app') . $path) or File::makeDirectory(storage_path('app') . '/' . $path, 0755, true, true);
                $img = $img->scaleDown(height: 1080);                     
                $img->save(storage_path('app') . $path .'/'. $set_path, 30);  
                File::isDirectory(storage_path('app') . $path .'/thumbnail') or File::makeDirectory(storage_path('app') . '/' . $path .'/thumbnail', 0755, true, true);
                $thumbnail = $img->scale(height: 130);  
                $thumbnail->toWebp()->save(storage_path('app') . $path .'/'. $thumbnail_path);
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

            $request->validate([
                'path' => 'required',
            ]);
            $nameSpace = '\\App\\Models\\'. ucfirst($request->path) . 'Record'; 

            $record = $request->edit_id ? $nameSpace::findOrFail($request->edit_id) : new $nameSpace; 
            $record->user_id = Auth::id();
            $record->title = $request->title;
            if($request->app_type == 2){
                $record->content_rule = $request->content_rule;
                $record->content_goal = $request->content_goal;
                $record->date_start = $request->date_start;
                $record->date_end = $request->date_end;
                $record->award_entry = $request->award_entry;
                $record->chargeable = $request->chargeable;
                $record->grantable = $request->grantable;
                $record->donatable = $request->donatable;
                $record->donation_target = $request->donation_target;
                $record->challenge_main_category = $request->challenge_main_category;
                $record->challenge_sub_category = $request->challenge_sub_category;
            }else{
                $record->content = $request->post_content;
                $record->challenge_main_category = null;
                $record->challenge_sub_category = null;
            }    
            // if($request->app_type == 5 ){
            //     $record->donation_target = $request->donation_target;
            // }        
            $record->referrer = $request->referrer; 
            $record->app_type = $request->app_type;    
            $record->refresh_amount = $request->refresh_amount;     
            if($request->edit_id){
                $record->timestamps = false;
            }
            $record->save();
            $user = Auth::user();
            $user->user_last_record()->firstOrCreate()->touch();
            if($request->app_type == 2 || $request->app_type == 0){
                $record->to_users()->sync($request->to_users);
            }           
            if ($request->grantable) {
                $record->grants()->createMany($request->grants);
            } 
            $tagIds = [];
            foreach ($request->tags as $text) {
                $tag = TagRecord::firstOrCreate(['text' => $text]);

                $tagIds[] = $tag->id;
                $tag->increment('hits');
            }
            $record->tags()->sync($tagIds);

            $record->files()->sync($request->file_ids);
                
            if($request->receipt_ids) {
                $record->receipts()->sync($request->receipt_ids);
            }
            $data = array(
                "app_name" => $request->path,
                "record_id" => $record->id,
            );
            $socket = array();
            array_push($socket, ["event" => 'post:new', "data" => $data]);
            array_push($socket, ["event" => 'post:badge', "data" => []]);  

            // $socket = array(
            //     array(
            //         "event" => "post:new",
            //         "data" => array(
            //             "app_name" => $request->path,
            //             "record_id" => $record->id,
            //         )
            //     )
            // );
            // event(new MessageSent($rebound));            
            return response()->json([
                "socket" => $socket,
                "record" => $record
            ]);
        }
    }
    public function challenge_charge_to(Request $request){


        $request->validate([
            'charge_bet' => 'required',
            'record_id' => 'required'
        ]);


        $record = PostRecord::findOrFail($request->record_id);
        $record->awards()->attach(Auth::id(), ['award_bet' => $request->charge_bet, 'created_at' => now(), 'updated_at' => now()]);
        Auth::user()->update(['award_charge' => Auth::user()->award_charge - $request->charge_bet]);
        return response()->json();        

    }
    public function get_post_comments(Request $request){
        $validatedData = $request->validate([
            'app_name' => 'required',
            'record_id' => 'required'
        ]);
        $comments = CommentRecord::where('record_id', $request->record_id)
                                ->where('app_name', $request->app_name)
                                ->where('deleted_flag', 0)
                                ->with('user')
                                ->with('claps')
                                ->with('emotedUsers')
                                ->get();
        return response()->json($comments);  
    }
    public function comment_send_emote(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'reaction' => 'required|string',
        ]);

        $activeUser = Auth::user();
        $comment = CommentRecord::with('emotedUsers')->findOrFail($request->id);
        $existingEmote = $comment->emotedUsers()->where('user_id', $activeUser->id)->first();

        if ($existingEmote && $existingEmote->pivot->emote_name == $request->reaction) {
            $comment->emotedUsers()->detach($activeUser->id);
        } else if ($existingEmote) {
            $comment->emotedUsers()->updateExistingPivot($activeUser->id, ['emote_name' => $request->reaction]);
        } else {
            $comment->emotedUsers()->attach($activeUser->id, ['emote_name' => $request->reaction]);
        }

        $comment->refresh();
        $comment->load(['user', 'claps', 'emotedUsers']);
        return response()->json($comment);
    }
    public function post_comment_add(Request $request){
        $validatedData = $request->validate([
            'app_name' => 'required',
            'record_id' => 'required',
            'message' => 'required',
            'comment_type' => 'nullable|string',
            'progress_checkpoint' => 'nullable|integer'
        ]);
        $comment = new CommentRecord;
        $comment->app_name = $request->app_name;
        $comment->record_id = $request->record_id;
        $comment->messages = $request->message;
        $comment->comment_type = $request->comment_type ?? 'normal';
        $comment->progress_checkpoint = $request->progress_checkpoint;
        $comment->user_id = Auth::id();
        $comment->emoji_flag = $this->containsOnlyEmojis($request->message);
        $comment->save();

        $nameSpace = '\\App\\Models\\'; 
        $model_name = $request->app_name  == 'post_entry' ? 'PostEntry' : ucfirst($request->app_name). 'Record';
        $model = "{$nameSpace}{$model_name}"; 
        $owner = $model::where('id', '=', $request->record_id)->first();
        $owner_id = $owner->user_id;
        $current_commenters_id = commentRecord::where('deleted_flag', '=', 0)->where('app_name', '=', $request->app_name)->where('record_id', '=', $request->record_id)->where('id', '!=', $comment->id)->where('user_id', '!=', Auth::id())->where('user_id', '!=', $owner_id)->pluck('user_id');
        $current_commenters_id_unique = [];
        foreach($current_commenters_id as $id){
            if(!(in_array($id, $current_commenters_id_unique))){
                $current_commenters_id_unique[] = $id;
            }
        }
        if($request->app_name == 'post'){           
            
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
            "post" => 'ポスト',
            "post_entry" => 'グラリンピクエントリー',
        ];
        $app_name_title = $app_name_list[$request->app_name];
        $from_name = Auth::user()->name . 'さんから、' . $app_name_title .'へコメントが届きました。'; 
        $comment_body = $comment->messages;
        $content = $from_name . '<br>コメント内容：<br>' . $comment_body;
       
        
        $subject ='【' . $app_name_title . 'へコメントが届きました】 ' . $owner->title;     

        $mail_list = User::where('retire', 0)->whereNotNull('email')->whereIn('id', $current_commenters_id_unique)->where('id', '!=', Auth::id())->pluck('email')->toArray();
        foreach($mail_list as $mail){
            Mail::to($mail)->send(new Comment($subject, $content, $comment->id, $request->app_name, $owner->id));                                  
        
        }         
                
        return response()->json();  
    }
    public function post_add_clap(Request $request){
        $request->validate([
            'app_name' => 'required',
            'record_id' => 'required',
            'action' => 'required'
        ]);
        $app_ids = [
            "portfolio" => 6,
            "post" => 2,
            "comment" => 5,
            "post_entry" => 7,
        ];
        $app_id = $app_ids[$request->app_name];
        $existingRecord = ClapRecord::where([
            'record_id' => $request->record_id,
            'from_user' => Auth::id(),
            'app_name' => $request->app_name,
            'app_id' => $app_id
        ])->first();
        
        if ($existingRecord) {
            // If a record exists, delete it
            $existingRecord->delete();
        } else {
            // If no record exists, create a new one
            ClapRecord::create([
                'record_id' => $request->record_id,
                'from_user' => Auth::id(),
                'app_name' => $request->app_name,
                'app_id' => $app_id
            ]);
        }
        return response()->json(); 
    }
    public function post_send_emote(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'reaction' => 'required|string',
        ]);

        $activeUser = Auth::user();
        $post = PostRecord::with('emotedUsers')->findOrFail($request->id);
        $existingEmote = $post->emotedUsers()->where('user_id', $activeUser->id)->first();

        if ($existingEmote && $existingEmote->pivot->emote_name == $request->reaction) {
            $post->emotedUsers()->detach($activeUser->id);
        } else if ($existingEmote) {
            $post->emotedUsers()->updateExistingPivot($activeUser->id, ['emote_name' => $request->reaction]);
        } else {
            $post->emotedUsers()->attach($activeUser->id, ['emote_name' => $request->reaction]);
        }

        return response()->json($this->post_refresh($post));
    }
    private function containsOnlyEmojis($text)
    {
        $emojiPattern = '/^[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{2B50}\x{2B06}\x{2934}\x{2935}\x{2B05}\x{2194}\x{2195}\x{25AA}\x{25AB}\x{25B6}\x{25C0}\x{25FB}\x{25FE}\x{25FD}\x{25FC}\x{25AA}\x{25AB}\x{25B6}\x{25C0}\x{25FB}\x{25FE}\x{25FD}\x{25FC}\x{0023}\x{002A}\x{0030}-\x{0039}\x{20E3}\x{00A9}\x{00AE}\x{2122}\x{23F3}\x{24C2}\x{23E9}\x{23EA}\x{3030}\x{1F004}-\x{1F0CF}\x{1F170}-\x{1F251}]{1}$/u';
        return preg_match($emojiPattern, $text);
    }
    public function post_comment_edit(Request $request){
        $validatedData = $request->validate([
            'id' => 'required',
            'message' => 'required',
        ]);
        $comment = CommentRecord::findOrFail($request->id)->update([
            "messages" => $request->message,
            "emoji_flag" => $this->containsOnlyEmojis($request->message)
        ]);
        return response()->json();  
    }
    public function post_comment_delete(Request $request){
        $validatedData = $request->validate([
            'id' => 'required',
        ]);
        $comment = CommentRecord::findOrFail($request->id);
        $comment->update([
            "deleted_flag" => 1
        ]);
        $comment->delete();
        return response()->json();  
    }
    public function post_status_update(Request $request){
        
        $request->validate([
            'id' => 'required',
            'status' => 'required'
        ]);

        
        $record = PostRecord::findOrFail($request->id);
        
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
        $user = Auth::user();
        $user->user_last_record()->firstOrCreate()->touch();
        PostStatusChangeNotification::dispatch($record, [Auth::id()]);
        
        return response()->json($record);  
    }
    public function post_get_all_possible_users(Request $request){
        $other_users = User::where('retire', 0)->where('deleted_flag', 0)->select('id', 'name', 'icon_path', 'icon_bg', 'icon_bg')->get();
        return response()->json($other_users); 
    }
    public function post_get_challenge_users(Request $request){
        $other_users = User::where('retire', 0)->where('deleted_flag', 0)->where('id', '>', 105)->select('id', 'name', 'icon_path', 'icon_bg', 'icon_bg')->get();
        return response()->json($other_users); 
    }
    public function post_get_post_users(Request $request){
        $other_users = User::where('retire', 0)->where('deleted_flag', 0)->where('id', '!=', Auth::id())->where('id', '>', 99)->select('id', 'name', 'icon_path', 'icon_bg', 'icon_bg')->get();
        return response()->json($other_users); 
    }
    public function update_post_badge(Request $request){
        $auth_user = Auth::user();
        if(!empty($auth_user)){
            $auth_user->user_last_record()->firstOrCreate()->touch();
            $update = $this->badgeService->post($auth_user);
            return $update;         
            
        }
        
    }
    public function get_top_tags(Request $request){
        
        $current_tag = $request->current_tag;  
        
        $tagsQuery = TagRecord::where('deleted_flag', 0)
        ->whereHas('postRecords', function ($query) {
            $query->whereNot('app_type', 1);
        })
        ->withCount("postOccurence")
        ->orderBy("post_occurence_count", 'desc');
        if($current_tag) {
            $relatedTagIds = TagRecord::where('deleted_flag', 0)
            ->whereHas('postRecords', function ($query) use ($current_tag) {
                $query->whereHas('tags', function ($query) use ($current_tag) {
                    $query->where('text', $current_tag);
                });
            })
            ->pluck('id');
            $tagsQuery->whereIn('id', $relatedTagIds)
                      ->where('text', '!=', $current_tag);
        }
        $tags = $tagsQuery->get();
        return response()->json($tags);       

    }
    public function get_featured_tags(Request $request){
        $nameSpace = '\\App\\Models\\'; 
        $model = $nameSpace . ucfirst($request->app_name) . 'UseTag';     
        if($request->pattern === 'first' || $request->pattern === 'reset'){
            
            $use_tags = $model::whereHas('app_record')
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
            $query = $model::query(); 
            $target_users = $request->target_users;
            if(count($target_users)){    
                $query->when(function($q) use($target_users){
                    $q->whereHas('to_users', function ($query) use ($target_users) {
                        $query->whereIn('users.id', $target_users);
                    })->orWhereHas('user', function ($query) use ($target_users) {
                        $query->whereIn('id', $target_users);
                    });  
                });                 
            }          
            $to = $request->to;
            $from = $request->from;
    
            $query->when(!empty($from), function($q) use($from) {
                $q->whereDate('created_at', '>=', $from);
            });
            $query->when(!empty($to), function($q) use($to) {
                $q->whereDate('created_at', '<=', $to);
            });
            if(!empty($request->key_list)){
                foreach($request->key_list as $key){ 
                    $query->whereRaw("CONCAT_WS('', title, ' ', content, ' ', content_rule, ' ', content_goal, ' ', challenge_main_category, ' ', challenge_sub_category, ' ', key_users, ' ', key_tags, ' ', result) LIKE ?", ['%' . $key . '%']);
                    
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
        $target_users = $request->target_users;
        if(count($target_users)){    
            $query->when($path == 'post', function ($q) use ($target_users) {
                $q->where(function ($q) use ($target_users) {
                    foreach($target_users as $user_id){
                        $q->where(function ($q) use ($user_id) {
                            $q->where(function ($q) {
                                $q->where('app_type', 0)
                                  ->orWhere('app_type', 2);
                            })->whereHas('to_users', function ($query) use ($user_id) {
                                $query->where('users.id', $user_id);
                            });
                        })
                        ->orWhere(function ($q) use ($target_users) {
                            $q->where('app_type', 1)
                              ->whereHas('user', function ($query) use ($target_users) {
                                  $query->whereIn('id', $target_users);
                              });
                        });
                        $q->orWhereIn('user_id', $target_users);
                    }
                });
            }); 
                      
                          
           
        }          
        $to = $request->to;
        $from = $request->from;

        $query->when(!empty($from), function($q) use($from) {
            $q->whereDate('created_at', '>=', $from);
        });
        $query->when(!empty($to), function($q) use($to) {
            $q->whereDate('created_at', '<=', $to);
        });
        foreach($request->key_list as $key){ 
            $query->when(($path == 'post'), function($q) use($key){
                $q->whereRaw("CONCAT_WS('', title, ' ', content, ' ', content_rule, ' ', content_goal, ' ', challenge_main_category, ' ', challenge_sub_category, ' ', key_users, ' ', key_tags, ' ', result) LIKE ?", ['%' . $key . '%']);
            });
                    
        }                   
            
        $q_result = $query->with('user')
        ->with('to_users')
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
    public function post_entries(Request $request){
        $validatedData = $request->validate([
            'record_id' => 'required',
            'calories' => 'required|numeric|max:10000',
            'file_ids' => 'array|required'
        ],[
            'calories.max' => 'カロリーは10000以下で入力してください。',
            'file_ids.required' => 'ファイルは必須です。',
        ]);
        $post = PostRecord::findOrFail($request->record_id);
        $entry = $post->entries()->updateOrCreate(
            ['id' => $request->id],
            [
                'user_id' => Auth::id(),
                'calories' => $request->calories,
                'comment' => $request->comment,
            ]
        );
        $file_ids = $request->file_ids ?? [];
        $entry->files()->sync($file_ids);
        return response()->json([
            'entry' => $entry,
            'files' => $entry->files
        ]);
    }
    public function get_top_posts(Request $request){

        $entry_users = User::whereHas('post_entries')
        // ->whereNotIn('id', [513])
        ->select('id', 'name', 'icon_path', 'icon_bg')
        ->get();
        $awards = [
            '🥇',
            '🥈',
            '🥉'
        ];
        $entry_users = $entry_users->map(fn($user) => [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'icon_path' => $user->icon_path,
                'icon_bg' => $user->icon_bg,
            ],
            'post_count' => $user->post_entries->count(),
            'sum_calories' => $user->post_entries->sum('calories'),
            'post_donation_targets' => $user->post_entries
                ->pluck('post.donation_target')
                ->filter(fn($v) => filled($v))
                ->unique()
                ->values(),
        ]);



        $entry_users = $entry_users->sortByDesc('sum_calories')->take(10)->values()->all();

        if(count($entry_users)){
            $entry_users[0]['award'] = $awards[0];
        }
        if(count($entry_users) > 1){
            $entry_users[1]['award'] = $awards[1];
        }
        if(count($entry_users) > 2){
            $entry_users[2]['award'] = $awards[2];
        }

        return response()->json($entry_users);
    }
    public function post_remove_file(Request $request) {
        $validatedData = $request->validate([
            'file_path' => 'required|string',
        ]);

        $file_path = $validatedData['file_path'];
        $relativePath = 'post_grant_files/' . $file_path;

        if (Storage::disk('local')->exists($relativePath)) {
            Storage::disk('local')->delete($relativePath);
        }

        return response()->json(['message' => 'File removed successfully']);
    }
    public function post_grant_upload(Request $request){
        $path = '/post_grant_files';
        $fileContent = $request->file('file');
        $file_path = $this->path_generator();           
        $file_extension = $fileContent->getClientOriginalExtension();
            
        $mime_type = $fileContent->getMimeType();
        $mime_type_array = explode('/',$mime_type);
        $file_type = $mime_type_array[0];           
        
        if($file_type == 'image' && $file_extension !== 'svg'){
            $img = Image::read($fileContent);
            $file_extension = 'webp';
            $img->scale(640);
            $file_path .= '.webp';
            File::isDirectory(storage_path('app') . $path) or File::makeDirectory(storage_path('app') . $path, 0755, true, true);                      
            $img->toWebp(80)->save(storage_path('app') . $path .'/'. $file_path);  
        } else {
            $file_path .= ".{$file_extension}";
            Storage::disk('local')->putFileAs(
                $path, $fileContent, $file_path
            );
        }
        $data = [
            "file_path" => $file_path,
            "file_type" => $file_type,
            "file_extension" => $file_extension
        ];
        return response()->json($data); 
    }
    private function path_generator(){
        $timestamp = time();
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < 5; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        $iconId = $timestamp . $randomString;
        if (strlen($iconId) > 15) {
            $iconId = substr($iconId, 0, 15);
        }    
        return $iconId;
    }
}
