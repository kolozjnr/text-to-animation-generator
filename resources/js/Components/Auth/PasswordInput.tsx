import { useState } from 'react'
import type { InputHTMLAttributes } from 'react'
import AuthInput from './AuthInput'

type PasswordInputProps = {
    label: string
    id: string
    error?: string
    hint?: string
} & Omit<InputHTMLAttributes<HTMLInputElement>, 'id' | 'type'>

export default function PasswordInput({ label, id, error, hint, ...props }: PasswordInputProps) {
    const [visible, setVisible] = useState(false)

    return (
        <AuthInput
            id={id}
            label={label}
            error={error}
            hint={hint}
            type={visible ? 'text' : 'password'}
            trailing={
                <button
                    type="button"
                    onClick={() => setVisible((value) => !value)}
                    aria-label={visible ? 'Hide password' : 'Show password'}
                    aria-pressed={visible}
                    aria-controls={id}
                    className="inline-flex h-8 w-8 items-center justify-center rounded-md text-neutral-500 transition hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                >
                    {visible ? <EyeOffIcon /> : <EyeIcon />}
                </button>
            }
            {...props}
        />
    )
}

function EyeIcon() {
    return (
        <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" aria-hidden="true">
            <path
                d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"
                stroke="currentColor"
                strokeWidth="1.6"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <circle cx="12" cy="12" r="3" stroke="currentColor" strokeWidth="1.6" />
        </svg>
    )
}

function EyeOffIcon() {
    return (
        <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" aria-hidden="true">
            <path
                d="M3 3l18 18M10.6 6.1A9.7 9.7 0 0 1 12 6c6 0 9.5 6 9.5 6a17 17 0 0 1-3.4 4M6.4 7.4A17 17 0 0 0 2.5 12S6 18 12 18a9.6 9.6 0 0 0 3.5-.6"
                stroke="currentColor"
                strokeWidth="1.6"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M9.9 9.9a3 3 0 0 0 4.2 4.2"
                stroke="currentColor"
                strokeWidth="1.6"
                strokeLinecap="round"
            />
        </svg>
    )
}
