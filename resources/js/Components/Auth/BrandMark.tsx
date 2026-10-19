import { brand } from '../../config/brand'

type BrandMarkProps = {
    tone?: 'light' | 'dark'
    className?: string
    /** Hide the wordmark to render a compact brand tile (used by the collapsed sidebar). */
    showWordmark?: boolean
}

export default function BrandMark({ tone = 'light', className = '', showWordmark = true }: BrandMarkProps) {
    const tile = tone === 'dark' ? 'bg-white/10 ring-1 ring-white/20' : 'bg-primary'
    const wordmark = tone === 'dark' ? 'text-white' : 'text-ink'

    return (
        <span className={`inline-flex items-center gap-2.5 ${className}`}>
            <span
                className={`relative inline-flex h-9 w-9 flex-none items-center justify-center rounded-xl ${tile}`}
            >
                <svg viewBox="0 0 24 24" className="h-5 w-5" aria-hidden="true">
                    <circle cx="12" cy="12" r="8.5" fill="none" stroke="white" strokeWidth="1.4" opacity="0.4" />
                    <path d="M10.4 8.7l4.3 3.3-4.3 3.3V8.7z" fill="white" />
                </svg>
                <span className="absolute -right-0.5 -top-0.5 h-2 w-2 rounded-full bg-gold" aria-hidden="true" />
            </span>
            {showWordmark ? (
                <span className={`text-base font-semibold tracking-tight ${wordmark}`}>{brand.name}</span>
            ) : (
                <span className="sr-only">{brand.name}</span>
            )}
        </span>
    )
}
