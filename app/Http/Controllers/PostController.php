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
use App\Models\FileAttachment;
use App\Models\ClapRecord;
use App\Models\SearchHistoryRecord;
use App\Models\CommentRecord;
use App\Models\PostRelay;
use App\Models\PostRelayPrize;
use Illuminate\Support\Facades\Mail;
use App\Mail\Comment;
use Illuminate\Support\Facades\File; 
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;
use App\Events\MessageSent;
use App\Jobs\PostStatusChangeNotification;
use App\Services\BadgeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Jobs\SocketEmitter;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

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
        $main_category = $params['main_category'] ?? null;
        $sub_category = $params['sub_category'] ?? null;
        $donation_target = $params['donation_target'] ?? null;
        $qr = $records->when($params, function ($query) use($params, $search_tags, $target_users, $path, $app_type, $main_category, $sub_category, $donation_target) {
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

            $query->when($main_category, function ($query) use ($main_category) {
                $query->where('challenge_main_category', $main_category);
            });

            $query->when($sub_category, function ($query) use ($sub_category) {
                $query->where('challenge_sub_category', $sub_category);
            });

            $query->when($donation_target === 'exists', function ($query) {
                $query->whereNotNull('donation_target')
                    ->where('donation_target', '!=', '');
            });

            $query->when($donation_target === 'missing', function ($query) {
                $query->where(function ($query) {
                    $query->whereNull('donation_target')
                        ->orWhere('donation_target', '');
                });
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
            'emotedUsers',
            'postRelays' => fn ($query) => $query
                ->with(['fromUser', 'toUser', 'declinedByUser', 'closedByUser', 'acceptedPost:id,title'])
                ->orderByDesc('updated_at'),
            'acceptedPostRelay' => fn ($query) => $query
                ->with(['fromUser', 'sourcePost:id,title'])
        ])
        ->withCount('comments')
        ->when(!$has_id, function ($query) use($skip) {
            $query->skip($skip);
            
        })
        ->orderBy('updated_at', 'desc')
        ->take(10)        
        ->get();
        $this->appendRelayChainUsers($qr);
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
            'postRelays' => function ($query) {
                $query->with(['fromUser', 'toUser', 'declinedByUser', 'closedByUser', 'acceptedPost:id,title'])
                    ->orderByDesc('updated_at');
            },
            'acceptedPostRelay' => function ($query) {
                $query->with(['fromUser', 'sourcePost:id,title']);
            },
            'entries' => function ($query) {
                $query->withCount('comments')
                    ->withCount('claps')
                    ->with('claps')
                    ->orderBy('created_at', 'desc');
            },
        ]);
        $post->loadCount('comments');
        $this->appendRelayChainUsers(collect([$post]));

        return $post;
    }
    private function appendRelayChainUsers($posts): void
    {
        foreach ($posts as $post) {
            $relayType = $this->relayTypeForPost($post);
            if (!$relayType) {
                continue;
            }

            $groups = $this->relayChainGroups($post, $relayType);
            $chain = $this->flattenRelayChainGroups($groups);
            $post->setAttribute('relay_chain_groups', $groups);
            $post->setAttribute('relay_chain', $chain);
            $post->setAttribute('relay_chain_users', array_map(fn ($node) => $node['user'], $chain));
        }
    }
    private function relayTypeForPost(PostRecord $post): ?string
    {
        return match ((int) $post->app_type) {
            0 => PostRelay::TYPE_NICE,
            2 => PostRelay::TYPE_CHALLENGE,
            default => null,
        };
    }
    private function relayChainGroups(PostRecord $post, string $relayType): array
    {
        $root = $this->relayRootPost($post, $relayType);
        $current = $root;
        $groups = [];
        $visitedPostIds = [];

        while ($current && !in_array((int) $current->id, $visitedPostIds, true)) {
            $visitedPostIds[] = (int) $current->id;
            $current->loadMissing('user');
            $this->pushRelayChainGroup($groups, [$current->user]);

            $relays = PostRelay::where('relay_type', $relayType)
                ->where('source_post_id', $current->id)
                ->whereNotIn('to_user_id', PostRelay::EXCLUDED_USER_IDS)
                ->with(['toUser', 'acceptedPost.user'])
                ->orderBy('assigned_at')
                ->orderBy('id')
                ->get();

            if ($relays->isEmpty()) {
                break;
            }

            if ($relayType === PostRelay::TYPE_NICE) {
                $nextPost = $this->appendNiceRelayChainGroup($groups, $relays, $current);
                if (!$nextPost) {
                    break;
                }

                $current = $nextPost;
                continue;
            }

            $nextPost = null;
            foreach ($relays as $relay) {
                if ($this->relayWasPassed($relay)) {
                    continue;
                }

                $this->pushRelayChainGroup($groups, [$relay->toUser], $this->relayConnector($relay));

                if ($relay->acceptedPost) {
                    $nextPost = $relay->acceptedPost;
                    break;
                }

                if ((int) $relay->status === PostRelay::STATUS_PENDING) {
                    break;
                }
            }

            if (!$nextPost) {
                break;
            }

            $current = $nextPost;
        }

        return $groups;
    }
    private function appendNiceRelayChainGroup(array &$groups, $relays, PostRecord $sourcePost): ?PostRecord
    {
        $sourcePost->loadMissing('to_users');
        $continuedRelay = $relays->first(fn (PostRelay $relay) => $relay->acceptedPost);

        if ($continuedRelay) {
            $continuedUser = $continuedRelay->toUser ?? $sourcePost->to_users->firstWhere('id', $continuedRelay->to_user_id);
            if ($continuedUser) {
                $this->pushRelayChainGroup($groups, [$continuedUser], 'solid');
            }
            return $continuedRelay->acceptedPost;
        }

        $users = $relays
            ->filter(function (PostRelay $relay) {
                if ((int) $relay->status === PostRelay::STATUS_PENDING) {
                    return true;
                }

                // Terminal completion: the final person completed the chain without
                // continuing it (their own post starts a fresh relay). Still show them.
                return (int) $relay->status === PostRelay::STATUS_COMPLETED
                    && is_null($relay->accepted_post_id);
            })
            ->map(fn (PostRelay $relay) => $relay->toUser ?? $sourcePost->to_users->firstWhere('id', $relay->to_user_id))
            ->filter()
            ->values()
            ->all();
        $this->pushRelayChainGroup($groups, $users, 'solid');

        return null;
    }
    private function flattenRelayChainGroups(array $groups): array
    {
        $chain = [];
        foreach ($groups as $group) {
            foreach ($group['users'] as $user) {
                $node = ['user' => $user];
                if (empty($chain) && isset($group['connector'])) {
                    $node['connector'] = $group['connector'];
                } elseif (!empty($chain)) {
                    $node['connector'] = $group['connector'] ?? 'solid';
                }
                $chain[] = $node;
            }
        }

        return $chain;
    }
    private function relayConnector(PostRelay $relay): string
    {
        return $this->relayWasPassed($relay)
            ? 'dashed'
            : 'solid';
    }
    private function relayWasPassed(PostRelay $relay): bool
    {
        return (bool) $relay->declined_at || (int) $relay->status === PostRelay::STATUS_DECLINED;
    }
    private function relayRootPost(PostRecord $post, string $relayType): PostRecord
    {
        $current = $post;
        $visitedPostIds = [];

        while (!in_array((int) $current->id, $visitedPostIds, true)) {
            $visitedPostIds[] = (int) $current->id;
            $incomingRelay = PostRelay::where('relay_type', $relayType)
                ->where('accepted_post_id', $current->id)
                ->with('sourcePost.user')
                ->orderBy('assigned_at')
                ->orderBy('id')
                ->first();

            if (!$incomingRelay?->sourcePost) {
                break;
            }

            $current = $incomingRelay->sourcePost;
        }

        return $current;
    }
    private function niceChainPostCount(?PostRecord $post): int
    {
        if (!$post) {
            return 0;
        }

        $count = 0;
        $currentId = (int) $post->id;
        $visited = [];

        while ($currentId && !in_array($currentId, $visited, true)) {
            $visited[] = $currentId;
            $count++;

            $incomingRelay = PostRelay::where('relay_type', PostRelay::TYPE_NICE)
                ->where('accepted_post_id', $currentId)
                ->orderBy('assigned_at')
                ->orderBy('id')
                ->first();

            if (!$incomingRelay) {
                break;
            }

            $currentId = (int) $incomingRelay->source_post_id;
        }

        return $count;
    }
    private function pushRelayChainGroup(array &$groups, array $users, ?string $connector = null): void
    {
        $formattedUsers = [];
        foreach ($users as $user) {
            if (!$user) {
                continue;
            }

            $formattedUser = [
                'id' => $user->id,
                'name' => $user->name,
                'icon_path' => $user->icon_path,
                'icon_bg' => $user->icon_bg,
            ];

            if (!collect($formattedUsers)->contains(fn ($existing) => (int) $existing['id'] === (int) $formattedUser['id'])) {
                $formattedUsers[] = $formattedUser;
            }
        }

        if (empty($formattedUsers)) {
            return;
        }

        $last = end($groups);
        if ($last && collect($formattedUsers)->every(fn ($user) => collect($last['users'])->contains(fn ($lastUser) => (int) $lastUser['id'] === (int) $user['id']))) {
            return;
        }

        $group = ['users' => $formattedUsers];
        if ($connector) {
            $group['connector'] = $connector;
        }

        $groups[] = $group;
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
                'challenge_relay_id' => 'nullable|integer|exists:post_relays,id',
            ]);
            $nameSpace = '\\App\\Models\\'. ucfirst($request->path) . 'Record'; 

            $record = $request->edit_id ? $nameSpace::findOrFail($request->edit_id) : new $nameSpace; 
            if((int) $request->app_type === 2 && !$request->edit_id && $request->filled('challenge_relay_id')){
                if (!$request->boolean('mini')) {
                    throw ValidationException::withMessages([
                        'mini' => 'リレーから作成するチャレンジはミニチャレンジにしてください。',
                    ]);
                }

                PostRelay::where('id', $request->challenge_relay_id)
                    ->where('relay_type', PostRelay::TYPE_CHALLENGE)
                    ->where('to_user_id', Auth::id())
                    ->where('status', PostRelay::STATUS_PENDING)
                    ->firstOrFail();
                $this->validateRelayChallengePlayers($request);
            }
            if ($request->app_type == 7) {
                $check = $this->check_rakuaward();
                
                if ($check) {
                    throw ValidationException::withMessages([
                        'rakuaward' => '今月の楽アワードノミネート既に作成してます',
                    ]);
                }
                $todayDay = Carbon::now()->day;
                if ($todayDay > 20) {
                    throw ValidationException::withMessages([
                        'rakuaward' => '20日を過ぎてしまったため、楽アワードノミネートすることはできません',
                    ]);
                }
            }
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
                $record->mini = $request->mini;
            }else{
                $record->content = $request->post_content;
                $record->challenge_main_category = null;
                $record->challenge_sub_category = null;
                $record->chargeable = (int) $request->app_type === 7 ? true : false;
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
            if((int) $request->app_type === 2 && !$request->edit_id){
                $request->filled('challenge_relay_id')
                    ? $this->completeChallengeRelayWithPost($record, (int) $request->challenge_relay_id)
                    : $this->closePendingChallengeRelaysForUser($record);
            }
            $user = Auth::user();
            $user->user_last_record()->firstOrCreate()->touch();
            $this->badgeService->invalidateBadgeSummaryCache();
            if($request->app_type == 2 || $request->app_type == 0 || $request->app_type == 7){
                $record->to_users()->sync($request->to_users);
                // A rakuaward nice is a standalone chargeable nomination, not part of the
                // nice-relay / GlowdNine system, so skip all relay handling for it.
                if ((int) $request->app_type === 0 && !$request->edit_id) {
                    $this->completePendingNiceRelaysForUser($record);
                    $this->createNiceRelaysForPost($record, $request->to_users ?? []);
                    $this->maybeAwardRelayGlowdNine($record, PostRelay::TYPE_NICE);
                }
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
            
            SocketEmitter::dispatchAfterResponse([
                ["event" => 'post:new', "data" => $data],
                ["event" => 'post:badge', "data" => []]
            ]);
            return response()->json([
                "record" => $this->post_refresh($record)
            ]);
        }
    }
    private function check_rakuaward(){
        $activeUser = Auth::user();
        $record = PostRecord::where('user_id', $activeUser->id)->where('app_type', 7)->where('created_at', '>=', Carbon::now()->startOfMonth())->where('created_at', '<=', Carbon::now()->endOfMonth())->first();
        if ($record) {
            return true;
        }
        return false;
    }
    public function challenge_charge_to(Request $request){

        $request->validate([
            'charge_bet' => 'required|integer',
            'record_id' => 'required|integer|exists:post_records,id',
        ]);

        $record = PostRecord::findOrFail($request->record_id);
        $chargeBet = (int) $request->charge_bet;
        $user = Auth::user();

        $isRakuawardNice = (int) $record->app_type === 7;
        $isChallenge = (int) $record->app_type === 2;

        if (!$isRakuawardNice && !$isChallenge) {
            throw ValidationException::withMessages(['record_id' => 'この投稿にはチャージできません。']);
        }

        // Rakuaward nice: enforce the 500 cap and the created_at -> end-of-month window server-side.
        if ($isRakuawardNice) {
            if (! is_null($record->rakuaward_granted_at) || ! is_null($record->rakuaward_refunded_at)) {
                throw ValidationException::withMessages(['charge_bet' => 'この楽アワードのチャージは締め切られました。']);
            }

            if (Carbon::now()->gt(Carbon::parse($record->created_at)->endOfMonth())) {
                throw ValidationException::withMessages(['charge_bet' => 'チャージ期間が終了しました。']);
            }

            if ($chargeBet < 100 || $chargeBet > 500 || $chargeBet % 100 !== 0) {
                throw ValidationException::withMessages(['charge_bet' => 'チャージ額は100円単位で最大500円までです。']);
            }

            if ($record->awards()->where('users.id', $user->id)->exists()) {
                throw ValidationException::withMessages(['charge_bet' => '既にチャージしています。']);
            }

            if ((int) $user->award_charge < $chargeBet) {
                throw ValidationException::withMessages(['charge_bet' => 'チャージ可能額が不足しています。']);
            }
        }

        $record->awards()->attach($user->id, ['award_bet' => $chargeBet, 'created_at' => now(), 'updated_at' => now()]);
        $user->update(['award_charge' => $user->award_charge - $chargeBet]);
        if ($request->emote) {
            $this->sendEmote($record->id, $request->emote);
        }
        $this->badgeService->invalidateBadgeSummaryCache();
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
                                ->with(['user' => function ($query) {
                                    $query->select('id', 'name', 'icon_path', 'icon_bg');
                                }, 'progress_files', 'emotedUsers', 'claps'])
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
        $comment->load(['user', 'progress_files', 'claps', 'emotedUsers']);
        return response()->json($comment);
    }
    public function post_comment_add(Request $request){
        $request->validate([
            'app_name' => 'required|string',
            'record_id' => 'required|integer',
            'message' => 'required|string|max:2000',
            'comment_type' => 'nullable|string',
            'progress_checkpoint' => 'nullable|integer',
            'progress_files' => 'nullable|array',
            'progress_files.*' => 'integer|exists:file_records,id',
            'status_to' => 'nullable|integer',
        ]);

        $comment = DB::transaction(function () use ($request) {
            return $this->createCommentRecord(
                $request->app_name,
                (int) $request->record_id,
                $request->message,
                $request->comment_type ?? 'normal',
                $request->progress_checkpoint,
                $request->progress_files ?? [],
                $request->status_to
            );
        });

        if ($request->app_name === 'post' && $comment->comment_type === 'progress_report') {
            $this->badgeService->invalidateBadgeSummaryCache();
        }

        $this->sendCommentNotification($comment);

        return response()->json($comment->load(['user', 'progress_files', 'emotedUsers', 'claps']));
    }

    private function createCommentRecord(
        string $appName,
        int $recordId,
        string $message,
        string $commentType = 'normal',
        ?int $progressCheckpoint = null,
        array $fileIds = [],
        ?int $statusTo = null
    ): CommentRecord {
        $comment = new CommentRecord;
        $comment->app_name = $appName;
        $comment->record_id = $recordId;
        $comment->messages = $message;
        $comment->comment_type = $commentType;
        $comment->progress_checkpoint = $progressCheckpoint;
        $comment->status_to = $statusTo;
        $comment->user_id = Auth::id();
        $comment->emoji_flag = $this->containsOnlyEmojis($message);
        $comment->save();

        $this->syncCommentFiles($comment, $fileIds);

        return $comment;
    }

    private function syncCommentFiles(CommentRecord $comment, array $fileIds): void
    {
        $fileIds = collect($fileIds)
            ->filter()
            ->map(fn ($fileId) => (int) $fileId)
            ->unique()
            ->values()
            ->all();

        FileAttachment::where('attachable_type', CommentRecord::class)
            ->where('attachable_id', $comment->id)
            ->where('collection', 'progress_files')
            ->when(count($fileIds), fn ($query) => $query->whereNotIn('file_id', $fileIds))
            ->delete();

        foreach ($fileIds as $fileId) {
            FileAttachment::firstOrCreate([
                'file_id' => $fileId,
                'attachable_type' => CommentRecord::class,
                'attachable_id' => $comment->id,
                'collection' => 'progress_files',
            ]);
        }
    }

    private function sendCommentNotification(CommentRecord $comment): void
    {
        $nameSpace = '\\App\\Models\\'; 
        $model_name = $comment->app_name  == 'post_entry' ? 'PostEntry' : ucfirst($comment->app_name). 'Record';
        $model = "{$nameSpace}{$model_name}"; 
        $owner = $model::where('id', '=', $comment->record_id)->first();
        $owner_id = $owner->user_id;
        $current_commenters_id = CommentRecord::where('deleted_flag', '=', 0)->where('app_name', '=', $comment->app_name)->where('record_id', '=', $comment->record_id)->where('id', '!=', $comment->id)->where('user_id', '!=', Auth::id())->where('user_id', '!=', $owner_id)->pluck('user_id');
        $current_commenters_id_unique = [];
        foreach($current_commenters_id as $id){
            if(!(in_array($id, $current_commenters_id_unique))){
                $current_commenters_id_unique[] = $id;
            }
        }
        if($comment->app_name == 'post'){
            
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
        $app_name_title = $app_name_list[$comment->app_name];
        $from_name = Auth::user()->name . 'さんから、' . $app_name_title .'へコメントが届きました。'; 
        $comment_body = $comment->messages;
        $content = $from_name . '<br>コメント内容：<br>' . $comment_body;
       
        
        $subject ='【' . $app_name_title . 'へコメントが届きました】 ' . $owner->title;     

        $mail_list = User::where('retire', 0)->whereNotNull('email')->whereIn('id', $current_commenters_id_unique)->where('id', '!=', Auth::id())->pluck('email')->toArray();
        foreach($mail_list as $mail){
            Mail::to($mail)->send(new Comment($subject, $content, $comment->id, $comment->app_name, $owner->id));
        
        }         
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

        $post = $this->sendEmote( 
            $request->id,
            $request->reaction
        );

        return response()->json($this->post_refresh($post));
    }
    private function sendEmote(int $id, string $reaction)
    {
        $activeUser = Auth::user();
        $post = PostRecord::with('emotedUsers')->findOrFail($id);
        $existingEmote = $post->emotedUsers()->where('user_id', $activeUser->id)->first();

        if ($existingEmote && $existingEmote->pivot->emote_name == $reaction) {
            $post->emotedUsers()->detach($activeUser->id);
        } else if ($existingEmote) {
            $post->emotedUsers()->updateExistingPivot($activeUser->id, ['emote_name' => $reaction]);
        } else {
            $post->emotedUsers()->attach($activeUser->id, ['emote_name' => $reaction]);
        }
        return $post;
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
            'id' => 'required|integer',
            'status' => 'required|integer|in:0,1,2,3',
            'message' => 'nullable|string|max:2000',
            'progress_files' => 'nullable|array',
            'progress_files.*' => 'integer|exists:file_records,id',
            'challenge_relay_to_user_id' => 'nullable|integer|exists:users,id',
        ]);

        $status = (int) $request->status;
        $message = trim((string) $request->message);
        if ($status !== 0 && $message === '') {
            throw ValidationException::withMessages([
                'message' => '結果を入力してください。',
            ]);
        }

        $comment = null;
        $record = DB::transaction(function () use ($request, $status, $message, &$comment) {
            $record = PostRecord::lockForUpdate()->findOrFail($request->id);

            $record->status_flag = $status;
            $record->save();

            if ($status !== 0) {
                $comment = $this->createCommentRecord(
                    'post',
                    $record->id,
                    $message,
                    'result',
                    null,
                    $request->progress_files ?? [],
                    $status
                );
            }

            if ((int) $record->app_type === 2 && $status === 1 && $request->filled('challenge_relay_to_user_id')) {
                if (!$record->mini) {
                    throw ValidationException::withMessages([
                        'challenge_relay_to_user_id' => 'ミニチャレンジのみバトンを渡せます。',
                    ]);
                }

                $this->upsertChallengeRelay($record, (int) $request->challenge_relay_to_user_id);
            }

            if ((int) $record->app_type === 2 && $status === 1) {
                $this->awardChallengeSupportersGlowdNine($record);
            }

            return $record;
        });

        if ($comment) {
            $this->sendCommentNotification($comment);
        }

        $user = Auth::user();
        $user->user_last_record()->firstOrCreate()->touch();
        $this->badgeService->invalidateBadgeSummaryCache();
        PostStatusChangeNotification::dispatch($record, [Auth::id()]);
        
        return response()->json($record);  
    }
    private function upsertChallengeRelay(PostRecord $record, int $toUserId): void
    {
        if ($toUserId === Auth::id()) {
            throw ValidationException::withMessages([
                'challenge_relay_to_user_id' => '自分以外のメンバーを選択してください。',
            ]);
        }
        $this->ensureEligibleChallengeRelayTarget($toUserId);
        $this->ensureChallengeRelayTargetHasNoHistory(
            $record->id,
            Auth::id(),
            $toUserId,
            'challenge_relay_to_user_id'
        );

        $now = Carbon::now();
        $values = [
            'to_user_id' => $toUserId,
            'declined_by_user_id' => null,
            'status' => PostRelay::STATUS_PENDING,
            'assigned_at' => $now,
            'deadline_at' => $now->copy()->addWeek(),
            'declined_at' => null,
            'accepted_post_id' => null,
            'closed_by_user_id' => null,
            'closed_at' => null,
        ];
        $openRelay = PostRelay::where('relay_type', PostRelay::TYPE_CHALLENGE)
            ->where('source_post_id', $record->id)
            ->where('from_user_id', Auth::id())
            ->where('status', PostRelay::STATUS_PENDING)
            ->whereNull('closed_at')
            ->first();

        if ($openRelay) {
            $openRelay->update($values);
        } else {
            PostRelay::updateOrCreate(
                [
                    'relay_type' => PostRelay::TYPE_CHALLENGE,
                    'source_post_id' => $record->id,
                    'from_user_id' => Auth::id(),
                    'to_user_id' => $toUserId,
                ],
                $values
            );
        }

        // Passing this baton may complete a 9-person challenge relay -> award GlowdNine.
        $this->maybeAwardRelayGlowdNine($record, PostRelay::TYPE_CHALLENGE);
    }
    private function closePendingChallengeRelaysForUser(PostRecord $record): void
    {
        PostRelay::where('relay_type', PostRelay::TYPE_CHALLENGE)
            ->where('to_user_id', Auth::id())
            ->where('status', PostRelay::STATUS_PENDING)
            ->where(function ($query) use ($record) {
                $query->whereNull('assigned_at')
                    ->orWhere('assigned_at', '<=', $record->created_at);
            })
            ->update([
                'status' => PostRelay::STATUS_COMPLETED,
                'accepted_post_id' => $record->id,
                'closed_by_user_id' => Auth::id(),
                'closed_at' => Carbon::now(),
            ]);
    }
    private function completeChallengeRelayWithPost(PostRecord $record, int $relayId): void
    {
        $relay = PostRelay::where('id', $relayId)
            ->where('relay_type', PostRelay::TYPE_CHALLENGE)
            ->where('to_user_id', Auth::id())
            ->where('status', PostRelay::STATUS_PENDING)
            ->firstOrFail();

        $relay->update([
            'status' => PostRelay::STATUS_COMPLETED,
            'accepted_post_id' => $record->id,
            'closed_by_user_id' => Auth::id(),
            'closed_at' => Carbon::now(),
        ]);

        // Continuing the chain may bring it to 9 participants -> award GlowdNine.
        $this->maybeAwardRelayGlowdNine($record, PostRelay::TYPE_CHALLENGE);
    }
    private function validateRelayChallengePlayers(Request $request): void
    {
        $playerIds = collect($request->input('to_users', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if (!$playerIds->contains(Auth::id())) {
            throw ValidationException::withMessages([
                'to_users' => 'リレーから作成する場合、自分をプレイヤーに含めてください。',
            ]);
        }
    }
    public function challenge_relay_pass(Request $request)
    {
        $request->validate([
            'relay_id' => 'required|integer|exists:post_relays,id',
        ]);

        $relay = PostRelay::where('id', $request->relay_id)
            ->where('relay_type', PostRelay::TYPE_CHALLENGE)
            ->where('to_user_id', Auth::id())
            ->where('status', PostRelay::STATUS_PENDING)
            ->firstOrFail();

        $relay->update([
            'status' => PostRelay::STATUS_DECLINED,
            'declined_by_user_id' => Auth::id(),
            'declined_at' => Carbon::now(),
            'accepted_post_id' => null,
            'closed_by_user_id' => null,
            'closed_at' => null,
        ]);
        $this->badgeService->invalidateBadgeSummaryCache();

        return response()->json($relay);
    }
    public function challenge_relay_reassign(Request $request)
    {
        $request->validate([
            'relay_id' => 'required|integer|exists:post_relays,id',
            'to_user_id' => 'required|integer|exists:users,id',
        ]);

        $relay = PostRelay::where('id', $request->relay_id)
            ->where('relay_type', PostRelay::TYPE_CHALLENGE)
            ->where('from_user_id', Auth::id())
            ->where('status', PostRelay::STATUS_DECLINED)
            ->whereNull('closed_at')
            ->firstOrFail();

        $toUserId = (int) $request->to_user_id;
        if ($toUserId === Auth::id() || $toUserId === (int) $relay->declined_by_user_id) {
            throw ValidationException::withMessages([
                'to_user_id' => '別のメンバーを選択してください。',
            ]);
        }
        $this->ensureEligibleChallengeRelayTarget($toUserId);
        $this->ensureChallengeRelayTargetHasNoHistory(
            $relay->source_post_id,
            Auth::id(),
            $toUserId,
            'to_user_id'
        );

        $now = Carbon::now();
        $relay->update([
            'closed_by_user_id' => Auth::id(),
            'closed_at' => $now,
        ]);

        $newRelay = PostRelay::updateOrCreate(
            [
                'relay_type' => PostRelay::TYPE_CHALLENGE,
                'source_post_id' => $relay->source_post_id,
                'from_user_id' => Auth::id(),
                'to_user_id' => $toUserId,
            ],
            [
                'declined_by_user_id' => null,
                'status' => PostRelay::STATUS_PENDING,
                'assigned_at' => $now,
                'deadline_at' => $now->copy()->addWeek(),
                'declined_at' => null,
                'accepted_post_id' => null,
                'closed_by_user_id' => null,
                'closed_at' => null,
            ]
        );
        $newRelay->loadMissing(['fromUser', 'toUser']);
        $this->badgeService->invalidateBadgeSummaryCache();

        return response()->json($newRelay);
    }
    public function challenge_relay_close(Request $request)
    {
        $request->validate([
            'relay_id' => 'required|integer|exists:post_relays,id',
        ]);

        $relay = PostRelay::where('id', $request->relay_id)
            ->where('relay_type', PostRelay::TYPE_CHALLENGE)
            ->where('from_user_id', Auth::id())
            ->where('status', PostRelay::STATUS_DECLINED)
            ->whereNull('closed_at')
            ->firstOrFail();

        $relay->update([
            'status' => PostRelay::STATUS_CLOSED,
            'closed_by_user_id' => Auth::id(),
            'closed_at' => Carbon::now(),
        ]);
        $this->badgeService->invalidateBadgeSummaryCache();

        return response()->json($relay);
    }
    public function nice_follow_up_dismiss(Request $request)
    {
        $request->validate([
            'post_id' => 'required|integer|exists:post_records,id',
        ]);

        if (in_array(Auth::id(), PostRelay::EXCLUDED_USER_IDS, true)) {
            return response()->json();
        }

        $post = PostRecord::where('id', $request->post_id)
            ->where('app_type', 0)
            ->where('user_id', '!=', Auth::id())
            ->whereHas('to_users', function ($query) {
                $query->where('users.id', Auth::id());
            })
            ->firstOrFail();

        PostRelay::updateOrCreate(
            [
                'relay_type' => PostRelay::TYPE_NICE,
                'source_post_id' => $post->id,
                'from_user_id' => $post->user_id,
                'to_user_id' => Auth::id(),
            ],
            [
                'status' => PostRelay::STATUS_CLOSED,
                'assigned_at' => $post->created_at,
                'deadline_at' => Carbon::parse($post->created_at)->addWeek(),
                'closed_by_user_id' => Auth::id(),
                'closed_at' => Carbon::now(),
            ]
        );
        $this->badgeService->invalidateBadgeSummaryCache();

        return response()->json();
    }
    private function createNiceRelaysForPost(PostRecord $post, array $toUserIds): void
    {
        $now = Carbon::now();
        collect($toUserIds)
            ->filter(fn ($id) => is_numeric($id)
                && (int) $id !== (int) $post->user_id
                && !in_array((int) $id, PostRelay::EXCLUDED_USER_IDS, true))
            ->unique()
            ->each(function ($userId) use ($post, $now) {
                PostRelay::firstOrCreate(
                    [
                        'relay_type' => PostRelay::TYPE_NICE,
                        'source_post_id' => $post->id,
                        'from_user_id' => $post->user_id,
                        'to_user_id' => (int) $userId,
                    ],
                    [
                        'status' => PostRelay::STATUS_PENDING,
                        'assigned_at' => $post->created_at ?? $now,
                        'deadline_at' => Carbon::parse($post->created_at ?? $now)->addWeek(),
                    ]
                );
            });
    }
    private function completePendingNiceRelaysForUser(PostRecord $post): void
    {
        $closedAt = $post->created_at ?? Carbon::now();
        $relays = PostRelay::where('relay_type', PostRelay::TYPE_NICE)
            ->where('to_user_id', Auth::id())
            ->where('status', PostRelay::STATUS_PENDING)
            ->where(function ($query) use ($post) {
                $query->whereNull('assigned_at')
                    ->orWhere('assigned_at', '<=', $post->created_at);
            })
            ->get();

        foreach ($relays as $relay) {
            // If accepting this relay makes the user the final participant, the chain is
            // complete. Complete it terminally (no accepted_post_id) so the new post is not
            // chained on and instead starts a fresh relay.
            $isChainComplete = $this->niceChainPostCount($relay->sourcePost) + 1 >= PostRelay::NICE_RELAY_LIMIT;

            $relay->update([
                'status' => PostRelay::STATUS_COMPLETED,
                'accepted_post_id' => $isChainComplete ? null : $post->id,
                'closed_by_user_id' => Auth::id(),
                'closed_at' => $closedAt,
            ]);

            $this->closePendingNiceRelaySiblings($relay, Auth::id(), $closedAt);
        }
    }
    private function closePendingNiceRelaySiblings(PostRelay $relay, int $closedByUserId, $closedAt): void
    {
        PostRelay::where('relay_type', PostRelay::TYPE_NICE)
            ->where('source_post_id', $relay->source_post_id)
            ->where('status', PostRelay::STATUS_PENDING)
            ->where('id', '!=', $relay->id)
            ->update([
                'status' => PostRelay::STATUS_CLOSED,
                'closed_by_user_id' => $closedByUserId,
                'closed_at' => $closedAt,
            ]);
    }
    private function maybeAwardRelayGlowdNine(PostRecord $post, string $relayType): void
    {
        // Participants who have already posted in this chain (root -> this post).
        $posterIds = $this->chainPosterIds($post, $relayType);

        // The people this newest post hands the baton to (the next participants).
        $pendingRecipientIds = PostRelay::where('relay_type', $relayType)
            ->where('source_post_id', $post->id)
            ->where('status', PostRelay::STATUS_PENDING)
            ->whereNotIn('to_user_id', PostRelay::EXCLUDED_USER_IDS)
            ->pluck('to_user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $participantIds = collect($posterIds)
            ->merge($pendingRecipientIds)
            ->unique()
            ->values();

        if ($participantIds->count() < PostRelay::NICE_RELAY_LIMIT) {
            return;
        }

        $rootPost = $this->relayRootPost($post, $relayType);

        // Every participant gets one GlowdNine play for this completed relay chain.
        $participantIds->each(function ($userId) use ($rootPost) {
            PostRelayPrize::firstOrCreate(
                [
                    'root_post_id' => (int) $rootPost->id,
                    'user_id' => (int) $userId,
                ],
                [
                    'prize' => 0,
                    'try_flag' => 0,
                    'source' => 'relay',
                ]
            );
        });

        $this->badgeService->invalidateBadgeSummaryCache();
    }
    private function awardChallengeSupportersGlowdNine(PostRecord $challenge): void
    {
        // When a challenge is achieved, everyone who charged it earns a GlowdNine play.
        $chargerIds = DB::table('post_awards')
            ->where('record_id', $challenge->id)
            ->distinct()
            ->pluck('user_id');

        $awarded = false;
        foreach ($chargerIds as $userId) {
            if (in_array((int) $userId, PostRelay::EXCLUDED_USER_IDS, true)) {
                continue;
            }

            PostRelayPrize::firstOrCreate(
                [
                    'root_post_id' => (int) $challenge->id,
                    'user_id' => (int) $userId,
                ],
                [
                    'prize' => 0,
                    'try_flag' => 0,
                    'source' => 'challenge_award',
                ]
            );
            $awarded = true;
        }

        if ($awarded) {
            $this->badgeService->invalidateBadgeSummaryCache();
        }
    }
    private function chainPosterIds(PostRecord $post, string $relayType): array
    {
        $ids = [];
        $visited = [];
        $current = $post->loadMissing('user');

        while ($current && !in_array((int) $current->id, $visited, true)) {
            $visited[] = (int) $current->id;

            if ($current->user && !in_array((int) $current->user->id, PostRelay::EXCLUDED_USER_IDS, true)) {
                $ids[] = (int) $current->user->id;
            }

            $incomingRelay = PostRelay::where('relay_type', $relayType)
                ->where('accepted_post_id', $current->id)
                ->orderBy('assigned_at')
                ->orderBy('id')
                ->first();

            if (!$incomingRelay || !$incomingRelay->source_post_id) {
                break;
            }

            $current = PostRecord::with('user')->find($incomingRelay->source_post_id);
        }

        return array_values(array_unique($ids));
    }
    public function save_relay_prize(Request $request)
    {
        $request->validate([
            'root_post_id' => 'required|integer|exists:post_records,id',
        ]);

        $params = $request->input('params', []);
        $prize = (int) ($params['prize'] ?? 0);
        if (!in_array($prize, PostRelay::GLOWD_NINE_PRIZES, true)) {
            $prize = 0;
        }

        // RollDice calls this on every roll (double-up rounds overwrite the previous prize),
        // so we must not gate on try_flag here — the last save wins. The dashboard hides the
        // play once try_flag is 1, which prevents re-playing after the modal closes.
        $prizeRow = PostRelayPrize::where('root_post_id', $request->root_post_id)
            ->where('user_id', Auth::id())
            ->first();

        if ($prizeRow) {
            $prizeRow->update([
                'prize' => $prize,
                'try_flag' => 1,
            ]);
            $this->badgeService->invalidateBadgeSummaryCache();
        }

        return response()->json(['message' => 'データが保存されました。']);
    }
    public function post_get_all_possible_users(Request $request){
        $other_users = User::where('retire', 0)->where('deleted_flag', 0)->select('id', 'name', 'icon_path', 'icon_bg', 'icon_bg')->get();
        return response()->json($other_users); 
    }
    public function post_get_challenge_users(Request $request){
        $exclude = collect($request->input('exclude', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
        $other_users = $this->eligibleChallengeMemberQuery()
            ->whereNotIn('id', array_values(array_unique(array_merge($exclude, PostRelay::EXCLUDED_USER_IDS))))
            ->select('id', 'name', 'icon_path', 'icon_bg', 'icon_bg')
            ->get();
        return response()->json($other_users); 
    }
    private function eligibleChallengeMemberQuery()
    {
        return User::where('retire', 0)
            ->where('deleted_flag', 0)
            ->whereNotIn('id', PostRelay::EXCLUDED_USER_IDS)
            ->where(function ($query) {
                $query->where('position_id', '<=', 12)
                    ->orWhere('position_id', 16);
            });
    }
    private function ensureEligibleChallengeRelayTarget(int $userId): void
    {
        if (!$this->eligibleChallengeMemberQuery()->where('id', $userId)->exists()) {
            throw ValidationException::withMessages([
                'to_user_id' => '対象メンバーを選択してください。',
            ]);
        }
    }
    private function ensureChallengeRelayTargetHasNoHistory(int $sourcePostId, int $fromUserId, int $toUserId, string $field): void
    {
        $handledRelayExists = PostRelay::where('relay_type', PostRelay::TYPE_CHALLENGE)
            ->where('source_post_id', $sourcePostId)
            ->where('from_user_id', $fromUserId)
            ->where('to_user_id', $toUserId)
            ->where(function ($query) {
                $query->whereNotNull('declined_at')
                    ->orWhereNotNull('accepted_post_id')
                    ->orWhereIn('status', [
                        PostRelay::STATUS_DECLINED,
                        PostRelay::STATUS_CLOSED,
                        PostRelay::STATUS_COMPLETED,
                    ]);
            })
            ->exists();

        if ($handledRelayExists) {
            throw ValidationException::withMessages([
                $field => '別のメンバーを選択してください。',
            ]);
        }
    }
    public function post_get_post_users(Request $request){
        $other_users = User::where('retire', 0)->where('deleted_flag', 0)->where('id', '!=', Auth::id())->where('id', '>', 99)->select('id', 'name', 'icon_path', 'icon_bg', 'icon_bg')->get();
        return response()->json($other_users); 
    }
    public function update_post_badge(Request $request){
        $auth_user = Auth::user();
        if(!empty($auth_user)){
            $auth_user->user_last_record()->firstOrCreate()->touch();
            $this->badgeService->forgetBadgeSummaryForUser($auth_user);
            
            return [
                'created' => 0,
                'changed' => 0,
                'changed_ids' => [],
                'changed_items' => [],
                'progress_report' => 0,
                'progress_report_ids' => [],
                'progress_report_items' => [],
                'last_chargeable' => 0,
                'last_chargeable_ids' => [],
                'last_chargeable_items' => [],
            ];       
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
