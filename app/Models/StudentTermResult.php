<?php

namespace App\Models;

use App\Observers\StudentTermResultObserver;
use Database\Factories\StudentTermResultFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $student_id
 * @property string $school_class_id
 * @property string $student_class_enrollment_id
 * @property string $academic_year_id
 * @property string $term_id
 * @property int $subjects_count
 * @property string $total_score
 * @property string $average_score
 * @property int|null $class_position
 * @property Carbon $calculated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'student_id',
    'school_class_id',
    'student_class_enrollment_id',
    'academic_year_id',
    'term_id',
    'subjects_count',
    'total_score',
    'average_score',
    'class_position',
    'calculated_at',
])]
#[ObservedBy([StudentTermResultObserver::class])]
class StudentTermResult extends Model
{
    /** @use HasFactory<StudentTermResultFactory> */
    use HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subjects_count' => 'integer',
            'total_score' => 'decimal:2',
            'average_score' => 'decimal:2',
            'class_position' => 'integer',
            'calculated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<SchoolClass, $this>
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    /**
     * @return BelongsTo<StudentClassEnrollment, $this>
     */
    public function classEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentClassEnrollment::class, 'student_class_enrollment_id');
    }

    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * @return BelongsTo<Term, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }
}
