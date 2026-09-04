<?php

namespace Tests\Feature\Api;

use App\Enums\EnrollmentStatus;
use App\Enums\ScoringMode;
use App\Enums\StudentSubjectStatus;
use App\Models\AcademicYear;
use App\Models\Aggregate;
use App\Models\Client;
use App\Models\Mark;
use App\Models\MarkSetting;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\Term;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{owner: Client, school: School, student: Student, schoolClass: SchoolClass, academicYear: AcademicYear, term: Term, subject: Subject, enrollment: StudentClassEnrollment, token: string}
     */
    protected function setupMarkContext(): array
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        MarkSetting::factory()->division()->create(['school_id' => $school->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id]);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id]);
        $term = Term::factory()->create(['academic_year_id' => $academicYear->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Mathematics']);
        $enrollment = StudentClassEnrollment::factory()->create([
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $academicYear->id,
            'status' => EnrollmentStatus::Active,
        ]);
        StudentSubject::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'school_class_id' => $schoolClass->id,
            'student_class_enrollment_id' => $enrollment->id,
            'status' => StudentSubjectStatus::Active,
        ]);
        Aggregate::factory()->create([
            'school_id' => $school->id,
            'min_score' => 80,
            'max_score' => 100,
            'grade' => 'A1',
            'remarks' => 'Excellent',
        ]);

        return [
            'owner' => $owner,
            'school' => $school,
            'student' => $student,
            'schoolClass' => $schoolClass,
            'academicYear' => $academicYear,
            'term' => $term,
            'subject' => $subject,
            'enrollment' => $enrollment,
            'token' => $owner->createToken('api-owner', ['permit:owner'])->plainTextToken,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function classPayload(array $context, array $overrides = []): array
    {
        return array_merge([
            'studentId' => $context['student']->id,
            'subjectId' => $context['subject']->id,
            'schoolClassId' => $context['schoolClass']->id,
            'studentClassEnrollmentId' => $context['enrollment']->id,
            'academicYearId' => $context['academicYear']->id,
            'termId' => $context['term']->id,
            'classScore' => 12,
            'homeAssignmentScore' => 14,
            'projectScore' => 13,
            'classTestScore' => 15,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function examPayload(array $context, array $overrides = []): array
    {
        return array_merge([
            'studentId' => $context['student']->id,
            'subjectId' => $context['subject']->id,
            'schoolClassId' => $context['schoolClass']->id,
            'studentClassEnrollmentId' => $context['enrollment']->id,
            'academicYearId' => $context['academicYear']->id,
            'termId' => $context['term']->id,
            'participated' => true,
            'examScore' => 80,
        ], $overrides);
    }

    public function test_owners_can_create_class_marks_without_an_exam_score(): void
    {
        $context = $this->setupMarkContext();

        $response = $this->withToken($context['token'])
            ->postJson(route('api.schools.class-marks.store', $context['school']), $this->classPayload($context))
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.schoolId', $context['school']->id)
            ->assertJsonPath('data.participated', true)
            ->assertJsonPath('data.continuousAssessmentScore', 54)
            ->assertJsonPath('data.continuousAssessmentContribution', 45)
            ->assertJsonPath('data.examScore', 0)
            ->assertJsonPath('data.examContribution', 0)
            ->assertJsonPath('data.totalScore', 45)
            ->assertJsonPath('data.examScoreUpdatedAt', null)
            ->assertJsonPath('data.subject.name', 'Mathematics');

        $this->assertNotNull($response->json('data.classScoreUpdatedAt'));

        $this->assertDatabaseHas('marks', [
            'school_id' => $context['school']->id,
            'student_id' => $context['student']->id,
            'exam_score' => 0.00,
            'total_score' => 45.00,
        ]);
    }

    public function test_owners_can_add_exam_marks_to_an_existing_class_mark(): void
    {
        $context = $this->setupMarkContext();

        $this->withToken($context['token'])
            ->postJson(route('api.schools.class-marks.store', $context['school']), $this->classPayload($context))
            ->assertCreated();

        $response = $this->withToken($context['token'])
            ->putJson(route('api.schools.exam-marks.upsert', $context['school']), $this->examPayload($context))
            ->assertOk()
            ->assertJsonPath('data.examScore', 80)
            ->assertJsonPath('data.examContribution', 40)
            ->assertJsonPath('data.continuousAssessmentContribution', 45)
            ->assertJsonPath('data.totalScore', 85)
            ->assertJsonPath('data.grade', 'A1')
            ->assertJsonPath('data.gradeRemark', 'Excellent');

        $this->assertNotNull($response->json('data.classScoreUpdatedAt'));
        $this->assertNotNull($response->json('data.examScoreUpdatedAt'));
    }

    public function test_owners_can_create_exam_marks_when_no_class_mark_exists(): void
    {
        $context = $this->setupMarkContext();

        $response = $this->withToken($context['token'])
            ->putJson(route('api.schools.exam-marks.upsert', $context['school']), $this->examPayload($context))
            ->assertCreated()
            ->assertJsonPath('data.examScore', 80)
            ->assertJsonPath('data.examContribution', 40)
            ->assertJsonPath('data.classScore', 0)
            ->assertJsonPath('data.continuousAssessmentScore', 0)
            ->assertJsonPath('data.totalScore', 40)
            ->assertJsonPath('data.classScoreUpdatedAt', null);

        $this->assertNotNull($response->json('data.examScoreUpdatedAt'));
        $this->assertDatabaseCount('marks', 1);
    }

    public function test_owners_can_record_that_a_student_did_not_sit_the_exam(): void
    {
        $context = $this->setupMarkContext();

        $this->withToken($context['token'])
            ->postJson(route('api.schools.class-marks.store', $context['school']), $this->classPayload($context))
            ->assertCreated();

        $this->withToken($context['token'])
            ->putJson(route('api.schools.exam-marks.upsert', $context['school']), $this->examPayload($context, [
                'participated' => false,
            ]))
            ->assertOk()
            ->assertJsonPath('data.participated', false)
            ->assertJsonPath('data.examScore', 0)
            ->assertJsonPath('data.examContribution', 0)
            ->assertJsonPath('data.continuousAssessmentContribution', 45)
            ->assertJsonPath('data.totalScore', 45)
            ->assertJsonPath('data.grade', null)
            ->assertJsonPath('data.gradeRemark', null);
    }

    public function test_owners_can_list_marks_for_their_school(): void
    {
        $context = $this->setupMarkContext();
        Mark::factory()->create([
            'student_id' => $context['student']->id,
            'subject_id' => $context['subject']->id,
            'school_class_id' => $context['schoolClass']->id,
            'student_class_enrollment_id' => $context['enrollment']->id,
            'academic_year_id' => $context['academicYear']->id,
            'term_id' => $context['term']->id,
        ]);
        Mark::factory()->create();

        $this->withToken($context['token'])
            ->getJson(route('api.schools.marks.index', $context['school']))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.studentId', $context['student']->id)
            ->assertJsonPath('data.0.schoolId', $context['school']->id);
    }

    public function test_owners_can_update_class_marks_without_changing_exam_score(): void
    {
        $context = $this->setupMarkContext();
        $mark = Mark::factory()->create([
            'student_id' => $context['student']->id,
            'subject_id' => $context['subject']->id,
            'school_class_id' => $context['schoolClass']->id,
            'student_class_enrollment_id' => $context['enrollment']->id,
            'academic_year_id' => $context['academicYear']->id,
            'term_id' => $context['term']->id,
            'class_score' => 12,
            'home_assignment_score' => 14,
            'project_score' => 13,
            'class_test_score' => 15,
            'exam_score' => 80,
        ]);

        $this->withToken($context['token'])
            ->putJson(route('api.schools.class-marks.update', [$context['school'], $mark]), [
                'classScore' => 10,
            ])
            ->assertOk()
            ->assertJsonPath('data.classScore', 10)
            ->assertJsonPath('data.examScore', 80)
            ->assertJsonPath('data.examContribution', 40)
            ->assertJsonPath('data.continuousAssessmentScore', 52);
    }

    public function test_exam_mark_to_not_participating_clears_the_grade_and_keeps_class_scores(): void
    {
        $context = $this->setupMarkContext();
        Mark::factory()->create([
            'student_id' => $context['student']->id,
            'subject_id' => $context['subject']->id,
            'school_class_id' => $context['schoolClass']->id,
            'student_class_enrollment_id' => $context['enrollment']->id,
            'academic_year_id' => $context['academicYear']->id,
            'term_id' => $context['term']->id,
            'class_score' => 12,
            'home_assignment_score' => 14,
            'project_score' => 13,
            'class_test_score' => 15,
            'exam_score' => 80,
        ]);

        $this->withToken($context['token'])
            ->putJson(route('api.schools.exam-marks.upsert', $context['school']), $this->examPayload($context, [
                'participated' => false,
            ]))
            ->assertOk()
            ->assertJsonPath('data.participated', false)
            ->assertJsonPath('data.examScore', 0)
            ->assertJsonPath('data.continuousAssessmentContribution', 45)
            ->assertJsonPath('data.totalScore', 45)
            ->assertJsonPath('data.grade', null)
            ->assertJsonPath('data.gradeRemark', null);
    }

    public function test_owners_cannot_create_a_class_mark_without_an_active_student_subject(): void
    {
        $context = $this->setupMarkContext();
        $otherSubject = Subject::factory()->create(['school_id' => $context['school']->id]);

        $this->withToken($context['token'])
            ->postJson(
                route('api.schools.class-marks.store', $context['school']),
                $this->classPayload($context, ['subjectId' => $otherSubject->id]),
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subjectId']);
    }

    public function test_owners_cannot_create_duplicate_class_marks_for_the_same_subject_and_term(): void
    {
        $context = $this->setupMarkContext();
        Mark::factory()->create([
            'student_id' => $context['student']->id,
            'subject_id' => $context['subject']->id,
            'school_class_id' => $context['schoolClass']->id,
            'student_class_enrollment_id' => $context['enrollment']->id,
            'academic_year_id' => $context['academicYear']->id,
            'term_id' => $context['term']->id,
        ]);

        $this->withToken($context['token'])
            ->postJson(
                route('api.schools.class-marks.store', $context['school']),
                $this->classPayload($context, [
                    'classScore' => 10,
                    'homeAssignmentScore' => 10,
                    'projectScore' => 10,
                    'classTestScore' => 10,
                ]),
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subjectId']);
    }

    public function test_owners_cannot_manage_marks_for_another_school(): void
    {
        $context = $this->setupMarkContext();
        $otherSchool = School::factory()->create();

        $this->withToken($context['token'])
            ->getJson(route('api.schools.marks.index', $otherSchool))
            ->assertForbidden();

        $this->withToken($context['token'])
            ->postJson(route('api.schools.class-marks.store', $otherSchool), $this->classPayload($context))
            ->assertForbidden();

        $this->withToken($context['token'])
            ->putJson(route('api.schools.exam-marks.upsert', $otherSchool), $this->examPayload($context))
            ->assertForbidden();
    }

    public function test_class_mark_store_requires_core_fields(): void
    {
        $context = $this->setupMarkContext();

        $this->withToken($context['token'])
            ->postJson(route('api.schools.class-marks.store', $context['school']), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'studentId',
                'subjectId',
                'schoolClassId',
                'studentClassEnrollmentId',
                'academicYearId',
                'termId',
            ]);
    }

    public function test_exam_mark_requires_participated(): void
    {
        $context = $this->setupMarkContext();

        $this->withToken($context['token'])
            ->putJson(route('api.schools.exam-marks.upsert', $context['school']), [
                'studentId' => $context['student']->id,
                'subjectId' => $context['subject']->id,
                'schoolClassId' => $context['schoolClass']->id,
                'studentClassEnrollmentId' => $context['enrollment']->id,
                'academicYearId' => $context['academicYear']->id,
                'termId' => $context['term']->id,
                'examScore' => 80,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['participated']);
    }

    public function test_participating_class_mark_requires_score_fields(): void
    {
        $context = $this->setupMarkContext();

        $this->withToken($context['token'])
            ->postJson(route('api.schools.class-marks.store', $context['school']), $this->classPayload($context, [
                'classScore' => null,
                'homeAssignmentScore' => null,
                'projectScore' => null,
                'classTestScore' => null,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'classScore',
                'homeAssignmentScore',
                'projectScore',
                'classTestScore',
            ]);
    }

    public function test_owners_can_create_a_total_score_class_mark_without_division_fields(): void
    {
        $context = $this->setupMarkContext();
        $context['school']->markSetting->update([
            'scoring_mode' => ScoringMode::TotalScore,
            'class_score_percent' => 50,
            'exam_score_percent' => 50,
        ]);

        $this->withToken($context['token'])
            ->postJson(route('api.schools.class-marks.store', $context['school']), [
                'studentId' => $context['student']->id,
                'subjectId' => $context['subject']->id,
                'schoolClassId' => $context['schoolClass']->id,
                'studentClassEnrollmentId' => $context['enrollment']->id,
                'academicYearId' => $context['academicYear']->id,
                'termId' => $context['term']->id,
                'classScore' => 40,
            ])
            ->assertCreated()
            ->assertJsonPath('data.continuousAssessmentContribution', 40)
            ->assertJsonPath('data.examContribution', 0)
            ->assertJsonPath('data.totalScore', 40);

        $this->withToken($context['token'])
            ->putJson(route('api.schools.exam-marks.upsert', $context['school']), $this->examPayload($context, [
                'examScore' => 70,
            ]))
            ->assertOk()
            ->assertJsonPath('data.continuousAssessmentContribution', 40)
            ->assertJsonPath('data.examContribution', 35)
            ->assertJsonPath('data.totalScore', 75);
    }

    public function test_total_score_class_score_cannot_exceed_class_percent(): void
    {
        $context = $this->setupMarkContext();
        $context['school']->markSetting->update([
            'scoring_mode' => ScoringMode::TotalScore,
            'class_score_percent' => 50,
            'exam_score_percent' => 50,
        ]);

        $this->withToken($context['token'])
            ->postJson(route('api.schools.class-marks.store', $context['school']), [
                'studentId' => $context['student']->id,
                'subjectId' => $context['subject']->id,
                'schoolClassId' => $context['schoolClass']->id,
                'studentClassEnrollmentId' => $context['enrollment']->id,
                'academicYearId' => $context['academicYear']->id,
                'termId' => $context['term']->id,
                'classScore' => 50.01,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['classScore']);
    }

    public function test_exam_score_cannot_exceed_100(): void
    {
        $context = $this->setupMarkContext();
        $context['school']->markSetting->update([
            'scoring_mode' => ScoringMode::TotalScore,
            'class_score_percent' => 50,
            'exam_score_percent' => 50,
        ]);

        $this->withToken($context['token'])
            ->putJson(route('api.schools.exam-marks.upsert', $context['school']), $this->examPayload($context, [
                'examScore' => 100,
            ]))
            ->assertCreated();

        $this->withToken($context['token'])
            ->putJson(route('api.schools.exam-marks.upsert', $context['school']), $this->examPayload($context, [
                'examScore' => 101,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['examScore']);
    }

    public function test_division_class_score_fields_cannot_exceed_setting_maxes(): void
    {
        $context = $this->setupMarkContext();

        $this->withToken($context['token'])
            ->postJson(route('api.schools.class-marks.store', $context['school']), $this->classPayload($context, [
                'classScore' => 15.01,
                'homeAssignmentScore' => 16,
                'projectScore' => 16,
                'classTestScore' => 16,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'classScore',
                'homeAssignmentScore',
                'projectScore',
                'classTestScore',
            ]);
    }

    public function test_division_exam_score_cannot_exceed_100(): void
    {
        $context = $this->setupMarkContext();

        $this->withToken($context['token'])
            ->putJson(route('api.schools.exam-marks.upsert', $context['school']), $this->examPayload($context, [
                'examScore' => 101,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['examScore']);
    }

    public function test_division_score_maxes_follow_the_active_mark_setting(): void
    {
        $context = $this->setupMarkContext();
        $context['school']->markSetting->update([
            'class_score_max' => 20,
            'home_assignment_max' => 10,
            'project_max' => 10,
            'class_test_max' => 10,
        ]);

        $this->withToken($context['token'])
            ->postJson(route('api.schools.class-marks.store', $context['school']), $this->classPayload($context, [
                'classScore' => 20,
                'homeAssignmentScore' => 10.01,
                'projectScore' => 10,
                'classTestScore' => 10,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['homeAssignmentScore'])
            ->assertJsonMissingValidationErrors(['classScore', 'projectScore', 'classTestScore']);
    }
}
