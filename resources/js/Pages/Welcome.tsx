import { Head } from '@inertiajs/react'

export default function Welcome() {
    return (
        <>
            <Head title="Welcome" />
            <div className="min-h-screen flex flex-col items-center justify-center bg-gray-100">
                <h1 className="text-4xl font-bold mb-4">Text to Animation Platform</h1>
                <p className="text-lg text-gray-600 mb-8">
                    Welcome to the platform for creating animated explanations from text.
                </p>
            </div>
        </>
    )
}
