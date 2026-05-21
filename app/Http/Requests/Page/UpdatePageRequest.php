<?php

namespace App\Http\Requests\Page;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePageRequest extends FormRequest
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
        $pageNumber = $this->route('pageNumber');

        return [
            'title'            => ['sometimes', 'string', 'max:255'],
            'slug'             => ['sometimes', 'string', 'max:255', "unique:pages,slug,{$pageNumber},page_number"],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'status'           => ['sometimes', 'in:active,draft,archived'],
        ];
    }
}
