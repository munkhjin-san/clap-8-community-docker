<?php

namespace App\Services;

use App\Models\boardToUser;
use App\Models\User;
use App\Models\customFieldDataRecord;
use App\Models\SupportMailFormRecord;
use Illuminate\Http\Request;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\TaskController;
use Carbon\Carbon;
class ReportService
{
    protected $boardController;
    protected $taskController;
    public function __construct(BoardController $boardController, TaskController $taskController)
    {
        $this->boardController = $boardController;
        $this->taskController = $taskController;
    }

    public function sendMessage($override_user_id, $board_id, $type)
    {
        $mention_users = boardToUser::where('record_id', $board_id)
                                    ->whereNot('user_id', $override_user_id)
                                    ->pluck('user_id')
                                    ->toArray();

        $override_user = User::select('id', 'name', 'icon_id')
                             ->findOrFail($override_user_id);
        
        $message = $this->generateMessage($type);
        $requestData = [
            'record_id' => $board_id,
            'override_user_id' => $override_user_id,
            'message' => $message,
            'override_user' => $override_user,
        ];
        $request = new Request($requestData);
        $chat = $this->boardController->chatAdd($request);
        
        return $chat;
    }
    public function checkRequest($chat, $override_user_id)
    {
        $chatMessage = $chat->original['data'];
        $mention_users = boardToUser::where('record_id', $chatMessage->record_id)
                                    ->whereNot('user_id', $override_user_id)
                                    ->pluck('user_id')
                                    ->toArray();
        $override_user = User::select('id', 'name', 'icon_id')->findOrFail($override_user_id);
        $checkData = [
            'type' => 'confirm',
            'users' => $mention_users,
            'msg_id' => $chatMessage->id,
            'override_user' => $override_user
        ];
        $check_request = new Request($checkData);
        $check_message = $this->boardController->checkRequest($check_request);

        return $check_message;
    }
    public function createTask($override_user_id, $board_id, $type){
        $mention_users = boardToUser::where('record_id', $board_id)->whereNot('user_id', 610)->pluck('user_id')->toArray();
        $override_user = User::select('id', 'name', 'icon_id')
                             ->findOrFail($override_user_id);
        $dateString = sprintf('%04d-%02d-%02d', date('Y'), date('m'), '20');
        $date = Carbon::parse($dateString);
        $closestDate = $this->closestWorkDay($date);
        $message = $this->generateMessage($type);
        $requestData = [
            'board_id' => $board_id,
            'qualified_users' => $mention_users,
            'remarks' => $message,
            'task_end_date' => $closestDate->format('Y-m-d'),
            'override_user' => $override_user
        ];
        $request = new Request($requestData);
        $task = $this->taskController->addTask($request);
        return $task;
    }
    public function generateMessage($type)
    {
        $message = '';

        switch ($type) {
            case 'incident':
                $message = $this->incident_message();
                break;
            case 'weekly':
                $message = $this->weekly_message();
                break;
            case 'monthly_3S':
                $message = $this->monthly_3s_message();
                break;
            case 'monthly_shift':
                $message = $this->monthly_shift_message();
                break;
            case 'monthly_performance':
                $message = $this->monthly_performance_message();
                break;
            case 'monthly_mailing':
                $message = $this->monthly_mailing_message();
                break;
        }

        return $message;
    }

    private function weekday_get($month, $day){
        $dateString = sprintf('%04d-%02d-%02d', date('Y'), $month, $day);
        $date = Carbon::parse($dateString);
        $closestDate = $this->closestWorkDay($date);
        Carbon::setLocale('ja');
        $weekday = $closestDate->isoFormat('ddd');
        $monthWithOutZero = $closestDate->format('n');
        $dayWithoutZero = $closestDate->format('j');
        return "{$monthWithOutZero}月{$dayWithoutZero}日($weekday)";
    }
    private function closestWorkDay($date) 
    {
        if($this->isWorkday($date)) {
            return $date;
        }

        $before = clone $date;
        $after = clone $date;

        while (true) {
            $before->subDay();
            if ($this->isWorkday($before)) {
                return $before;
            }

            $after->addDay();
            if ($this->isWorkday($after)) {
                return $after;
            }
        }
    }
    private function isWorkday($date)
    {
        $dayOfWeek = $date->dayOfWeek;
        return $dayOfWeek >= 1 && $dayOfWeek <= 5;
    }

    private function incident_message()
    {
        $yesterday = date("Y-m-d",strtotime('-1 day'));
        $incident_list = customFieldDataRecord::where('date', $yesterday)
                                                ->where('type_id', 40)
                                                ->where('value_int', 1)
                                                ->with('user')
                                                ->get();
        $support_result = SupportMailFormRecord::where('deleted_flag', 0)
                                                ->where('created_at', '>=', $yesterday)
                                                ->count();
        
        $incident_result = '';
        $is_first = true;
        foreach($incident_list as $incident){
            $date = htmlspecialchars($incident->date, ENT_QUOTES, 'UTF-8');
            $name = htmlspecialchars($incident->user['name'], ENT_QUOTES, 'UTF-8');
            if ($is_first) {
                $incident_result .= $date . '　氏名：' . $name . "\n";
                $is_first = false;
            } else {
                $incident_result .= '                             ' . $date . '　氏名：' . $name . "\n";
            }
        }
        if ($incident_result) {
            $incident_result = "・インシデント：" . $incident_result . "└上記対象社員へ内容をヒアリングしていただき、共有をお願い致します。";
        } else {
            $incident_result = "・インシデント：なし";
        }
        $message = <<<EOT
        [To:全員:]
        各位
        お疲れ様です。経営管理本部です。
        昨日までにインシデント有を選択されていた社員は下記の通りです。

        {$incident_result}
        ・サポートデスク：{$support_result}件
        EOT;

        return $message;
    }

    private function weekly_message()
    {
        $message = <<<EOT
        [To:全員:]
        各位
        お疲れ様です。経営管理本部です。
        
        担当部門において、以下の事項について変更または変更予定がある場合は、速やかに経営管理本部にご連絡いただきますようお願い申し上げます。
        また、変更の可能性がある場合は、事前に経営管理本部と情報共有をお願いいたします。
        
        【人員配置に関する事項】
        ・部門間での異動
        ・パートナー社員の追加・終了
        
        【人事考課に関する事項】
        ・成果目標確認（提出状況・進捗）
        ・半年管理対象者進捗確認
        
        【法務に関する事項】
        ・新規契約
        ・契約更新
        
        下記派遣更新者（派遣料金・就業場所等変更がないかご確認の程よろしくお願いいたします）
        https://docs.google.com/spreadsheets/d/1G6sn_MaaunSbrkEarEKBQbDdtG-WoxS5G8ZhWV4LKGI/edit#gid=116057480
        
        ・契約終了
        
        【会計に関する事項】
        ・仮払未精算
        ・未入金確認
        ・交際費稟議
        ・稟議未計上
        
        【事業戦略に関する事項】
        ・収支入力・更新
        ・タスクの期日確認
        ・戦略会議の宿題進捗
        
        【総務に関する事項】
        ・事務所移転
        ・車入替
        
        https://docs.google.com/spreadsheets/d/1upDP3a8e2TLgaWPPcFCoCxbAGtLvUJ5YZ_F4HVgJy4U/edit#gid=0
        
        ・物品移動
        ・kintoneアカウント追加・削除
        
        【インシデントに関する事項】
        ・インシデント報告・進捗各位
        EOT;

        return $message;
    }

    private function monthly_3s_message()
    {
        $month = date("n");
        $message = <<<EOT
        [To:全員:]
        お疲れ様です。経営管理本部 宇都宮です。

        {$month}月の3Sご回答をお願い致します。

        フルネーム・漢字・苗字と名前のスペースなしでの入力を宜しくお願いいたします。
        また、物品管理でのご自身の貸与品について変更がないか確認を必ずお願いいたします。

        個人単位➝全社員・パートナー社員必須　
        　　　　　営業所に常駐してない場合も必須になります。
        　　　　　現在の職場でのルールを把握して、3Sを運用出来ているかの
        　　　　　確認になります。
        　　　　　[3Sフォーム]https://forms.gle/E6BkpHZauLWoeBm4A

        3Sとは
        「会社のお片付け」「お掃除活動」などの意味合いももちろんありますが、きれいにすることだけが目的ではありません。
        3S活動の目的は、「安全」で「効率的」で「快適」な職場を作ることです。

        物品管理の確認方法
        「1.毎月の個別物品確認を徹底しており、常に管理情報が最新の状態である。」
        上記の項目は、物品管理アプリが常に最新の情報が入力されている状態にする為に、毎月個人で実施していただきます。
        https://docs.google.com/document/d/1n3_T-y5q43mpfulaZYHPlwF4mAMNeqjbWXLuuFK267g/edit?usp=sharing

        不明点等出てくるかと思いますが、毎月不明点・質問事項などは、下記URLまでお願い致します。
        https://docs.google.com/spreadsheets/d/1DtHU5xa5uEu2Q6Zhu6fHEbhoukTn7oNIXdU-A-53-7k/edit?usp=sharing
        EOT;

        return $message;
    }

    private function monthly_performance_message()
    {
        $month = date("n");
        $achieve = $this->weekday_get($month, '20');
        
        $message = <<<EOT
        [To:全員:]
        🔔お願い🔔
        翌月成果シート作成。
        
        各位
        お疲れ様です、経営管理本部です。
        
        成果シート
        {$achieve}が提出期日となっております。
        作成後、上長に提出をお願い致します。
        
        契約社員、正社員、執行役員すべて同じアプリに入力いただきます。
        人事考課時にメンターと共に設定した職務評価基準のレベルにふさわしい内容を目標としてください。
        
        成果目標の確認責任は各案件の執行役員（プロジェクトマネージャー）となります。
        ▶成果目標アプリ
        https://glowd-hldgs.cybozu.com/k/954
        
        ＊転籍社員の方には別途上長の方から、ご連絡をお願い致します。
        
        お忙しいとは思いますが、ご対応の程、宜しくお願い致します。
        EOT;

        return $message;
    }

    private function monthly_shift_message()
    {
        $month = date("n");
        $nextMonth = date("n", strtotime("+1 month"));
        $shift = $this->weekday_get($month, '25');
        $attendance = $this->weekday_get($nextMonth, '2');
        
        $message = <<<EOT
        [To:全員:]
        🔔お願い🔔
        翌月シフト、勤怠月締め、勤怠管理者変更について
        
        各位
        お疲れ様です、経営管理本部です。
        表題の件3点のお願いです。
        
        1.来月のシフト
        {$shift}が提出期日となっております。
        作成をお願い致します。
        
        2.勤怠月締め
        {$attendance}が月締めの期日となります。
        こちらも早めのご対応をお願い致します。
        
        3.勤怠承認者変更・マイグループ変更の方がいらっしゃいましたら、ご連絡お願い致します。
        
        ＊転籍社員の方には別途上長の方から、ご連絡をお願い致します。
        
        お忙しいとは思いますが、ご対応の程、宜しくお願い致します。
        EOT;

        return $message;
    }

    private function monthly_mailing_message()
    {
        $nextMonth = date("n", strtotime("+1 month"));
        $record = $this->weekday_get($nextMonth, '2');
        $mail = $this->weekday_get($nextMonth, '9');
        $message = <<<EOT
        [To:全員:]
        🔔立替経費・仮払　領収証原本の郵送につきまして🔔
        お疲れ様です。経営管理本部　平川です。

        {$record}までに、レコードの作成、執行役員承認済みのものが{$nextMonth}月精算となります。

        領収書は{$mail}までに本社必着で郵送お願いいたします。
        EOT;

        return $message;
    }
}