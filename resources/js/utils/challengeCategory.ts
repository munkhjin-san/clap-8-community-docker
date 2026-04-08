export type ChallengeCategory = {
    label: string
    subcategories: string[]
}

export type ChallengeCategorySuggestion = {
    main: string
    sub: string
}

export type ChallengeSuggestionRule = ChallengeCategorySuggestion & {
    pattern: RegExp
}

export const challengeCategories: ChallengeCategory[] = [
    {
        label: '健康・フィットネス',
        subcategories: ['ダイエット / 減量', '筋トレ / 運動', '禁煙 / 禁酒', '生活習慣（睡眠・食事）']
    },
    {
        label: '学習・資格',
        subcategories: ['資格試験', '語学', '業務知識', 'IT / 技術学習']
    },
    {
        label: '仕事・業務',
        subcategories: ['プロジェクト完遂', '数値目標（KPI）', '新業務挑戦', '業務改善']
    },
    {
        label: '目標達成',
        subcategories: ['スキル到達（例：100kg）', '昇級・昇格', '技術習得', '個人成果']
    },
    {
        label: '習慣・継続',
        subcategories: ['日記', '読書', '毎日○○', '継続チャレンジ']
    },
    {
        label: 'イベント・挑戦',
        subcategories: ['大会 / 試合', '試験当日', '旅行 / 体験', '特定イベント']
    },
    {
        label: '趣味・遊び',
        subcategories: ['ゲーム', 'スポーツ（ゆる）', 'クリエイティブ', '娯楽']
    },
    {
        label: 'スキル・競技',
        subcategories: ['格闘技', '競技スポーツ', '専門技術', '実技スキル']
    },
    {
        label: '社会貢献',
        subcategories: ['寄付', 'チーム支援', 'CSR活動', '誰かのための挑戦']
    },
    {
        label: 'ネタ・自己挑戦',
        subcategories: ['無謀チャレンジ', '面白系', 'ノリ系', '自己追い込み']
    }
]

export const challengeSuggestionRules: ChallengeSuggestionRule[] = [
    { pattern: /(禁煙|禁酒|断酒|卒煙)/i, main: '健康・フィットネス', sub: '禁煙 / 禁酒' },
    { pattern: /(ダイエット|減量|痩せ|体脂肪)/i, main: '健康・フィットネス', sub: 'ダイエット / 減量' },
    { pattern: /(筋トレ|トレーニング|運動|ランニング|ジョギング|ウォーキング|マラソン)/i, main: '健康・フィットネス', sub: '筋トレ / 運動' },
    { pattern: /(睡眠|食事|生活習慣|早寝|早起き)/i, main: '健康・フィットネス', sub: '生活習慣（睡眠・食事）' },
    { pattern: /(資格|受験|検定|合格|試験勉強)/i, main: '学習・資格', sub: '資格試験' },
    { pattern: /(英語|toeic|ielts|語学|中国語|韓国語|english)/i, main: '学習・資格', sub: '語学' },
    { pattern: /(業務知識|会計|法務|営業知識|業界知識)/i, main: '学習・資格', sub: '業務知識' },
    { pattern: /(\bit\b|技術学習|プログラミング|開発|aws|sql|laravel|vue|typescript|php)/i, main: '学習・資格', sub: 'IT / 技術学習' },
    { pattern: /(プロジェクト|完遂|納品|リリース)/i, main: '仕事・業務', sub: 'プロジェクト完遂' },
    { pattern: /(kpi|売上|件数|目標数字|数値目標|cv|ctr|粗利)/i, main: '仕事・業務', sub: '数値目標（KPI）' },
    { pattern: /(新業務|新しい業務|初担当|初挑戦|新規担当)/i, main: '仕事・業務', sub: '新業務挑戦' },
    { pattern: /(業務改善|効率化|自動化|工数削減|改善)/i, main: '仕事・業務', sub: '業務改善' },
    { pattern: /(\d+\s?kg|100kg|自己ベスト|pb|記録更新)/i, main: '目標達成', sub: 'スキル到達（例：100kg）' },
    { pattern: /(昇級|昇格|昇段)/i, main: '目標達成', sub: '昇級・昇格' },
    { pattern: /(技術習得|習得|マスター|身につける)/i, main: '目標達成', sub: '技術習得' },
    { pattern: /(受注|表彰|優勝|完走|個人成果|達成率)/i, main: '目標達成', sub: '個人成果' },
    { pattern: /(日記)/i, main: '習慣・継続', sub: '日記' },
    { pattern: /(読書|読了|本を読む)/i, main: '習慣・継続', sub: '読書' },
    { pattern: /(毎日|毎週|習慣|継続|連続|streak)/i, main: '習慣・継続', sub: '継続チャレンジ' },
    { pattern: /(大会|試合|コンテスト|発表会)/i, main: 'イベント・挑戦', sub: '大会 / 試合' },
    { pattern: /(試験当日|受験当日|本番当日)/i, main: 'イベント・挑戦', sub: '試験当日' },
    { pattern: /(旅行|体験|遠征|waterfall|滝)/i, main: 'イベント・挑戦', sub: '旅行 / 体験' },
    { pattern: /(イベント|フェス|祭り|ライブ|展示会)/i, main: 'イベント・挑戦', sub: '特定イベント' },
    { pattern: /(ゲーム|gaming|apex|valorant|switch)/i, main: '趣味・遊び', sub: 'ゲーム' },
    { pattern: /(ゆるスポーツ|フットサル|キャッチボール|軽い運動)/i, main: '趣味・遊び', sub: 'スポーツ（ゆる）' },
    { pattern: /(イラスト|作曲|動画|写真|創作|クリエイティブ)/i, main: '趣味・遊び', sub: 'クリエイティブ' },
    { pattern: /(映画|アニメ|娯楽|鑑賞)/i, main: '趣味・遊び', sub: '娯楽' },
    { pattern: /(格闘技|柔道|空手|ボクシング|mma)/i, main: 'スキル・競技', sub: '格闘技' },
    { pattern: /(競技スポーツ|陸上|水泳|サッカー|野球|テニス)/i, main: 'スキル・競技', sub: '競技スポーツ' },
    { pattern: /(専門技術|設計|溶接|旋盤)/i, main: 'スキル・競技', sub: '専門技術' },
    { pattern: /(実技|演奏|プレゼン|手技|クラフト)/i, main: 'スキル・競技', sub: '実技スキル' },
    { pattern: /(寄付|募金|支援|ボランティア)/i, main: '社会貢献', sub: '寄付' },
    { pattern: /(csr)/i, main: '社会貢献', sub: 'CSR活動' },
    { pattern: /(チーム支援|応援|仲間のため)/i, main: '社会貢献', sub: 'チーム支援' },
    { pattern: /(無謀|ネタ|面白|ノリ|追い込み|罰ゲーム)/i, main: 'ネタ・自己挑戦', sub: '無謀チャレンジ' }
]
