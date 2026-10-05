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
    protected function totalScorePayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'scoringMode' => ScoringMode::TotalScore->value,
            'totalScore' => [
                'classScorePercent' => 50,
                'examScorePercent' => 50,
            ],
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    protected function divisionScorePayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'scoringMode' => ScoringMode::DivisionScore->value,
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

    public function test_owners_can_update_total_score_settings_without_division_score(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        MarkSetting::factory()->division()->create([
            'school_id' => $school->id,
            'class_score_max' => 10,
            'home_assignment_max' => 20,
            'project_max' => 15,
            'class_test_max' => 15,
            'exam_allocation_percent' => 40,
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.schools.mark-settings.update', $school), $this->totalScorePayload([
                'totalScore' => [
                    'classScorePercent' => 40,
                    'examScorePercent' => 60,
                ],
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.scoringMode', ScoringMode::TotalScore->value)
            ->assertJsonPath('data.totalScore.classScorePercent', 40)
            ->assertJsonPath('data.totalScore.examScorePercent', 60)
            ->assertJsonPath('data.divisionScore.classScoreMax', 10)
            ->assertJsonPath('data.divisionScore.examAllocationPercent', 40);

        $this->assertDatabaseHas('mark_settings', [
            'school_id' => $school->id,
            'scoring_mode' => ScoringMode::TotalScore->value,
            'class_score_percent' => 40,
            'exam_score_percent' => 60,
            'class_score_max' => 10,
            'exam_allocation_percent' => 40,
        ]);
    }

    public function test_owners_can_update_division_score_settings_without_total_score(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        MarkSetting::factory()->create([
            'school_id' => $school->id,
            'class_score_percent' => 45,
            'exam_score_percent' => 55,
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.schools.mark-settings.update', $school), $this->divisionScorePayload([
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
            ->assertJsonPath('data.totalScore.classScorePercent', 45)
            ->assertJsonPath('data.totalScore.examScorePercent', 55)
            ->assertJsonPath('data.divisionScore.divisionTotal', 60)
            ->assertJsonPath('data.divisionScore.divisionTotalPercent', 60)
            ->assertJsonPath('data.divisionScore.examAllocationPercent', 40);

        $this->assertDatabaseHas('mark_settings', [
            'school_id' => $school->id,
            'scoring_mode' => ScoringMode::DivisionScore->value,
            'class_score_percent' => 45,
            'exam_score_percent' => 55,
            'division_total' => 60,
            'division_total_percent' => 60,
        ]);
    }

    public function test_mark_settings_require_total_score_when_mode_is_total_score(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        MarkSetting::factory()->create(['school_id' => $school->id]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.schools.mark-settings.update', $school), [
                'scoringMode' => ScoringMode::TotalScore->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'totalScore',
                'totalScore.classScorePercent',
                'totalScore.examScorePercent',
            ]);
    }

    public function test_mark_settings_require_division_score_when_mode_is_division_score(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        MarkSetting::factory()->create(['school_id' => $school->id]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.schools.mark-settings.update', $school), [
                'scoringMode' => ScoringMode::DivisionScore->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'divisionScore',
                'divisionScore.classScoreMax',
                'divisionScore.homeAssignmentMax',
                'divisionScore.projectMax',
                'divisionScore.classTestMax',
                'divisionScore.examAllocationPercent',
            ]);
    }

    public function test_mark_settings_require_total_score_percents_to_sum_to_100(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        MarkSetting::factory()->create(['school_id' => $school->id]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.schools.mark-settings.update', $school), $this->totalScorePayload([
                'totalScore' => [
                    'classScorePercent' => 40,
                    'examScorePercent' => 40,
                ],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['totalScore.examScorePercent']);
    }

    public function test_owners_cannot_manage_mark_settings_for_another_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $otherSchool = School::factory()->create();
        MarkSetting::factory()->create(['school_id' => $otherSchool->id]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.mark-settings.show', $otherSchool))
            ->assertForbidden();

        $this->withToken($token)
            ->putJson(route('api.schools.mark-settings.update', $otherSchool), $this->totalScorePayload())
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
