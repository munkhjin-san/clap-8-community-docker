<?php

namespace App\Http\Controllers;
use App\Models\positionRecord;
use App\Models\User;
use App\Models\SalaryIssue;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Auth;
use Illuminate\Support\Facades\File; 
use Illuminate\Support\Facades\Log;
class MemberController extends Controller
{
    public function reset_charge(){
        $today = Carbon::today();

        if ($today->day == 10 && in_array($today->month, [3, 6, 9, 12])) {
            echo "Yay, lets reset";
        
            $filePath = storage_path('logs/Chargereset.log');

            if (!File::exists($filePath)) {
                File::put($filePath, '');
            }
            $list = $this->fetch_members(true);
            $blocks = collect($list);
            $charge_amounts = array(
                "G" => 12000,
                "F" => 12000,
                "E" => 9000,
                "D" => 9000,
                "C" => 6000,
                "B" => 6000,
                "A" => 3000,
                "一般職" => 3000
            );
           
            foreach($blocks as $block){
                if($block['id'] <= 5){
                    $members = $block['employees'];
                    foreach($members as $member){
                        $user = User::find($member['id']);
                        $user->timestamps = false; 
                        $user->update(['award_charge' => 15000]);
                        $user->timestamp = true;
                        
                        Log::channel('custom')->info("{$user->id}",['name' => $user->name, 'amount' => 15000]);
                    }
                }else if($block['level']){
                    $members = $block['employees'];
                    foreach($members as $member){
                        $user = User::find($member['id']);
                        $user->timestamps = false; 
                        $user->update(['award_charge' => $charge_amounts[$block['level']]]);
                        $user->timestamp = true;
                        Log::channel('custom')->info("{$user->id}",['name' => $user->name, 'amount' => (int)$charge_amounts[$block['level']]]);
                    }
                }
            }        
        } else {
            echo "Today is not reset day";
        }
    }
    private function fetch_members($sort){

        $today = Carbon::now()->format('Y-m-d');
        $list = positionRecord::where('deleted_flag', 0)
        ->with([
            'employees' => function ($q) use ($today) {
                $q->where('hide_flag', 0)
                    ->where('partner_flag', 0)
                    ->with([
                        'positions' => function ($q) {
                            $q->where('deleted_flag', 0);
                        },
                        'offices',
                        'today_weather'
                    ])
                    ->select('id', 'name', 'name_kana', 'motto', 'icon_id', 'office_id', 'position_id', 'phone_number', 'work_email', 'user_code');
            }
        ])
        ->orderBy('sort_flag', 'asc')
        ->get();   



        if(!$sort){
            return $list;
        }else{
            $allEmployees = collect($list)->pluck('employees')->flatten()->filter(function ($employee) {
                return $employee->user_code != null;
            })->pluck('user_code')->toArray();
    
            $strings = array_map('strval', $allEmployees);
            $result = '(' . implode(', ', $strings) . ')';
            $queryParams = [
                'app' => '9',
                "query" => "社員コード in $result limit 200",
                'fields' => ['$id', '社員コード', '氏名', '文字列__1行__15']
            ];
            
            $queryString = http_build_query($queryParams);
            $url = 'https://glowd-hldgs.cybozu.com/k/v1/records.json?' . $queryString;
            $headers = [
                'Authorization' => 'Basic', 
                'X-Cybozu-API-Token' => 'BH1geaWExPVVIaa48izBjDzCilqRslkNlcZgNvp4'
            ];
            $response = Http::withHeaders($headers)->get($url);
            $responseContent = $response->body();
            $responseData = $response->json();
            $pre = [];
            $pr = [];
            $sks = array(
                array(
                    "id" => 61,
                    "name" => "職階G",
                    "level" => "G",
                    "employees" => array(),
                ), 
                array(
                    "id" => 62,
                    "name" => "職階F",
                    "level" => "F",
                    "employees" => array(),
                ), 
                array(
                    "id" => 63,
                    "name" => "職階E",
                    "level" => "E",
                    "employees" => array(),
                ), 
                array(
                    "id" => 64,
                    "name" => "職階D",
                    "level" => "D",
                    "employees" => array(),
                ), 
                array(
                    "id" => 65,
                    "name" => "職階C",
                    "level" => "C",
                    "employees" => array(),
                ), 
                array(
                    "id" => 66,
                    "name" => "職階B",
                    "level" => "B",
                    "employees" => array(),
                ), 
                array(
                    "id" => 67,
                    "name" => "職階A",
                    "level" => "A",
                    "employees" => array(),
                ), 
                array(
                    "id" => 68,
                    "name" => "一般職",
                    "level" => "一般職",
                    "employees" => array(),
                ), 
                array(
                    "id" => 68,
                    "name" => "未分類",
                    "level" => "",
                    "employees" => array(),
                ), 
            );
            $collection = collect($sks);
            $s_list = $collection->map(function($sk) use ($responseData) {
                $rs = collect($responseData['records']);
                $filteredCollection = $rs->where('文字列__1行__15.value', '=', $sk['level']);
                $filteredArray = $filteredCollection->values()->pluck('社員コード.value')->map(function ($item) {
                    return (int) $item;
                })->toArray();
                $emp = User::where('hide_flag', 0)
                ->where('position_id', '>', 5)
                ->where('partner_flag', 0)
                ->whereIn('user_code', $filteredArray)
                ->with([
                    'positions' => function ($q) {
                        $q->where('deleted_flag', 0);
                    },
                    'offices',
                    'today_weather'
                ])
                ->select('id', 'name', 'name_kana', 'motto', 'icon_id', 'office_id', 'position_id', 'phone_number', 'work_email', 'user_code')->get();
                $sk['employees'] = $emp;
                $sk['user_codes'] = $filteredArray;
                return $sk;
            })->values()->toArray();

            $officers = positionRecord::where('deleted_flag', 0)
            ->where('id', '<=', 5)
            ->with([
                'employees' => function ($q) use ($today) {
                    $q->where('hide_flag', 0)
                        ->where('partner_flag', 0)
                        ->with([
                            'positions' => function ($q) {
                                $q->where('deleted_flag', 0);
                            },
                            'offices',
                            'today_weather'
                        ])
                        ->select('id', 'name', 'name_kana', 'motto', 'icon_id', 'office_id', 'position_id', 'phone_number', 'work_email', 'user_code');
                }
            ])
            ->orderBy('sort_flag', 'asc')
            ->get()->values()->toArray(); 
            $merged = array_merge($officers, $s_list);
            return $merged;
        }

    }
    public function get_members_list(Request $request){

        $sort = $request->byShokkai;
        $list = $this->fetch_members($sort);
        return response()->json($list);      
    }
    public function get_kadai_list(Request $request){

        $template_header = "社員の昇給課題を設定しようとしています。 
        下記昇給課題の内容は、昇給課題の設定ルールにのっとり、昇給課題のテーマに凡そふさわしい内容になっていますか？
        一般常識をもって業務にあたれば達成可能な目標は除外します。課題達成することによって、得られる能力が具体的に記されていますか？
        判定結果と判定理由をわかりやすく教えてください。
        ふさわしくない場合は具体的に変更すべき箇所を明示してください。
        まず昇給課題のテーマは次の通りです。";

        $kadai_header = "昇給課題の内容は、次の通りです。";

        $theme_details = [
            
        "自己認識" => "自分自身を客観的に見つめ、自己の価値観、信念、感情、能力、興味関心、人生の目的などを正確に把握し、受容することによって、
        自己肯定感を持ち、自己価値を認め、尊重することです。
        自己理解を深めることは、自分自身に対する自信や誇りを高め、自分に対する愛着を育むことができます。
        これによって、自分自身を受け入れることができ、より健全な人間関係を築くことができます。",
        "ミッション・ビジョン・バリュー" => "ミッションは、組織の存在意義や事業目的を、ビジョンは将来の組織の姿を、バリューは重視する価値観を示すものです。
        これらの概念を正確に理解し、自分自身の使命や役割、チームや企業の使命や役割を理解し、十分な貢献をすることが、
        組織の事業戦略や戦術を正しく把握し、周囲に適切に働きかけるために重要です。",
        "CSR" => "経済的、社会的、環境的影響を考慮し行動することで、企業が社会的信頼性を確保し、環境保護、従業員の福利厚生、
        コンプライアンス強化、ブランド価値向上などの効果をもたらします。
        また、SDGsへの取り組み、ステークホルダーへの利益還元、企業、地域、社会への貢献意識を持つこともCSRの重要な要素です。",

        "セルフマネジメント" => "自己目標や組織の目標を明確にし、業務の優先順位や期限を把握し、自己管理能力を高めることによって、
        効率的に業務をこなし、自己成長を促進し、モチベーションを維持する能力のことです。
        自己啓発やタイムマネジメント、ストレス管理などの能力を身につけることで、仕事において優れた成果を上げることができます。",
        "ガバナンス" => "適切なルールや仕組みを策定し、遵守することで、信頼性、持続可能性、責任明確化、透明性、成果最大化を実現することを目的としています。
        具体的な手段としては、法令・社内規定・業務マニュアルの遵守、報連相の実施、率先した職場作り、健全な管理体制、公平な評価などがあります。",
        "ダイバーシティ＆インクルージョン" => "多様なバックグラウンドを持つ人々が互いを尊重し、受け入れることを促進します。
        その結果、異なる視点やアイデアが生まれ、グローバル市場で競争力を向上させることができ、働きやすい環境が確立され、人材の確保と定着の促進にもつながります。",

        "キャリア形成" => "自分自身のスキルや価値観、興味などを把握し、将来のキャリアについて明確なビジョンを持ち、そのために具体的な計画を立てることが重要です。
        また、問題解決能力、交渉能力、そしてその計画を実現するための能力が必要です。
        これらの能力を持つことで、よりスムーズにキャリアを形成し、自己成長や成功への道を切り開くことができます。",
        "リーダーシップ" => "リーダーシップは、組織やグループにおいて、方向性の確立、チームワークの促進、モチベーションの向上、問題解決の支援などの重要な役割を果たします。
        リーダーシップによって、メンバーが目標に向かって行動し、チームワークを構築し、問題を解決することができます。また、リーダーがメンバーを育成し、成長に貢献することもできます。",
        "イノベーション" => "イノベーションは、企業の競争力の向上や生産性の向上、社会の発展、人類の生活や仕事の改善につながります。新しいアイデアや技術を取り入れることで、
        価値を創造し、経済や社会を発展させることができます。イノベーションには、企業や社会をより良いものに変えるためのチャレンジ精神や、一般常識に捕らわれない多角的な発想が必要です。"
        ];

        // $date = Carbon::now();
        // $active_year = $date->year;
        // $active_month = $date->month;
        // return $active_month;
        $queryParams = [
            'app' => '928',
            "query" => '管理番号 like "'. $request->date . '" and 社員コード = ' . Auth::user()->user_code . 'and $id != 83',
            // "query" => "レコード番号 =" . $request->id,
            // 'fields' => ["レコード番号", "社員コード", "ステータス", "氏名", "管理番号", "日時"]
        ];
        
        $queryString = http_build_query($queryParams);
        $url = 'https://glowd-hldgs.cybozu.com/k/v1/records.json?' . $queryString;
        // $url = 'https://glowd-hldgs.cybozu.com/k/v1/records.json?app=928';

        $headers = [
            'Authorization' => 'Basic', // Example custom header
            'X-Cybozu-API-Token' => 'CZu7ui76ORFwrIwcjomN7yTwx7Y3mzusxG7lyroS'
        ];

        $response = Http::withHeaders($headers)->get($url);
        $responseContent = $response->body();
        $responseData = $response->json();
        $ready_texts = [];
        // return response()->json($responseData );
        foreach($responseData['records'] as $record){
            $kadais = $record['昇給課題'];
            foreach($kadais['value'] as $kadai){
                // return response()->json($kadai );
                $kadai_key = $kadai['value']['評価課題']['value'];
                $parts = preg_split('/[×&#8203;``oaicite:{"number":4,"invalid_reason":"Malformed citation 【】"}``&#8203;]/u', $kadai_key, -1, PREG_SPLIT_NO_EMPTY);
                $selected_theme = $parts[2];
                $theme_detail = $theme_details[$selected_theme];

                $kadai_content = $kadai['value']["昇給課題内容・詳細"]['value'];

                $kadai_reviev = $kadai['value']["ChatGPT添削結果"]['value'];

                $final_text = $template_header . $kadai_key . ':' . $theme_detail . $kadai_header . $kadai_content;

                $pop = [
                    "template_header" => $template_header,
                    "kadai_key" => $kadai_key,
                    "theme_detail" => $theme_detail,
                    "kadai_header" => $kadai_header,
                    "kadai_content" => $kadai_content,
                    "full" => $final_text,
                    "id" => $kadai["id"],
                    "name" => $record["氏名"]['value'],
                    "status" => $record["ステータス"]['value'],
                    "record_id" => $record['$id']['value'],
                    "review" => $kadai_reviev,
                    "review_active" => false,
                    "update_active" => false,
                    "can_update" => false
                ]; 
                $ready_texts[] = $pop;
                // return $final_text;
                // return response()->json($kadai );
            }
            // return response()->json($kadais );
        }

        
        return response()->json($ready_texts );
    }
    public function update_kadai(Request $request){


        
        $queryParams = [
            'app' => '928',
            "id" => $request->record_id,            
        ];
        
        $queryString = http_build_query($queryParams);
        $url = 'https://glowd-hldgs.cybozu.com/k/v1/record.json?' . $queryString;

        $headers = [
            'Authorization' => 'Basic', // Example custom header
            'X-Cybozu-API-Token' => 'CZu7ui76ORFwrIwcjomN7yTwx7Y3mzusxG7lyroS'
        ];

        $response = Http::withHeaders($headers)->get($url);
        $responseContent = $response->body();
        $responseData = $response->json();

        $kadais = $responseData['record']['昇給課題']['value'];
        $exists = [];
        foreach($kadais as $kadai){
            $ex_data = $kadai['value']['ChatGPT添削結果']['value'];
            if($kadai['id'] == $request->kadai_id){
                $ex_data = $request->review;
            }
            $prep = [
                "id" => $kadai['id'],
                "value" => [
                    "ChatGPT添削結果" => [
                        "value" => $ex_data
                    ]
                ]
            ];
            array_push($exists, $prep);
        }



        // return response()->json($exists);
        
       


        $data = [
            "app" => 928,
            "id" => $request->record_id,
            "record" => [
                "昇給課題" => [
                    "value" => $exists
                    
                ]
            ]
        ];
        // return response()->json($data);
        $url = 'https://glowd-hldgs.cybozu.com/k/v1/record.json';

        $headers = [
            'Authorization' => 'Basic', // Example custom header
            'X-Cybozu-API-Token' => 'CZu7ui76ORFwrIwcjomN7yTwx7Y3mzusxG7lyroS'
        ];
        $response = Http::withHeaders($headers)->put($url,$data);
        $responseContent = $response->body();
        $responseData = $response->json();
        return response()->json($responseData);
    }
    public function get_kadai_template(Request $request){
        $record = SalaryIssue::where('user_id', Auth::id())->where('date', $request->date)->get();
        return response()->json($record);
    }
    public function delete_kadai_template(Request $request){
        $record = SalaryIssue::where('id', $request->id)->where('user_id', Auth::id())->delete();
        return response()->json($record);
    }
    public function save_kadai_template(Request $request){
        if($request->editId){
            $record = SalaryIssue::findOrFail($request->editId);
            // if($record->content !== $request->content){
            //     throw ValidationException::withMessages(['message' => '昇給課題の内容に変更がある場合、ChatGPT添削結果削除']);
            // }
        }else{
            $record = new SalaryIssue;
            $check_exists = SalaryIssue::where('theme', $request->theme)->where('date', $request->date)->where('user_id', Auth::id())->exists();
            if($check_exists){
                throw ValidationException::withMessages(['message' => 'このテーマで既に昇給課題が作られています。']);
            }
        }
        
        $record->user_id = Auth::id();
        $record->project_goal_id = $request->goal_id;
        $record->title = $request->title;
        $record->theme = $request->theme;
        $record->date = $request->date;
        $record->content = $request->content;
        $record->review = $request->review;
        $record->ability = $request->ability;
        $record->status = $request->status;
        $record->save();
        return response()->json($record);
    }
    public function get_kadai_themes(){
        $list = $this->kadai_themes(null);
        $after = [];
        foreach($list as $item){
            // return response()->json($item);
            $parts = preg_split('/[×&#8203;``oaicite:{"number":4,"invalid_reason":"Malformed citation 【】"}``&#8203;]/u', $item['文字列__1行_']['value'], -1, PREG_SPLIT_NO_EMPTY);
            
            $d = [
                "id" => (int)$item['$id']['value'],
                "level" => $parts[0],
                "theme" => $parts[1],
                "title" => $parts[2],
                "content" => $item['文字列__複数行_']['value'],
                "title_full" => $item['文字列__1行_']['value']
            ];
            array_push($after, $d);
            $groupedData = collect($after)->groupBy('level')->map(function ($group) {
                return [
                    'level' => $group->first()['level'],
                    'issues' => $group->map(function ($item) {                        
                        return $item;
                    })->values()->all(),
                ];
            })->values()->all();
        }
        return response()->json($groupedData);
    }
    private function kadai_themes($id){
        $themes_headers = [
            'Authorization' => 'Basic',
            'X-Cybozu-API-Token' => 'AeQBG9oyDw8G8weRjXZR7M7kk1wYTPB6cyqkUCp3'
        ];  
        
        if($id){
            $url = 'https://glowd-hldgs.cybozu.com/k/v1/record.json?app=964&id='.$id;
        }else{
            $url = 'https://glowd-hldgs.cybozu.com/k/v1/records.json?app=964';
        }
             
        $get_theme = Http::withHeaders($themes_headers)->get($url);
        $themes = $get_theme->json();

        if($id){
            if($themes['record']){
                return $themes['record'];
            }   
            return null;
        }else{
            if($themes['records']){
                return $themes['records'];
            }   
            return [];
        }
        
    }
    public function check_kadai_record(Request $request){

        

        $user_name = env('KINTONE_USER_NAME');
        $password = env('KINTONE_PASSWORD');
        $string = $user_name. ':'. $password;
        $x_token = base64_encode($string);
        $queryParams = [
            'app' => '928',
            "query" => '管理番号 like "'. $request->date . '" and 社員コード = ' . Auth::user()->user_code . 'and $id != 83',
        ];
        
        $queryString = http_build_query($queryParams);
        $multi_url = 'https://glowd-hldgs.cybozu.com/k/v1/records.json?' . $queryString;

        $headers = [
            'Authorization' => 'Basic',
            'X-Cybozu-Authorization' => $x_token
        ];

        


        $response = Http::withHeaders($headers)->get($multi_url);
        $responseContent = $response->body();
        $responseData = $response->json();
        
        if(array_key_exists('records', $responseData) && $responseData['records'] && count($responseData['records'])){
            if($responseData['records'][0]['ステータス']['value'] == '管理部提出'){
                throw ValidationException::withMessages(['message' => 'ステータスが<strong>'.$responseData['records'][0]['ステータス']['value'] . '</strong>のため申請ができません。']);
            }

            $kadais = $responseData['records'][0]['昇給課題']['value'];
            $record_id = $responseData['records'][0]['$id']['value']; 
            



            $template = SalaryIssue::find($request->record_id);
          
            $queryParams = [
                'app' => '928',
                "id" => $record_id,            
            ];            
            $queryString = http_build_query($queryParams);
            $url = 'https://glowd-hldgs.cybozu.com/k/v1/record.json?' . $queryString;
    
            $response = Http::withHeaders($headers)->get($url);
            $responseContent = $response->body();
            $responseData = $response->json();
    
            $kadais = $responseData['record']['昇給課題']['value'];
            $exists = [];
           
            $existing_themes = [];
            foreach($kadais as $kadai){   
                $existing_themes[] = $kadai['value']['評価課題']['value'];
                $prep = [ "id" => $kadai['id']];
                array_push($exists, $prep);
            }       
            if (in_array($template['theme'], $existing_themes)) {
                throw ValidationException::withMessages(['message' => 'このテーマで既に昇給課題が作られています。<br>' . $template['theme']]);
            }
            $add = [
                'value' => [
                    'ChatGPT添削結果' => [ 'value' => $template->review ],
                    '昇給課題タイトル' => [ 'value' => $template->title ],
                    '昇給課題内容・詳細' => [ 'value' => $template->content ],
                    '課題達成による取得能力' => [ 'value' => $template->ability ],
                    '評価課題' => [ 'value' => $template->theme ],
                ]
            ];
            array_push($exists, $add);
            
            $data = [
                "app" => 928,
                "id" => $record_id,
                "record" => [
                    "昇給課題" => [
                        "value" => $exists
                        
                    ]
                ]
            ];
            $response = Http::withHeaders($headers)->put($url,$data);
            $responseData = $response->json();

            if (array_key_exists('revision', $responseData)) {

                $add_comment = $this->add_issue_comment($record_id, ': 昇給課題追加しました。');                
                $template->delete();
                
                // status_updade
                $status_url = 'https://glowd-hldgs.cybozu.com/k/v1/record/status.json';
                $status_data = [
                    'app' => '928',
                    "id"=> $record_id,
                    "action" => "CLAPにて作成済",
                ];    
                $status_update = Http::withHeaders($headers)->put($status_url, $status_data);
                $status_response = $status_update->json();
                // return response()->json($status_response);
                // status_update
                return response()->json('success');
            } else {
                throw ValidationException::withMessages(['message' => '昇給課題申請に失敗しました。']);
            }          
        }else if($responseData['message']){
            throw ValidationException::withMessages(['message' => $responseData['message']]);
        }
        throw ValidationException::withMessages(['message' => '人事考課レコードは作成されていません。']);
    }
    private function add_issue_comment($record_id, $message){
        $comment_url = 'https://glowd-hldgs.cybozu.com/k/v1/record/comment.json';
        $comment_data = [
            "app" => '928',
            "record" => $record_id,
            "comment" => [
                "text" => Auth::user()->name . $message . Carbon::now()->format('Y-m-d H:i:s')
            ]
        ];
        $user_name = env('KINTONE_USER_NAME');
        $password = env('KINTONE_PASSWORD');
        $string = $user_name. ':'. $password;
        $x_token = base64_encode($string);
        $headers = [
            'Authorization' => 'Basic', 
            'X-Cybozu-Authorization' => $x_token
        ];
        $add_comment = Http::withHeaders($headers)->post($comment_url,$comment_data);
        return $add_comment;        
    }
    public function get_applied_issues(Request $request){
        $queryParams = [
            'app' => '928',
            "query" => '管理番号 like "'. $request->date . '" and 社員コード = ' . Auth::user()->user_code . 'and $id != 83',
        ];
        
        $queryString = http_build_query($queryParams);
        $url = 'https://glowd-hldgs.cybozu.com/k/v1/records.json?' . $queryString;
        $user_name = env('KINTONE_USER_NAME');
        $password = env('KINTONE_PASSWORD');
        $string = $user_name. ':'. $password;
        $x_token = base64_encode($string);
        $headers = [
            'Authorization' => 'Basic', 
            'X-Cybozu-Authorization' => $x_token
        ];

        // $headers = [
        //     'Authorization' => 'Basic', 
        //     'X-Cybozu-API-Token' => 'CZu7ui76ORFwrIwcjomN7yTwx7Y3mzusxG7lyroS'
        // ];

        $response = Http::withHeaders($headers)->get($url);
        $responseData = $response->json();
        
        if(array_key_exists('records', $responseData) && $responseData['records'] && count($responseData['records'])){
            $record = $responseData['records'][0];
            $ready_texts = [];
            return response()->json($record );
        }
        
        // foreach($responseData['records'] as $record){

            // $kadais = $record['昇給課題'];
            // foreach($kadais['value'] as $kadai){

            //     $kadai_reviev = $kadai['value']["ChatGPT添削結果"]['value'];

               

            //     $pop = [
            //         "id" => $kadai["id"],
            //         "name" => $record["氏名"]['value'],
            //         "status" => $record["ステータス"]['value'],
            //         "record_id" => $record['$id']['value'],
            //         "review" => $kadai_reviev,
            //     ]; 
            //     $ready_texts[] = $pop;;
            // }
        // }

        
        
    }
    public function update_issue(Request $request){
        $validatedData = $request->validate([
            'record_id' => 'required',
            'kadai_id' => 'required'
        ]);
        $url = 'https://glowd-hldgs.cybozu.com/k/v1/record.json?app=928&id=' . $request->record_id;
        $record_id = $request->record_id;
        $user_name = env('KINTONE_USER_NAME');
        $password = env('KINTONE_PASSWORD');
        $string = $user_name. ':'. $password;
        $x_token = base64_encode($string);
        $headers = [
            'Authorization' => 'Basic', 
            'X-Cybozu-Authorization' => $x_token
        ];
        $response = Http::withHeaders($headers)->get($url);
        $responseData = $response->json();
        
        if(array_key_exists('record', $responseData)){
            
            $kadais = $responseData['record']['昇給課題']['value'];
            $exists = [];
        
            foreach($kadais as $kadai){   
                
                if($kadai['id'] == $request->kadai_id){
                    $update = [
                        "id" => $kadai['id'],
                        'value' => [
                            'ChatGPT添削結果' => [ 'value' => $request['review'] ],
                            '昇給課題タイトル' => [ 'value' => $request['title'] ],
                            '昇給課題内容・詳細' => [ 'value' => $request['content'] ],
                            '課題達成による取得能力' => [ 'value' => $request['ability'] ],
                            '評価課題' => [ 'value' => $request['theme'] ],
                        ]
                    ];
                    array_push($exists, $update);
                }else{
                    $prep = [ "id" => $kadai['id']];
                    array_push($exists, $prep);
                }
                

            }  
            // return response()->json($exists);
            $data = [
                "app" => 928,
                "id" => $request->record_id,
                "record" => [
                    "昇給課題" => [
                        "value" => $exists
                        
                    ]
                ]
            ];
            $update_url = 'https://glowd-hldgs.cybozu.com/k/v1/record.json';
            $response = Http::withHeaders($headers)->put($url,$data);
            $responseData = $response->json();
            if (array_key_exists('revision', $responseData)) {
                $add_comment = $this->add_issue_comment($record_id, ': 昇給課題更新しました。');  
                return response()->json($add_comment);
            } else {
                throw ValidationException::withMessages(['message' => '昇給課題削除に失敗しました。']);
            }   
        }
        throw ValidationException::withMessages(['message' => '人事考課レコードが見つかりませんでした。']);

    }
    public function delete_applied_issue(Request $request){
        $validatedData = $request->validate([
            'record_id' => 'required',
            'kadai_id' => 'required'
        ]);
        $url = 'https://glowd-hldgs.cybozu.com/k/v1/record.json?app=928&id=' . $request->record_id;
        $record_id = $request->record_id;
        $headers = [
            'Authorization' => 'Basic', 
            'X-Cybozu-API-Token' => 'CZu7ui76ORFwrIwcjomN7yTwx7Y3mzusxG7lyroS'
        ];
        $response = Http::withHeaders($headers)->get($url);
        $responseData = $response->json();
        if(array_key_exists('record', $responseData)){
            
            $kadais = $responseData['record']['昇給課題']['value'];
            $exists = [];
        
            foreach($kadais as $kadai){   
                $prep = [ "id" => $kadai['id']];
                if($kadai['id'] !== $request->kadai_id){
                    array_push($exists, $prep);
                }
                

            }  
            $data = [
                "app" => 928,
                "id" => $request->record_id,
                "record" => [
                    "昇給課題" => [
                        "value" => $exists
                        
                    ]
                ]
            ];
            $update_url = 'https://glowd-hldgs.cybozu.com/k/v1/record.json';
            $response = Http::withHeaders($headers)->put($url,$data);
            $responseData = $response->json();



            if (array_key_exists('revision', $responseData)) {
                $add_comment = $this->add_issue_comment($record_id, ': 昇給課題削除しました。');  
                return response()->json('success');
            } else {
                throw ValidationException::withMessages(['message' => '昇給課題削除に失敗しました。']);
            }   
        }
        throw ValidationException::withMessages(['message' => '人事考課レコードが見つかりませんでした。']);
         

        

    }

    public function get_performance_options(Request $request) {
        $queryParams = [
            'app' => '954',
        ];
        $queryString = http_build_query($queryParams);
        $url_app = 'https://glowd-hldgs.cybozu.com/k/v1/app/form/fields.json?' . $queryString;
        $user_name = env('KINTONE_USER_NAME');
        $password = env('KINTONE_PASSWORD');
        $string = $user_name. ':'. $password;
        $x_token = base64_encode($string);
        $headers = [
            'Authorization' => 'Basic', 
            'X-Cybozu-Authorization' => $x_token
        ];
        $response = Http::withHeaders($headers)->get($url_app);
        $responseData = $response->json();
        return response()->json($responseData['properties']['ドロップダウン']['options']);
        
        
    }
    public function get_performance_records(Request $request) {
        $queryParams = [
            'app' => '954',
            "query" =>  '社員ｺｰﾄﾞ = ' . Auth::user()->user_code . 'and ドロップダウン in ' . '("' . $request->date . '")',
        ];

        $queryString = http_build_query($queryParams);
        $url = 'https://glowd-hldgs.cybozu.com/k/v1/records.json?' . $queryString;
        $user_name = env('KINTONE_USER_NAME');
        $password = env('KINTONE_PASSWORD');
        $string = $user_name. ':'. $password;
        $x_token = base64_encode($string);
        $headers = [
            'Authorization' => 'Basic', 
            'X-Cybozu-Authorization' => $x_token
        ];
        $response = Http::withHeaders($headers)->get($url);
        
        $responseData = $response->json();
        if(array_key_exists('records', $responseData) && $responseData['records'] && count($responseData['records'])){
            $records = $responseData['records'];
            foreach($records as $record){
                if($record['社員ｺｰﾄﾞ']['value'] == Auth::user()->user_code){
                    return response()->json($record);
                }
            }
        }
    }

    public function get_job_evaluation(Request $request) {
        $queryParams = [
            'app' => '948',
            "query" => '文字列__1行__1 = "' . $request->level . '"'
        ];
        $queryString = http_build_query($queryParams);
        $url = 'https://glowd-hldgs.cybozu.com/k/v1/records.json?' . $queryString;
        $user_name = env('KINTONE_USER_NAME');
        $password = env('KINTONE_PASSWORD');
        $string = $user_name. ':'. $password;
        $x_token = base64_encode($string);
        $headers = [
            'Authorization' => 'Basic', 
            'X-Cybozu-Authorization' => $x_token
        ];
        $response = Http::withHeaders($headers)->get($url);
        
        $responseData = $response->json();
        return response()->json($responseData['records']);
    }
}
