<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\RacePriceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * What it costs to enter a race, optionally only within a period.
 *
 * @property int $id
 * @property int $race_id
 * @property array<string, string>|null $label
 * @property int $amount_cents
 * @property string $currency
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_until
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['race_id', 'label', 'amount_cents', 'currency', 'valid_from', 'valid_until'])]
class RacePrice extends Model
{
    /** @use HasFactory<RacePriceFactory> */
    use HasFactory;

    use HasTranslations;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => 'EUR',
    ];

    /**
     * The race the price is for.
     *
     * @return BelongsTo<Race, $this>
     */
    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'label' => 'array',
            'amount_cents' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }
}
