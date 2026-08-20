<?php

namespace App\Http\Resources;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Student
 */
class StudentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'clientId' => $this->client_id,
            'schoolId' => $this->school_id,
            'schoolClassId' => $this->school_class_id,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'gender' => $this->gender->value,
            'admissionNumber' => $this->admission_number,
            'dateOfBirth' => $this->date_of_birth->toDateString(),
            'client' => new ClientResource($this->whenLoaded('client')),
            'school' => new SchoolResource($this->whenLoaded('school')),
            'schoolClass' => new SchoolClassResource($this->whenLoaded('schoolClass')),
        ];
    }
}
