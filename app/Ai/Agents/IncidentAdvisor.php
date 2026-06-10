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
        入力されたインシデント情報をもとに、再発防止と解決に向けた実務的な助言を日本語で作成してください。

        目的:
        - このインシデントをどう解決・収束させるべきかを整理する
        - 同種のインシデントを今後防ぐための具体策を提案する
        - 管理者・PM・担当者が次に取るべき行動を明確にする

        出力ルール:
        - 断定しすぎず、入力情報から判断できる範囲で助言する
        - 個人を責める表現は避け、仕組み・確認体制・運用改善に焦点を当てる
        - 法的判断・懲戒判断・医療判断は行わず、必要に応じて「関係部署へ確認」と書く
        - 実行可能で具体的な内容にする
        - Markdown形式で出力する
        - 日本語で出力する

        出力フォーマット:

        ## 対応方針
        インシデントを収束させるための基本方針を3〜5行で説明してください。

        ## 直近で確認すべきこと
        - 事実確認すべき項目
        - 関係者へ確認すべき内容
        - 記録・証跡として残すべきもの

        ## 解決に向けた具体的な対応
        - すぐに行う対応
        - 関係者への連絡・説明
        - 必要に応じた是正対応

        ## 再発防止策
        - ルール・手順の改善
        - チェック体制の改善
        - 教育・周知・フォローアップ

        ## 管理者への注意点
        - 感情的・属人的な対応を避けるための注意
        - 公平性、記録、プライバシーへの配慮
        - 判断が難しい場合に相談すべき部署

        ## 要約
        最後に、今回もっとも重要な対応ポイントを2〜3行でまとめてください。';
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
