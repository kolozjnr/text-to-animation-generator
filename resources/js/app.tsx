import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'

createInertiaApp({
    title: (title) => `${title} - Text to Animation`,
    setup({ el, App, props }) {
        if (!el) {
            throw new Error('Inertia root element (#app) was not found.')
        }

        createRoot(el).render(<App {...props} />)
    },
})
