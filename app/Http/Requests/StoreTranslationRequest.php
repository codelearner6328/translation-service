<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTranslationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'namespace' => ['nullable','string','max:50'],
            'key'       => ['required','string','max:190'],
            'description' => ['nullable','string','max:500'],
            'tags'      => ['array'],
            'tags.*'    => ['string','max:50'],
            'values'    => ['required','array','min:1'],
            'values.*.locale' => ['required','string','max:10'],
            'values.*.value'  => ['required','string'],
        ];
    }
}
