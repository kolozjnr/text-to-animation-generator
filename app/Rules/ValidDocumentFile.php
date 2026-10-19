<?php

namespace App\Rules;

use App\Exceptions\InvalidDocumentException;
use App\Services\Documents\DocumentFileInspector;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Throwable;

class ValidDocumentFile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('The upload must be a valid file.');

            return;
        }

        try {
            app(DocumentFileInspector::class)->inspect($value);
        } catch (InvalidDocumentException $exception) {
            $fail($exception->getMessage());
        } catch (Throwable) {
            $fail('The document could not be validated.');
        }
    }
}
