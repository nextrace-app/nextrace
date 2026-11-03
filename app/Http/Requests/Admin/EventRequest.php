<?php

namespace App\Http\Requests\Admin;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    /**
     * The policy is checked in the controller, which knows the event.
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
        $event = $this->route('event');

        $translated = fn (string $field, bool $required = false, int $max = 5000) => [
            $field => [$required ? 'required' : 'nullable', 'array'],
            "{$field}.el" => [$required ? 'required' : 'nullable', 'string', "max:{$max}"],
            "{$field}.en" => [$required ? 'required' : 'nullable', 'string', "max:{$max}"],
        ];

        return [
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('events', 'slug')->ignore($event instanceof Event ? $event->id : null)],
            ...$translated('name', true, 255),
            ...$translated('description'),
            ...$translated('venue', false, 255),
            ...$translated('package_description'),
            ...$translated('cancellation_policy'),
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'city' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'organizer_id' => ['nullable', 'integer', 'exists:organizers,id'],
            'series_id' => ['nullable', 'integer', 'exists:event_series,id'],
            'edition_number' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'status' => ['required', Rule::enum(EventStatus::class)],
            'date_confirmed' => ['boolean'],
            'registration_status' => ['required', Rule::enum(RegistrationStatus::class)],
            'registration_url' => ['nullable', 'url:http,https', 'max:2048'],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date', 'after_or_equal:registration_opens_at'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url:http,https', 'max:2048'],
            'image_url' => ['nullable', 'url:http,https', 'max:2048'],
            'medical_certificate_required' => ['boolean'],
        ];
    }

    /**
     * The validated input with empty translations dropped.
     *
     * @return array<string, mixed>
     */
    public function attributesForModel(): array
    {
        $data = $this->validated();

        foreach (['name', 'description', 'venue', 'package_description', 'cancellation_policy'] as $field) {
            $values = array_filter($data[$field] ?? [], fn ($value) => $value !== null && $value !== '');
            $data[$field] = $values === [] && ! in_array($field, ['name', 'description'], true) ? null : $values;
        }

        return $data;
    }
}
