/**
 * Lightweight, locally defined icon set for the authenticated application shell.
 *
 * The project does not use an icon dependency. Keeping these inline SVGs avoids
 * adding a package while matching the existing hand-authored icon style used by
 * the authentication screens.
 */

type IconProps = {
    className?: string
}

function iconClass(className?: string) {
    return className ?? 'h-5 w-5'
}

export function MenuIcon({ className }: IconProps) {
    return (
        <svg
            viewBox="0 0 24 24"
            className={iconClass(className)}
            fill="none"
            stroke="currentColor"
            strokeWidth="1.7"
            strokeLinecap="round"
            aria-hidden="true"
            focusable="false"
        >
            <path d="M4 7h16M4 12h16M4 17h16" />
        </svg>
    )
}

export function CloseIcon({ className }: IconProps) {
    return (
        <svg
            viewBox="0 0 24 24"
            className={iconClass(className)}
            fill="none"
            stroke="currentColor"
            strokeWidth="1.7"
            strokeLinecap="round"
            aria-hidden="true"
            focusable="false"
        >
            <path d="M6 6l12 12M18 6L6 18" />
        </svg>
    )
}

export function HomeIcon({ className }: IconProps) {
    return (
        <svg
            viewBox="0 0 24 24"
            className={iconClass(className)}
            fill="none"
            stroke="currentColor"
            strokeWidth="1.7"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            focusable="false"
        >
            <path d="M4 10.5L12 4l8 6.5V19a1 1 0 0 1-1 1h-4.5v-5.5h-5V20H5a1 1 0 0 1-1-1v-8.5z" />
        </svg>
    )
}

export function WandIcon({ className }: IconProps) {
    return (
        <svg
            viewBox="0 0 24 24"
            className={iconClass(className)}
            fill="none"
            stroke="currentColor"
            strokeWidth="1.7"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            focusable="false"
        >
            <path d="M5 19l10.5-10.5" />
            <path d="M14 5.5l1.2-2.2 1.2 2.2 2.2 1.2-2.2 1.2-1.2 2.2-1.2-2.2-2.2-1.2 2.2-1.2z" />
            <path d="M6 5.5l.7-1.3.7 1.3 1.3.7-1.3.7-.7 1.3-.7-1.3-1.3-.7 1.3-.7z" />
            <path d="M18.5 14l.7-1.3.7 1.3 1.3.7-1.3.7-.7 1.3-.7-1.3-1.3-.7 1.3-.7z" />
        </svg>
    )
}

export function BookOpenIcon({ className }: IconProps) {
    return (
        <svg
            viewBox="0 0 24 24"
            className={iconClass(className)}
            fill="none"
            stroke="currentColor"
            strokeWidth="1.7"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            focusable="false"
        >
            <path d="M12 6.5C10.5 5.2 8.6 4.6 6 4.6c-.8 0-1.5.1-2 .2v13.4c.5-.1 1.2-.2 2-.2 2.6 0 4.5.6 6 1.9 1.5-1.3 3.4-1.9 6-1.9.8 0 1.5.1 2 .2V4.8c-.5-.1-1.2-.2-2-.2-2.6 0-4.5.6-6 1.9z" />
            <path d="M12 6.5V20" />
        </svg>
    )
}

export function UserCircleIcon({ className }: IconProps) {
    return (
        <svg
            viewBox="0 0 24 24"
            className={iconClass(className)}
            fill="none"
            stroke="currentColor"
            strokeWidth="1.7"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            focusable="false"
        >
            <circle cx="12" cy="12" r="8.5" />
            <circle cx="12" cy="9.8" r="2.7" />
            <path d="M6.8 18.4c1.1-1.9 2.9-2.9 5.2-2.9s4.1 1 5.2 2.9" />
        </svg>
    )
}

export function LogOutIcon({ className }: IconProps) {
    return (
        <svg
            viewBox="0 0 24 24"
            className={iconClass(className)}
            fill="none"
            stroke="currentColor"
            strokeWidth="1.7"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            focusable="false"
        >
            <path d="M14.5 4.5h3a1.5 1.5 0 0 1 1.5 1.5v12a1.5 1.5 0 0 1-1.5 1.5h-3" />
            <path d="M10 8l-4 4 4 4" />
            <path d="M6 12h9" />
        </svg>
    )
}

export function SparklesIcon({ className }: IconProps) {
    return (
        <svg
            viewBox="0 0 24 24"
            className={iconClass(className)}
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            focusable="false"
        >
            <path d="M12 4l1.4 3.6L17 9l-3.6 1.4L12 14l-1.4-3.6L7 9l3.6-1.4L12 4z" />
            <path d="M18 14l.8 2.2L21 17l-2.2.8L18 20l-.8-2.2L15 17l2.2-.8L18 14z" />
            <path d="M5.5 14l.6 1.6 1.6.6-1.6.6-.6 1.6-.6-1.6-1.6-.6 1.6-.6.6-1.6z" />
        </svg>
    )
}
