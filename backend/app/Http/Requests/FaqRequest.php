<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCreate = $this->isMethod('post');

        return [
            'question' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:500'],
            'answer' => [$isCreate ? 'required' : 'sometimes', 'string'],
            'is_published' => ['sometimes', 'boolean'],
            'position' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'published_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
