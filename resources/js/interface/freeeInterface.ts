export type FreeeCredentialStatus =
    | 'unconfigured'
    | 'awaiting_consent'
    | 'connected'
    | 'needs_reauth'

export interface FreeeCredentialSetting {
    id: number
    label: string
    client_id: string | null
    redirect_uri: string | null
    company_id: number | null
    company_name: string | null
    status: FreeeCredentialStatus
    active: boolean
    app_configured: boolean
    client_secret_configured: boolean
    connected: boolean
    out_of_band: boolean
    awaiting_company_selection: boolean
    reauthorization_required: boolean
    access_token_expires_at: string | null
    refresh_token_expires_at: string | null
    refresh_token_days_left: number | null
    last_refreshed_at: string | null
    refresh_count: number
    authorized_at: string | null
    authorized_by_name: string | null
    last_error: string | null
    last_error_at: string | null
    updated_at: string | null
}

export interface FreeeCredentialIndexResponse {
    credentials: FreeeCredentialSetting[]
    callback_url: string
    oob_redirect_uri: string
}

export interface FreeeConnectResponse {
    authorization_url?: string
    out_of_band?: boolean
}

export interface FreeeConnectionCompany {
    id: number | null
    name: string | null
    role?: string | null
}

export interface FreeePartner {
    id: number | null
    code: string | null
    name: string | null
    name_kana: string | null
    long_name: string | null
    phone: string | null
    contact_name: string | null
    email: string | null
    available: boolean | null
    invoice_registration_number: string | null
    update_date: string | null
}

export type FreeePartnerSort = 'id' | 'code' | 'name' | 'name_kana' | 'update_date'

export interface FreeePartnersResponse {
    partners: FreeePartner[]
    meta: {
        page: number
        per_page: number
        total_count: number
        last_page: number
        has_more: boolean
        from: number
        to: number
        // freeeは並び替えパラメータを持たないため、サーバー側で全件を並べ替えている
        sort: FreeePartnerSort
        direction: 'asc' | 'desc'
        sortable: FreeePartnerSort[]
    }
}

export interface FreeeCompaniesResponse {
    companies: FreeeConnectionCompany[]
}

export interface FreeeTestResponse {
    message?: string
    connection?: {
        email: string | null
        display_name: string | null
        company_id: number | null
        companies: FreeeConnectionCompany[]
        hr_available: boolean
        hr_status: number | null
        hr_message: string | null
    }
    credential?: FreeeCredentialSetting
}
