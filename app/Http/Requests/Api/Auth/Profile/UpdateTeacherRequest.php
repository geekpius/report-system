<?php

namespace App\Http\Requests\Api\Auth\Profile;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Teacher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends FormRequest
{
    /**
     * Determine if the client is authorized to update this teacher.
     */
    public function authorize(): bool
    {
        $client = $this->user();
        $teacher = $this->route('teacher');

        return $client instanceof Client
            && $client->role === Role::Teacher
            && $teacher instanceof Teacher
            && $teacher->client_id === $client->id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $teacher = $this->route('teacher');

        return [
            'staffNumber' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Teacher::class, 'staff_number')
                    ->where('school_id', $teacher->school_id)
                    ->ignore($teacher->id),
            ],
            'phone' => ['required', 'string', 'max:255'],
        ];
    }
}
