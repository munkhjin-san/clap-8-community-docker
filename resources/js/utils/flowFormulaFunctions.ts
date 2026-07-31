// Formula functions supported by the backend evaluator (app/Services/FlowFormulaEvaluator.php).
// Used by the formula editor for autocomplete + signature hints. Keep in sync with callFunction()
// AND with supportedFunctions(), which KintoneFormulaConverter checks when importing an app.
//
// Not listed here because it is an operator, not a function: `&` joins text — [姓] & " " & [名].
// `+` stays arithmetic on purpose (an empty field is non-numeric, so an overloaded + would turn
// [数量] + [単価] into text the moment one was blank).

export interface FormulaFunc {
    name: string
    signature: string
    description: string
}

export const FLOW_FORMULA_FUNCTIONS: FormulaFunc[] = [
    { name: 'IF', signature: 'IF(条件, 真のときの値, 偽のときの値)', description: '条件が真なら2番目、偽なら3番目の値を返します。' },
    { name: 'AND', signature: 'AND(値1, 値2, …)', description: 'すべてが真のときに真を返します。' },
    { name: 'OR', signature: 'OR(値1, 値2, …)', description: 'いずれかが真のときに真を返します。' },
    { name: 'NOT', signature: 'NOT(値)', description: '真偽を反転します。' },
    { name: 'CONTAINS', signature: 'CONTAINS(文字列, 部分文字列)', description: '文字列に部分文字列が含まれるかを判定します。' },
    { name: 'SUM', signature: 'SUM(値1, 値2, …)', description: '合計を返します。' },
    { name: 'ROUND', signature: 'ROUND(数値, 桁数)', description: '指定した桁で四捨五入します。' },
    { name: 'ROUNDUP', signature: 'ROUNDUP(数値, 桁数)', description: '指定した桁で切り上げます。' },
    { name: 'ROUNDDOWN', signature: 'ROUNDDOWN(数値, 桁数)', description: '指定した桁で切り捨てます。' },
    { name: 'CEILING', signature: 'CEILING(数値, 基準値)', description: '基準値の倍数に切り上げます。' },
    { name: 'FLOOR', signature: 'FLOOR(数値, 基準値)', description: '基準値の倍数に切り捨てます。' },
    { name: 'ABS', signature: 'ABS(数値)', description: '絶対値を返します。' },
    { name: 'MIN', signature: 'MIN(値1, 値2, …)', description: '最小値を返します。' },
    { name: 'MAX', signature: 'MAX(値1, 値2, …)', description: '最大値を返します。' },

    // 文字列
    { name: 'LEN', signature: 'LEN(文字列)', description: '文字数を返します。' },
    { name: 'LEFT', signature: 'LEFT(文字列, 文字数)', description: '先頭から指定した文字数を取り出します。' },
    { name: 'RIGHT', signature: 'RIGHT(文字列, 文字数)', description: '末尾から指定した文字数を取り出します。' },
    { name: 'MID', signature: 'MID(文字列, 開始位置, 文字数)', description: '開始位置（1文字目＝1）から指定した文字数を取り出します。' },
    { name: 'TRIM', signature: 'TRIM(文字列)', description: '前後の空白を削除し、間の連続した空白を1つにまとめます（全角スペースも対象）。' },
    { name: 'REMOVESPACE', signature: 'REMOVESPACE(文字列)', description: '空白をすべて削除します（半角・全角スペース、タブ、改行）。前後だけ整えたい場合は TRIM を使います。' },
    { name: 'UPPER', signature: 'UPPER(文字列)', description: '英字を大文字にします。' },
    { name: 'LOWER', signature: 'LOWER(文字列)', description: '英字を小文字にします。' },
    { name: 'REPLACE', signature: 'REPLACE(文字列, 探す文字列, 置き換える文字列)', description: '文字列を探して置き換えます。' },
    { name: 'JOIN', signature: 'JOIN(値, 区切り文字)', description: '複数の値（ユーザー・チェックボックス・テーブルの列など）を区切り文字でつなげます。省略時は「, 」。' },

    // 件数
    { name: 'COUNT', signature: 'COUNT(値1, 値2, …)', description: '数値の個数を返します。テーブルの列を渡すと行数を数えられます。' },
    { name: 'COUNTA', signature: 'COUNTA(値1, 値2, …)', description: '空でない値の個数を返します。' },

    // 日付
    { name: 'TODAY', signature: 'TODAY()', description: '今日の日付を返します。' },
    { name: 'YEAR', signature: 'YEAR(日付)', description: '年を返します。' },
    { name: 'MONTH', signature: 'MONTH(日付)', description: '月を返します。' },
    { name: 'DAY', signature: 'DAY(日付)', description: '日を返します。' },
    { name: 'DATEDIF', signature: 'DATEDIF(開始日, 終了日, 単位)', description: '開始日から終了日までの差を返します。単位は "D"（日・既定）/"M"（月）/"Y"（年）。終了日が前なら負の数になります。' },
]

export const FLOW_FORMULA_FUNCTION_NAMES: string[] = FLOW_FORMULA_FUNCTIONS.map((f) => f.name)
