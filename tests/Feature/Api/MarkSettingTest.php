<?php

namespace Tests\Feature\Api;

use App\Enums\ScoringMode;
use App\Models\Client;
use App\Models\MarkSetting;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkSettingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    protected function validPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'scoringMode' => ScoringMode::TotalScore->value,
            'totalScore' => [
                'classScorePercent' => 50,
                'examScorePercent' => 50,
            ],
            'divisionScore' => [
                'classScoreMax' => 15,
                'homeAssignmentMax' => 15,
                'projectMax' => 15,
                'classTestMax' => 15,
                'examAllocationPercent' => 50,
            ],
        ], $overrides);
    }

    public function test_owners_can_get_default_mark_settings_for_their_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.mark-settings.show', $school))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.scoringMode', ScoringMode::TotalScore->value)
            ->assertJsonPath('data.totalScore.classScorePercent', 50)
            ->assertJsonPath('data.totalScore.examScorePercent', 50)
            ->assertJsonPath('data.divisionScore.classScoreMax', 0)
            ->assertJsonPath('data.divisionScore.homeAssignmentMax', 0)
            ->assertJsonPath('data.divisionScore.projectMax', 0)
            ->assertJsonPath('data.divisionScore.classTestMax', 0)
            ->assertJsonPath('data.divisionScore.divisionTotal', 0)
            ->assertJsonPath('data.divisionScore.divisionTotalPercent', 50)
            ->assertJsonPath('data.divisionScore.examAllocationPercent', 50);

        $this->assertDatabaseHas('mark_settings', [
            'school_id' => $school->id,
            'scoring_mode' => ScoringMode::TotalScore->value,
        ]);
    }

    public function test_owners_can_update_mark_settings_and_observer_derives_totals(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.schools.mark-settings.update', $school), $this->validPayload([
                'scoringMode' => ScoringMode::DivisionScore->value,
                'totalScore' => [
                    'classScorePercent' => 40,
                    'examScorePercent' => 60,
                ],
                'divisionScore' => [
                    'classScoreMax' => 10,
                    'homeAssignmentMax' => 20,
                    'projectMax' => 15,
                    'classTestMax' => 15,
                    'examAllocationPercent' => 40,
                ],
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.scoringMode', ScoringMode::DivisionScore->value)
            ->assertJsonPath('data.totalScore.classScorePercent', 40)
            ->assertJsonPath('data.totalScore.examScorePercent', 60)
            ->assertJsonPath('data.divisionScore.divisionTotal', 60)
            ->assertJsonPath('data.divisionScore.divisionTotalPercent', 60)
            ->assertJsonPath('data.divisionScore.examAllocationPercent', 40);

        $this->assertDatabaseHas('mark_settings', [
            'school_id' => $school->id,
            'scoring_mode' => ScoringMode::DivisionScore->value,
            'division_total' => 60,
            'division_total_percent' => 60,
        ]);
    }

    public function test_mark_settings_require_total_score_percents_to_sum_to_100(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.schools.mark-settings.update', $school), $this->validPayload([
                'totalScore' => [
                    'classScorePercent' => 40,
                    'examScorePercent' => 40,
                ],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['totalScore.examScorePercent']);
    }

    public function test_mark_settings_require_exam_allocation_percent(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $payload = $this->validPayload();
        unset($payload['divisionScore']['examAllocationPercent']);

        $this->withToken($token)
            ->putJson(route('api.schools.mark-settings.update', $school), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['divisionScore.examAllocationPercent']);
    }

    public function test_owners_cannot_manage_mark_settings_for_another_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $otherSchool = School::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.mark-settings.show', $otherSchool))
            ->assertForbidden();

        $this->withToken($token)
            ->putJson(route('api.schools.mark-settings.update', $otherSchool), $this->validPayload())
            ->assertForbidden();
    }

    public function test_mark_setting_observer_derives_fields_on_create(): void
    {
        $setting = MarkSetting::factory()->division()->create();

        $this->assertSame('60.00', $setting->division_total);
        $this->assertSame('50.00', $setting->division_total_percent);
        $this->assertSame(ScoringMode::DivisionScore, $setting->scoring_mode);
    }
}
