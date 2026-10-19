<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Document Storage
    |--------------------------------------------------------------------------
    |
    | Uploaded originals are stored in private Cloudinary storage. The
    | extraction disk below holds the private JSON artifacts that are
    | generated from those originals.
    |
    */

    'disk' => env('DOCUMENTS_DISK', 'local'),

    'directory' => 'documents',

    /*
    |--------------------------------------------------------------------------
    | Upload Limits
    |--------------------------------------------------------------------------
    |
    | The default matches the current PHP upload_max_filesize of 2 MB. Raise
    | DOCUMENTS_MAX_SIZE_KB only after the PHP upload and POST limits have
    | been increased in the deployment environment.
    |
    */

    'max_size_kb' => (int) env('DOCUMENTS_MAX_SIZE_KB', 2048),

    /*
    |--------------------------------------------------------------------------
    | Supported Formats
    |--------------------------------------------------------------------------
    */

    'allowed_extensions' => ['pdf', 'docx', 'txt'],

    'allowed_mime_types' => [
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'txt' => 'text/plain',
    ],

    /*
    |--------------------------------------------------------------------------
    | Extraction
    |--------------------------------------------------------------------------
    |
    | Extracted content is stored as a private JSON artifact. The database
    | keeps only a reference and summary metadata.
    |
    */

    'queue' => env('DOCUMENTS_QUEUE', 'documents'),

    'extraction' => [
        'disk' => env('DOCUMENTS_EXTRACTION_DISK', env('DOCUMENTS_DISK', 'local')),
        'directory' => env('DOCUMENTS_EXTRACTION_DIRECTORY', 'extractions'),
        'min_text_characters' => (int) env('DOCUMENTS_MIN_TEXT_CHARACTERS', 20),
        'max_artifact_bytes' => (int) env('DOCUMENTS_MAX_ARTIFACT_BYTES', 10_485_760),
        'txt_encodings' => ['UTF-8', 'UTF-16LE', 'UTF-16BE'],
    ],

    /*
    |--------------------------------------------------------------------------
    | DOCX Safety Limits
    |--------------------------------------------------------------------------
    */

    'docx' => [
        'max_entries' => (int) env('DOCUMENTS_DOCX_MAX_ENTRIES', 2_000),
        'max_uncompressed_bytes' => (int) env('DOCUMENTS_DOCX_MAX_UNCOMPRESSED_BYTES', 104_857_600),
        'max_compression_ratio' => (int) env('DOCUMENTS_DOCX_MAX_COMPRESSION_RATIO', 100),
    ],

];
