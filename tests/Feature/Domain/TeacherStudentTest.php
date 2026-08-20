<?php

namespace Tests\Feature\Domain;

use App\Enums\Gender;
use App\Enums\Role;
use App\Models\Client;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
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
}
