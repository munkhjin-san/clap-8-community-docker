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
        $apiKey = config('app.gemini_api_key');

        $thisMonth = Carbon::now()->format('m');
        $thisMonthWithoutzero = ltrim($thisMonth, '0');
        $thisDay = Carbon::now()->format('d');
        $day = "{$thisMonthWithoutzero}月{$thisDay}日";
        if (empty($apiKey)) {
            return;
        }
        $check = WelcomeMessage::where('date', Carbon::now()->format('Y-m-d'))->exists();
        if ($check) {
            return;
        }
        $instruction = <<<EOD
            {$day}は日本国内もしくは国際で何の日ですか。ネットで検索し次のようにメッセージを作成してください。
            1個だけでいいです。もし結果が複数の場合ランダムで選択してください。
            最大150文字にまとめてください。
            そしてちょっとしたメッセージも付けてください。
            例1：今日は『〇〇の日』です。△△しましょう。
            フォーマットは：本日は『〇〇日』です。△△。
            NGな例：今日は『〇月〇日』です。
        EOD;
        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'text' => $instruction,
                        ],
                    ],
                ],
            ],
            "tools" => [
                [
                    "google_search" => (object)[]
                ]
            ],
            'generationConfig' => [
                'temperature' => 1,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'text/plain'
            ],
        ];
    
        // Send request
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$apiKey";
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $payload);

        
        $date = Carbon::now()->format('Y-m-d');
        $data = $response->json();
        $chunks = collect(data_get($data, 'candidates.0.groundingMetadata.groundingChunks'));
        $text = data_get($data, 'candidates.0.content.parts.0.text');
        if(empty($text)){
            return;
        }
        WelcomeMessage::create([
            'date' => $date,
            'content' => data_get($data, 'candidates.0.content.parts.0.text'),
            'chunks' => $chunks
        ]);
        dd($text);
    }
}
