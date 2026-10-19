import { usePage } from '@inertiajs/react'
import AppLayout from '../Layouts/AppLayout'
import { SparklesIcon } from '../Components/Layout/Icons'
import type { SharedProps } from '../types'

export default function Dashboard() {
    const { auth } = usePage<SharedProps>().props
    const firstName = auth?.user?.name?.trim().split(/\s+/)[0] ?? 'there'

    return (
        <AppLayout title="Dashboard">
            <div className="space-y-8">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-ink sm:text-3xl">
                        Welcome back, {firstName}
                    </h1>
                    <p className="mt-2 max-w-2xl text-sm leading-relaxed text-neutral-600">
                        Turn documents and text into clear, step-by-step visual explanations that stay faithful to
                        the original source.
                    </p>
                </div>

                <section
                    aria-label="Workspace overview"
                    className="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm sm:p-8"
                >
                    <div className="flex items-start gap-4">
                        <span className="inline-flex h-10 w-10 flex-none items-center justify-center rounded-lg bg-primary/5 text-primary">
                            <SparklesIcon />
                        </span>
                        <div className="min-w-0">
                            <h2 className="text-base font-semibold text-ink">Your workspace is ready</h2>
                            <p className="mt-1.5 max-w-xl text-sm leading-relaxed text-neutral-600">
                                Conversion tools and saved lessons are not available yet. This space will hold
                                your recent activity once those features arrive.
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </AppLayout>
    )
}
