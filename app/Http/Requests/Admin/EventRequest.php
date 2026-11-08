<?php

namespace App\Http\Requests\Admin;

use App\Enums\EventStatus;
use App\Enums\LinkType;
use App\Enums\RaceDiscipline;
use App\Enums\RaceStatus;
use App\Enums\RaceTerrain;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

            'races' => ['required', 'array', 'min:1'],
            'races.*.slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'distinct'],
            'races.*.name' => ['required', 'array'],
            'races.*.name.el' => ['required', 'string', 'max:255'],
            'races.*.name.en' => ['required', 'string', 'max:255'],
            'races.*.is_main' => ['boolean'],
            'races.*.discipline' => ['required', Rule::enum(RaceDiscipline::class)],
            'races.*.terrain' => ['nullable', Rule::enum(RaceTerrain::class)],
            'races.*.distance_meters' => ['required', 'integer', 'min:1', 'max:1000000'],
            'races.*.elevation_gain_m' => ['nullable', 'integer', 'min:0', 'max:20000'],
            'races.*.start_location' => ['nullable', 'array'],
            'races.*.start_location.el' => ['nullable', 'string', 'max:255'],
            'races.*.start_location.en' => ['nullable', 'string', 'max:255'],
            'races.*.finish_location' => ['nullable', 'array'],
            'races.*.finish_location.el' => ['nullable', 'string', 'max:255'],
            'races.*.finish_location.en' => ['nullable', 'string', 'max:255'],
            'races.*.time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:20000'],
            'races.*.min_age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'races.*.participant_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'races.*.aid_stations' => ['nullable', 'integer', 'min:0', 'max:500'],
            'races.*.medical_certificate_required' => ['nullable', 'boolean'],
            'races.*.route_url' => ['nullable', 'url:http,https', 'max:2048'],
            'races.*.starts_at' => ['required', 'date'],
            'races.*.status' => ['required', Rule::enum(RaceStatus::class)],
            'races.*.registration_url' => ['nullable', 'url:http,https', 'max:2048'],
            'races.*.prices' => ['nullable', 'array'],
            'races.*.prices.*.label' => ['nullable', 'array'],
            'races.*.prices.*.label.el' => ['nullable', 'string', 'max:255'],
            'races.*.prices.*.label.en' => ['nullable', 'string', 'max:255'],
            'races.*.prices.*.amount_cents' => ['required', 'integer', 'min:0', 'max:100000000'],
            'races.*.prices.*.valid_from' => ['nullable', 'date'],
            'races.*.prices.*.valid_until' => ['nullable', 'date', 'after_or_equal:races.*.prices.*.valid_from'],

            'links' => ['nullable', 'array'],
            'links.*.type' => ['required', Rule::enum(LinkType::class)],
            'links.*.url' => ['required', 'url:http,https', 'max:2048'],
            'links.*.label' => ['nullable', 'array'],
            'links.*.label.el' => ['nullable', 'string', 'max:255'],
            'links.*.label.en' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Exactly one race is the main race, as the model expects.
     */
    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $mains = count(array_filter($this->array('races'), fn ($race) => is_array($race) && ($race['is_main'] ?? false) === true));

            if ($mains !== 1) {
                $validator->errors()->add('races', trans('ui.admin.events.main_race_error'));
            }
        }];
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

        $label = fn (mixed $value) => array_filter(is_array($value) ? $value : [], fn ($text) => $text !== null && $text !== '') ?: null;

        $data['races'] = array_map(function (array $race) use ($label): array {
            $race['start_location'] = $label($race['start_location'] ?? null);
            $race['finish_location'] = $label($race['finish_location'] ?? null);
            $race['prices'] = array_map(fn (array $price) => [...$price, 'label' => $label($price['label'] ?? null)], $race['prices'] ?? []);

            return $race;
        }, $data['races']);

        $data['links'] = array_map(fn (array $link) => [...$link, 'label' => $label($link['label'] ?? null)], $data['links'] ?? []);

        return $data;
    }
}
