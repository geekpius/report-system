<?php

use App\Http\Controllers\Api\AcademicYear\AcademicYearController;
use App\Http\Controllers\Api\Aggregate\AggregateController;
use App\Http\Controllers\Api\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\Profile\UpdateSchoolController;
use App\Http\Controllers\Api\Auth\Profile\UpdateStudentController;
use App\Http\Controllers\Api\Auth\Profile\UpdateTeacherController;
use App\Http\Controllers\Api\Auth\RegisteredClientController;
use App\Http\Controllers\Api\ClassSubject\ClassSubjectController;
use App\Http\Controllers\Api\ClassSubjectTeacher\ClassSubjectTeacherController;
use App\Http\Controllers\Api\SchoolClass\SchoolClassController;
use App\Http\Controllers\Api\StudentClassEnrollment\StudentClassEnrollmentController;
use App\Http\Controllers\Api\StudentSubject\StudentSubjectController;
use App\Http\Controllers\Api\Subject\SubjectController;
use App\Http\Controllers\Api\Term\TermController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/sign-up', [RegisteredClientController::class, 'store'])
    ->middleware('throttle:api-signup')
    ->name('api.auth.sign-up');

Route::post('/auth/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('throttle:api-login')
    ->name('api.auth.login');

Route::post('/auth/forgot-password', [PasswordResetController::class, 'store'])
    ->middleware('throttle:api-forgot-password')
    ->name('api.auth.forgot-password');

Route::post('/auth/reset-password', [PasswordResetController::class, 'update'])
    ->middleware('throttle:api-forgot-password')
    ->name('api.auth.reset-password');

Route::middleware('auth:sanctum')->group(function () {
    // auth routes
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthenticatedSessionController::class, 'show'])
            ->name('api.me');

        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
            ->name('api.logout');

        // profile routes
        Route::put('/profile/schools/{school}', [UpdateSchoolController::class, 'update'])
            ->middleware('abilities:permit:owner')
            ->name('api.profile.schools.update');

        Route::put('/profile/teachers/{teacher}', [UpdateTeacherController::class, 'update'])
            ->middleware('abilities:permit:teacher')
            ->name('api.profile.teachers.update');

        Route::put('/profile/students/{student}', [UpdateStudentController::class, 'update'])
            ->middleware('abilities:permit:student')
            ->name('api.profile.students.update');
    });

    // school class routes
    Route::prefix('schools/{school}/classes')->middleware('abilities:permit:owner')->group(function () {
        Route::get('/', [SchoolClassController::class, 'index'])
            ->name('api.schools.classes.index');

        Route::post('/', [SchoolClassController::class, 'store'])
            ->name('api.schools.classes.store');

        Route::prefix('{schoolClass}/subjects')->group(function () {
            Route::get('/', [ClassSubjectController::class, 'index'])
                ->name('api.schools.classes.subjects.index');

            Route::post('/', [ClassSubjectController::class, 'store'])
                ->name('api.schools.classes.subjects.store');
        });
    });

    // subject routes
    Route::prefix('schools/{school}/subjects')->middleware('abilities:permit:owner')->group(function () {
        Route::get('/', [SubjectController::class, 'index'])
            ->name('api.schools.subjects.index');

        Route::post('/', [SubjectController::class, 'store'])
            ->name('api.schools.subjects.store');
    });

    // class subject teacher routes
    Route::prefix('schools/{school}/class-subject-teachers')->middleware('abilities:permit:owner')->group(function () {
        Route::get('/', [ClassSubjectTeacherController::class, 'index'])
            ->name('api.schools.class-subject-teachers.index');

        Route::post('/', [ClassSubjectTeacherController::class, 'store'])
            ->name('api.schools.class-subject-teachers.store');
    });

    // academic year routes
    Route::prefix('schools/{school}/academic-years')->middleware('abilities:permit:owner')->group(function () {
        Route::get('/', [AcademicYearController::class, 'index'])
            ->name('api.schools.academic-years.index');

        Route::post('/', [AcademicYearController::class, 'store'])
            ->name('api.schools.academic-years.store');

        Route::prefix('{academicYear}/terms')->group(function () {
            Route::get('/', [TermController::class, 'index'])
                ->name('api.schools.academic-years.terms.index');

            Route::post('/', [TermController::class, 'store'])
                ->name('api.schools.academic-years.terms.store');
        });
    });

    // aggregate routes
    Route::prefix('schools/{school}/aggregates')->middleware('abilities:permit:owner')->group(function () {
        Route::get('/', [AggregateController::class, 'index'])
            ->name('api.schools.aggregates.index');

        Route::post('/', [AggregateController::class, 'store'])
            ->name('api.schools.aggregates.store');

        Route::put('/{aggregate}', [AggregateController::class, 'update'])
            ->name('api.schools.aggregates.update');
    });

    // student class enrollment routes
    Route::prefix('schools/{school}/students/{student}/class-enrollments')->middleware('abilities:permit:owner')->group(function () {
        Route::get('/', [StudentClassEnrollmentController::class, 'index'])
            ->name('api.schools.students.class-enrollments.index');

        Route::post('/', [StudentClassEnrollmentController::class, 'store'])
            ->name('api.schools.students.class-enrollments.store');

        Route::prefix('{studentClassEnrollment}/subjects')->group(function () {
            Route::get('/', [StudentSubjectController::class, 'index'])
                ->name('api.schools.students.class-enrollments.subjects.index');

            Route::post('/', [StudentSubjectController::class, 'store'])
                ->name('api.schools.students.class-enrollments.subjects.store');
        });
    });
});
