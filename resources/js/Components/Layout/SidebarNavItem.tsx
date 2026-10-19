import { Link } from '@inertiajs/react'
import type { ComponentType, MouseEvent } from 'react'

type SidebarNavItemProps = {
    label: string
    icon: ComponentType<{ className?: string }>
    active?: boolean
    collapsed?: boolean
    /** Render as an Inertia link when provided. */
    href?: string
    /** Render as a button when no href is provided. */
    onClick?: () => void
    /** Visually distinguish destructive actions such as logout. */
    variant?: 'default' | 'danger'
    /** Called after activation, used to close the mobile drawer. */
    onNavigate?: () => void
    /** Disables the item and exposes a busy state. */
    disabled?: boolean
    busy?: boolean
}

const base =
    'group relative flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-offset-1 motion-reduce:transition-none'

const variants = {
    default: {
        idle: 'text-neutral-700 hover:bg-neutral-100 hover:text-primary',
        active: 'bg-primary text-white shadow-sm hover:bg-primary-dark',
    },
    danger: {
        idle: 'text-neutral-600 hover:bg-red-50 hover:text-red-700',
        active: 'bg-red-50 text-red-700',
    },
} as const

export default function SidebarNavItem({
    label,
    icon: Icon,
    active = false,
    collapsed = false,
    href,
    onClick,
    variant = 'default',
    onNavigate,
    disabled = false,
    busy = false,
}: SidebarNavItemProps) {
    const palette = variants[variant]
    const stateClass = active ? palette.active : palette.idle
    const isDisabled = disabled || busy

    const className = [
        base,
        stateClass,
        collapsed ? 'justify-center px-0' : '',
        isDisabled ? 'cursor-not-allowed opacity-60' : '',
    ]
        .filter(Boolean)
        .join(' ')

    const content = (
        <>
            {active && (
                <span
                    aria-hidden="true"
                    className="absolute inset-y-2 left-0 w-[3px] rounded-r-full bg-gold"
                />
            )}
            <Icon className="h-5 w-5 flex-none" />
            <span className={collapsed ? 'sr-only' : 'truncate'}>{busy ? 'Signing out…' : label}</span>
        </>
    )

    if (href) {
        return (
            <li>
                <Link
                    href={href}
                    aria-label={label}
                    aria-current={active ? 'page' : undefined}
                    title={collapsed ? label : undefined}
                    className={className}
                    onClick={onNavigate}
                >
                    {content}
                </Link>
            </li>
        )
    }

    const handleClick = (event: MouseEvent<HTMLButtonElement>) => {
        event.preventDefault()
        if (isDisabled) {
            return
        }
        onClick?.()
    }

    return (
        <li>
            <button
                type="button"
                onClick={handleClick}
                disabled={isDisabled}
                aria-label={label}
                aria-busy={busy || undefined}
                title={collapsed ? label : undefined}
                className={className}
            >
                {content}
            </button>
        </li>
    )
}
