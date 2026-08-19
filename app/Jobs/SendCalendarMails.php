<?php

namespace App\Jobs;

use App\Mail\Calendar;
use App\Models\CalendarRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * 予定の作成・更新メール。レスポンスを返した後に送るので、
 * 宛先が多くても予定の保存そのものは待たされない。
 */
class SendCalendarMails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @param string $type メール件名の区分：作成 / 更新 / 確定 */
    public function __construct(
        public array $record_ids,
        public array $target_user_ids,
        public int $actor_id,
        public string $type
    ) {
        //
    }

    public function handle(): void
    {
        try {
            $emails = User::where('retire', 0)
                        ->whereNotNull('email')
                        ->whereIn('id', $this->target_user_ids)
                        ->where('id', '!=', $this->actor_id)
                        ->pluck('email')
                        ->toArray();

            $records = CalendarRecord::whereIn('id', $this->record_ids)->get();
            if (!$emails || $records->isEmpty()) {
                return;
            }

            $recursion_types = ['1回のみ', '毎週', '毎月', '毎年'];
            $details = [];
            $title = $records[0]['title'];
            $temp_flag = null;

            foreach ($records as $rec) {
                // 元の実装どおり、件名と (仮) 判定は最後のレコードのものが残る
                $title = $rec['temp_flag'] ? ' (仮)'.$rec['title'] : $rec['title'];
                $temp_flag = $rec['temp_flag'];
                $details[] = [
                    'title' => $rec['title'],
                    'id' => $rec['id'],
                    'start_at' => Carbon::parse($rec['date_start'])->format('Y/m/d H:i'),
                    'recursion' => $recursion_types[$rec['repetition_type']],
                    'content' => $rec['remarks'],
                ];
            }

            foreach ($emails as $to) {
                // 1件失敗しても残りは送る
                try {
                    Mail::to($to)->send(new Calendar($details, $title, $this->type, $temp_flag));
                } catch (\Throwable $e) {
                    Log::warning('SendCalendarMails: failed for one recipient', ['error' => $e->getMessage()]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('SendCalendarMails failed', ['error' => $e->getMessage()]);
        }
    }
}
