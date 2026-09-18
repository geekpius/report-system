<?php

namespace App\Http\Requests\Api\School;

use App\Enums\Role;
use App\Models\Client;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ListSchoolRequest extends FormRequest
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
        return [];
    }
}
