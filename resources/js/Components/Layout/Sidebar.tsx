import { Link, usePage } from '@inertiajs/react'
import { useEffect, useRef } from 'react'
import BrandMark from '../Auth/BrandMark'
import { brand } from '../../config/brand'
import {
    BookOpenIcon,
    CloseIcon,
    HomeIcon,
    LogOutIcon,
    UserCircleIcon,
    WandIcon,
} from './Icons'
import SidebarNavItem from './SidebarNavItem'

type SidebarProps = {
    collapsed: boolean
    mobileOpen: boolean
    onCloseMobile: () => void
    onLogout: () => void
    loggingOut: boolean
}

type NavLink = {
    label: string
    href: string
    icon: typeof HomeIcon
}

const navLinks: NavLink[] = [
    { label: 'Dashboard', href: '/dashboard', icon: HomeIcon },
    { label: 'Convert', href: '/convert', icon: WandIcon },
    { label: 'View Lessons', href: '/lessons', icon: BookOpenIcon },
    { label: 'Profile', href: '/profile', icon: UserCircleIcon },
]

function isActive(path: string, href: string) {
    return path === href || path.startsWith(`${href}/`)
}

export default function Sidebar({
    collapsed,
    mobileOpen,
    onCloseMobile,
    onLogout,
    loggingOut,
}: SidebarProps) {
    const { url } = usePage()
    const path = url.split('?')[0]
    const drawerRef = useRef<HTMLDivElement>(null)
    const closeButtonRef = useRef<HTMLButtonElement>(null)

    useEffect(() => {
        if (!mobileOpen) {
            return
        }

        const previouslyFocused = document.activeElement as HTMLElement | null
        const previousOverflow = document.body.style.overflow

        document.body.style.overflow = 'hidden'
        closeButtonRef.current?.focus()

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                event.preventDefault()
                onCloseMobile()
                return
            }

            if (event.key !== 'Tab') {
                return
            }

            const container = drawerRef.current
            if (!container) {
                return
            }

            const focusable = Array.from(
                container.querySelectorAll<HTMLElement>(
                    'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])',
                ),
            ).filter((element) => element.offsetParent !== null)

            if (focusable.length === 0) {
                return
            }

            const first = focusable[0]
            const last = focusable[focusable.length - 1]

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault()
                last.focus()
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault()
                first.focus()
            }
        }

        document.addEventListener('keydown', handleKeyDown)

        return () => {
            document.removeEventListener('keydown', handleKeyDown)
            document.body.style.overflow = previousOverflow
            previouslyFocused?.focus?.()
        }
    }, [mobileOpen, onCloseMobile])

    const navigation = (isCollapsed: boolean) => (
        <ul className="space-y-1">
            {navLinks.map((link) => (
                <SidebarNavItem
                    key={link.href}
                    label={link.label}
                    href={link.href}
                    icon={link.icon}
                    active={isActive(path, link.href)}
                    collapsed={isCollapsed}
                    onNavigate={onCloseMobile}
                />
            ))}
        </ul>
    )

    const logoutItem = (isCollapsed: boolean) => (
        <SidebarNavItem
            label="Logout"
            icon={LogOutIcon}
            variant="danger"
            collapsed={isCollapsed}
            onClick={onLogout}
            busy={loggingOut}
        />
    )

    return (
        <>
            {/* Desktop sidebar */}
            <aside
                id="desktop-sidebar"
                aria-label="Main navigation"
                className={[
                    'fixed inset-y-0 left-0 z-30 hidden flex-col border-r border-neutral-200 bg-white lg:flex',
                    'transition-[width] duration-200 ease-out motion-reduce:transition-none',
                    collapsed ? 'w-[4.75rem]' : 'w-64',
                ].join(' ')}
            >
                <div
                    className={[
                        'flex h-16 flex-none items-center border-b border-neutral-200',
                        collapsed ? 'justify-center px-2' : 'px-5',
                    ].join(' ')}
                >
                    <Link
                        href="/dashboard"
                        className="rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                        aria-label="Text to Animation — go to dashboard"
                    >
                        <BrandMark showWordmark={!collapsed} />
                    </Link>
                </div>

                <nav className="flex-1 overflow-y-auto px-3 py-5">
                    {navigation(collapsed)}
                </nav>

                <div className="flex-none border-t border-neutral-200 px-3 py-4">
                    <ul>{logoutItem(collapsed)}</ul>
                </div>
            </aside>

            {/* Mobile drawer */}
            <div className="lg:hidden">
                <div
                    aria-hidden="true"
                    onClick={onCloseMobile}
                    className={[
                        'fixed inset-0 z-40 bg-ink/40 backdrop-blur-[2px] transition-opacity duration-200 motion-reduce:transition-none',
                        mobileOpen ? 'opacity-100' : 'pointer-events-none opacity-0',
                    ].join(' ')}
                />

                <div
                    ref={drawerRef}
                    id="mobile-navigation"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Navigation menu"
                    inert={!mobileOpen}
                    className={[
                        'fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] flex-col border-r border-neutral-200 bg-white shadow-xl',
                        'transition-transform duration-200 ease-out motion-reduce:transition-none',
                        mobileOpen ? 'visible translate-x-0' : 'invisible -translate-x-full',
                    ].join(' ')}
                >
                    <div className="flex h-16 flex-none items-center justify-between gap-3 border-b border-neutral-200 px-4">
                        <Link
                            href="/dashboard"
                            className="rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                            aria-label="Text to Animation — go to dashboard"
                            onClick={onCloseMobile}
                        >
                            <BrandMark />
                        </Link>
                        <button
                            ref={closeButtonRef}
                            type="button"
                            onClick={onCloseMobile}
                            aria-label="Close navigation"
                            className="inline-flex h-9 w-9 items-center justify-center rounded-lg text-neutral-600 transition hover:bg-neutral-100 hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                        >
                            <CloseIcon />
                        </button>
                    </div>

                    <nav className="flex-1 overflow-y-auto px-3 py-5">{navigation(false)}</nav>

                    <div className="flex-none border-t border-neutral-200 px-3 py-4">
                        <ul>{logoutItem(false)}</ul>
                    </div>
                </div>
            </div>
        </>
    )
}
