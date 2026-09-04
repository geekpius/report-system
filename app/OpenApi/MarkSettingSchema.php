<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'MarkSetting',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'schoolId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'scoringMode', type: 'string', example: 'total_score'),
        new OA\Property(
            property: 'totalScore',
            properties: [
                new OA\Property(property: 'classScorePercent', type: 'number', format: 'float', example: 50),
                new OA\Property(property: 'examScorePercent', type: 'number', format: 'float', example: 50),
            ],
            type: 'object'
        ),
        new OA\Property(
            property: 'divisionScore',
            properties: [
                new OA\Property(property: 'classScoreMax', type: 'number', format: 'float', example: 15),
                new OA\Property(property: 'homeAssignmentMax', type: 'number', format: 'float', example: 15),
                new OA\Property(property: 'projectMax', type: 'number', format: 'float', example: 15),
                new OA\Property(property: 'classTestMax', type: 'number', format: 'float', example: 15),
                new OA\Property(property: 'divisionTotal', type: 'number', format: 'float', example: 60),
                new OA\Property(property: 'divisionTotalPercent', type: 'number', format: 'float', example: 50),
                new OA\Property(property: 'examAllocationPercent', type: 'number', format: 'float', example: 50),
            ],
            type: 'object'
        ),
    ]
)]
class MarkSettingSchema
{
    // Mark settings schema is kept separate so PHP attribute parsing stays valid.
}
