<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Docling Serve
    |--------------------------------------------------------------------------
    |
    | PDF and DOCX extraction is delegated to a separate docling-serve
    | process. The service is called from a queued Laravel job, never from
    | the upload HTTP request.
    |
    */

    'base_url' => rtrim((string) env('DOCLING_SERVE_URL', 'http://127.0.0.1:5001'), '/'),

    'api_key' => env('DOCLING_SERVE_API_KEY'),

    'timeout' => (int) env('DOCLING_TIMEOUT_SECONDS', 240),

    'connect_timeout' => (int) env('DOCLING_CONNECT_TIMEOUT_SECONDS', 5),

    'document_timeout' => (int) env('DOCLING_DOCUMENT_TIMEOUT_SECONDS', 180),

    'max_pages' => (int) env('DOCLING_MAX_PAGES', 200),

    'ocr_enabled' => (bool) env('DOCLING_OCR_ENABLED', false),

    'ocr_lang' => (string) env('DOCLING_OCR_LANG', 'en'),

    'table_structure' => (bool) env('DOCLING_TABLE_STRUCTURE', true),

    'heading_hierarchy' => (bool) env('DOCLING_HEADING_HIERARCHY', true),

    'verify_ssl' => (bool) env('DOCLING_VERIFY_SSL', true),

];
