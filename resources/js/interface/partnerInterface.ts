import { User } from './globalInterface'

/** 取引先に紐付くプロジェクト（一覧表示用の最小構成）。 */
export interface PartnerProjectRef {
    id: number
    name: string
}

export interface PartnerRecord {
    id: number
    name: string
    name_kana: string | null
    long_name: string | null
    /** 区分。corporate=法人 / individual=個人。 */
    entity_type: string | null
    /** 取引区分。client / partner / property_vehicle_parking / payable / other。 */
    transaction_category: string | null
    code: string | null
    corporate_number: string | null
    invoice_registration_number: string | null
    postal_code: string | null
    prefecture_code: number | null
    address_1: string | null
    address_2: string | null
    phone: string | null
    contact_name: string | null
    /** 担当者の役職。freeeに対応する項目が無いため同期しない。 */
    contact_position: string | null
    email: string | null
    website: string | null
    note: string | null
    isms_registration_number: string | null
    privacy_mark_number: string | null
    /** ヒアリング回答。設問キー => 真偽値（未回答のキーは持たない）。 */
    information_security_answers: Record<string, boolean> | null
    labor_contract_answers: Record<string, boolean> | null
    available: boolean
    /** freee会計の取引先ID。null なら未連携（有無がそのまま連携状態）。 */
    freee_partner_id: number | null
    freee_synced_at: string | null
    freee_update_date: string | null
    /** freee_partner_id の有無をサーバー側で真偽値にしたもの。 */
    freee_linked: boolean
    /** 前回同期時点のfreeeの値と比べて、こちらだけが進んでいるか。 */
    has_unsynced_changes: boolean
    created_by: number | null
    creator?: User | null
    projects?: PartnerProjectRef[]
    created_at: string | null
    updated_at: string | null
}

export type PartnerSort = 'name' | 'name_kana' | 'code' | 'updated_at' | 'created_at' | 'freee_partner_id'

export interface PartnerListResponse {
    partners: PartnerRecord[]
    meta: {
        page: number
        per_page: number
        total_count: number
        last_page: number
        has_more: boolean
        from: number
        to: number
        sort: PartnerSort
        direction: 'asc' | 'desc'
        sortable: PartnerSort[]
    }
}

/** 競合・差分の1項目。local と freee のどちらを残すかを人が選ぶための材料。 */
export interface PartnerFieldDifference {
    field: string
    label: string
    local: unknown
    freee: unknown
}

export type PartnerSyncResult =
    | 'linked'
    | 'created'
    | 'updated'
    | 'unchanged'
    | 'pulled'
    | 'conflict'

export interface PartnerSyncResponse {
    result: PartnerSyncResult
    message: string
    conflicts: PartnerFieldDifference[]
    partner?: PartnerRecord
}

export interface PartnerCheckResponse {
    exists: boolean
    message: string
    differences: PartnerFieldDifference[]
    partner?: PartnerRecord
}

/** 保存レスポンス。保存はDBのみで、freeeへの反映は含まない。 */
export interface PartnerSaveResponse {
    partner: PartnerRecord
    message: string
}

/** 保存する値はラベルではなく value。表示名を変えても既存データが壊れないようにするため。 */
export const ENTITY_TYPES: { value: string; label: string }[] = [
    { value: 'corporate', label: '法人' },
    { value: 'individual', label: '個人' },
]

export const TRANSACTION_CATEGORIES: { value: string; label: string }[] = [
    { value: 'client', label: 'クライアント' },
    { value: 'partner', label: 'パートナー企業' },
    { value: 'property_vehicle_parking', label: '物件_車両_駐車場' },
    { value: 'payable', label: '買掛先' },
    { value: 'other', label: 'その他' },
]

export interface PartnerQuestion {
    /** 保存キー。設問を並び替えても回答がずれないよう、添字ではなくこの値で持つ。変更しないこと。 */
    key: string
    text: string
}

/** 情報セキュリティに関するヒアリング項目。 */
export const INFO_SECURITY_QUESTIONS: PartnerQuestion[] = [
    { key: 'is_01', text: '情報セキュリティに関する基本的な考え方・方針を定めるとともに、遵守すべきセキュリティ水準についてガイドライン等で示している' },
    { key: 'is_02', text: '情報の取り扱いに関する規程等への違反に対し、懲戒等の処分に関する手続きを定めている' },
    { key: 'is_03', text: '情報セキュリティに関するインシデントが発生した際の検知・報告・通報の仕組みを整えており、社外公表やユーザー対応等、事故発生からクロージングまでの対応がルール化されている' },
    { key: 'is_04', text: '情報管理責任者が任命され、役割を責任が明確化されている' },
    { key: 'is_05', text: '業務に従事する全社員を対象として、情報セキュリティに関する研修を定期的に実施しており、実施のタイミングや内容についても必要に応じて見直しを行っている' },
    { key: 'is_06', text: '機密情報が記載された書類は関係者以外に振れることがないよう、施錠された場所へ保管している' },
    { key: 'is_07', text: '機密情報を社外へ持ち出す場合、持出記録を行っている' },
    { key: 'is_08', text: '不要になった情報の速やかな廃棄を行うためのルール・仕組がある' },
    { key: 'is_09', text: 'ファイルサーバー等に機密情報を保存する場合、パスワード設定等により暗号化している' },
    { key: 'is_10', text: '業務で利用する端末および外部記録媒体について、保有状況・利用（払い出し）状況を管理・記録している' },
    { key: 'is_11', text: '機密情報を保存する端末及び外部記録媒体は関係者以外が容易に持ち出すことのできないよう対策がなされている' },
]

/** 労働契約に関する質問。 */
export const LABOR_CONTRACT_QUESTIONS: PartnerQuestion[] = [
    { key: 'lc_01', text: '労働基準法の定める周知義務に準じている' },
    { key: 'lc_02', text: '所属社員の身元保証人の把握をしている' },
    { key: 'lc_03', text: '労働条件の明示を労働条件通知書の交付により実施している' },
    { key: 'lc_04', text: '３６協定に基づく労使協定を締結している' },
    { key: 'lc_05', text: '健康診断の定期受診をしている' },
    { key: 'lc_06', text: '賃金未払い、遅延またはサービス残業など労働者の不利益となる事象を発生させたことがある' },
    { key: 'lc_07', text: '個人の責に帰すべき事由による損害賠償請求において、企業が個人に対して賠償責任を問う旨の内容が就業規則に記載されているか' },
    { key: 'lc_08', text: '業務遂行者に対して、本業務遂行に関わる内容を直接ヒアリングすることがあります。' },
]

/** freeeの都道府県コード。0〜46で、未設定は保存しない（freeeは -1 を返す）。 */
export const PREFECTURES: { code: number; name: string }[] = [
    { code: 0, name: '北海道' }, { code: 1, name: '青森県' }, { code: 2, name: '岩手県' },
    { code: 3, name: '宮城県' }, { code: 4, name: '秋田県' }, { code: 5, name: '山形県' },
    { code: 6, name: '福島県' }, { code: 7, name: '茨城県' }, { code: 8, name: '栃木県' },
    { code: 9, name: '群馬県' }, { code: 10, name: '埼玉県' }, { code: 11, name: '千葉県' },
    { code: 12, name: '東京都' }, { code: 13, name: '神奈川県' }, { code: 14, name: '新潟県' },
    { code: 15, name: '富山県' }, { code: 16, name: '石川県' }, { code: 17, name: '福井県' },
    { code: 18, name: '山梨県' }, { code: 19, name: '長野県' }, { code: 20, name: '岐阜県' },
    { code: 21, name: '静岡県' }, { code: 22, name: '愛知県' }, { code: 23, name: '三重県' },
    { code: 24, name: '滋賀県' }, { code: 25, name: '京都府' }, { code: 26, name: '大阪府' },
    { code: 27, name: '兵庫県' }, { code: 28, name: '奈良県' }, { code: 29, name: '和歌山県' },
    { code: 30, name: '鳥取県' }, { code: 31, name: '島根県' }, { code: 32, name: '岡山県' },
    { code: 33, name: '広島県' }, { code: 34, name: '山口県' }, { code: 35, name: '徳島県' },
    { code: 36, name: '香川県' }, { code: 37, name: '愛媛県' }, { code: 38, name: '高知県' },
    { code: 39, name: '福岡県' }, { code: 40, name: '佐賀県' }, { code: 41, name: '長崎県' },
    { code: 42, name: '熊本県' }, { code: 43, name: '大分県' }, { code: 44, name: '宮崎県' },
    { code: 45, name: '鹿児島県' }, { code: 46, name: '沖縄県' },
]
