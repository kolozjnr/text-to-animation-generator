export interface FlashProps {
    status: string | null
    error: string | null
}

export type ExtractionStatus = 'pending' | 'extracting' | 'completed' | 'needs_review' | 'failed'

export interface DocumentSummary {
    id: number
    name: string
    size_bytes: number
    extraction_status: ExtractionStatus
    page_count: number | null
    warning_count: number
    failure_reason: string | null
    created_at: string | null
}

export interface AuthUser {
    id: number
    name: string
    email: string
}

export interface AuthProps {
    user: AuthUser | null
}

/**
 * Props shared with every Inertia page by App\Http\Middleware\HandleInertiaRequests.
 *
 * Only non-sensitive user fields are shared. The User model does not hide
 * two_factor_secret / two_factor_recovery_codes, so never share the raw model.
 */
export interface SharedProps {
    [key: string]: unknown
    auth: AuthProps
    flash: FlashProps
}
