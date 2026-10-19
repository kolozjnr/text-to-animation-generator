import { Head } from '@inertiajs/react'
import type { ReactNode } from 'react'
import BrandMark from '../Components/Auth/BrandMark'
import { brand } from '../config/brand'

type AuthLayoutProps = {
    title: string
    description?: string
    children: ReactNode
}

const highlights = [
    'Documents and text become step-by-step visual explanations.',
    'Faithful to your source material — nothing invented.',
    'Clear captions, labels, and diagrams at a glance.',
]

export default function AuthLayout({ title, description, children }: AuthLayoutProps) {
    return (
        <>
            <Head title={title} />
            <div className="min-h-screen w-full bg-white text-ink lg:grid lg:grid-cols-2">
                <aside className="relative hidden overflow-hidden bg-primary px-12 py-14 text-white lg:flex lg:flex-col lg:justify-between">
                    <div
                        className="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-gold/10 blur-3xl"
                        aria-hidden="true"
                    />
                    <div
                        className="pointer-events-none absolute -bottom-32 -left-24 h-80 w-80 rounded-full bg-white/5 blur-3xl"
                        aria-hidden="true"
                    />

                    <BrandMark tone="dark" />

                    <div className="relative max-w-md">
                        <h2 className="text-3xl font-semibold leading-tight tracking-tight">
                            Understand more by reading less.
                        </h2>
                        <p className="mt-4 text-sm leading-relaxed text-white/70">{brand.tagline}</p>

                        <ul className="mt-8 space-y-4">
                            {highlights.map((item) => (
                                <li key={item} className="flex gap-3 text-sm text-white/85">
                                    <span className="mt-0.5 inline-flex h-5 w-5 flex-none items-center justify-center rounded-full bg-gold/20 text-gold">
                                        <svg viewBox="0 0 20 20" className="h-3 w-3" aria-hidden="true">
                                            <path
                                                d="M4.5 10.5l3.2 3.2 7.8-7.8"
                                                fill="none"
                                                stroke="currentColor"
                                                strokeWidth="2"
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                            />
                                        </svg>
                                    </span>
                                    <span>{item}</span>
                                </li>
                            ))}
                        </ul>
                    </div>

                    <p className="relative text-xs text-white/50">{brand.copyright}</p>
                </aside>

                <main className="flex min-h-screen flex-col justify-center px-5 py-10 sm:px-8 lg:px-14">
                    <div className="mx-auto w-full max-w-md">
                        <div className="mb-8 lg:hidden">
                            <BrandMark tone="light" />
                        </div>

                        <h1 className="text-2xl font-semibold tracking-tight text-ink sm:text-3xl">{title}</h1>
                        {description && <p className="mt-2 text-sm text-neutral-600">{description}</p>}

                        <div className="mt-8">{children}</div>

                        <p className="mt-10 text-center text-xs text-neutral-400 lg:hidden">{brand.copyright}</p>
                    </div>
                </main>
            </div>
        </>
    )
}
