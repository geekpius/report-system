<?php

namespace App\Http\Requests\Api\Term;

use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\Client;
use App\Models\School;
use App\Models\Term;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->user();
        $school = $this->route('school');
        $academicYear = $this->route('academicYear');

        return $client instanceof Client
            && $client->role === Role::Owner
            && $school instanceof School
            && $school->owner_id === $client->id
            && $academicYear instanceof AcademicYear
            && $academicYear->school_id === $school->id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $academicYear = $this->route('academicYear');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Term::class, 'name')->where('academic_year_id', $academicYear->id),
            ],
            'number' => [
                'required',
                'integer',
                'min:1',
                'max:12',
                Rule::unique(Term::class, 'number')->where('academic_year_id', $academicYear->id),
            ],
            'startsOn' => ['required', 'date'],
            'endsOn' => ['required', 'date', 'after:startsOn'],
            'isCurrent' => ['sometimes', 'boolean'],
        ];
    }
}
