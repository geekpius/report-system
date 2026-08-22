<?php

namespace Tests\Feature\Api;

use App\Models\AcademicYear;
use App\Models\Client;
use App\Models\School;
use App\Models\Term;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TermTest extends TestCase
{
    use RefreshDatabase;

    public function test_owners_can_create_a_term_for_an_academic_year(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.academic-years.terms.store', [$school, $academicYear]), [
                'name' => 'Term 1',
                'number' => 1,
                'startsOn' => '2025-09-01',
                'endsOn' => '2025-12-15',
                'isCurrent' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Term 1')
            ->assertJsonPath('data.number', 1)
            ->assertJsonPath('data.startsOn', '2025-09-01')
            ->assertJsonPath('data.endsOn', '2025-12-15')
            ->assertJsonPath('data.isCurrent', true)
            ->assertJsonPath('data.academicYearId', $academicYear->id);

        $this->assertDatabaseHas('terms', [
            'academic_year_id' => $academicYear->id,
            'name' => 'Term 1',
            'number' => 1,
            'is_current' => true,
        ]);
    }

    public function test_setting_a_current_term_unsets_the_previous_current_term(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id]);
        $existing = Term::factory()->current()->create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Term 1',
            'number' => 1,
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.academic-years.terms.store', [$school, $academicYear]), [
                'name' => 'Term 2',
                'number' => 2,
                'startsOn' => '2026-01-10',
                'endsOn' => '2026-04-15',
                'isCurrent' => true,
            ])
            ->assertCreated();

        $this->assertFalse($existing->fresh()->is_current);
        $this->assertSame(1, Term::query()->where('academic_year_id', $academicYear->id)->where('is_current', true)->count());
    }

    public function test_owners_can_list_terms_for_an_academic_year(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id]);
        Term::factory()->create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Term 1',
            'number' => 1,
        ]);
        Term::factory()->create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Term 2',
            'number' => 2,
        ]);
        Term::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.academic-years.terms.index', [$school, $academicYear]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Term 1')
            ->assertJsonPath('data.1.name', 'Term 2');
    }

    public function test_owners_cannot_manage_terms_for_an_academic_year_in_another_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $otherYear = AcademicYear::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.academic-years.terms.index', [$school, $otherYear]))
            ->assertForbidden();

        $this->withToken($token)
            ->postJson(route('api.schools.academic-years.terms.store', [$school, $otherYear]), [
                'name' => 'Term 1',
                'number' => 1,
                'startsOn' => '2025-09-01',
                'endsOn' => '2025-12-15',
            ])
            ->assertForbidden();
    }

    public function test_owners_cannot_create_duplicate_term_numbers_in_the_same_academic_year(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id]);
        Term::factory()->create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Term 1',
            'number' => 1,
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.academic-years.terms.store', [$school, $academicYear]), [
                'name' => 'First Term',
                'number' => 1,
                'startsOn' => '2025-09-01',
                'endsOn' => '2025-12-15',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['number']);
    }

    public function test_term_store_requires_core_fields(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.academic-years.terms.store', [$school, $academicYear]), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'number', 'startsOn', 'endsOn']);
    }
}
