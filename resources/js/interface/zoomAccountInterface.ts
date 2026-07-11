export interface ZoomAccountSetting {
    id: number
    slot: number
    label: string
    host_email: string
    account_id: string
    client_id: string
    active: boolean
    api_configured: boolean
    host_password_configured: boolean
    client_secret_configured: boolean
    webhook_secret_configured: boolean
    updated_at: string | null
}
