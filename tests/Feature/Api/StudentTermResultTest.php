<?php

namespace Tests\Feature\Api;

use App\Enums\EnrollmentStatus;
use App\Enums\StudentSubjectStatus;
use App\Models\AcademicYear;
use App\Models\Client;
use App\Models\Mark;
use App\Models\MarkSetting;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use App\Models\StudentSubject;
use App\Models\StudentTermResult;
use App\Models\Subject;
use App\Models\Term;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentTermResultTest extends TestCase
{
    use RefreshDatabase;

    public function test_owners_can_list_student_term_results_for_their_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        MarkSetting::factory()->division()->create(['school_id' => $school->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id, 'name' => 'JHS 1']);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2025/2026']);
        $term = Term::factory()->create(['academic_year_id' => $academicYear->id, 'name' => 'Term 1']);
        $subject = Subject::factory()->create(['school_id' => $school->id]);
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
        Mark::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'school_class_id' => $schoolClass->id,
            'student_class_enrollment_id' => $enrollment->id,
            'academic_year_id' => $academicYear->id,
            'term_id' => $term->id,
            'class_score' => 12,
            'home_assignment_score' => 14,
            'project_score' => 13,
            'class_test_score' => 15,
            'exam_score' => 80,
        ]);
        StudentTermResult::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.student-term-results.index', $school))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.studentId', $student->id)
            ->assertJsonPath('data.0.subjectsCount', 1)
            ->assertJsonPath('data.0.totalScore', 85)
            ->assertJsonPath('data.0.averageScore', 85)
            ->assertJsonPath('data.0.classPosition', 1)
            ->assertJsonPath('data.0.schoolClass.name', 'JHS 1')
            ->assertJsonPath('data.0.term.name', 'Term 1');
    }

    public function test_owners_can_filter_student_term_results(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id]);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id]);
        $term = Term::factory()->create(['academic_year_id' => $academicYear->id]);
        $result = StudentTermResult::factory()->create([
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $academicYear->id,
            'term_id' => $term->id,
        ]);
        StudentTermResult::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.student-term-results.index', [
                'school' => $school,
                'schoolClassId' => $schoolClass->id,
                'termId' => $term->id,
                'academicYearId' => $academicYear->id,
                'studentId' => $student->id,
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $result->id);
    }

    public function test_owners_cannot_list_student_term_results_for_another_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $otherSchool = School::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.student-term-results.index', $otherSchool))
            ->assertForbidden();
    }
}
