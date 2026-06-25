<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth is handled by middleware
    }

    public function rules(): array
    {
        return [
            'title'          => ['required', 'string', 'max:255'],
            'content'        => ['nullable', 'string'],
            'content_format' => ['sometimes', 'in:html,markdown'],
            'color'          => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_pinned'      => ['sometimes', 'boolean'],
            'tag_ids'        => ['sometimes', 'array', 'max:5'],
            'tag_ids.*'      => ['integer', 'exists:tags,id'],
        ];
    }
}
