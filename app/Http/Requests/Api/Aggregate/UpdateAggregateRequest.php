<?php

namespace App\Http\Requests\Api\Aggregate;

use App\Enums\Role;
use App\Models\Aggregate;
use App\Models\Client;
use App\Models\School;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAggregateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->user();
        $school = $this->route('school');
        $aggregate = $this->route('aggregate');

        return $client instanceof Client
            && $client->role === Role::Owner
            && $school instanceof School
            && $school->owner_id === $client->id
            && $aggregate instanceof Aggregate
            && $aggregate->school_id === $school->id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $school = $this->route('school');
        /** @var Aggregate $aggregate */
        $aggregate = $this->route('aggregate');

        return [
            'minScore' => [
                'sometimes',
                'integer',
                'min:0',
                'max:100',
                function (string $attribute, mixed $value, Closure $fail) use ($aggregate): void {
                    $maxScore = $this->input('maxScore', $aggregate->max_score);

                    if ((int) $value > (int) $maxScore) {
                        $fail('The min score must be less than or equal to the max score.');
                    }
                },
            ],
            'maxScore' => [
                'sometimes',
                'integer',
                'min:0',
                'max:100',
                function (string $attribute, mixed $value, Closure $fail) use ($aggregate): void {
                    $minScore = $this->input('minScore', $aggregate->min_score);

                    if ((int) $value < (int) $minScore) {
                        $fail('The max score must be greater than or equal to the min score.');
                    }
                },
            ],
            'grade' => [
                'sometimes',
                'string',
                'max:10',
                Rule::unique(Aggregate::class, 'grade')
                    ->where('school_id', $school->id)
                    ->ignore($aggregate->id),
            ],
            'remarks' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
