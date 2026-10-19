import type { ReactNode } from 'react'

type FormAlertProps = {
    variant?: 'success' | 'error' | 'info'
    children: ReactNode
}

const styles: Record<'success' | 'error' | 'info', string> = {
    success: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    error: 'border-red-200 bg-red-50 text-red-800',
    info: 'border-primary/20 bg-primary/5 text-primary',
}

export default function FormAlert({ variant = 'info', children }: FormAlertProps) {
    return (
        <div
            role={variant === 'error' ? 'alert' : 'status'}
            className={`rounded-lg border px-3.5 py-3 text-sm ${styles[variant]}`}
        >
            {children}
        </div>
    )
}
