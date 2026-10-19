import AppLayout from '../../Layouts/AppLayout'
import PageHeader from '../../Components/Layout/PageHeader'
import { BookOpenIcon } from '../../Components/Layout/Icons'

export default function LessonsIndex() {
    return (
        <AppLayout title="View Lessons">
            <div className="space-y-8">
                <PageHeader
                    title="View Lessons"
                    description="Saved and generated lessons will be listed here once lesson storage is available."
                />

                <section
                    aria-label="Lessons list"
                    className="rounded-xl border border-dashed border-neutral-300 bg-white px-6 py-16 text-center"
                >
                    <span className="mx-auto inline-flex h-11 w-11 items-center justify-center rounded-xl bg-primary/5 text-primary">
                        <BookOpenIcon />
                    </span>
                    <h2 className="mt-4 text-base font-semibold text-ink">No lessons yet</h2>
                    <p className="mx-auto mt-2 max-w-md text-sm leading-relaxed text-neutral-600">
                        Lessons you create will appear here with their titles and processing status.
                    </p>
                </section>
            </div>
        </AppLayout>
    )
}
