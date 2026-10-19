import { Link, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'
import AuthButton from '../../Components/Auth/AuthButton'
import AuthInput from '../../Components/Auth/AuthInput'
import PasswordInput from '../../Components/Auth/PasswordInput'
import AuthLayout from '../../Layouts/AuthLayout'

type ResetPasswordProps = {
    token: string
    email: string
}

export default function ResetPassword({ token, email }: ResetPasswordProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    })

    const submit = (event: FormEvent) => {
        event.preventDefault()
        post('/reset-password', { onFinish: () => reset('password', 'password_confirmation') })
    }

    return (
        <AuthLayout title="Choose a new password" description="Set a new password for your account, then sign in again.">
            <form onSubmit={submit} className="space-y-5">
                <AuthInput
                    id="email"
                    label="Email address"
                    type="email"
                    name="email"
                    value={data.email}
                    autoComplete="email"
                    required
                    onChange={(event) => setData('email', event.target.value)}
                    error={errors.email}
                />

                <PasswordInput
                    id="password"
                    label="New password"
                    name="password"
                    value={data.password}
                    autoComplete="new-password"
                    autoFocus
                    required
                    onChange={(event) => setData('password', event.target.value)}
                    error={errors.password}
                />

                <PasswordInput
                    id="password_confirmation"
                    label="Confirm new password"
                    name="password_confirmation"
                    value={data.password_confirmation}
                    autoComplete="new-password"
                    required
                    onChange={(event) => setData('password_confirmation', event.target.value)}
                    error={errors.password_confirmation}
                />

                <AuthButton type="submit" processing={processing}>
                    {processing ? 'Resetting password…' : 'Reset password'}
                </AuthButton>
            </form>

            <p className="mt-6 text-center text-sm text-neutral-600">
                <Link href="/login" className="font-medium text-primary hover:text-primary-dark hover:underline">
                    Back to sign in
                </Link>
            </p>
        </AuthLayout>
    )
}
