<?php

namespace App\Models;

use App\Observers\MarkObserver;
use Database\Factories\MarkFactory;
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
 * @property string $student_id
 * @property string $subject_id
 * @property string $school_class_id
 * @property string $student_class_enrollment_id
 * @property string $academic_year_id
 * @property string $term_id
 * @property bool $participated
 * @property string $class_score
 * @property string $home_assignment_score
 * @property string $project_score
 * @property string $class_test_score
 * @property string $continuous_assessment_score
 * @property string $continuous_assessment_contribution
 * @property string $exam_score
 * @property string $exam_contribution
 * @property string $total_score
 * @property Carbon|null $class_score_updated_at
 * @property Carbon|null $exam_score_updated_at
 * @property bool $close_class_score_entry
 * @property bool $close_exam_score_entry
 * @property string|null $grade
 * @property string|null $grade_remark
 * @property string|null $teacher_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'school_id',
    'student_id',
    'subject_id',
    'school_class_id',
    'student_class_enrollment_id',
    'academic_year_id',
    'term_id',
    'participated',
    'class_score',
    'home_assignment_score',
    'project_score',
    'class_test_score',
    'exam_score',
    'teacher_id',
])]
#[ObservedBy([MarkObserver::class])]
class Mark extends Model
{
    /** @use HasFactory<MarkFactory> */
    use HasFactory, HasUuids;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'participated' => true,
        'close_class_score_entry' => false,
        'close_exam_score_entry' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'participated' => 'boolean',
            'class_score' => 'decimal:2',
            'home_assignment_score' => 'decimal:2',
            'project_score' => 'decimal:2',
            'class_test_score' => 'decimal:2',
            'continuous_assessment_score' => 'decimal:2',
            'continuous_assessment_contribution' => 'decimal:2',
            'exam_score' => 'decimal:2',
            'exam_contribution' => 'decimal:2',
            'total_score' => 'decimal:2',
            'class_score_updated_at' => 'datetime',
            'exam_score_updated_at' => 'datetime',
            'close_class_score_entry' => 'boolean',
            'close_exam_score_entry' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
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

    /**
     * @return BelongsTo<Teacher, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
