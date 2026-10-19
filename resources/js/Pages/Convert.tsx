import { router, useForm, usePage } from '@inertiajs/react'
import { useRef } from 'react'
import type { ChangeEvent, FormEvent } from 'react'
import AppLayout from '../Layouts/AppLayout'
import PageHeader from '../Components/Layout/PageHeader'
import { BookOpenIcon, WandIcon } from '../Components/Layout/Icons'
import type { DocumentSummary, ExtractionStatus, SharedProps } from '../types'

type ConvertProps = {
    documents: DocumentSummary[]
    maxSizeMb: number
}

const statusLabels: Record<ExtractionStatus, string> = {
    pending: 'Queued',
    extracting: 'Extracting',
    completed: 'Extracted',
    needs_review: 'Needs review',
    failed: 'Failed',
}

const statusStyles: Record<ExtractionStatus, string> = {
    pending: 'border-neutral-200 bg-neutral-50 text-neutral-600',
    extracting: 'border-sky-200 bg-sky-50 text-sky-700',
    completed: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    needs_review: 'border-amber-200 bg-amber-50 text-amber-800',
    failed: 'border-red-200 bg-red-50 text-red-700',
}

function formatBytes(bytes: number): string {
    if (!Number.isFinite(bytes) || bytes <= 0) {
        return '0 B'
    }

    const units = ['B', 'KB', 'MB', 'GB']
    const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1)
    const value = bytes / 1024 ** index

    return `${value.toFixed(index === 0 ? 0 : 1)} ${units[index]}`
}

function formatDate(value: string | null): string {
    if (!value) {
        return 'Unknown date'
    }

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) {
        return 'Unknown date'
    }

    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}

export default function Convert({ documents, maxSizeMb }: ConvertProps) {
    const { flash } = usePage<SharedProps>().props
    const fileInputRef = useRef<HTMLInputElement>(null)
    const { data, setData, post, processing, progress, errors, reset } = useForm<{
        document: File | null
    }>({ document: null })

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        if (!data.document) {
            return
        }

        post('/documents', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset('document')

                if (fileInputRef.current) {
                    fileInputRef.current.value = ''
                }
            },
        })
    }

    const selectFile = (event: ChangeEvent<HTMLInputElement>) => {
        setData('document', event.target.files?.[0] ?? null)
    }

    const refreshDocuments = () => {
        router.reload({ only: ['documents'] })
    }

    return (
        <AppLayout title="Convert">
            <div className="space-y-8">
                <PageHeader
                    title="Convert"
                    description="Upload a PDF, DOCX, or TXT document. Files are stored privately and extracted into a source-grounded lesson in a later step."
                />

                <section
                    aria-labelledby="upload-heading"
                    className="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm sm:p-8"
                >
                    <div className="flex items-start gap-4">
                        <span className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/5 text-primary">
                            <WandIcon />
                        </span>
                        <div className="min-w-0">
                            <h2 id="upload-heading" className="text-base font-semibold text-ink">
                                Upload a document
                            </h2>
                            <p className="mt-1 text-sm leading-relaxed text-neutral-600">
                                PDF, DOCX, or TXT up to {maxSizeMb} MB. Extraction starts in the
                                background after the upload completes.
                            </p>
                        </div>
                    </div>

                    <form onSubmit={submit} className="mt-6 space-y-4">
                        {flash.status && (
                            <div
                                role="status"
                                className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
                            >
                                {flash.status}
                            </div>
                        )}

                        {flash.error && (
                            <div
                                role="alert"
                                className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
                            >
                                {flash.error}
                            </div>
                        )}

                        <div>
                            <label htmlFor="document" className="block text-sm font-medium text-ink">
                                Document file
                            </label>
                            <input
                                ref={fileInputRef}
                                id="document"
                                name="document"
                                type="file"
                                accept=".pdf,.docx,.txt"
                                onChange={selectFile}
                                aria-describedby={
                                    errors.document
                                        ? 'document-hint document-error'
                                        : 'document-hint'
                                }
                                className="mt-1.5 block w-full cursor-pointer rounded-lg border border-neutral-300 bg-white text-sm text-neutral-600 file:mr-4 file:border-0 file:bg-primary/5 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-primary hover:file:bg-primary/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                            />
                            <p id="document-hint" className="mt-1.5 text-xs text-neutral-500">
                                Only PDF, DOCX, and TXT files are accepted.
                            </p>
                            {errors.document && (
                                <p
                                    id="document-error"
                                    role="alert"
                                    className="mt-1.5 text-sm text-red-600"
                                >
                                    {errors.document}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-wrap items-center gap-3">
                            <button
                                type="submit"
                                disabled={processing || !data.document}
                                className="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-primary/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {processing ? 'Uploading…' : 'Upload document'}
                            </button>

                            {processing && (
                                <span role="status" className="text-xs font-medium text-neutral-500">
                                    {progress ? `${progress.percentage ?? 0}%` : 'Uploading…'}
                                </span>
                            )}
                        </div>
                    </form>
                </section>

                <section
                    aria-labelledby="documents-heading"
                    className="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm sm:p-8"
                >
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <h2 id="documents-heading" className="text-base font-semibold text-ink">
                            Your documents
                        </h2>
                        <div className="flex items-center gap-3">
                            <span className="text-xs font-medium text-neutral-500">
                                {documents.length} {documents.length === 1 ? 'document' : 'documents'}
                            </span>
                            <button
                                type="button"
                                onClick={refreshDocuments}
                                className="rounded-lg border border-neutral-300 bg-white px-3 py-1.5 text-xs font-semibold text-neutral-700 transition-colors hover:bg-neutral-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                            >
                                Refresh status
                            </button>
                        </div>
                    </div>

                    {documents.length === 0 ? (
                        <div className="mt-6 rounded-lg border border-dashed border-neutral-300 bg-neutral-50/50 px-6 py-12 text-center">
                            <BookOpenIcon className="mx-auto h-6 w-6 text-primary" />
                            <p className="mt-3 text-sm font-medium text-ink">
                                No documents uploaded yet
                            </p>
                            <p className="mx-auto mt-1 max-w-sm text-sm text-neutral-600">
                                Upload a PDF, DOCX, or TXT file to start building your source
                                library.
                            </p>
                        </div>
                    ) : (
                        <ul role="list" className="mt-4 divide-y divide-neutral-200">
                            {documents.map((document) => {
                                const status = document.extraction_status
                                const detailParts = [
                                    formatBytes(document.size_bytes),
                                    formatDate(document.created_at),
                                ]

                                if (document.page_count !== null) {
                                    detailParts.push(
                                        `${document.page_count} ${
                                            document.page_count === 1 ? 'page' : 'pages'
                                        }`,
                                    )
                                }

                                if (document.warning_count > 0) {
                                    detailParts.push(
                                        `${document.warning_count} ${
                                            document.warning_count === 1 ? 'warning' : 'warnings'
                                        }`,
                                    )
                                }

                                return (
                                    <li
                                        key={document.id}
                                        className="flex flex-col gap-2 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-start sm:justify-between sm:gap-6"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium text-ink">
                                                {document.name}
                                            </p>
                                            <p className="mt-0.5 text-xs text-neutral-500">
                                                {detailParts.join(' · ')}
                                            </p>
                                            {document.failure_reason &&
                                                (status === 'failed' || status === 'needs_review') && (
                                                    <p className="mt-1 max-w-2xl break-words text-xs text-amber-800">
                                                        {document.failure_reason}
                                                    </p>
                                                )}
                                        </div>
                                        <span
                                            className={[
                                                'inline-flex w-fit shrink-0 items-center rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                                statusStyles[status],
                                            ].join(' ')}
                                        >
                                            {statusLabels[status]}
                                        </span>
                                    </li>
                                )
                            })}
                        </ul>
                    )}
                </section>
            </div>
        </AppLayout>
    )
}
