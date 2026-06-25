<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ensure only the note's owner can update it
        return $this->user()->id === $this->route('note')->user_id;
    }

    public function rules(): array
    {
        return [
            'title'          => ['sometimes', 'string', 'max:255'],
            'content'        => ['sometimes', 'string'],
            'content_format' => ['sometimes', 'in:html,markdown'],
            'color'          => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_pinned'      => ['sometimes', 'boolean'],
            'x_position'     => ['sometimes', 'numeric'],
            'y_position'     => ['sometimes', 'numeric'],
            'tag_ids'        => ['sometimes', 'array', 'max:5'],
            'tag_ids.*'      => ['integer', 'exists:tags,id'],
        ];
    }
}
