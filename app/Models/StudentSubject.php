<?php

namespace App\Models;

use App\Enums\StudentSubjectStatus;
use Database\Factories\StudentSubjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $student_id
 * @property string $subject_id
 * @property string $school_class_id
 * @property string $student_class_enrollment_id
 * @property StudentSubjectStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'student_id',
    'subject_id',
    'school_class_id',
    'student_class_enrollment_id',
    'status',
])]
class StudentSubject extends Model
{
    /** @use HasFactory<StudentSubjectFactory> */
    use HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StudentSubjectStatus::class,
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', StudentSubjectStatus::Active);
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
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
}
