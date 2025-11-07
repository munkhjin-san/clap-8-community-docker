<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use App\Models\WelcomeMessage;
use Log;
class GenerateWelcomeMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $apiKey = config('app.openai_api_key');
        $date = Carbon::now()->format('Y-m-d');
        $check_exists = WelcomeMessage::where('date', $date)->exists();
        if ($check_exists) {
            return;
        }
        $modes = ['影モード'];
        $mode = $modes[array_rand($modes)];
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->timeout(120)->post('https://api.openai.com/v1/responses', [
            'input' => $date,
            'prompt' => [
                "id" => "pmpt_68f19f5c26e8819388a5544744979f8f00729071049ead92",
                "variables" => [
                    "date" => $date,
                    "mode" => $mode
                ],
            ],
            'include' => [
                "web_search_call.action.sources"
            ],
            
        ]);
        // dd($response->json());
        Log::info('Welcome message request sent', [
            'date' => $date,
            'response_status' => $response->status(),
            'error' => $response->failed() ? $response->body() : null,
        ]);
        $data = $response->json();
        $text = $this->responseParser($data);
        WelcomeMessage::create([
            'date' => $date,
            'content' => $text,
            'chunks' => []
        ]);
        Log::info('Welcome message generated', [
            'date' => $date,
            'content' => $text,
        ]);
    }
    private function responseParser($response)
    {
        $message = '';
        foreach ($response['output'] as $output) {
            if(isset($output['role']) && $output['role'] === 'assistant') {
                foreach ($output['content'] as $content) {                    
                    $message = $content['text'];
                }
            }        
        }
       
        return $message;
    }
}
