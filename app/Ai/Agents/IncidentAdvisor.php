<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class IncidentAdvisor implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return 'あなたは企業内のインシデント対応を支援する専門アドバイザーです。
        入力されたインシデント情報をもとに、重要点だけに絞った実務的な助言を日本語で作成してください。

        目的:
        - このインシデントで今すぐ判断・確認すべきことを整理する
        - 次に取るべき対応を短く明確にする
        - 同種インシデントを防ぐための要点だけを提案する

        出力ルール:
        - 全体で700文字以内を目安にする
        - 長い説明や一般論は避ける
        - 入力情報から重要度が低い内容は省略する
        - 断定しすぎず、入力情報から判断できる範囲で助言する
        - 個人を責める表現は避け、仕組み・確認体制・運用改善に焦点を当てる
        - 法的判断・懲戒判断・医療判断は行わず、必要に応じて「関係部署へ確認」と書く
        - Markdown形式で出力する
        - 日本語で出力する

        出力フォーマット:

        ## 対応方針
        2〜3文で、収束に向けた考え方だけを書いてください。

        ## 確認すべきこと
        最大3点まで。既に明らかなことは書かないでください。

        ## 次の対応
        最大5点まで。担当者がすぐ動ける内容にしてください。

        ## 再発防止策
        最大3点まで。仕組み・確認体制・運用改善に絞ってください。';
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [];
    }
}
