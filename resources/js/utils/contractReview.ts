type ApiClient = {
    get: (url: string, data?: any, options?: any) => Promise<any>
    post: (url: string, data?: any, options?: any) => Promise<any>
}

const REVIEW_POLL_INTERVAL_MS = 3000
const REVIEW_POLL_TIMEOUT_MS = 20 * 60 * 1000
const REVIEW_POLL_MAX_CONSECUTIVE_ERRORS = 5

const sleep = (ms: number) => new Promise<void>(resolve => setTimeout(resolve, ms))

export const isRenderedPagesRequiredError = (error: any) => {
    return error?.response?.data?.code === 'rendered_pages_required'
        || error?.response?.data?.message === 'PDF_RENDERED_PAGES_REQUIRED'
}

/**
 * The review endpoint queues a job and returns `{ job_id }`; poll its status
 * until the review completes. A plain payload (legacy sync shape) passes through.
 */
export const waitForContractReviewResult = async (
    api: ApiClient,
    submitted: Record<string, any> | null,
): Promise<Record<string, any> | null> => {
    if (!submitted) {
        return null
    }
    if (!submitted.job_id) {
        return submitted
    }

    const startedAt = Date.now()
    let consecutiveErrors = 0

    while (Date.now() - startedAt <= REVIEW_POLL_TIMEOUT_MS) {
        let status: Record<string, any> | null = null
        try {
            status = await api.get('/review_document/status', { job_id: submitted.job_id }, { silent: true })
            consecutiveErrors = 0
        } catch (error) {
            consecutiveErrors += 1
            if (consecutiveErrors >= REVIEW_POLL_MAX_CONSECUTIVE_ERRORS) {
                throw error
            }
        }

        if (status?.status === 'completed') {
            return status.result ?? null
        }
        if (status?.status === 'failed') {
            throw new Error(status?.error || 'AIレビューに失敗しました。')
        }

        await sleep(REVIEW_POLL_INTERVAL_MS)
    }

    throw new Error('AIレビューがタイムアウトしました。時間を置いて再度お試しください。')
}

export const submitContractReview = async (api: ApiClient, formData: FormData) => {
    const submitted = await api.post('/review_document', formData, { silent: true })
    return waitForContractReviewResult(api, submitted)
}
