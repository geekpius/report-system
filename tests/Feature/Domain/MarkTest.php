<?php

namespace Tests\Feature\Domain;

use App\Models\AcademicYear;
use App\Models\Aggregate;
use App\Models\Mark;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use App\Models\Subject;
use App\Models\Term;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_observer_calculates_contributions_total_and_grade(): void
    {
        $school = School::factory()->create();
        Aggregate::factory()->create([
            'school_id' => $school->id,
            'min_score' => 80,
            'max_score' => 100,
            'grade' => 'A1',
            'remarks' => 'Excellent',
        ]);

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
            'exam_score' => 80,
        ]);

        $this->assertTrue(Str::isUuid($mark->id));
        $this->assertSame('54.00', $mark->continuous_assessment_score);
        $this->assertSame('45.00', $mark->continuous_assessment_contribution);
        $this->assertSame('40.00', $mark->exam_contribution);
        $this->assertSame('85.00', $mark->total_score);
        $this->assertSame('A1', $mark->grade);
        $this->assertSame('Excellent', $mark->grade_remark);
        $this->assertTrue($mark->student->is($student));
        $this->assertTrue($mark->subject->is($subject));
        $this->assertTrue($mark->term->is($term));
    }

    public function test_updating_a_mark_recalculates_scores_and_grade(): void
    {
        $school = School::factory()->create();
        Aggregate::factory()->create([
            'school_id' => $school->id,
            'min_score' => 80,
            'max_score' => 100,
            'grade' => 'A1',
            'remarks' => 'Excellent',
        ]);
        Aggregate::factory()->create([
            'school_id' => $school->id,
            'min_score' => 70,
            'max_score' => 79,
            'grade' => 'B2',
            'remarks' => 'Very Good',
        ]);

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
            'exam_score' => 80,
        ]);

        $mark->update([
            'exam_score' => 50,
        ]);

        $mark->refresh();

        $this->assertSame('54.00', $mark->continuous_assessment_score);
        $this->assertSame('45.00', $mark->continuous_assessment_contribution);
        $this->assertSame('25.00', $mark->exam_contribution);
        $this->assertSame('70.00', $mark->total_score);
        $this->assertSame('B2', $mark->grade);
        $this->assertSame('Very Good', $mark->grade_remark);
    }
}
