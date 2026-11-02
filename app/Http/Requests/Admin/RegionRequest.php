<?php

namespace App\Http\Requests\Admin;

use App\Models\Region;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegionRequest extends FormRequest
{
    /**
     * The policy is checked in the controller, which knows the record.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $region = $this->route('region');
        $ownId = $region instanceof Region ? $region->id : null;
        $forbidden = $region instanceof Region ? $region->withDescendantIds() : [];

        return [
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('regions', 'slug')->ignore($ownId)],
            'name' => ['required', 'array'],
            'name.el' => ['required', 'string', 'max:255'],
            'name.en' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:regions,id', Rule::notIn($forbidden)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForModel(): array
    {
        $data = $this->validated();

        return [
            'slug' => $data['slug'],
            'name' => array_filter($data['name'], fn ($value) => $value !== null && $value !== ''),
            'parent_id' => $data['parent_id'] ?? null,
        ];
    }
}
