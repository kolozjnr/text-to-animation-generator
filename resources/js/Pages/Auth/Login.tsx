import { Link, useForm, usePage } from '@inertiajs/react'
import type { FormEvent } from 'react'
import AuthButton from '../../Components/Auth/AuthButton'
import AuthInput from '../../Components/Auth/AuthInput'
import FormAlert from '../../Components/Auth/FormAlert'
import PasswordInput from '../../Components/Auth/PasswordInput'
import AuthLayout from '../../Layouts/AuthLayout'
import type { SharedProps } from '../../types'

export default function Login() {
    const { flash } = usePage<SharedProps>().props
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    })

    const submit = (event: FormEvent) => {
        event.preventDefault()
        post('/login', { onFinish: () => reset('password') })
    }

    return (
        <AuthLayout title="Sign in" description="Welcome back. Enter your details to continue.">
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

                <PasswordInput
                    id="password"
                    label="Password"
                    name="password"
                    value={data.password}
                    autoComplete="current-password"
                    required
                    onChange={(event) => setData('password', event.target.value)}
                    error={errors.password}
                />

                <div className="flex items-center justify-between gap-4">
                    <label className="inline-flex items-center gap-2 text-sm text-neutral-700">
                        <input
                            type="checkbox"
                            name="remember"
                            checked={data.remember}
                            onChange={(event) => setData('remember', event.target.checked)}
                            className="h-4 w-4 rounded border-neutral-300 text-primary focus:ring-primary/40"
                        />
                        Remember me
                    </label>

                    <Link
                        href="/forgot-password"
                        className="text-sm font-medium text-primary hover:text-primary-dark hover:underline"
                    >
                        Forgot password?
                    </Link>
                </div>

                <AuthButton type="submit" processing={processing}>
                    {processing ? 'Signing in…' : 'Sign in'}
                </AuthButton>
            </form>

            <p className="mt-6 text-center text-sm text-neutral-600">
                Don&apos;t have an account?{' '}
                <Link href="/register" className="font-medium text-primary hover:text-primary-dark hover:underline">
                    Create one
                </Link>
            </p>
        </AuthLayout>
    )
}
