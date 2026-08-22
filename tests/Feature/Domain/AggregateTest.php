<?php

namespace Tests\Feature\Domain;

use App\Models\Aggregate;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AggregateTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_aggregate_defines_a_grading_band_for_a_school(): void
    {
        $school = School::factory()->create();
        $aggregate = Aggregate::factory()->create([
            'school_id' => $school->id,
            'min_score' => 80,
            'max_score' => 100,
            'grade' => 'A1',
            'remarks' => 'Excellent',
        ]);

        $this->assertTrue(Str::isUuid($aggregate->id));
        $this->assertTrue($aggregate->school->is($school));
        $this->assertTrue($school->aggregates->contains($aggregate));
    }

    public function test_find_for_score_returns_the_matching_grading_band(): void
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

        $aggregate = Aggregate::findForScore(82, $school->id);

        $this->assertNotNull($aggregate);
        $this->assertSame('A1', $aggregate->grade);
        $this->assertSame('Excellent', $aggregate->remarks);
    }
}
