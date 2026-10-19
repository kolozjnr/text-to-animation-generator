import type { ReactNode } from 'react'

type PageHeaderProps = {
    title: string
    description: string
}

export default function PageHeader({ title, description }: PageHeaderProps) {
    return (
        <div className="min-w-0">
            <h1 className="text-2xl font-semibold tracking-tight text-ink sm:text-3xl">{title}</h1>
            <p className="mt-2 max-w-2xl text-sm leading-relaxed text-neutral-600">{description}</p>
        </div>
    )
}
