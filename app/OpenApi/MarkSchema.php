<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreClassMarkTotalScoreRequest',
    required: ['studentId', 'subjectId', 'schoolClassId', 'studentClassEnrollmentId', 'academicYearId', 'termId', 'classScore'],
    properties: [
        new OA\Property(property: 'studentId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'subjectId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'schoolClassId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'studentClassEnrollmentId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'academicYearId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'termId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'classScore', type: 'number', minimum: 0, example: 40, description: 'Must not exceed classScorePercent on the active mark setting.'),
        new OA\Property(property: 'teacherId', type: 'string', format: 'uuid', nullable: true),
    ],
    type: 'object',
    example: [
        'studentId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
        'subjectId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
        'schoolClassId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
        'studentClassEnrollmentId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
        'academicYearId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
        'termId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
        'classScore' => 40,
        'teacherId' => null,
    ]
)]
#[OA\Schema(
    schema: 'StoreClassMarkDivisionScoreRequest',
    required: ['studentId', 'subjectId', 'schoolClassId', 'studentClassEnrollmentId', 'academicYearId', 'termId', 'classScore', 'homeAssignmentScore', 'projectScore', 'classTestScore'],
    properties: [
        new OA\Property(property: 'studentId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'subjectId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'schoolClassId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'studentClassEnrollmentId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'academicYearId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'termId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'classScore', type: 'number', minimum: 0, example: 12, description: 'Must not exceed classScoreMax on the active mark setting.'),
        new OA\Property(property: 'homeAssignmentScore', type: 'number', minimum: 0, example: 14, description: 'Must not exceed homeAssignmentMax on the active mark setting.'),
        new OA\Property(property: 'projectScore', type: 'number', minimum: 0, example: 13, description: 'Must not exceed projectMax on the active mark setting.'),
        new OA\Property(property: 'classTestScore', type: 'number', minimum: 0, example: 15, description: 'Must not exceed classTestMax on the active mark setting.'),
        new OA\Property(property: 'teacherId', type: 'string', format: 'uuid', nullable: true),
    ],
    type: 'object',
    example: [
        'studentId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
        'subjectId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
        'schoolClassId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
        'studentClassEnrollmentId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
        'academicYearId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
        'termId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
        'classScore' => 12,
        'homeAssignmentScore' => 14,
        'projectScore' => 13,
        'classTestScore' => 15,
        'teacherId' => null,
    ]
)]
#[OA\Schema(
    schema: 'UpdateClassMarkTotalScoreRequest',
    properties: [
        new OA\Property(property: 'classScore', type: 'number', minimum: 0, example: 40),
        new OA\Property(property: 'teacherId', type: 'string', format: 'uuid', nullable: true),
    ],
    type: 'object',
    example: [
        'classScore' => 40,
    ]
)]
#[OA\Schema(
    schema: 'UpdateClassMarkDivisionScoreRequest',
    properties: [
        new OA\Property(property: 'classScore', type: 'number', minimum: 0, example: 12),
        new OA\Property(property: 'homeAssignmentScore', type: 'number', minimum: 0, example: 14),
        new OA\Property(property: 'projectScore', type: 'number', minimum: 0, example: 13),
        new OA\Property(property: 'classTestScore', type: 'number', minimum: 0, example: 15),
        new OA\Property(property: 'teacherId', type: 'string', format: 'uuid', nullable: true),
    ],
    type: 'object',
    example: [
        'classScore' => 12,
        'homeAssignmentScore' => 14,
        'projectScore' => 13,
        'classTestScore' => 15,
    ]
)]
#[OA\Schema(
    schema: 'UpsertExamMarkTotalScoreRequest',
    required: ['studentId', 'subjectId', 'schoolClassId', 'studentClassEnrollmentId', 'academicYearId', 'termId', 'participated', 'examScore'],
    properties: [
        new OA\Property(property: 'studentId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'subjectId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'schoolClassId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'studentClassEnrollmentId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'academicYearId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'termId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'participated', type: 'boolean', example: true),
        new OA\Property(property: 'examScore', type: 'number', minimum: 0, maximum: 100, example: 70, description: 'Exam score out of 100. Required when participated is true.'),
        new OA\Property(property: 'teacherId', type: 'string', format: 'uuid', nullable: true),
    ],
    type: 'object',
    example: [
        'studentId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
        'subjectId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
        'schoolClassId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
        'studentClassEnrollmentId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
        'academicYearId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
        'termId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
        'participated' => true,
        'examScore' => 70,
    ]
)]
#[OA\Schema(
    schema: 'UpsertExamMarkDivisionScoreRequest',
    required: ['studentId', 'subjectId', 'schoolClassId', 'studentClassEnrollmentId', 'academicYearId', 'termId', 'participated', 'examScore'],
    properties: [
        new OA\Property(property: 'studentId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'subjectId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'schoolClassId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'studentClassEnrollmentId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'academicYearId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'termId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'participated', type: 'boolean', example: true),
        new OA\Property(property: 'examScore', type: 'number', minimum: 0, maximum: 100, example: 80, description: 'Exam score out of 100. Required when participated is true.'),
        new OA\Property(property: 'teacherId', type: 'string', format: 'uuid', nullable: true),
    ],
    type: 'object',
    example: [
        'studentId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
        'subjectId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
        'schoolClassId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
        'studentClassEnrollmentId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
        'academicYearId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
        'termId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
        'participated' => true,
        'examScore' => 80,
    ]
)]
class MarkSchema
{
    // Mark request schemas are kept separate so PHP attribute parsing stays valid.
}
