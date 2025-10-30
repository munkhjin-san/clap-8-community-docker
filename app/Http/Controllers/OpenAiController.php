<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\StreamedEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use OpenAI;
use Generator;
class OpenAiController extends Controller
{
    public function prepare(Request $request)
    {
        $validated = $request->validate([
            'text'        => 'required|string',
        ]); 

        $id = (string) Str::uuid();
        Cache::put("ai_correction:$id", $validated, now()->addMinutes(5));

        return response()->json(['id' => $id]);
    }
    public function stream_prompt(Request $request){
        $query = $request->query(); 
        $id = $query['request_id'] ?? null;
        abort_if(!$id, 400, '入力データが見つかりません。');
        
        $input = Cache::pull("ai_correction:$id");        

        abort_if(!$input, 410, 'セッションの有効期限が切れています。');

        $package = [
            'input' => $input['text'] ?? '',
            'prompt' => [
                'id' => '',
                'variables' => []
            ],
        ];

        abort_if(!$package['input'], 400, '入力テキストが見つかりません。');

        Arr::forget($query, 'request_id');

        foreach($query as $key => $value){
            if($key === 'config_key'){
                $configKey = $value;
                $configKey = "services.openai.prompts." . $configKey;
                $promptId = config($configKey);
                abort_if(!$promptId, 400, '無効なconfig_keyです。');

                $package['prompt']['id'] = $promptId;
            } else {                
                $package['prompt']['variables'][$key] = $value;
            }
        }
        
        if(empty($package['prompt']['variables'])){
            Arr::forget($package['prompt'], 'variables');
        }
        $apiKey = config('services.openai.api_key');
        if(!$apiKey){
            abort(500, 'OpenAI APIキーが設定されていません。');
        }
        

        $headers = [
            'Content-Type'       => 'text/plain; charset=utf-8',
            'Cache-Control'      => 'no-cache, no-transform',
            'X-Accel-Buffering'  => 'no',             // avoid Nginx buffering
            'Content-Encoding'   => 'none',           // dodge proxy gzip buffering
        ];
        
        $client = OpenAI::client($apiKey);
        return response()->eventStream(function () use ($client, $package, $headers) {
            $stream = $client->responses()->createStreamed($package);
            foreach ($stream as $event) {
                yield json_encode($event);
            }
        }, $headers);
        
    }
    public function non_stream_prompt(Request $request){

        $text = $request['message'] ?? '';
        abort_if(!$text, 400, 'Missing message');

        $apiKey = config('services.openai.api_key');
        $client = OpenAI::client($apiKey);
        $configKey = $request['config_key'] ?? '';
        if(!$configKey){
            abort(400, 'Missing config_key');
        }
        $configKey = "services.openai.prompts." . $configKey;
        $promptId = config($configKey);
        if(!$promptId){
            abort(400, 'Invalid config_key');
        }
        $response = $client->responses()->create([
            'input' => $text,
            'prompt' => [
                "id" => $promptId,
            ],
        ]);

        $reply = '';
        if($response->status !== 'completed') {
            $reply = '申し訳ございませんが現在、リクエストを処理できません。後でもう一度お試しください。';
        } else {
            foreach ($response->output as $output) {
                if(isset($output['role']) && $output['role'] === 'assistant') {
                    foreach ($output['content'] as $content) {
                        $reply .= $content['text'];
                    }
                }
                
            }
        }
        return response()->json($reply);
    }
}
