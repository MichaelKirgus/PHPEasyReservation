<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FormFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('form_field')?->id;

        return [
            'key' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                'string',
                'max:255',
                Rule::unique('form_fields', 'key')->ignore($id),
            ],
            'label' => [$this->isMethod('post') ? 'required' : 'sometimes', 'string', 'max:255'],
            'type' => [$this->isMethod('post') ? 'required' : 'sometimes', 'string', 'max:50'],
            'required' => ['sometimes', 'boolean'],
            'options' => ['sometimes', 'nullable', 'array'],
            'placeholder' => ['sometimes', 'nullable', 'string', 'max:255'],
            'help_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'text_align' => ['sometimes', 'nullable', 'string', 'max:10'],
            'min_length' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'max_length' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'pattern' => ['sometimes', 'nullable', 'string', 'max:255'],
            'order' => ['sometimes', 'integer', 'min:0'],
            'active' => ['sometimes', 'boolean'],
            'visible_public' => ['sometimes', 'boolean'],
            'visible_admin' => ['sometimes', 'boolean'],
            'is_email' => ['sometimes', 'boolean'],
        ];
    }
}
