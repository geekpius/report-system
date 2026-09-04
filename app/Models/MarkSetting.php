<?php

namespace App\Models;

use App\Enums\ScoringMode;
use App\Observers\MarkSettingObserver;
use Database\Factories\MarkSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $school_id
 * @property ScoringMode $scoring_mode
 * @property string $class_score_percent
 * @property string $exam_score_percent
 * @property string $class_score_max
 * @property string $home_assignment_max
 * @property string $project_max
 * @property string $class_test_max
 * @property string $division_total
 * @property string $division_total_percent
 * @property string $exam_allocation_percent
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'school_id',
    'scoring_mode',
    'class_score_percent',
    'exam_score_percent',
    'class_score_max',
    'home_assignment_max',
    'project_max',
    'class_test_max',
    'exam_allocation_percent',
])]
#[ObservedBy([MarkSettingObserver::class])]
class MarkSetting extends Model
{
    /** @use HasFactory<MarkSettingFactory> */
    use HasFactory, HasUuids;

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'scoring_mode' => ScoringMode::TotalScore,
            'class_score_percent' => 50,
            'exam_score_percent' => 50,
            'class_score_max' => 0,
            'home_assignment_max' => 0,
            'project_max' => 0,
            'class_test_max' => 0,
            'exam_allocation_percent' => 50,
        ];
    }

    public static function resolveForSchool(School $school): self
    {
        return $school->markSetting()->firstOrCreate([], self::defaults());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scoring_mode' => ScoringMode::class,
            'class_score_percent' => 'decimal:2',
            'exam_score_percent' => 'decimal:2',
            'class_score_max' => 'decimal:2',
            'home_assignment_max' => 'decimal:2',
            'project_max' => 'decimal:2',
            'class_test_max' => 'decimal:2',
            'division_total' => 'decimal:2',
            'division_total_percent' => 'decimal:2',
            'exam_allocation_percent' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
