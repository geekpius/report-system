<?php

namespace App\Http\Requests\Api\Teacher;

use App\Enums\Role;
use App\Models\Client;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(Client::class, 'email')],
            'staffNumber' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Teacher::class, 'staff_number')->where('school_id', $school->id),
            ],
            'phone' => ['required', 'string', 'max:255'],
        ];
    }
}
