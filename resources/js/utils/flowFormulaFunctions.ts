// Formula functions supported by the backend evaluator (app/Services/FlowFormulaEvaluator.php).
// Used by the formula editor for autocomplete + signature hints. Keep in sync with callFunction().

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
]

export const FLOW_FORMULA_FUNCTION_NAMES: string[] = FLOW_FORMULA_FUNCTIONS.map((f) => f.name)
