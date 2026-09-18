<?php

namespace App\Http\Requests\Api\School;

use App\Enums\Role;
use App\Enums\SchoolType;
use App\Models\Client;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->user();

        return $client instanceof Client
            && $client->role === Role::Owner;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(SchoolType::class)],
            'phone' => ['required', 'string', 'max:255'],
            'motto' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }
}
