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

        $instruction = <<<EOD
            今日は「〇〇の日」です。  
            この記念日をテーマに、その背景や文化的意味から連想される  
            “ちょっと哲学的で、少しだけ前向きになれる”ような短い一文を丁寧語で出力してください。  
            1文のみ（句点1つ）、90〜110文字程度を目安に、やさしい口調でシンプルに言い切ってください。  
            文末の結び方は、下記の4タイプから1つを日替わりで使用してください。
            ## 🎯 文末の結び方タイプ（4分類）

            | タグ      | 割合  | 目的          | 文末の例文                |
            | ------- | --- | ----------- | -------------------- |
            | ❓ 問いかけ  | 20% | 余韻・思考を促す    | 〜なのかもしれませんね。／〜でしょうか？ |
            | 💡 提案   | 20% | 軽い行動の後押し    | 〜してみてはいかがでしょうか。      |
            | ✅ 言い切り  | 30% | 安定感・説得力     | 〜なのです。／〜にすぎません。      |
            | 😄 ユーモア | 30% | 親しみ・軽さ・ニヤリ感 | 〜ってことにしておきましょうか。     |

            ※ユーモアタイプは“寒すぎない・やりすぎない”ラインで調整すること
            例：「今日くらいはそれでいいと思いませんか。」
            「気にせず乗り切って、あとで考えましょうか。」

            ---

            ## 🔁 出力サンプル（4タイプ）

            **今日は「パンツの日」**
            人に見えない部分を整えることが、自分への信頼につながるのかもしれませんね。

            **今日は「七夕」**
            願いごとを言葉にするだけで、未来に向けた一歩になることもあるのです。

            **今日は「海苔の日」**
            おにぎりに巻くだけで評価されるなら、自分もそれくらいでいい日があっていいですよね。

            **今日は「歯ブラシ交換デー」**
            そろそろ交換してみると、心も口もスッキリするかもしれません。


        EOD;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->post('https://api.openai.com/v1/responses', [
            'model' => 'gpt-4.1',
            'instructions' => $instruction,
            'input' => $date,
        ]);
        Log::info('Welcome message request sent', [
            'date' => $date,
            'response_status' => $response->status(),
            'error' => $response->failed() ? $response->body() : null,
        ]);
        $data = $response->json();
        // dd($data);
        $text = data_get($data, 'output.0.content.0.text', '');
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
}
