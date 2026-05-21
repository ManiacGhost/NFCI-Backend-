<?php

namespace App\Http\Requests\Page;

use Illuminate\Foundation\Http\FormRequest;

class AttachComponentsRequest extends FormRequest
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
            'components'                    => ['required', 'array', 'min:1'],
            'components.*.component_id'     => ['required', 'string'],  // compound id like "CTA 2"
            'components.*.sort_order'       => ['nullable', 'integer', 'min:0'],
            'components.*.config_overrides' => ['nullable', 'array'],
            'components.*.is_visible'       => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'components.required'                  => 'At least one component must be provided.',
            'components.*.component_id.required'   => 'Each component must have a component_id (e.g. "CTA 2").',
        ];
    }
}
