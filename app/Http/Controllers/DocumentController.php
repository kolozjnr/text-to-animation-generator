<?php

namespace App\Http\Controllers;

use App\Exceptions\CloudinaryUploadException;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Services\Documents\DocumentIngestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class DocumentController extends Controller
{
    /**
     * Show the conversion workspace and the user's uploaded documents.
     */
    public function index(Request $request, DocumentRepositoryInterface $documents): Response
    {
        $user = $request->user();

        return Inertia::render('Convert', [
            'documents' => $documents->recentForUser($user)->map(fn (Document $document): array => [
                'id' => $document->id,
                'name' => $document->original_name,
                'size_bytes' => $document->size_bytes,
                'extraction_status' => $document->extraction_status->value,
                'page_count' => $document->page_count,
                'warning_count' => count((array) data_get($document->extraction_metadata, 'warnings', [])),
                'failure_reason' => $document->failure_reason,
                'created_at' => $document->created_at?->toIso8601String(),
            ])->values(),
            'maxSizeMb' => round(((int) config('documents.max_size_kb', 2048)) / 1024, 1),
        ]);
    }

    /**
     * Store a newly uploaded source document.
     */
    public function store(StoreDocumentRequest $request, DocumentIngestionService $ingestion): RedirectResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('document');

        try {
            $ingestion->ingest($request->user(), $file);
        } catch (CloudinaryUploadException) {
            Log::warning('Document upload failed.', [
                'user_id' => $request->user()->id,
                'exception' => CloudinaryUploadException::class,
            ]);

            return back()->with('error', 'The document could not be uploaded to private storage. Please try again.');
        } catch (Throwable $exception) {
            Log::error('Document ingestion failed.', [
                'user_id' => $request->user()->id,
                'exception' => $exception::class,
            ]);

            return back()->with('error', 'The document could not be processed. Please try again.');
        }

        return redirect()
            ->route('convert')
            ->with('status', 'Document uploaded. Extraction has been queued.');
    }
}
