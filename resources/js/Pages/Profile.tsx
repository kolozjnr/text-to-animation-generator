import { usePage } from '@inertiajs/react'
import AppLayout from '../Layouts/AppLayout'
import PageHeader from '../Components/Layout/PageHeader'
import type { SharedProps } from '../types'

export default function Profile() {
    const { auth } = usePage<SharedProps>().props
    const user = auth?.user ?? null

    return (
        <AppLayout title="Profile">
            <div className="space-y-8">
                <PageHeader
                    title="Profile"
                    description="Your account details. Profile editing will be available in a later step."
                />

                <section
                    aria-label="Account details"
                    className="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm sm:p-8"
                >
                    {user ? (
                        <dl className="grid gap-6 sm:grid-cols-2">
                            <div>
                                <dt className="text-xs font-medium uppercase tracking-wide text-neutral-500">
                                    Name
                                </dt>
                                <dd className="mt-1 text-sm font-medium text-ink">{user.name}</dd>
                            </div>
                            <div>
                                <dt className="text-xs font-medium uppercase tracking-wide text-neutral-500">
                                    Email address
                                </dt>
                                <dd className="mt-1 break-words text-sm font-medium text-ink">{user.email}</dd>
                            </div>
                        </dl>
                    ) : (
                        <p className="text-sm text-neutral-600">No account details are available.</p>
                    )}
                </section>
            </div>
        </AppLayout>
    )
}
