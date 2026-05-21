<?php

namespace App\Http\Requests\Page;

use Illuminate\Foundation\Http\FormRequest;

class StorePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'page_number'      => ['required', 'integer', 'min:1', 'unique:pages,page_number'],
            'title'            => ['required', 'string', 'max:255'],
            'slug'             => ['nullable', 'string', 'max:255', 'unique:pages,slug'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'status'           => ['nullable', 'in:active,draft,archived'],
            'components'       => ['nullable', 'array'],
            'components.*'     => ['string'],  // e.g. "CTA 2", "IMGGAL 3"
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'page_number.unique' => 'A page with this number already exists.',
            'slug.unique'        => 'A page with this slug already exists.',
        ];
    }
}
