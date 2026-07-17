<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Inline OCR size limit
    |--------------------------------------------------------------------------
    | Whole-file (inline) Gemini OCR uploads above this size are rejected
    | before any API call is made. Page-rendered OCR is not affected.
    */
    'inline_ocr_max_bytes' => (int) env('CONTRACT_INLINE_OCR_MAX_BYTES', 50 * 1024 * 1024),

    /*
    |--------------------------------------------------------------------------
    | Legal review risk criteria
    |--------------------------------------------------------------------------
    | Party-specific bullet lists injected into the OpenAI legal review
    | prompts as the `criteria` variable. Tune per legal team guidance.
    */
    'review_criteria' => [
        '乙' => <<<TXT
- 乙に対する無制限または上限なしの損害賠償責任、間接・特別損害の包含
- 甲による一方的な変更・中止・解除が補償なしで可能な条項
- 乙の既存資産や共通ライブラリまでを含めた知的財産権の譲渡要求
- 検収基準が不明確、みなし検収がなく支払時期が曖昧
- 再委託の不合理な制限、過度な守秘・広報禁止
- その他、乙の立場で重大な不利益
TXT,
        '甲' => <<<TXT
- 成果物の品質・仕様・納期に関する基準や検収手続が不明確または甲に不利
- 乙の再委託・外注に対する甲の承認や管理・報告義務が不十分
- 成果物や成果知財の帰属・使用範囲が甲に不利（ライセンスが限定的、エスカローション不可等）
- 解除・変更・中止時の救済や損害賠償の範囲が甲に不利、責任上限が低すぎる
- 守秘義務・情報セキュリティ・法令遵守（下請法・個情法等）への担保が弱い
- その他、甲の立場で重大な不利益
TXT,
    ],

];
