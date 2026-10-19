<?php

namespace App\Support;

use App\Exceptions\CloudinaryUploadException;
use App\Exceptions\DocumentExtractionException;
use App\Exceptions\InvalidDocumentException;
use Throwable;

final class FailureReason
{
    public static function fromThrowable(Throwable $exception): string
    {
        if ($exception instanceof CloudinaryUploadException
            || $exception instanceof DocumentExtractionException
            || $exception instanceof InvalidDocumentException) {
            return self::sanitize($exception->getMessage());
        }

        return 'The document could not be processed.';
    }

    public static function sanitize(string $message): string
    {
        $message = strip_tags($message);
        $message = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $message) ?? '';
        $message = preg_replace(
            '#(https?://|file://|api_key=\S+|api_secret=\S+|signature=\S+|secret=\S+)\S*#i',
            '[redacted]',
            $message,
        ) ?? '';
        $message = preg_replace('#\b[A-Za-z]:\\\\[^\s]+#', '[redacted]', $message) ?? '';
        $message = preg_replace(
            '#(?<!\w)/(?:home|var|tmp|opt|usr|srv|mnt|root|private)/[^\s]+#i',
            '[redacted]',
            $message,
        ) ?? '';
        $message = trim(preg_replace('/\s+/u', ' ', $message) ?? '');

        if ($message === '') {
            return 'The document could not be processed.';
        }

        return mb_strlen($message) > 500
            ? mb_substr($message, 0, 499).'…'
            : $message;
    }
}
