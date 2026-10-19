<?php

namespace App\Http\Requests;

use App\Rules\ValidDocumentFile;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $maxSizeKb = max(1, (int) config('documents.max_size_kb', 2048));
        $extensions = implode(',', (array) config('documents.allowed_extensions', ['pdf', 'docx', 'txt']));

        return [
            'document' => [
                'required',
                'file',
                // File size rules are expressed in kilobytes; 0.001 KB is one byte.
                'min:0.001',
                'max:'.$maxSizeKb,
                'extensions:'.$extensions,
                'mimes:'.$extensions,
                new ValidDocumentFile,
            ],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document.required' => 'Choose a document to upload.',
            'document.file' => 'The upload must be a valid file.',
            'document.min' => 'The document cannot be empty.',
            'document.max' => 'The document must not be larger than :max kilobytes.',
            'document.extensions' => 'Only PDF, DOCX, and TXT files are supported.',
            'document.mimes' => 'Only PDF, DOCX, and TXT files are supported.',
        ];
    }
}
