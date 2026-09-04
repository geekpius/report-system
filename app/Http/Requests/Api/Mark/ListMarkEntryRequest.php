<?php

namespace App\Http\Requests\Api\Mark;

use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\Client;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Term;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListMarkEntryRequest extends FormRequest
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

        if (! $school instanceof School) {
            return [];
        }

        return [
            'schoolClassId' => [
                'required',
                'uuid',
                Rule::exists(SchoolClass::class, 'id')->where('school_id', $school->id),
            ],
            'subjectId' => [
                'required',
                'uuid',
                Rule::exists(Subject::class, 'id')->where('school_id', $school->id),
            ],
            'termId' => [
                'required',
                'uuid',
                function (string $attribute, mixed $value, Closure $fail) use ($school): void {
                    if (! is_string($value)) {
                        return;
                    }

                    $exists = Term::query()
                        ->whereKey($value)
                        ->whereHas('academicYear', fn ($query) => $query->where('school_id', $school->id))
                        ->exists();

                    if (! $exists) {
                        $fail('The selected term does not belong to this school.');
                    }
                },
            ],
            'academicYearId' => [
                'sometimes',
                'uuid',
                Rule::exists(AcademicYear::class, 'id')->where('school_id', $school->id),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $academicYearId = $this->input('academicYearId');
            $termId = $this->input('termId');

            if (! is_string($academicYearId) || ! is_string($termId)) {
                return;
            }

            $termAcademicYearId = Term::query()->whereKey($termId)->value('academic_year_id');

            if ($termAcademicYearId !== $academicYearId) {
                $validator->errors()->add('academicYearId', 'The academic year must match the selected term.');
            }
        });
    }
}
