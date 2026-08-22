<?php

namespace App\Http\Requests\Api\Aggregate;

use App\Enums\Role;
use App\Models\Aggregate;
use App\Models\Client;
use App\Models\School;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAggregateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->user();
        $school = $this->route('school');

        return $client instanceof Client
            && $client->role === Role::Owner
            && $school instanceof School
            && $school->owner_id === $client->id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $school = $this->route('school');

        return [
            'minScore' => ['required', 'integer', 'min:0', 'max:100'],
            'maxScore' => ['required', 'integer', 'min:0', 'max:100', 'gte:minScore'],
            'grade' => [
                'required',
                'string',
                'max:10',
                Rule::unique(Aggregate::class, 'grade')->where('school_id', $school->id),
            ],
            'remarks' => ['required', 'string', 'max:255'],
        ];
    }
}
