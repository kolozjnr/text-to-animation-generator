import type { ButtonHTMLAttributes } from 'react'

type AuthButtonProps = {
    variant?: 'primary' | 'secondary'
    processing?: boolean
} & ButtonHTMLAttributes<HTMLButtonElement>

const base =
    'inline-flex w-full items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60'

const variants: Record<'primary' | 'secondary', string> = {
    primary: 'bg-primary text-white hover:bg-primary-dark focus-visible:ring-primary/50',
    secondary: 'border border-neutral-300 bg-white text-ink hover:bg-neutral-50 focus-visible:ring-primary/30',
}

export default function AuthButton({
    variant = 'primary',
    processing = false,
    disabled,
    className = '',
    children,
    ...props
}: AuthButtonProps) {
    return (
        <button
            {...props}
            disabled={processing || disabled}
            aria-busy={processing}
            className={`${base} ${variants[variant]} ${className}`}
        >
            {processing && (
                <svg className="h-4 w-4 animate-spin" viewBox="0 0 24 24" aria-hidden="true">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" />
                    <path
                        className="opacity-90"
                        d="M12 2a10 10 0 0 1 10 10"
                        stroke="currentColor"
                        strokeWidth="4"
                        strokeLinecap="round"
                        fill="none"
                    />
                </svg>
            )}
            {children}
        </button>
    )
}
