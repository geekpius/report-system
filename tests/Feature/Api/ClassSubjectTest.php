<?php

namespace Tests\Feature\Api;

use App\Models\ClassSubject;
use App\Models\Client;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassSubjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_owners_can_assign_mandatory_and_elective_subjects_to_a_class(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $class = SchoolClass::factory()->create(['school_id' => $school->id, 'name' => 'JHS 1']);
        $mathematics = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Mathematics']);
        $english = Subject::factory()->create(['school_id' => $school->id, 'name' => 'English']);
        $french = Subject::factory()->create(['school_id' => $school->id, 'name' => 'French']);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.classes.subjects.store', [$school, $class]), [
                'subjects' => [
                    ['subjectId' => $mathematics->id, 'isMandatory' => true],
                    ['subjectId' => $english->id, 'isMandatory' => true],
                    ['subjectId' => $french->id, 'isMandatory' => false],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.subject.name', 'Mathematics')
            ->assertJsonPath('data.0.isMandatory', true)
            ->assertJsonPath('data.2.subject.name', 'French')
            ->assertJsonPath('data.2.isMandatory', false);

        $this->assertDatabaseHas('class_subjects', [
            'school_class_id' => $class->id,
            'subject_id' => $mathematics->id,
            'is_mandatory' => true,
        ]);
        $this->assertDatabaseHas('class_subjects', [
            'school_class_id' => $class->id,
            'subject_id' => $french->id,
            'is_mandatory' => false,
        ]);
    }

    public function test_subjects_default_to_mandatory_when_flag_is_omitted(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $class = SchoolClass::factory()->create(['school_id' => $school->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.classes.subjects.store', [$school, $class]), [
                'subjects' => [
                    ['subjectId' => $subject->id],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.0.isMandatory', true);

        $this->assertDatabaseHas('class_subjects', [
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'is_mandatory' => true,
        ]);
    }

    public function test_owners_can_list_subjects_assigned_to_a_class(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $class = SchoolClass::factory()->create(['school_id' => $school->id]);
        $english = Subject::factory()->create(['school_id' => $school->id, 'name' => 'English']);
        $math = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Mathematics']);
        ClassSubject::factory()->create([
            'school_class_id' => $class->id,
            'subject_id' => $english->id,
            'is_mandatory' => true,
        ]);
        ClassSubject::factory()->create([
            'school_class_id' => $class->id,
            'subject_id' => $math->id,
            'is_mandatory' => false,
        ]);
        ClassSubject::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.classes.subjects.index', [$school, $class]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.subject.name', 'English')
            ->assertJsonPath('data.0.isMandatory', true)
            ->assertJsonPath('data.1.subject.name', 'Mathematics')
            ->assertJsonPath('data.1.isMandatory', false);
    }

    public function test_owners_cannot_assign_subjects_from_another_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $class = SchoolClass::factory()->create(['school_id' => $school->id]);
        $otherSubject = Subject::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.classes.subjects.store', [$school, $class]), [
                'subjects' => [
                    ['subjectId' => $otherSubject->id],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subjects.0.subjectId']);
    }

    public function test_owners_cannot_assign_the_same_subject_to_a_class_twice(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $class = SchoolClass::factory()->create(['school_id' => $school->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id]);
        ClassSubject::factory()->create([
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.classes.subjects.store', [$school, $class]), [
                'subjects' => [
                    ['subjectId' => $subject->id],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subjects.0.subjectId']);
    }

    public function test_owners_cannot_submit_duplicate_subject_ids(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $class = SchoolClass::factory()->create(['school_id' => $school->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.classes.subjects.store', [$school, $class]), [
                'subjects' => [
                    ['subjectId' => $subject->id],
                    ['subjectId' => $subject->id],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subjects.1.subjectId']);
    }

    public function test_owners_cannot_manage_class_subjects_for_a_class_in_another_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $otherClass = SchoolClass::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.classes.subjects.index', [$school, $otherClass]))
            ->assertForbidden();

        $this->withToken($token)
            ->postJson(route('api.schools.classes.subjects.store', [$school, $otherClass]), [
                'subjects' => [
                    ['subjectId' => Subject::factory()->create()->id],
                ],
            ])
            ->assertForbidden();
    }

    public function test_teachers_cannot_manage_class_subjects(): void
    {
        $teacher = Client::factory()->teacher()->create();
        $school = School::factory()->create();
        $class = SchoolClass::factory()->create(['school_id' => $school->id]);
        $token = $teacher->createToken('api-teacher', ['permit:teacher'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.classes.subjects.index', [$school, $class]))
            ->assertForbidden();

        $this->withToken($token)
            ->postJson(route('api.schools.classes.subjects.store', [$school, $class]), [
                'subjects' => [
                    ['subjectId' => Subject::factory()->create(['school_id' => $school->id])->id],
                ],
            ])
            ->assertForbidden();
    }

    public function test_guests_cannot_manage_class_subjects(): void
    {
        $school = School::factory()->create();
        $class = SchoolClass::factory()->create(['school_id' => $school->id]);

        $this->getJson(route('api.schools.classes.subjects.index', [$school, $class]))
            ->assertUnauthorized();

        $this->postJson(route('api.schools.classes.subjects.store', [$school, $class]), [
            'subjects' => [
                ['subjectId' => Subject::factory()->create(['school_id' => $school->id])->id],
            ],
        ])->assertUnauthorized();
    }

    public function test_class_subject_store_requires_subjects(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $class = SchoolClass::factory()->create(['school_id' => $school->id]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.classes.subjects.store', [$school, $class]), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subjects']);
    }
}
