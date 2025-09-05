<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'namespace'       => ['nullable', 'string', 'max:50'],
            'key'             => ['nullable', 'string', 'max:190'], // was required
            'description'     => ['nullable', 'string', 'max:500'],
            'tags'            => ['nullable', 'array'], // made optional
            'tags.*'          => ['string', 'max:50'],
            'values'          => ['nullable', 'array'], // was required
            'values.*.locale' => ['nullable', 'string', 'max:10'], // was required
            'values.*.value'  => ['nullable', 'string'], // was required
        ];
    }
}
