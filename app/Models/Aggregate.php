<?php

namespace App\Models;

use Database\Factories\AggregateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property string $school_id
 * @property int $min_score
 * @property int $max_score
 * @property string $grade
 * @property string $remarks
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'school_id',
    'min_score',
    'max_score',
    'grade',
    'remarks',
])]
class Aggregate extends Model
{
    /** @use HasFactory<AggregateFactory> */
    use HasFactory, HasUuids;

    /**
     * @return Attribute<string, string>
     */
    protected function remarks(): Attribute
    {
        return Attribute::make(
            get: fn (string $value): string => Str::title($value),
            set: fn (string $value): string => Str::lower(Str::squish($value)),
        );
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForScore(Builder $query, float|int $score): Builder
    {
        return $query
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score);
    }

    public static function findForScore(float|int $score, string $schoolId): ?self
    {
        return self::query()
            ->where('school_id', $schoolId)
            ->forScore($score)
            ->first();
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
