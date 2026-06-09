<?php

namespace App\Http\Requests\Note;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('user_id', $this->user()?->id),
            ],
            'title' => ['required', 'string', 'min:1', 'max:150'],
            'content' => ['nullable', 'string', 'max:65535'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('title') && is_string($this->input('title'))) {
            $data['title'] = trim($this->input('title'));
        }

        if ($this->has('content') && is_string($this->input('content'))) {
            $data['content'] = trim($this->input('content'));
        }

        $this->merge($data);
    }
}
