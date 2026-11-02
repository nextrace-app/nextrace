<?php

namespace App\Http\Requests\Admin;

use App\Models\EventSeries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SeriesRequest extends FormRequest
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
        $series = $this->route('series');

        return [
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('event_series', 'slug')->ignore($series instanceof EventSeries ? $series->id : null)],
            'name' => ['required', 'array'],
            'name.el' => ['required', 'string', 'max:255'],
            'name.en' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.el' => ['nullable', 'string', 'max:5000'],
            'description.en' => ['nullable', 'string', 'max:5000'],
            'logo_url' => ['nullable', 'url:http,https', 'max:2048'],
            'website' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForModel(): array
    {
        $data = $this->validated();
        $description = array_filter($data['description'] ?? [], fn ($value) => $value !== null && $value !== '');

        return [
            'slug' => $data['slug'],
            'name' => array_filter($data['name'], fn ($value) => $value !== null && $value !== ''),
            'description' => $description === [] ? null : $description,
            'logo_url' => $data['logo_url'] ?? null,
            'website' => $data['website'] ?? null,
        ];
    }
}
