import { Link, useForm, usePage } from '@inertiajs/react'
import type { FormEvent } from 'react'
import AuthButton from '../../Components/Auth/AuthButton'
import AuthInput from '../../Components/Auth/AuthInput'
import FormAlert from '../../Components/Auth/FormAlert'
import AuthLayout from '../../Layouts/AuthLayout'
import type { SharedProps } from '../../types'

export default function ForgotPassword() {
    const { flash } = usePage<SharedProps>().props
    const { data, setData, post, processing, errors } = useForm({ email: '' })

    const submit = (event: FormEvent) => {
        event.preventDefault()
        post('/forgot-password')
    }

    return (
        <AuthLayout
            title="Reset your password"
            description="Enter the email address for your account and we will send you a secure link to choose a new password."
        >
            <form onSubmit={submit} className="space-y-5">
                {flash?.status && <FormAlert variant="success">{flash.status}</FormAlert>}

                <AuthInput
                    id="email"
                    label="Email address"
                    type="email"
                    name="email"
                    value={data.email}
                    autoComplete="email"
                    autoFocus
                    required
                    onChange={(event) => setData('email', event.target.value)}
                    error={errors.email}
                />

                <AuthButton type="submit" processing={processing}>
                    {processing ? 'Sending link…' : 'Email password reset link'}
                </AuthButton>
            </form>

            <p className="mt-6 text-center text-sm text-neutral-600">
                Remembered your password?{' '}
                <Link href="/login" className="font-medium text-primary hover:text-primary-dark hover:underline">
                    Back to sign in
                </Link>
            </p>
        </AuthLayout>
    )
}
