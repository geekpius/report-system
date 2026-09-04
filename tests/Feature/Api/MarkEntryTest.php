<?php

namespace Tests\Feature\Api;

use App\Enums\EnrollmentStatus;
use App\Enums\StudentSubjectStatus;
use App\Models\AcademicYear;
use App\Models\Client;
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

class MarkEntryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{
     *     owner: Client,
     *     school: School,
     *     schoolClass: SchoolClass,
     *     academicYear: AcademicYear,
     *     term: Term,
     *     subject: Subject,
     *     pendingStudent: Student,
     *     recordedStudent: Student,
     *     pendingEnrollment: StudentClassEnrollment,
     *     recordedEnrollment: StudentClassEnrollment,
     *     token: string
     * }
     */
    protected function setupEntryContext(): array
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        MarkSetting::factory()->division()->create(['school_id' => $school->id]);
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id]);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id]);
        $term = Term::factory()->create(['academic_year_id' => $academicYear->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Mathematics']);

        $pendingStudent = Student::factory()->create([
            'school_id' => $school->id,
            'first_name' => 'Akosua',
            'last_name' => 'Mensah',
            'admission_number' => 'ADM-2026-001',
        ]);
        $recordedStudent = Student::factory()->create([
            'school_id' => $school->id,
            'first_name' => 'Kwame',
            'last_name' => 'Boateng',
            'admission_number' => 'ADM-2026-002',
        ]);

        $pendingEnrollment = $this->enrollStudent($pendingStudent, $schoolClass, $academicYear, $subject);
        $recordedEnrollment = $this->enrollStudent($recordedStudent, $schoolClass, $academicYear, $subject);

        return [
            'owner' => $owner,
            'school' => $school,
            'schoolClass' => $schoolClass,
            'academicYear' => $academicYear,
            'term' => $term,
            'subject' => $subject,
            'pendingStudent' => $pendingStudent,
            'recordedStudent' => $recordedStudent,
            'pendingEnrollment' => $pendingEnrollment,
            'recordedEnrollment' => $recordedEnrollment,
            'token' => $owner->createToken('api-owner', ['permit:owner'])->plainTextToken,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, string>
     */
    protected function entryQuery(array $context): array
    {
        return [
            'schoolClassId' => $context['schoolClass']->id,
            'subjectId' => $context['subject']->id,
            'termId' => $context['term']->id,
        ];
    }

    protected function enrollStudent(
        Student $student,
        SchoolClass $schoolClass,
        AcademicYear $academicYear,
        Subject $subject,
    ): StudentClassEnrollment {
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

        return $enrollment;
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function classPayload(array $context, Student $student, StudentClassEnrollment $enrollment, array $overrides = []): array
    {
        return array_merge([
            'studentId' => $student->id,
            'subjectId' => $context['subject']->id,
            'schoolClassId' => $context['schoolClass']->id,
            'studentClassEnrollmentId' => $enrollment->id,
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
    protected function examPayload(array $context, Student $student, StudentClassEnrollment $enrollment, array $overrides = []): array
    {
        return array_merge([
            'studentId' => $student->id,
            'subjectId' => $context['subject']->id,
            'schoolClassId' => $context['schoolClass']->id,
            'studentClassEnrollmentId' => $enrollment->id,
            'academicYearId' => $context['academicYear']->id,
            'termId' => $context['term']->id,
            'participated' => true,
            'examScore' => 80,
        ], $overrides);
    }

    public function test_class_mark_pending_lists_students_without_a_class_score_contribution(): void
    {
        $context = $this->setupEntryContext();

        $this->withToken($context['token'])
            ->postJson(
                route('api.schools.class-marks.store', $context['school']),
                $this->classPayload($context, $context['recordedStudent'], $context['recordedEnrollment']),
            )
            ->assertCreated();

        $this->withToken($context['token'])
            ->getJson(route('api.schools.class-marks.pending', $context['school']).'?'.http_build_query($this->entryQuery($context)))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.studentId', $context['pendingStudent']->id)
            ->assertJsonPath('data.0.student.firstName', 'Akosua')
            ->assertJsonPath('data.0.student.admissionNumber', 'ADM-2026-001')
            ->assertJsonPath('data.0.mark', null);
    }

    public function test_class_mark_recorded_lists_students_with_a_class_score_contribution(): void
    {
        $context = $this->setupEntryContext();

        $this->withToken($context['token'])
            ->postJson(
                route('api.schools.class-marks.store', $context['school']),
                $this->classPayload($context, $context['recordedStudent'], $context['recordedEnrollment']),
            )
            ->assertCreated();

        $this->withToken($context['token'])
            ->getJson(route('api.schools.class-marks.recorded', $context['school']).'?'.http_build_query($this->entryQuery($context)))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.studentId', $context['recordedStudent']->id)
            ->assertJsonPath('data.0.mark.continuousAssessmentContribution', 45)
            ->assertJsonPath('data.0.mark.examScore', 0);
    }

    public function test_exam_only_marks_remain_pending_for_class_scores(): void
    {
        $context = $this->setupEntryContext();

        $this->withToken($context['token'])
            ->putJson(
                route('api.schools.exam-marks.upsert', $context['school']),
                $this->examPayload($context, $context['recordedStudent'], $context['recordedEnrollment']),
            )
            ->assertCreated();

        $this->withToken($context['token'])
            ->getJson(route('api.schools.class-marks.pending', $context['school']).'?'.http_build_query($this->entryQuery($context)))
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->withToken($context['token'])
            ->getJson(route('api.schools.class-marks.recorded', $context['school']).'?'.http_build_query($this->entryQuery($context)))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->withToken($context['token'])
            ->getJson(route('api.schools.exam-marks.pending', $context['school']).'?'.http_build_query($this->entryQuery($context)))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.studentId', $context['pendingStudent']->id);

        $this->withToken($context['token'])
            ->getJson(route('api.schools.exam-marks.recorded', $context['school']).'?'.http_build_query($this->entryQuery($context)))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.studentId', $context['recordedStudent']->id)
            ->assertJsonPath('data.0.mark.examScore', 80);
    }

    public function test_class_only_marks_remain_pending_for_exam_scores(): void
    {
        $context = $this->setupEntryContext();

        $this->withToken($context['token'])
            ->postJson(
                route('api.schools.class-marks.store', $context['school']),
                $this->classPayload($context, $context['recordedStudent'], $context['recordedEnrollment']),
            )
            ->assertCreated();

        $this->withToken($context['token'])
            ->getJson(route('api.schools.exam-marks.pending', $context['school']).'?'.http_build_query($this->entryQuery($context)))
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->withToken($context['token'])
            ->getJson(route('api.schools.exam-marks.recorded', $context['school']).'?'.http_build_query($this->entryQuery($context)))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_students_without_the_subject_are_excluded(): void
    {
        $context = $this->setupEntryContext();
        $otherSubject = Subject::factory()->create(['school_id' => $context['school']->id]);
        $outsider = Student::factory()->create(['school_id' => $context['school']->id]);
        $this->enrollStudent($outsider, $context['schoolClass'], $context['academicYear'], $otherSubject);

        $this->withToken($context['token'])
            ->getJson(route('api.schools.class-marks.pending', $context['school']).'?'.http_build_query($this->entryQuery($context)))
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_mark_entry_lists_require_class_subject_and_term(): void
    {
        $context = $this->setupEntryContext();

        $this->withToken($context['token'])
            ->getJson(route('api.schools.class-marks.pending', $context['school']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['schoolClassId', 'subjectId', 'termId']);
    }

    public function test_owners_cannot_list_mark_entries_for_another_school(): void
    {
        $context = $this->setupEntryContext();
        $otherSchool = School::factory()->create();

        $this->withToken($context['token'])
            ->getJson(route('api.schools.class-marks.pending', $otherSchool).'?'.http_build_query($this->entryQuery($context)))
            ->assertForbidden();
    }

    public function test_closing_class_score_entry_hides_marks_from_class_lists(): void
    {
        $context = $this->setupEntryContext();

        $this->withToken($context['token'])
            ->postJson(
                route('api.schools.class-marks.store', $context['school']),
                $this->classPayload($context, $context['recordedStudent'], $context['recordedEnrollment']),
            )
            ->assertCreated();

        $this->withToken($context['token'])
            ->postJson(route('api.schools.class-marks.close', $context['school']), $this->entryQuery($context))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.closeClassScoreEntry', true)
            ->assertJsonPath('data.0.closeExamScoreEntry', false);

        $this->assertDatabaseHas('marks', [
            'student_id' => $context['recordedStudent']->id,
            'close_class_score_entry' => true,
            'close_exam_score_entry' => false,
        ]);

        $this->withToken($context['token'])
            ->getJson(route('api.schools.class-marks.recorded', $context['school']).'?'.http_build_query($this->entryQuery($context)))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->withToken($context['token'])
            ->getJson(route('api.schools.class-marks.pending', $context['school']).'?'.http_build_query($this->entryQuery($context)))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.studentId', $context['pendingStudent']->id);

        $this->withToken($context['token'])
            ->getJson(route('api.schools.exam-marks.pending', $context['school']).'?'.http_build_query($this->entryQuery($context)))
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_closing_exam_score_entry_hides_marks_from_exam_lists(): void
    {
        $context = $this->setupEntryContext();

        $this->withToken($context['token'])
            ->putJson(
                route('api.schools.exam-marks.upsert', $context['school']),
                $this->examPayload($context, $context['recordedStudent'], $context['recordedEnrollment']),
            )
            ->assertCreated();

        $this->withToken($context['token'])
            ->postJson(route('api.schools.exam-marks.close', $context['school']), $this->entryQuery($context))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.closeExamScoreEntry', true)
            ->assertJsonPath('data.0.closeClassScoreEntry', false);

        $this->withToken($context['token'])
            ->getJson(route('api.schools.exam-marks.recorded', $context['school']).'?'.http_build_query($this->entryQuery($context)))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->withToken($context['token'])
            ->getJson(route('api.schools.exam-marks.pending', $context['school']).'?'.http_build_query($this->entryQuery($context)))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.studentId', $context['pendingStudent']->id);

        $this->withToken($context['token'])
            ->getJson(route('api.schools.class-marks.pending', $context['school']).'?'.http_build_query($this->entryQuery($context)))
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_closed_class_score_entry_cannot_be_updated(): void
    {
        $context = $this->setupEntryContext();

        $response = $this->withToken($context['token'])
            ->postJson(
                route('api.schools.class-marks.store', $context['school']),
                $this->classPayload($context, $context['recordedStudent'], $context['recordedEnrollment']),
            )
            ->assertCreated();

        $this->withToken($context['token'])
            ->postJson(route('api.schools.class-marks.close', $context['school']), $this->entryQuery($context))
            ->assertOk();

        $this->withToken($context['token'])
            ->putJson(
                route('api.schools.class-marks.update', [$context['school'], $response->json('data.id')]),
                ['classScore' => 10, 'homeAssignmentScore' => 10, 'projectScore' => 10, 'classTestScore' => 10],
            )
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Class score entry is closed for this mark.');
    }
}
