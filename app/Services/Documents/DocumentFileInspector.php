<?php

namespace App\Services\Documents;

use App\Exceptions\InvalidDocumentException;
use Illuminate\Http\UploadedFile;
use ZipArchive;

class DocumentFileInspector
{
    public function inspect(UploadedFile $file): void
    {
        $path = $file->getRealPath();

        if ($path === false || ! is_file($path)) {
            throw new InvalidDocumentException('The uploaded file could not be read.');
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());

        $this->inspectPath($path, $extension);
    }

    public function inspectPath(string $path, string $extension): void
    {
        if (! is_file($path) || filesize($path) === 0) {
            throw new InvalidDocumentException('The uploaded file is empty.');
        }

        $extension = strtolower($extension);

        match ($extension) {
            'pdf' => $this->inspectPdf($path),
            'docx' => $this->inspectDocx($path),
            'txt' => $this->inspectTxt($path),
            default => throw new InvalidDocumentException('Only PDF, DOCX, and TXT files are supported.'),
        };
    }

    private function inspectPdf(string $path): void
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new InvalidDocumentException('The PDF could not be read.');
        }

        try {
            $header = fread($handle, 1024);
        } finally {
            fclose($handle);
        }

        if (! is_string($header) || ! str_contains($header, '%PDF-')) {
            throw new InvalidDocumentException('The file is not a valid PDF document.');
        }
    }

    private function inspectDocx(string $path): void
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new InvalidDocumentException('The file is not a valid DOCX document.');
        }

        try {
            $maxEntries = max(1, (int) config('documents.docx.max_entries', 2000));
            $maxUncompressed = max(1, (int) config('documents.docx.max_uncompressed_bytes', 104_857_600));
            $maxRatio = max(1, (int) config('documents.docx.max_compression_ratio', 100));

            if ($zip->numFiles > $maxEntries) {
                throw new InvalidDocumentException('The DOCX contains too many archive entries.');
            }

            $uncompressed = 0;
            $compressed = 0;

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);

                if ($stat === false) {
                    continue;
                }

                $uncompressed += (int) ($stat['size'] ?? 0);
                $compressed += (int) ($stat['comp_size'] ?? 0);
            }

            if ($uncompressed > $maxUncompressed) {
                throw new InvalidDocumentException('The DOCX expands beyond the allowed size.');
            }

            if ($uncompressed > 0 && ($uncompressed / max(1, $compressed)) > $maxRatio) {
                throw new InvalidDocumentException('The DOCX compression ratio is unsafe.');
            }

            if ($zip->locateName('[Content_Types].xml') === false
                || $zip->locateName('word/document.xml') === false) {
                throw new InvalidDocumentException('The DOCX is missing required document parts.');
            }

            if ($zip->locateName('word/vbaProject.bin') !== false) {
                throw new InvalidDocumentException('Macro-enabled DOCX files are not supported.');
            }

            $documentXml = $zip->getFromName('word/document.xml');

            if ($documentXml === false || trim($documentXml) === '') {
                throw new InvalidDocumentException('The DOCX document body is empty.');
            }

            $this->rejectExternalDocxRelationships($zip);
        } finally {
            $zip->close();
        }
    }

    private function rejectExternalDocxRelationships(ZipArchive $zip): void
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if ($name === false || ! str_ends_with($name, '.rels')) {
                continue;
            }

            $contents = $zip->getFromName($name);

            if ($contents !== false && preg_match('/TargetMode\s*=\s*["\']External["\']/i', $contents) === 1) {
                throw new InvalidDocumentException('The DOCX contains unsupported external links.');
            }
        }
    }

    private function inspectTxt(string $path): void
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new InvalidDocumentException('The text file could not be read.');
        }

        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            $contents = substr($contents, 3);
        } elseif (str_starts_with($contents, "\xFF\xFE")) {
            $converted = mb_convert_encoding(substr($contents, 2), 'UTF-8', 'UTF-16LE');

            if (! is_string($converted)) {
                throw new InvalidDocumentException('The text file encoding is not supported.');
            }

            $contents = $converted;
        } elseif (str_starts_with($contents, "\xFE\xFF")) {
            $converted = mb_convert_encoding(substr($contents, 2), 'UTF-8', 'UTF-16BE');

            if (! is_string($converted)) {
                throw new InvalidDocumentException('The text file encoding is not supported.');
            }

            $contents = $converted;
        }

        if (! mb_check_encoding($contents, 'UTF-8')) {
            throw new InvalidDocumentException('The text file must use UTF-8 or UTF-16 encoding.');
        }

        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', $contents) === 1) {
            throw new InvalidDocumentException('The text file contains binary or unsupported control characters.');
        }

        if (trim($contents) === '') {
            throw new InvalidDocumentException('The document cannot be empty.');
        }
    }
}
