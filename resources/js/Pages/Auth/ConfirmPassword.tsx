import { useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'
import AuthButton from '../../Components/Auth/AuthButton'
import PasswordInput from '../../Components/Auth/PasswordInput'
import AuthLayout from '../../Layouts/AuthLayout'

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({ password: '' })

    const submit = (event: FormEvent) => {
        event.preventDefault()
        post('/user/confirm-password', { onFinish: () => reset('password') })
    }

    return (
        <AuthLayout
            title="Confirm your password"
            description="This is a secure area of the application. Please confirm your password before continuing."
        >
            <form onSubmit={submit} className="space-y-5">
                <PasswordInput
                    id="password"
                    label="Password"
                    name="password"
                    value={data.password}
                    autoComplete="current-password"
                    autoFocus
                    required
                    onChange={(event) => setData('password', event.target.value)}
                    error={errors.password}
                />

                <AuthButton type="submit" processing={processing}>
                    {processing ? 'Confirming…' : 'Confirm'}
                </AuthButton>
            </form>
        </AuthLayout>
    )
}
