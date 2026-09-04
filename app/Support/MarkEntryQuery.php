<?php

namespace App\Support;

use App\Enums\EnrollmentStatus;
use App\Enums\MarkEntryKind;
use App\Enums\StudentSubjectStatus;
use App\Http\Requests\Api\Mark\ListMarkEntryRequest;
use App\Models\Mark;
use App\Models\School;
use App\Models\StudentSubject;
use App\Models\Term;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

class MarkEntryQuery
{
    /**
     * @return Collection<int, array{studentSubject: StudentSubject, academicYearId: string, termId: string, mark: Mark|null}>
     */
    public function get(
        ListMarkEntryRequest $request,
        School $school,
        MarkEntryKind $kind,
        bool $recorded,
    ): Collection {
        $term = Term::query()
            ->whereKey($request->validated('termId'))
            ->firstOrFail(['id', 'academic_year_id']);

        $studentSubjects = StudentSubject::query()
            ->where('student_subjects.status', StudentSubjectStatus::Active)
            ->where('student_subjects.school_class_id', $request->validated('schoolClassId'))
            ->where('student_subjects.subject_id', $request->validated('subjectId'))
            ->join('students', 'students.id', '=', 'student_subjects.student_id')
            ->where('students.school_id', $school->id)
            ->join('student_class_enrollments', function (JoinClause $join) use ($term): void {
                $join->on('student_class_enrollments.id', '=', 'student_subjects.student_class_enrollment_id')
                    ->where('student_class_enrollments.status', EnrollmentStatus::Active)
                    ->where('student_class_enrollments.academic_year_id', $term->academic_year_id);
            })
            ->{$recorded ? 'join' : 'leftJoin'}('marks', function (JoinClause $join) use ($school, $term): void {
                $join->on('marks.student_class_enrollment_id', '=', 'student_subjects.student_class_enrollment_id')
                    ->on('marks.subject_id', '=', 'student_subjects.subject_id')
                    ->where('marks.term_id', '=', $term->id)
                    ->where('marks.school_id', '=', $school->id);
            });

        $studentSubjects = $this->applyEntryScope($studentSubjects, $kind, $recorded)
            ->orderBy('students.last_name')
            ->orderBy('students.first_name')
            ->select([
                'student_subjects.*',
                'student_class_enrollments.academic_year_id as entry_academic_year_id',
                'marks.id as entry_mark_id',
            ])
            ->with('student')
            ->get();

        $marks = Mark::query()
            ->select([
                'id',
                'participated',
                'class_score',
                'home_assignment_score',
                'project_score',
                'class_test_score',
                'continuous_assessment_score',
                'continuous_assessment_contribution',
                'exam_score',
                'exam_contribution',
                'total_score',
                'class_score_updated_at',
                'exam_score_updated_at',
                'close_class_score_entry',
                'close_exam_score_entry',
                'grade',
                'grade_remark',
                'teacher_id',
            ])
            ->whereIn('id', $studentSubjects->pluck('entry_mark_id')->filter())
            ->get()
            ->keyBy('id');

        return $studentSubjects->map(fn (StudentSubject $studentSubject) => [
            'studentSubject' => $studentSubject,
            'academicYearId' => $studentSubject->entry_academic_year_id,
            'termId' => $term->id,
            'mark' => $studentSubject->entry_mark_id === null
                ? null
                : $marks->get($studentSubject->entry_mark_id),
        ]);
    }

    /**
     * @param  Builder<StudentSubject>  $query
     * @return Builder<StudentSubject>
     */
    protected function applyEntryScope(Builder $query, MarkEntryKind $kind, bool $recorded): Builder
    {
        if ($kind === MarkEntryKind::ClassScore) {
            if ($recorded) {
                return $query
                    ->where('marks.close_class_score_entry', false)
                    ->where('marks.continuous_assessment_contribution', '>', 0);
            }

            return $query->where(function (Builder $query): void {
                $query->whereNull('marks.id')
                    ->orWhere(function (Builder $query): void {
                        $query->where('marks.close_class_score_entry', false)
                            ->where('marks.continuous_assessment_contribution', '<=', 0);
                    });
            });
        }

        if ($recorded) {
            return $query
                ->where('marks.close_exam_score_entry', false)
                ->where('marks.exam_score', '>', 0);
        }

        return $query->where(function (Builder $query): void {
            $query->whereNull('marks.id')
                ->orWhere(function (Builder $query): void {
                    $query->where('marks.close_exam_score_entry', false)
                        ->where('marks.exam_score', '<=', 0);
                });
        });
    }
}
