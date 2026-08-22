<?php

namespace Tests\Feature\Domain;

use App\Enums\Gender;
use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\ClassSubjectTeacher;
use App\Models\Client;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TeacherStudentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_teacher_belongs_to_a_client_and_a_school(): void
    {
        $client = Client::factory()->teacher()->create();
        $school = School::factory()->create();
        $teacher = Teacher::factory()->create([
            'client_id' => $client->id,
            'school_id' => $school->id,
            'staff_number' => 'STF-1001',
            'phone' => '0240000001',
        ]);

        $this->assertTrue(Str::isUuid($teacher->id));
        $this->assertSame(Role::Teacher, $client->role);
        $this->assertTrue($teacher->client->is($client));
        $this->assertTrue($teacher->school->is($school));
        $this->assertTrue($school->teachers->contains($teacher));
        $this->assertTrue($client->teachers->contains($teacher));
    }

    public function test_a_student_belongs_to_a_school_and_may_have_a_client(): void
    {
        $client = Client::factory()->student()->create();
        $school = School::factory()->create();
        $student = Student::factory()->create([
            'client_id' => $client->id,
            'school_id' => $school->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'gender' => Gender::Female,
            'admission_number' => 'ADM-1001',
            'date_of_birth' => '2012-04-15',
        ]);

        $this->assertTrue(Str::isUuid($student->id));
        $this->assertSame(Role::Student, $client->role);
        $this->assertTrue($student->client->is($client));
        $this->assertTrue($student->school->is($school));
        $this->assertTrue($school->students->contains($student));
        $this->assertTrue($client->student->is($student));
        $this->assertNull($student->school_class_id);
    }

    public function test_a_school_class_belongs_to_a_school_and_may_have_a_class_teacher(): void
    {
        $school = School::factory()->create();
        $teacher = Teacher::factory()->create(['school_id' => $school->id]);
        $class = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'name' => 'JHS 1A',
            'class_teacher_id' => $teacher->id,
        ]);
        $student = Student::factory()->create([
            'school_id' => $school->id,
            'school_class_id' => $class->id,
        ]);

        $this->assertTrue(Str::isUuid($class->id));
        $this->assertTrue($class->school->is($school));
        $this->assertTrue($class->classTeacher->is($teacher));
        $this->assertTrue($school->classes->contains($class));
        $this->assertTrue($teacher->classes->contains($class));
        $this->assertTrue($student->schoolClass->is($class));
        $this->assertTrue($class->students->contains($student));
    }

    public function test_a_subject_belongs_to_a_school(): void
    {
        $school = School::factory()->create();
        $subject = Subject::factory()->create([
            'school_id' => $school->id,
            'name' => 'Mathematics',
        ]);

        $this->assertTrue(Str::isUuid($subject->id));
        $this->assertTrue($subject->school->is($school));
        $this->assertTrue($school->subjects->contains($subject));
    }

    public function test_a_subject_name_is_normalized_when_set_and_capitalized_when_read(): void
    {
        $subject = Subject::factory()->create([
            'name' => '  english language  ',
        ]);

        $this->assertSame('english language', $subject->getRawOriginal('name'));
        $this->assertSame('English Language', $subject->name);
    }

    public function test_a_client_name_is_normalized_when_set_and_title_cased_when_read(): void
    {
        $client = Client::factory()->create([
            'name' => '  ama owner  ',
        ]);

        $this->assertSame('ama owner', $client->getRawOriginal('name'));
        $this->assertSame('Ama Owner', $client->name);
    }

    public function test_a_student_name_is_normalized_when_set_and_title_cased_when_read(): void
    {
        $student = Student::factory()->create([
            'first_name' => '  akosua  ',
            'last_name' => '  boateng mensah  ',
        ]);

        $this->assertSame('akosua', $student->getRawOriginal('first_name'));
        $this->assertSame('boateng mensah', $student->getRawOriginal('last_name'));
        $this->assertSame('Akosua', $student->first_name);
        $this->assertSame('Boateng Mensah', $student->last_name);
    }

    public function test_a_school_class_alias_is_normalized_when_set_and_title_cased_when_read(): void
    {
        $class = SchoolClass::factory()->create([
            'alias' => '  form one  ',
        ]);

        $this->assertSame('form one', $class->getRawOriginal('alias'));
        $this->assertSame('Form One', $class->alias);
        $this->assertNull(SchoolClass::factory()->create(['alias' => null])->alias);
    }

    public function test_a_class_subject_teacher_links_a_teacher_subject_and_class(): void
    {
        $school = School::factory()->create();
        $class = SchoolClass::factory()->create(['school_id' => $school->id, 'name' => 'JHS 1A']);
        $subject = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Mathematics']);
        $teacher = Teacher::factory()->create(['school_id' => $school->id]);
        $assignment = ClassSubjectTeacher::factory()->create([
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        $this->assertTrue(Str::isUuid($assignment->id));
        $this->assertTrue($assignment->schoolClass->is($class));
        $this->assertTrue($assignment->subject->is($subject));
        $this->assertTrue($assignment->teacher->is($teacher));
        $this->assertTrue($class->teacherAssignments->contains($assignment));
        $this->assertTrue($subject->teacherAssignments->contains($assignment));
        $this->assertTrue($teacher->subjectAssignments->contains($assignment));
        $this->assertTrue($school->classSubjectTeachers->contains($assignment));
    }

    public function test_a_class_subject_links_a_subject_to_a_class_menu(): void
    {
        $school = School::factory()->create();
        $class = SchoolClass::factory()->create(['school_id' => $school->id, 'name' => 'JHS 1A']);
        $subject = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Mathematics']);
        $classSubject = ClassSubject::factory()->create([
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'is_mandatory' => true,
        ]);

        $this->assertTrue(Str::isUuid($classSubject->id));
        $this->assertTrue($classSubject->schoolClass->is($class));
        $this->assertTrue($classSubject->subject->is($subject));
        $this->assertTrue($class->classSubjects->contains($classSubject));
        $this->assertTrue($subject->classSubjects->contains($classSubject));
        $this->assertTrue($classSubject->is_mandatory);
    }

    public function test_an_academic_year_belongs_to_a_school_and_has_terms(): void
    {
        $school = School::factory()->create();
        $academicYear = AcademicYear::factory()->create([
            'school_id' => $school->id,
            'name' => '2025/2026',
        ]);
        $term = Term::factory()->create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Term 1',
            'number' => 1,
        ]);

        $this->assertTrue(Str::isUuid($academicYear->id));
        $this->assertTrue($academicYear->school->is($school));
        $this->assertTrue($school->academicYears->contains($academicYear));
        $this->assertTrue($academicYear->terms->contains($term));
        $this->assertTrue($term->academicYear->is($academicYear));
    }
}
