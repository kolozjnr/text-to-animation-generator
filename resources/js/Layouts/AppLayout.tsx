import { Head, router, usePage } from '@inertiajs/react'
import { useCallback, useEffect, useState } from 'react'
import type { ReactNode } from 'react'
import AppHeader from '../Components/Layout/AppHeader'
import Sidebar from '../Components/Layout/Sidebar'

type AppLayoutProps = {
    title: string
    children: ReactNode
}

const SIDEBAR_STORAGE_KEY = 'app.sidebar.collapsed'

/**
 * Reads the persisted desktop sidebar preference.
 *
 * The value is only a presentation preference, so failures (private browsing,
 * disabled storage) fall back to the expanded layout.
 */
function readSidebarPreference(): boolean {
    if (typeof window === 'undefined') {
        return false
    }

    try {
        return window.localStorage.getItem(SIDEBAR_STORAGE_KEY) === 'true'
    } catch {
        return false
    }
}

export default function AppLayout({ title, children }: AppLayoutProps) {
    const { url } = usePage()
    const [collapsed, setCollapsed] = useState(readSidebarPreference)
    const [mobileOpen, setMobileOpen] = useState(false)
    const [loggingOut, setLoggingOut] = useState(false)

    const closeMobile = useCallback(() => setMobileOpen(false), [])

    // Persist the desktop preference without introducing a dependency.
    useEffect(() => {
        try {
            window.localStorage.setItem(SIDEBAR_STORAGE_KEY, String(collapsed))
        } catch {
            // Persisting the preference is optional; ignore storage failures.
        }
    }, [collapsed])

    // Always dismiss the drawer after an Inertia navigation.
    useEffect(() => {
        setMobileOpen(false)
    }, [url])

    const logout = () => {
        setLoggingOut(true)
        router.post('/logout', {}, { onFinish: () => setLoggingOut(false) })
    }

    return (
        <>
            <Head title={title} />

            <div className="min-h-screen bg-neutral-50 text-ink">
                <a
                    href="#main-content"
                    className="sr-only rounded-md bg-primary px-3 py-2 text-sm font-medium text-white focus:not-sr-only focus:absolute focus:left-4 focus:top-3 focus:z-50"
                >
                    Skip to content
                </a>

                <Sidebar
                    collapsed={collapsed}
                    mobileOpen={mobileOpen}
                    onCloseMobile={closeMobile}
                    onLogout={logout}
                    loggingOut={loggingOut}
                />

                <div
                    className={[
                        'flex min-h-screen min-w-0 flex-col',
                        'transition-[padding] duration-200 ease-out motion-reduce:transition-none',
                        collapsed ? 'lg:pl-[4.75rem]' : 'lg:pl-64',
                    ].join(' ')}
                >
                    <AppHeader
                        title={title}
                        collapsed={collapsed}
                        onToggleSidebar={() => setCollapsed((value) => !value)}
                        onOpenMobile={() => setMobileOpen(true)}
                        mobileOpen={mobileOpen}
                    />

                    <main id="main-content" className="flex-1 px-4 py-8 sm:px-6 lg:px-8">
                        <div className="mx-auto w-full max-w-5xl">{children}</div>
                    </main>
                </div>
            </div>
        </>
    )
}
