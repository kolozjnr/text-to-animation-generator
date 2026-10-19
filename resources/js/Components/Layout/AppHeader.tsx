import { Link, usePage } from '@inertiajs/react'
import type { SharedProps } from '../../types'
import { MenuIcon } from './Icons'

type AppHeaderProps = {
    title: string
    collapsed: boolean
    onToggleSidebar: () => void
    onOpenMobile: () => void
    mobileOpen: boolean
}

function initials(name: string) {
    const parts = name.trim().split(/\s+/).filter(Boolean)

    if (parts.length === 0) {
        return '?'
    }

    const first = parts[0][0] ?? ''
    const last = parts.length > 1 ? parts[parts.length - 1][0] ?? '' : ''

    return `${first}${last}`.toUpperCase()
}

export default function AppHeader({
    title,
    collapsed,
    onToggleSidebar,
    onOpenMobile,
    mobileOpen,
}: AppHeaderProps) {
    const { auth } = usePage<SharedProps>().props
    const user = auth?.user ?? null

    return (
        <header className="sticky top-0 z-20 border-b border-neutral-200 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80">
            <div className="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                <button
                    type="button"
                    onClick={onOpenMobile}
                    aria-label="Open navigation"
                    aria-controls="mobile-navigation"
                    aria-expanded={mobileOpen}
                    className="inline-flex h-9 w-9 flex-none items-center justify-center rounded-lg text-neutral-700 transition hover:bg-neutral-100 hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 lg:hidden"
                >
                    <MenuIcon />
                </button>

                <button
                    type="button"
                    onClick={onToggleSidebar}
                    aria-label={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
                    aria-controls="desktop-sidebar"
                    aria-expanded={!collapsed}
                    className="hidden h-9 w-9 flex-none items-center justify-center rounded-lg text-neutral-700 transition hover:bg-neutral-100 hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 lg:inline-flex"
                >
                    <MenuIcon />
                </button>

                <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-semibold tracking-tight text-ink sm:text-base">{title}</p>
                </div>

                {user && (
                    <Link
                        href="/profile"
                        className="group inline-flex items-center gap-2.5 rounded-lg px-1.5 py-1.5 transition hover:bg-neutral-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                        aria-label={`Open profile for ${user.name}`}
                    >
                        <span
                            aria-hidden="true"
                            className="inline-flex h-8 w-8 flex-none items-center justify-center rounded-full bg-primary text-xs font-semibold text-white ring-2 ring-gold/50"
                        >
                            {initials(user.name)}
                        </span>
                        <span className="hidden max-w-[12rem] truncate text-sm font-medium text-neutral-700 group-hover:text-ink sm:block">
                            {user.name}
                        </span>
                    </Link>
                )}
            </div>
        </header>
    )
}
