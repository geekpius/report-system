<?php

namespace App\Http\Requests\Api\SchoolClass;

use App\Enums\Role;
use App\Enums\SchoolClassStatus;
use App\Models\Client;
use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolClassStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->user();
        $school = $this->route('school');
        $schoolClass = $this->route('schoolClass');

        return $client instanceof Client
            && $client->role === Role::Owner
            && $school instanceof School
            && $school->owner_id === $client->id
            && $schoolClass instanceof SchoolClass
            && $schoolClass->school_id === $school->id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(SchoolClassStatus::class)],
        ];
    }
}
