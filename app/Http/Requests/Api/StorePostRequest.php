<?php

namespace App\Http\Requests\Api;

use App\Enums\PostCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A post handed in through the API. It is always saved as a draft, so the
 * fields that would publish it are refused rather than quietly ignored.
 */
class StorePostRequest extends FormRequest
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
            'locale' => ['required', Rule::in(config('app.supported_locales'))],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:200000'],
            'category' => ['required', Rule::enum(PostCategory::class)],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('posts', 'slug')],
            'source_url' => ['nullable', 'url:http,https', 'max:2048', Rule::unique('posts', 'source_url')],
            'cover_image_url' => ['nullable', 'url:http,https', 'max:2048'],
            'cover_image_alt' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'event_ids' => ['nullable', 'array', 'max:50'],
            'event_ids.*' => ['integer', 'distinct', Rule::exists('events', 'id')->whereNull('deleted_at')],

            // A post made through the API is always a draft that belongs to the owner of the token.
            'status' => ['prohibited'],
            'published_at' => ['prohibited'],
            'author_id' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.prohibited' => 'Posts created through the API are always drafts; publish them on the site.',
            'published_at.prohibited' => 'Posts created through the API are always drafts; publish them on the site.',
            'author_id.prohibited' => 'The author is the owner of the token.',
            'source_url.unique' => 'A post with this source_url already exists.',
        ];
    }
}
