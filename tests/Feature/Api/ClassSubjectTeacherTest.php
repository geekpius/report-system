<?php

namespace Tests\Feature\Api;

use App\Models\ClassSubjectTeacher;
use App\Models\Client;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassSubjectTeacherTest extends TestCase
{
    use RefreshDatabase;

    public function test_owners_can_assign_a_teacher_to_multiple_subjects_in_a_class(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $class = SchoolClass::factory()->create(['school_id' => $school->id, 'name' => 'JHS 1']);
        $mathematics = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Mathematics']);
        $statistics = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Statistics']);
        $physics = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Physics']);
        $teacher = Teacher::factory()->create(['school_id' => $school->id]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.class-subject-teachers.store', $school), [
                'schoolClassId' => $class->id,
                'subjectIds' => [$mathematics->id, $statistics->id, $physics->id],
                'teacherId' => $teacher->id,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.schoolClassId', $class->id)
            ->assertJsonPath('data.0.teacherId', $teacher->id)
            ->assertJsonPath('data.0.subject.name', 'Mathematics')
            ->assertJsonPath('data.1.subject.name', 'Statistics')
            ->assertJsonPath('data.2.subject.name', 'Physics');

        $this->assertDatabaseHas('class_subject_teachers', [
            'school_class_id' => $class->id,
            'subject_id' => $mathematics->id,
            'teacher_id' => $teacher->id,
        ]);
        $this->assertDatabaseHas('class_subject_teachers', [
            'school_class_id' => $class->id,
            'subject_id' => $statistics->id,
            'teacher_id' => $teacher->id,
        ]);
        $this->assertDatabaseHas('class_subject_teachers', [
            'school_class_id' => $class->id,
            'subject_id' => $physics->id,
            'teacher_id' => $teacher->id,
        ]);
    }

    public function test_owners_cannot_assign_entities_from_another_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $class = SchoolClass::factory()->create(['school_id' => $school->id]);
        $otherSubject = Subject::factory()->create();
        $otherTeacher = Teacher::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.class-subject-teachers.store', $school), [
                'schoolClassId' => $class->id,
                'subjectIds' => [$otherSubject->id],
                'teacherId' => $otherTeacher->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subjectIds.0', 'teacherId']);
    }

    public function test_owners_cannot_assign_two_teachers_to_the_same_subject_in_a_class(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $class = SchoolClass::factory()->create(['school_id' => $school->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id]);
        $firstTeacher = Teacher::factory()->create(['school_id' => $school->id]);
        $secondTeacher = Teacher::factory()->create(['school_id' => $school->id]);
        ClassSubjectTeacher::factory()->create([
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $firstTeacher->id,
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.class-subject-teachers.store', $school), [
                'schoolClassId' => $class->id,
                'subjectIds' => [$subject->id],
                'teacherId' => $secondTeacher->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subjectIds.0']);
    }

    public function test_owners_cannot_submit_duplicate_subject_ids(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $class = SchoolClass::factory()->create(['school_id' => $school->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id]);
        $teacher = Teacher::factory()->create(['school_id' => $school->id]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.class-subject-teachers.store', $school), [
                'schoolClassId' => $class->id,
                'subjectIds' => [$subject->id, $subject->id],
                'teacherId' => $teacher->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subjectIds.1']);
    }

    public function test_owners_can_list_assignments_for_their_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $class = SchoolClass::factory()->create(['school_id' => $school->id, 'name' => 'JHS 1A']);
        $english = Subject::factory()->create(['school_id' => $school->id, 'name' => 'English']);
        $math = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Mathematics']);
        $teacher = Teacher::factory()->create(['school_id' => $school->id]);
        ClassSubjectTeacher::factory()->create([
            'school_class_id' => $class->id,
            'subject_id' => $english->id,
            'teacher_id' => $teacher->id,
        ]);
        ClassSubjectTeacher::factory()->create([
            'school_class_id' => $class->id,
            'subject_id' => $math->id,
            'teacher_id' => $teacher->id,
        ]);
        ClassSubjectTeacher::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.class-subject-teachers.index', $school))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.subject.name', 'English')
            ->assertJsonPath('data.1.subject.name', 'Mathematics');
    }

    public function test_owners_cannot_manage_assignments_for_another_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $otherSchool = School::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.class-subject-teachers.index', $otherSchool))
            ->assertForbidden();

        $this->withToken($token)
            ->postJson(route('api.schools.class-subject-teachers.store', $otherSchool), [
                'schoolClassId' => SchoolClass::factory()->create()->id,
                'subjectIds' => [Subject::factory()->create()->id],
                'teacherId' => Teacher::factory()->create()->id,
            ])
            ->assertForbidden();
    }

    public function test_teachers_cannot_manage_class_subject_teacher_assignments(): void
    {
        $teacherClient = Client::factory()->teacher()->create();
        $school = School::factory()->create();
        $token = $teacherClient->createToken('api-teacher', ['permit:teacher'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.class-subject-teachers.index', $school))
            ->assertForbidden();

        $this->withToken($token)
            ->postJson(route('api.schools.class-subject-teachers.store', $school), [
                'schoolClassId' => SchoolClass::factory()->create(['school_id' => $school->id])->id,
                'subjectIds' => [Subject::factory()->create(['school_id' => $school->id])->id],
                'teacherId' => Teacher::factory()->create(['school_id' => $school->id])->id,
            ])
            ->assertForbidden();
    }

    public function test_guests_cannot_manage_class_subject_teacher_assignments(): void
    {
        $school = School::factory()->create();

        $this->getJson(route('api.schools.class-subject-teachers.index', $school))
            ->assertUnauthorized();

        $this->postJson(route('api.schools.class-subject-teachers.store', $school), [
            'schoolClassId' => SchoolClass::factory()->create(['school_id' => $school->id])->id,
            'subjectIds' => [Subject::factory()->create(['school_id' => $school->id])->id],
            'teacherId' => Teacher::factory()->create(['school_id' => $school->id])->id,
        ])->assertUnauthorized();
    }

    public function test_assignment_store_requires_core_fields(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.class-subject-teachers.store', $school), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['schoolClassId', 'subjectIds', 'teacherId']);
    }
}
