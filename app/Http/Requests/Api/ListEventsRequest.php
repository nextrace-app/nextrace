<?php

namespace App\Http\Requests\Api;

use App\Http\Controllers\EventController as SiteEventController;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListEventsRequest extends FormRequest
{
    /**
     * Access is decided by the api.token middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'locale' => ['nullable', Rule::in(config('app.supported_locales'))],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'distance' => ['nullable', Rule::in(SiteEventController::DISTANCE_FILTERS)],
            'q' => ['nullable', 'string', 'max:'.SiteEventController::MAX_SEARCH_LENGTH],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
