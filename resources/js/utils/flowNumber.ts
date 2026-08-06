import type { FlowFieldValidation } from '@/types/flow'

/**
 * 数値の表示。桁区切り・小数桁・単位は項目ごとの設定に従う。
 *
 * これが要るのは、数値項目が2種類の意味で使われるから：金額のように桁区切りが読みやすいものと、
 * IDや年度のように区切ってはいけないもの。取引先ID「88745493」が「88,745,493」と出るのは後者で、
 * 桁区切りが「量」を示唆してしまう。
 *
 * 既定は桁区切りあり——今までの表示がそうだったので、設定していない既存の項目は見た目が変わらない。
 */
export interface FlowNumberFormat {
    /** 桁区切り（1,000）を入れるか。既定 true */
    thousand_separator?: boolean
    /** 小数点以下の桁数。null/未設定なら入力されたまま */
    decimals?: number | null
    /** 単位記号（円・人・% など） */
    unit?: string | null
    /** 単位を前に付けるか後ろに付けるか。既定 after */
    unit_position?: 'before' | 'after'
}

export const numberFormatOf = (validation?: FlowFieldValidation | null): FlowNumberFormat => ({
    thousand_separator: validation?.thousand_separator ?? true,
    decimals: validation?.decimals ?? null,
    unit: validation?.unit ?? null,
    unit_position: validation?.unit_position ?? 'after',
})

/**
 * 表示用の文字列にする。数として読めない値はそのまま返す（消さない）。
 */
export const formatFlowNumber = (value: any, validation?: FlowFieldValidation | null): string => {
    if (value === null || value === undefined || value === '') return ''

    const n = Number(value)
    if (!isFinite(n)) return String(value)

    const fmt = numberFormatOf(validation)
    const decimals = fmt.decimals

    let text = n.toLocaleString('ja-JP', {
        useGrouping: fmt.thousand_separator !== false,
        // 桁数の指定が無いときは、入っている小数をそのまま出す。既定の maximumFractionDigits(3) に
        // 任せると 0.00001 のような値が黙って丸められる。
        minimumFractionDigits: decimals ?? 0,
        maximumFractionDigits: decimals ?? 20,
    })

    const unit = (fmt.unit ?? '').trim()
    if (unit !== '') {
        text = fmt.unit_position === 'before' ? unit + text : text + unit
    }

    return text
}
