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
        return [
            'marks' => [
                array_merge([
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
                ], $overrides),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function examPayload(array $context, array $overrides = []): array
    {
        return [
            'marks' => [
                array_merge([
                    'studentId' => $context['student']->id,
                    'subjectId' => $context['subject']->id,
                    'schoolClassId' => $context['schoolClass']->id,
                    'studentClassEnrollmentId' => $context['enrollment']->id,
                    'academicYearId' => $context['academicYear']->id,
                    'termId' => $context['term']->id,
                    'participated' => true,
                    'examScore' => 80,
                ], $overrides),
            ],
        ];
    }

    public function test_owners_can_create_class_marks_without_an_exam_score(): void
    {
        $context = $this->setupMarkContext();

        $response = $this->withToken($context['token'])
            ->postJson(route('api.schools.class-marks.store', $context['school']), $this->classPayload($context))
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.schoolId', $context['school']->id)
            ->assertJsonPath('data.0.participated', true)
            ->assertJsonPath('data.0.continuousAssessmentScore', 54)
            ->assertJsonPath('data.0.continuousAssessmentContribution', 45)
            ->assertJsonPath('data.0.examScore', 0)
            ->assertJsonPath('data.0.examContribution', 0)
            ->assertJsonPath('data.0.totalScore', 45)
            ->assertJsonPath('data.0.examScoreUpdatedAt', null)
            ->assertJsonPath('data.0.subjectId', $context['subject']->id);

        $this->assertNotNull($response->json('data.0.classScoreUpdatedAt'));

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
            ->assertJsonPath('data.0.examScore', 80)
            ->assertJsonPath('data.0.examContribution', 40)
            ->assertJsonPath('data.0.continuousAssessmentContribution', 45)
            ->assertJsonPath('data.0.totalScore', 85)
            ->assertJsonPath('data.0.grade', 'A1')
            ->assertJsonPath('data.0.gradeRemark', 'Excellent');

        $this->assertNotNull($response->json('data.0.classScoreUpdatedAt'));
        $this->assertNotNull($response->json('data.0.examScoreUpdatedAt'));
    }

    public function test_owners_can_create_exam_marks_when_no_class_mark_exists(): void
    {
        $context = $this->setupMarkContext();

        $response = $this->withToken($context['token'])
            ->putJson(route('api.schools.exam-marks.upsert', $context['school']), $this->examPayload($context))
            ->assertCreated()
            ->assertJsonPath('data.0.examScore', 80)
            ->assertJsonPath('data.0.examContribution', 40)
            ->assertJsonPath('data.0.classScore', 0)
            ->assertJsonPath('data.0.continuousAssessmentScore', 0)
            ->assertJsonPath('data.0.totalScore', 40)
            ->assertJsonPath('data.0.classScoreUpdatedAt', null);

        $this->assertNotNull($response->json('data.0.examScoreUpdatedAt'));
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
            ->assertJsonPath('data.0.participated', false)
            ->assertJsonPath('data.0.examScore', 0)
            ->assertJsonPath('data.0.examContribution', 0)
            ->assertJsonPath('data.0.continuousAssessmentContribution', 45)
            ->assertJsonPath('data.0.totalScore', 45)
            ->assertJsonPath('data.0.grade', null)
            ->assertJsonPath('data.0.gradeRemark', null);
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
            ->assertJsonPath('data.0.participated', false)
            ->assertJsonPath('data.0.examScore', 0)
            ->assertJsonPath('data.0.continuousAssessmentContribution', 45)
            ->assertJsonPath('data.0.totalScore', 45)
            ->assertJsonPath('data.0.grade', null)
            ->assertJsonPath('data.0.gradeRemark', null);
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
            ->assertJsonValidationErrors(['marks.0.subjectId']);
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
            ->assertJsonValidationErrors(['marks.0.subjectId']);
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
                'marks',
            ]);
    }

    public function test_exam_mark_requires_participated(): void
    {
        $context = $this->setupMarkContext();

        $this->withToken($context['token'])
            ->putJson(route('api.schools.exam-marks.upsert', $context['school']), $this->examPayload($context, [
                'participated' => null,
                'examScore' => 80,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['marks.0.participated']);
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
                'marks.0.classScore',
                'marks.0.homeAssignmentScore',
                'marks.0.projectScore',
                'marks.0.classTestScore',
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
            ->postJson(route('api.schools.class-marks.store', $context['school']), $this->classPayload($context, [
                'classScore' => 40,
                'homeAssignmentScore' => 0,
                'projectScore' => 0,
                'classTestScore' => 0,
            ]))
            ->assertCreated()
            ->assertJsonPath('data.0.continuousAssessmentContribution', 40)
            ->assertJsonPath('data.0.examContribution', 0)
            ->assertJsonPath('data.0.totalScore', 40);

        $this->withToken($context['token'])
            ->putJson(route('api.schools.exam-marks.upsert', $context['school']), $this->examPayload($context, [
                'examScore' => 70,
            ]))
            ->assertOk()
            ->assertJsonPath('data.0.continuousAssessmentContribution', 40)
            ->assertJsonPath('data.0.examContribution', 35)
            ->assertJsonPath('data.0.totalScore', 75);
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
            ->postJson(route('api.schools.class-marks.store', $context['school']), $this->classPayload($context, [
                'classScore' => 50.01,
                'homeAssignmentScore' => 0,
                'projectScore' => 0,
                'classTestScore' => 0,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['marks.0.classScore']);
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
            ->assertJsonValidationErrors(['marks.0.examScore']);
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
                'marks.0.classScore',
                'marks.0.homeAssignmentScore',
                'marks.0.projectScore',
                'marks.0.classTestScore',
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
            ->assertJsonValidationErrors(['marks.0.examScore']);
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
            ->assertJsonValidationErrors(['marks.0.homeAssignmentScore'])
            ->assertJsonMissingValidationErrors(['marks.0.classScore', 'marks.0.projectScore', 'marks.0.classTestScore']);
    }

    public function test_owners_can_create_multiple_class_and_exam_marks_in_one_request(): void
    {
        $context = $this->setupMarkContext();
        $secondStudent = Student::factory()->create(['school_id' => $context['school']->id]);
        $secondEnrollment = StudentClassEnrollment::factory()->create([
            'student_id' => $secondStudent->id,
            'school_class_id' => $context['schoolClass']->id,
            'academic_year_id' => $context['academicYear']->id,
            'status' => EnrollmentStatus::Active,
        ]);
        StudentSubject::factory()->create([
            'student_id' => $secondStudent->id,
            'subject_id' => $context['subject']->id,
            'school_class_id' => $context['schoolClass']->id,
            'student_class_enrollment_id' => $secondEnrollment->id,
            'status' => StudentSubjectStatus::Active,
        ]);

        $first = $this->classPayload($context)['marks'][0];
        $second = $this->classPayload($context, [
            'studentId' => $secondStudent->id,
            'studentClassEnrollmentId' => $secondEnrollment->id,
            'classScore' => 10,
            'homeAssignmentScore' => 10,
            'projectScore' => 10,
            'classTestScore' => 10,
        ])['marks'][0];

        $this->withToken($context['token'])
            ->postJson(route('api.schools.class-marks.store', $context['school']), [
                'marks' => [$first, $second],
            ])
            ->assertCreated()
            ->assertJsonCount(2, 'data');

        $this->assertDatabaseCount('marks', 2);

        $this->withToken($context['token'])
            ->putJson(route('api.schools.exam-marks.upsert', $context['school']), [
                'marks' => [
                    $this->examPayload($context)['marks'][0],
                    $this->examPayload($context, [
                        'studentId' => $secondStudent->id,
                        'studentClassEnrollmentId' => $secondEnrollment->id,
                        'examScore' => 60,
                    ])['marks'][0],
                ],
            ])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.examScore', 80)
            ->assertJsonPath('data.1.examScore', 60);
    }
}
