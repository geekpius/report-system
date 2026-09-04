<?php

namespace Tests\Feature\Domain;

use App\Enums\EnrollmentStatus;
use App\Enums\StudentSubjectStatus;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
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
use Illuminate\Support\Str;
use Tests\TestCase;

class StudentTermResultTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_observer_recalculates_student_term_results(): void
    {
        $school = School::factory()->create();
        MarkSetting::factory()->division()->create(['school_id' => $school->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id]);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id]);
        $term = Term::factory()->create(['academic_year_id' => $academicYear->id]);
        $mathematics = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Mathematics']);
        $english = Subject::factory()->create(['school_id' => $school->id, 'name' => 'English']);
        $enrollment = StudentClassEnrollment::factory()->create([
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $academicYear->id,
            'status' => EnrollmentStatus::Active,
        ]);

        foreach ([$mathematics, $english] as $subject) {
            StudentSubject::factory()->create([
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'school_class_id' => $schoolClass->id,
                'student_class_enrollment_id' => $enrollment->id,
                'status' => StudentSubjectStatus::Active,
            ]);
        }

        Mark::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $mathematics->id,
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

        Mark::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $english->id,
            'school_class_id' => $schoolClass->id,
            'student_class_enrollment_id' => $enrollment->id,
            'academic_year_id' => $academicYear->id,
            'term_id' => $term->id,
            'class_score' => 10,
            'home_assignment_score' => 10,
            'project_score' => 10,
            'class_test_score' => 10,
            'exam_score' => 60,
        ]);

        $result = StudentTermResult::query()
            ->where('student_class_enrollment_id', $enrollment->id)
            ->where('term_id', $term->id)
            ->first();

        $this->assertNotNull($result);
        $this->assertTrue(Str::isUuid($result->id));
        $this->assertSame(2, $result->subjects_count);
        $this->assertSame('148.33', $result->total_score);
        $this->assertSame('74.17', $result->average_score);
        $this->assertNotNull($result->calculated_at);
        $this->assertTrue($student->fresh()->termResults->contains($result));
    }

    public function test_class_positions_are_ranked_by_average_score(): void
    {
        $school = School::factory()->create();
        MarkSetting::factory()->division()->create(['school_id' => $school->id]);
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id]);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id]);
        $term = Term::factory()->create(['academic_year_id' => $academicYear->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id]);

        ClassSubject::factory()->create([
            'school_class_id' => $schoolClass->id,
            'subject_id' => $subject->id,
            'is_mandatory' => true,
        ]);

        $topStudent = Student::factory()->create([
            'school_id' => $school->id,
            'admission_number' => 'ADM-1001',
        ]);
        $secondStudent = Student::factory()->create([
            'school_id' => $school->id,
            'admission_number' => 'ADM-1002',
        ]);

        $topEnrollment = StudentClassEnrollment::factory()->create([
            'student_id' => $topStudent->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $academicYear->id,
            'status' => EnrollmentStatus::Active,
        ]);
        $secondEnrollment = StudentClassEnrollment::factory()->create([
            'student_id' => $secondStudent->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $academicYear->id,
            'status' => EnrollmentStatus::Active,
        ]);

        foreach ([$topEnrollment, $secondEnrollment] as $enrollment) {
            StudentSubject::factory()->create([
                'student_id' => $enrollment->student_id,
                'subject_id' => $subject->id,
                'school_class_id' => $schoolClass->id,
                'student_class_enrollment_id' => $enrollment->id,
                'status' => StudentSubjectStatus::Active,
            ]);
        }

        Mark::factory()->create([
            'student_id' => $topStudent->id,
            'subject_id' => $subject->id,
            'school_class_id' => $schoolClass->id,
            'student_class_enrollment_id' => $topEnrollment->id,
            'academic_year_id' => $academicYear->id,
            'term_id' => $term->id,
            'class_score' => 15,
            'home_assignment_score' => 15,
            'project_score' => 15,
            'class_test_score' => 15,
            'exam_score' => 100,
        ]);

        Mark::factory()->create([
            'student_id' => $secondStudent->id,
            'subject_id' => $subject->id,
            'school_class_id' => $schoolClass->id,
            'student_class_enrollment_id' => $secondEnrollment->id,
            'academic_year_id' => $academicYear->id,
            'term_id' => $term->id,
            'class_score' => 10,
            'home_assignment_score' => 10,
            'project_score' => 10,
            'class_test_score' => 10,
            'exam_score' => 60,
        ]);

        $topResult = StudentTermResult::query()
            ->where('student_class_enrollment_id', $topEnrollment->id)
            ->where('term_id', $term->id)
            ->first();
        $secondResult = StudentTermResult::query()
            ->where('student_class_enrollment_id', $secondEnrollment->id)
            ->where('term_id', $term->id)
            ->first();

        $this->assertSame(1, $topResult->class_position);
        $this->assertSame(2, $secondResult->class_position);
    }

    public function test_students_without_all_mandatory_marks_do_not_receive_a_class_position(): void
    {
        $school = School::factory()->create();
        MarkSetting::factory()->division()->create(['school_id' => $school->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id]);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id]);
        $term = Term::factory()->create(['academic_year_id' => $academicYear->id]);
        $mathematics = Subject::factory()->create(['school_id' => $school->id]);
        $english = Subject::factory()->create(['school_id' => $school->id]);
        $enrollment = StudentClassEnrollment::factory()->create([
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $academicYear->id,
            'status' => EnrollmentStatus::Active,
        ]);

        ClassSubject::factory()->create([
            'school_class_id' => $schoolClass->id,
            'subject_id' => $mathematics->id,
            'is_mandatory' => true,
        ]);
        ClassSubject::factory()->create([
            'school_class_id' => $schoolClass->id,
            'subject_id' => $english->id,
            'is_mandatory' => true,
        ]);

        StudentSubject::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $mathematics->id,
            'school_class_id' => $schoolClass->id,
            'student_class_enrollment_id' => $enrollment->id,
            'status' => StudentSubjectStatus::Active,
        ]);

        Mark::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $mathematics->id,
            'school_class_id' => $schoolClass->id,
            'student_class_enrollment_id' => $enrollment->id,
            'academic_year_id' => $academicYear->id,
            'term_id' => $term->id,
            'class_score' => 12,
            'home_assignment_score' => 12,
            'project_score' => 12,
            'class_test_score' => 12,
            'exam_score' => 80,
        ]);

        $result = StudentTermResult::query()
            ->where('student_class_enrollment_id', $enrollment->id)
            ->where('term_id', $term->id)
            ->first();

        $this->assertNotNull($result);
        $this->assertNull($result->class_position);
    }

    public function test_class_marks_without_an_exam_score_do_not_create_a_term_result(): void
    {
        $school = School::factory()->create();
        MarkSetting::factory()->division()->create(['school_id' => $school->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id]);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id]);
        $term = Term::factory()->create(['academic_year_id' => $academicYear->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id]);
        $enrollment = StudentClassEnrollment::factory()->create([
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $academicYear->id,
        ]);
        StudentSubject::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'school_class_id' => $schoolClass->id,
            'student_class_enrollment_id' => $enrollment->id,
            'status' => StudentSubjectStatus::Active,
        ]);

        $mark = Mark::factory()->create([
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
            'exam_score' => 0,
        ]);

        $this->assertDatabaseMissing('student_term_results', [
            'student_class_enrollment_id' => $enrollment->id,
            'term_id' => $term->id,
        ]);

        $mark->update(['class_score' => 10]);

        $this->assertDatabaseHas('student_term_results', [
            'student_class_enrollment_id' => $enrollment->id,
            'term_id' => $term->id,
        ]);

        $mark->update(['exam_score' => 80]);

        $result = StudentTermResult::query()
            ->where('student_class_enrollment_id', $enrollment->id)
            ->where('term_id', $term->id)
            ->first();

        $this->assertNotNull($result);
        $this->assertSame('83.33', $result->total_score);
    }
}
