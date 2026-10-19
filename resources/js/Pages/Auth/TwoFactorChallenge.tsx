import { useForm } from '@inertiajs/react'
import { useState } from 'react'
import type { FormEvent } from 'react'
import AuthButton from '../../Components/Auth/AuthButton'
import AuthInput from '../../Components/Auth/AuthInput'
import PasswordInput from '../../Components/Auth/PasswordInput'
import AuthLayout from '../../Layouts/AuthLayout'

export default function TwoFactorChallenge() {
    const [useRecoveryCode, setUseRecoveryCode] = useState(false)
    const { data, setData, post, processing, errors, reset } = useForm({
        code: '',
        recovery_code: '',
        remember: false,
    })

    const submit = (event: FormEvent) => {
        event.preventDefault()
        post('/two-factor-challenge', {
            onFinish: () => reset(useRecoveryCode ? 'recovery_code' : 'code'),
        })
    }

    return (
        <AuthLayout
            title="Two-factor authentication"
            description={
                useRecoveryCode
                    ? 'Enter one of your emergency recovery codes to regain access to your account.'
                    : 'Enter the authentication code from your authenticator app to continue.'
            }
        >
            <form onSubmit={submit} className="space-y-5">
                {useRecoveryCode ? (
                    <AuthInput
                        id="recovery_code"
                        label="Recovery code"
                        type="text"
                        name="recovery_code"
                        value={data.recovery_code}
                        autoComplete="one-time-code"
                        autoFocus
                        required
                        onChange={(event) => setData('recovery_code', event.target.value)}
                        error={errors.recovery_code}
                    />
                ) : (
                    <AuthInput
                        id="code"
                        label="Authentication code"
                        type="text"
                        name="code"
                        value={data.code}
                        inputMode="numeric"
                        autoComplete="one-time-code"
                        autoFocus
                        required
                        onChange={(event) => setData('code', event.target.value)}
                        error={errors.code}
                    />
                )}

                <AuthButton type="submit" processing={processing}>
                    {processing ? 'Verifying…' : 'Verify and continue'}
                </AuthButton>

                <button
                    type="button"
                    onClick={() => setUseRecoveryCode((value) => !value)}
                    className="block w-full text-center text-sm font-medium text-primary hover:text-primary-dark hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                >
                    {useRecoveryCode ? 'Use an authentication code instead' : 'Use a recovery code instead'}
                </button>
            </form>
        </AuthLayout>
    )
}
