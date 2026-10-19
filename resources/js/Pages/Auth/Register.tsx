import { Link, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'
import AuthButton from '../../Components/Auth/AuthButton'
import AuthInput from '../../Components/Auth/AuthInput'
import PasswordInput from '../../Components/Auth/PasswordInput'
import AuthLayout from '../../Layouts/AuthLayout'

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    })

    const submit = (event: FormEvent) => {
        event.preventDefault()
        post('/register', { onFinish: () => reset('password', 'password_confirmation') })
    }

    return (
        <AuthLayout title="Create your account" description="Start turning your documents into visual explanations.">
            <form onSubmit={submit} className="space-y-5">
                <AuthInput
                    id="name"
                    label="Full name"
                    type="text"
                    name="name"
                    value={data.name}
                    autoComplete="name"
                    autoFocus
                    required
                    onChange={(event) => setData('name', event.target.value)}
                    error={errors.name}
                />

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
                    label="Password"
                    name="password"
                    value={data.password}
                    autoComplete="new-password"
                    required
                    onChange={(event) => setData('password', event.target.value)}
                    error={errors.password}
                    hint="Use at least 8 characters."
                />

                <PasswordInput
                    id="password_confirmation"
                    label="Confirm password"
                    name="password_confirmation"
                    value={data.password_confirmation}
                    autoComplete="new-password"
                    required
                    onChange={(event) => setData('password_confirmation', event.target.value)}
                    error={errors.password_confirmation}
                />

                <AuthButton type="submit" processing={processing}>
                    {processing ? 'Creating account…' : 'Create account'}
                </AuthButton>
            </form>

            <p className="mt-6 text-center text-sm text-neutral-600">
                Already have an account?{' '}
                <Link href="/login" className="font-medium text-primary hover:text-primary-dark hover:underline">
                    Sign in
                </Link>
            </p>
        </AuthLayout>
    )
}
