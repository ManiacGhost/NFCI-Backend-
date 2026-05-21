<?php

namespace App\Http\Requests\Asset;

use Illuminate\Foundation\Http\FormRequest;

class UploadAssetRequest extends FormRequest
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
            'page_component_id' => ['required', 'integer', 'exists:page_components,id'],
            'files'             => ['required', 'array', 'min:1', 'max:20'],
            'files.*'           => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,svg,mp4,webm,pdf,doc,docx'],
            'alt_texts'         => ['nullable', 'array'],
            'alt_texts.*'       => ['nullable', 'string', 'max:255'],
            'asset_type'        => ['nullable', 'in:image,video,document,icon'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'files.*.max'   => 'Each file must not exceed 10 MB.',
            'files.*.mimes' => 'Allowed file types: jpg, jpeg, png, gif, webp, svg, mp4, webm, pdf, doc, docx.',
        ];
    }
}
