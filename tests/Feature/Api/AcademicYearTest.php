<?php

namespace Tests\Feature\Api;

use App\Models\AcademicYear;
use App\Models\Client;
use App\Models\School;
use App\Models\Term;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicYearTest extends TestCase
{
    use RefreshDatabase;

    public function test_owners_can_create_an_academic_year(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.academic-years.store', $school), [
                'name' => '2025/2026',
                'startsOn' => '2025-09-01',
                'endsOn' => '2026-07-31',
                'isCurrent' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', '2025/2026')
            ->assertJsonPath('data.startsOn', '2025-09-01')
            ->assertJsonPath('data.endsOn', '2026-07-31')
            ->assertJsonPath('data.isCurrent', true)
            ->assertJsonPath('data.schoolId', $school->id);

        $this->assertDatabaseHas('academic_years', [
            'school_id' => $school->id,
            'name' => '2025/2026',
            'is_current' => true,
        ]);
    }

    public function test_setting_a_current_academic_year_unsets_the_previous_current_year(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $existing = AcademicYear::factory()->current()->create([
            'school_id' => $school->id,
            'name' => '2024/2025',
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.academic-years.store', $school), [
                'name' => '2025/2026',
                'startsOn' => '2025-09-01',
                'endsOn' => '2026-07-31',
                'isCurrent' => true,
            ])
            ->assertCreated();

        $this->assertFalse($existing->fresh()->is_current);
        $this->assertSame(1, AcademicYear::query()->where('school_id', $school->id)->where('is_current', true)->count());
    }

    public function test_owners_can_list_academic_years_for_their_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $year = AcademicYear::factory()->create([
            'school_id' => $school->id,
            'name' => '2025/2026',
        ]);
        Term::factory()->create([
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'number' => 1,
        ]);
        AcademicYear::factory()->create(['name' => 'Other School Year']);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.academic-years.index', $school))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', '2025/2026')
            ->assertJsonCount(1, 'data.0.terms');
    }

    public function test_owners_cannot_create_a_duplicate_academic_year_name(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        AcademicYear::factory()->create([
            'school_id' => $school->id,
            'name' => '2025/2026',
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.academic-years.store', $school), [
                'name' => '2025/2026',
                'startsOn' => '2025-09-01',
                'endsOn' => '2026-07-31',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_owners_cannot_manage_academic_years_for_another_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $otherSchool = School::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.academic-years.index', $otherSchool))
            ->assertForbidden();

        $this->withToken($token)
            ->postJson(route('api.schools.academic-years.store', $otherSchool), [
                'name' => '2025/2026',
                'startsOn' => '2025-09-01',
                'endsOn' => '2026-07-31',
            ])
            ->assertForbidden();
    }

    public function test_academic_year_store_requires_core_fields(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.academic-years.store', $school), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'startsOn', 'endsOn']);
    }
}
