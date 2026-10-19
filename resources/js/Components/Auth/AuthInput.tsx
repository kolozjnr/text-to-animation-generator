import type { InputHTMLAttributes, ReactNode } from 'react'

type AuthInputProps = {
    label: string
    id: string
    error?: string
    hint?: string
    trailing?: ReactNode
} & Omit<InputHTMLAttributes<HTMLInputElement>, 'id' | 'className'>

export default function AuthInput({ label, id, error, hint, trailing, ...props }: AuthInputProps) {
    const describedBy = error ? `${id}-error` : hint ? `${id}-hint` : undefined

    return (
        <div className="space-y-1.5">
            <label htmlFor={id} className="block text-sm font-medium text-ink">
                {label}
            </label>
            <div className="relative">
                <input
                    id={id}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={describedBy}
                    className={[
                        'block w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-ink shadow-sm outline-none transition',
                        'placeholder:text-neutral-400',
                        'focus:border-primary focus:ring-2 focus:ring-primary/30',
                        'disabled:cursor-not-allowed disabled:bg-neutral-100 disabled:text-neutral-500',
                        trailing ? 'pr-11' : '',
                        error
                            ? 'border-red-400 focus:border-red-500 focus:ring-red-500/30'
                            : 'border-neutral-300',
                    ].join(' ')}
                    {...props}
                />
                {trailing && (
                    <div className="absolute inset-y-0 right-0 flex items-center pr-1.5">{trailing}</div>
                )}
            </div>
            {error ? (
                <p id={`${id}-error`} className="text-sm text-red-600">
                    {error}
                </p>
            ) : hint ? (
                <p id={`${id}-hint`} className="text-xs text-neutral-500">
                    {hint}
                </p>
            ) : null}
        </div>
    )
}
