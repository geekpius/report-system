<?php

namespace App\Http\Requests\Api\AcademicYear;

use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\Client;
use App\Models\School;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicYearRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(AcademicYear::class, 'name')->where('school_id', $school->id),
            ],
            'startsOn' => ['required', 'date'],
            'endsOn' => ['required', 'date', 'after:startsOn'],
            'isCurrent' => ['sometimes', 'boolean'],
        ];
    }
}
