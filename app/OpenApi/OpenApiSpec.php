<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Report System API',
    description: 'API documentation for the report system backend. Most school-scoped endpoints require an owner Sanctum token with the `permit:owner` ability.'
)]
#[OA\Server(url: '/api', description: 'API server')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Sanctum personal access token. Send as: Bearer {token}'
)]
#[OA\Schema(
    schema: 'ApiSuccess',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Success.'),
    ]
)]
#[OA\Schema(
    schema: 'ApiError',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Something went wrong.'),
    ]
)]
#[OA\Schema(
    schema: 'ValidationError',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string')
            )
        ),
    ]
)]
#[OA\Schema(
    schema: 'School',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Ridge SHS'),
        new OA\Property(property: 'address', type: 'string', example: '12 Independence Ave'),
        new OA\Property(property: 'city', type: 'string', example: 'Accra'),
        new OA\Property(property: 'type', type: 'string', enum: ['private', 'public']),
        new OA\Property(property: 'imageUrl', type: 'string', nullable: true),
        new OA\Property(property: 'phone', type: 'string', example: '0240000000'),
        new OA\Property(property: 'motto', type: 'string', nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
        new OA\Property(property: 'ownerId', type: 'string', format: 'uuid'),
    ]
)]
#[OA\Schema(
    schema: 'Client',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Ama Owner'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'owner@example.com'),
        new OA\Property(property: 'role', type: 'string', enum: ['owner', 'teacher', 'student']),
        new OA\Property(
            property: 'schools',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/School')
        ),
    ]
)]
#[OA\Schema(
    schema: 'AuthTokenData',
    properties: [
        new OA\Property(property: 'token', type: 'string', example: '1|abcdef...'),
        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
        new OA\Property(property: 'client', ref: '#/components/schemas/Client'),
    ]
)]
#[OA\Schema(
    schema: 'Teacher',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'clientId', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'schoolId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'staffNumber', type: 'string', example: 'STF-1001'),
        new OA\Property(property: 'phone', type: 'string', example: '0240000001'),
    ]
)]
#[OA\Schema(
    schema: 'Student',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'clientId', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'schoolId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'schoolClassId', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'firstName', type: 'string', example: 'Akosua'),
        new OA\Property(property: 'lastName', type: 'string', example: 'Mensah'),
        new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female']),
        new OA\Property(property: 'admissionNumber', type: 'string', example: 'ADM-1001'),
        new OA\Property(property: 'dateOfBirth', type: 'string', format: 'date', example: '2012-04-15'),
    ]
)]
#[OA\Schema(
    schema: 'SchoolClass',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'schoolId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'JHS 1A'),
        new OA\Property(property: 'alias', type: 'string', nullable: true, example: 'Form 1'),
        new OA\Property(property: 'classTeacherId', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'classTeacher', ref: '#/components/schemas/Teacher', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'Subject',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'schoolId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Mathematics'),
    ]
)]
#[OA\Schema(
    schema: 'ClassSubject',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'schoolClassId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'subjectId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'isMandatory', type: 'boolean', example: true),
        new OA\Property(property: 'schoolClass', ref: '#/components/schemas/SchoolClass', nullable: true),
        new OA\Property(property: 'subject', ref: '#/components/schemas/Subject', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'ClassSubjectTeacher',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'schoolClassId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'subjectId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'teacherId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'schoolClass', ref: '#/components/schemas/SchoolClass', nullable: true),
        new OA\Property(property: 'subject', ref: '#/components/schemas/Subject', nullable: true),
        new OA\Property(property: 'teacher', ref: '#/components/schemas/Teacher', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'AcademicYear',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'schoolId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: '2025/2026'),
        new OA\Property(property: 'startsOn', type: 'string', format: 'date', example: '2025-09-01'),
        new OA\Property(property: 'endsOn', type: 'string', format: 'date', example: '2026-07-31'),
        new OA\Property(property: 'isCurrent', type: 'boolean', example: true),
        new OA\Property(
            property: 'terms',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Term'),
            nullable: true
        ),
    ]
)]
#[OA\Schema(
    schema: 'Term',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'academicYearId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Term 1'),
        new OA\Property(property: 'number', type: 'integer', example: 1),
        new OA\Property(property: 'startsOn', type: 'string', format: 'date', example: '2025-09-01'),
        new OA\Property(property: 'endsOn', type: 'string', format: 'date', example: '2025-12-15'),
        new OA\Property(property: 'isCurrent', type: 'boolean', example: true),
        new OA\Property(property: 'academicYear', ref: '#/components/schemas/AcademicYear', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'Aggregate',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'schoolId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'minScore', type: 'integer', example: 80),
        new OA\Property(property: 'maxScore', type: 'integer', example: 100),
        new OA\Property(property: 'grade', type: 'string', example: 'A'),
        new OA\Property(property: 'remarks', type: 'string', example: 'Excellent'),
    ]
)]
#[OA\Schema(
    schema: 'Mark',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'schoolId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'studentId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'subjectId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'schoolClassId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'studentClassEnrollmentId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'academicYearId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'termId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'participated', type: 'boolean', example: true),
        new OA\Property(property: 'classScore', type: 'number', example: 10),
        new OA\Property(property: 'homeAssignmentScore', type: 'number', example: 12),
        new OA\Property(property: 'projectScore', type: 'number', example: 14),
        new OA\Property(property: 'classTestScore', type: 'number', example: 13),
        new OA\Property(property: 'continuousAssessmentScore', type: 'number'),
        new OA\Property(property: 'continuousAssessmentContribution', type: 'number'),
        new OA\Property(property: 'examScore', type: 'number', example: 70),
        new OA\Property(property: 'examContribution', type: 'number'),
        new OA\Property(property: 'totalScore', type: 'number'),
        new OA\Property(property: 'classScoreUpdatedAt', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'examScoreUpdatedAt', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'closeClassScoreEntry', type: 'boolean', example: false),
        new OA\Property(property: 'closeExamScoreEntry', type: 'boolean', example: false),
        new OA\Property(property: 'grade', type: 'string', nullable: true, example: 'A'),
        new OA\Property(property: 'gradeRemark', type: 'string', nullable: true, example: 'Excellent'),
        new OA\Property(property: 'teacherId', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'school', ref: '#/components/schemas/School', nullable: true),
        new OA\Property(property: 'student', ref: '#/components/schemas/Student', nullable: true),
        new OA\Property(property: 'subject', ref: '#/components/schemas/Subject', nullable: true),
        new OA\Property(property: 'schoolClass', ref: '#/components/schemas/SchoolClass', nullable: true),
        new OA\Property(property: 'academicYear', ref: '#/components/schemas/AcademicYear', nullable: true),
        new OA\Property(property: 'term', ref: '#/components/schemas/Term', nullable: true),
        new OA\Property(property: 'teacher', ref: '#/components/schemas/Teacher', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'MarkEntry',
    properties: [
        new OA\Property(property: 'studentId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'subjectId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'schoolClassId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'studentClassEnrollmentId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'academicYearId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'termId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'student', ref: '#/components/schemas/Student'),
        new OA\Property(property: 'mark', ref: '#/components/schemas/MarkEntryMark', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'MarkEntryMark',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'participated', type: 'boolean'),
        new OA\Property(property: 'classScore', type: 'number'),
        new OA\Property(property: 'homeAssignmentScore', type: 'number'),
        new OA\Property(property: 'projectScore', type: 'number'),
        new OA\Property(property: 'classTestScore', type: 'number'),
        new OA\Property(property: 'continuousAssessmentScore', type: 'number'),
        new OA\Property(property: 'continuousAssessmentContribution', type: 'number'),
        new OA\Property(property: 'examScore', type: 'number'),
        new OA\Property(property: 'examContribution', type: 'number'),
        new OA\Property(property: 'totalScore', type: 'number'),
        new OA\Property(property: 'classScoreUpdatedAt', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'examScoreUpdatedAt', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'closeClassScoreEntry', type: 'boolean'),
        new OA\Property(property: 'closeExamScoreEntry', type: 'boolean'),
        new OA\Property(property: 'grade', type: 'string', nullable: true),
        new OA\Property(property: 'gradeRemark', type: 'string', nullable: true),
        new OA\Property(property: 'teacherId', type: 'string', format: 'uuid', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'StudentTermResult',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'studentId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'schoolClassId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'studentClassEnrollmentId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'academicYearId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'termId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'subjectsCount', type: 'integer', example: 8),
        new OA\Property(property: 'totalScore', type: 'number', example: 640),
        new OA\Property(property: 'averageScore', type: 'number', example: 80),
        new OA\Property(property: 'classPosition', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'calculatedAt', type: 'string', format: 'date-time'),
        new OA\Property(property: 'student', ref: '#/components/schemas/Student', nullable: true),
        new OA\Property(property: 'schoolClass', ref: '#/components/schemas/SchoolClass', nullable: true),
        new OA\Property(property: 'classEnrollment', ref: '#/components/schemas/StudentClassEnrollment', nullable: true),
        new OA\Property(property: 'academicYear', ref: '#/components/schemas/AcademicYear', nullable: true),
        new OA\Property(property: 'term', ref: '#/components/schemas/Term', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'StudentClassEnrollment',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'studentId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'schoolClassId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'academicYearId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'promoted', 'transferred', 'repeated']),
        new OA\Property(property: 'startedAt', type: 'string', format: 'date-time'),
        new OA\Property(property: 'endedAt', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'schoolClass', ref: '#/components/schemas/SchoolClass', nullable: true),
        new OA\Property(property: 'academicYear', ref: '#/components/schemas/AcademicYear', nullable: true),
        new OA\Property(
            property: 'studentSubjects',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/StudentSubject'),
            nullable: true
        ),
    ]
)]
#[OA\Schema(
    schema: 'StudentSubject',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'studentId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'subjectId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'schoolClassId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'studentClassEnrollmentId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'dropped', 'completed']),
        new OA\Property(property: 'subject', ref: '#/components/schemas/Subject', nullable: true),
        new OA\Property(property: 'schoolClass', ref: '#/components/schemas/SchoolClass', nullable: true),
        new OA\Property(property: 'classEnrollment', ref: '#/components/schemas/StudentClassEnrollment', nullable: true),
    ]
)]
class OpenApiSpec
{
    // Shared OpenAPI schemas for l5-swagger.
}
