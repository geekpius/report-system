<?php

namespace App\Http\Requests\Api\Teacher;

use App\Enums\Role;
use App\Models\Client;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ListTeacherSubjectsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->user();
        $school = $this->route('school');
        $teacher = $this->route('teacher');

        return $client instanceof Client
            && $client->role === Role::Owner
            && $school instanceof School
            && $school->owner_id === $client->id
            && $teacher instanceof Teacher
            && $teacher->school_id === $school->id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
