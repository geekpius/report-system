<?php

namespace Tests\Feature\Api;

use App\Models\Aggregate;
use App\Models\Client;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AggregateTest extends TestCase
{
    use RefreshDatabase;

    public function test_owners_can_create_a_grading_band_for_their_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.aggregates.store', $school), [
                'minScore' => 80,
                'maxScore' => 100,
                'grade' => 'A1',
                'remarks' => 'Excellent',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.minScore', 80)
            ->assertJsonPath('data.maxScore', 100)
            ->assertJsonPath('data.grade', 'A1')
            ->assertJsonPath('data.remarks', 'Excellent')
            ->assertJsonPath('data.schoolId', $school->id);

        $this->assertDatabaseHas('aggregates', [
            'school_id' => $school->id,
            'min_score' => 80,
            'max_score' => 100,
            'grade' => 'A1',
            'remarks' => 'Excellent',
        ]);
    }

    public function test_owners_can_list_grading_bands_for_their_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
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
        Aggregate::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.aggregates.index', $school))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.grade', 'A1')
            ->assertJsonPath('data.1.grade', 'B2');
    }

    public function test_owners_can_update_a_grading_band(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $aggregate = Aggregate::factory()->create([
            'school_id' => $school->id,
            'min_score' => 80,
            'max_score' => 100,
            'grade' => 'A1',
            'remarks' => 'Excellent',
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.schools.aggregates.update', [$school, $aggregate]), [
                'minScore' => 75,
                'remarks' => 'Outstanding',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.minScore', 75)
            ->assertJsonPath('data.maxScore', 100)
            ->assertJsonPath('data.remarks', 'Outstanding');

        $this->assertDatabaseHas('aggregates', [
            'id' => $aggregate->id,
            'min_score' => 75,
            'max_score' => 100,
            'remarks' => 'Outstanding',
        ]);
    }

    public function test_owners_cannot_create_a_duplicate_grade_in_the_same_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        Aggregate::factory()->create([
            'school_id' => $school->id,
            'grade' => 'A1',
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.aggregates.store', $school), [
                'minScore' => 80,
                'maxScore' => 100,
                'grade' => 'A1',
                'remarks' => 'Excellent',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['grade']);
    }

    public function test_max_score_must_be_greater_than_or_equal_to_min_score(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.aggregates.store', $school), [
                'minScore' => 80,
                'maxScore' => 70,
                'grade' => 'B2',
                'remarks' => 'Very Good',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['maxScore']);
    }

    public function test_owners_cannot_manage_aggregates_for_another_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $otherSchool = School::factory()->create();
        $aggregate = Aggregate::factory()->create(['school_id' => $otherSchool->id]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.aggregates.index', $otherSchool))
            ->assertForbidden();

        $this->withToken($token)
            ->postJson(route('api.schools.aggregates.store', $otherSchool), [
                'minScore' => 80,
                'maxScore' => 100,
                'grade' => 'A1',
                'remarks' => 'Excellent',
            ])
            ->assertForbidden();

        $this->withToken($token)
            ->putJson(route('api.schools.aggregates.update', [$otherSchool, $aggregate]), [
                'remarks' => 'Updated',
            ])
            ->assertForbidden();
    }

    public function test_aggregate_store_requires_core_fields(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.aggregates.store', $school), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['minScore', 'maxScore', 'grade', 'remarks']);
    }
}
