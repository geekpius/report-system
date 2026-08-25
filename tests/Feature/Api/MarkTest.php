<?php

namespace Tests\Feature\Api;

use App\Enums\EnrollmentStatus;
use App\Enums\StudentSubjectStatus;
use App\Models\AcademicYear;
use App\Models\Aggregate;
use App\Models\Client;
use App\Models\Mark;
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

    public function test_owners_can_create_a_mark_with_calculated_scores(): void
    {
        $context = $this->setupMarkContext();

        $this->withToken($context['token'])
            ->postJson(route('api.schools.marks.store', $context['school']), [
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
                'examScore' => 80,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.continuousAssessmentScore', 54)
            ->assertJsonPath('data.continuousAssessmentContribution', 45)
            ->assertJsonPath('data.examContribution', 40)
            ->assertJsonPath('data.totalScore', 85)
            ->assertJsonPath('data.grade', 'A1')
            ->assertJsonPath('data.gradeRemark', 'Excellent')
            ->assertJsonPath('data.subject.name', 'Mathematics');

        $this->assertDatabaseHas('marks', [
            'student_id' => $context['student']->id,
            'subject_id' => $context['subject']->id,
            'term_id' => $context['term']->id,
            'total_score' => 85.00,
            'grade' => 'A1',
        ]);
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
            ->assertJsonPath('data.0.studentId', $context['student']->id);
    }

    public function test_owners_can_update_a_mark(): void
    {
        $context = $this->setupMarkContext();
        Aggregate::factory()->create([
            'school_id' => $context['school']->id,
            'min_score' => 70,
            'max_score' => 79,
            'grade' => 'B2',
            'remarks' => 'Very Good',
        ]);
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
            ->putJson(route('api.schools.marks.update', [$context['school'], $mark]), [
                'examScore' => 50,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.examScore', 50)
            ->assertJsonPath('data.examContribution', 25)
            ->assertJsonPath('data.totalScore', 70)
            ->assertJsonPath('data.grade', 'B2')
            ->assertJsonPath('data.gradeRemark', 'Very Good');
    }

    public function test_owners_cannot_create_a_mark_without_an_active_student_subject(): void
    {
        $context = $this->setupMarkContext();
        $otherSubject = Subject::factory()->create(['school_id' => $context['school']->id]);

        $this->withToken($context['token'])
            ->postJson(route('api.schools.marks.store', $context['school']), [
                'studentId' => $context['student']->id,
                'subjectId' => $otherSubject->id,
                'schoolClassId' => $context['schoolClass']->id,
                'studentClassEnrollmentId' => $context['enrollment']->id,
                'academicYearId' => $context['academicYear']->id,
                'termId' => $context['term']->id,
                'classScore' => 12,
                'homeAssignmentScore' => 14,
                'projectScore' => 13,
                'classTestScore' => 15,
                'examScore' => 80,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subjectId']);
    }

    public function test_owners_cannot_create_duplicate_marks_for_the_same_subject_and_term(): void
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
            ->postJson(route('api.schools.marks.store', $context['school']), [
                'studentId' => $context['student']->id,
                'subjectId' => $context['subject']->id,
                'schoolClassId' => $context['schoolClass']->id,
                'studentClassEnrollmentId' => $context['enrollment']->id,
                'academicYearId' => $context['academicYear']->id,
                'termId' => $context['term']->id,
                'classScore' => 10,
                'homeAssignmentScore' => 10,
                'projectScore' => 10,
                'classTestScore' => 10,
                'examScore' => 70,
            ])
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
            ->postJson(route('api.schools.marks.store', $otherSchool), [
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
                'examScore' => 80,
            ])
            ->assertForbidden();
    }

    public function test_mark_store_requires_core_fields(): void
    {
        $context = $this->setupMarkContext();

        $this->withToken($context['token'])
            ->postJson(route('api.schools.marks.store', $context['school']), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'studentId',
                'subjectId',
                'schoolClassId',
                'studentClassEnrollmentId',
                'academicYearId',
                'termId',
                'classScore',
                'homeAssignmentScore',
                'projectScore',
                'classTestScore',
                'examScore',
            ]);
    }
}
