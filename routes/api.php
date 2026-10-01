<?php

use App\Http\Controllers\Api\AcademicYear\AcademicYearController;
use App\Http\Controllers\Api\Aggregate\AggregateController;
use App\Http\Controllers\Api\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\Profile\UpdateStudentController;
use App\Http\Controllers\Api\Auth\Profile\UpdateTeacherController;
use App\Http\Controllers\Api\Auth\RegisteredClientController;
use App\Http\Controllers\Api\ClassSubject\ClassSubjectController;
use App\Http\Controllers\Api\ClassSubjectTeacher\ClassSubjectTeacherController;
use App\Http\Controllers\Api\Mark\ClassMarkController;
use App\Http\Controllers\Api\Mark\ExamMarkController;
use App\Http\Controllers\Api\Mark\MarkController;
use App\Http\Controllers\Api\MarkSetting\MarkSettingController;
use App\Http\Controllers\Api\School\SchoolController;
use App\Http\Controllers\Api\SchoolClass\SchoolClassController;
use App\Http\Controllers\Api\Student\StudentController;
use App\Http\Controllers\Api\StudentClassEnrollment\StudentClassEnrollmentController;
use App\Http\Controllers\Api\StudentSubject\StudentSubjectController;
use App\Http\Controllers\Api\StudentTermResult\StudentTermResultController;
use App\Http\Controllers\Api\Subject\SubjectController;
use App\Http\Controllers\Api\Teacher\TeacherController;
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
        Route::put('/profile/teachers/{teacher}', [UpdateTeacherController::class, 'update'])
            ->middleware('abilities:permit:teacher')
            ->name('api.profile.teachers.update');

        Route::put('/profile/students/{student}', [UpdateStudentController::class, 'update'])
            ->middleware('abilities:permit:student')
            ->name('api.profile.students.update');
    });

    // school routes
    Route::middleware('abilities:permit:owner')->group(function () {
        Route::get('/schools', [SchoolController::class, 'index'])
            ->name('api.schools.index');

        Route::post('/schools', [SchoolController::class, 'store'])
            ->name('api.schools.store');

        Route::put('/schools/{school}', [SchoolController::class, 'update'])
            ->name('api.schools.update');

        Route::put('/schools/{school}/in-session', [SchoolController::class, 'setInSession'])
            ->name('api.schools.in-session');

        Route::put('/schools/{school}/status', [SchoolController::class, 'updateStatus'])
            ->name('api.schools.status');
    });

    // school class routes
    Route::prefix('schools/{school}/classes')->middleware('abilities:permit:owner')->group(function () {
        Route::get('/', [SchoolClassController::class, 'index'])
            ->name('api.schools.classes.index');

        Route::post('/', [SchoolClassController::class, 'store'])
            ->name('api.schools.classes.store');

        Route::put('/{schoolClass}', [SchoolClassController::class, 'update'])
            ->name('api.schools.classes.update');

        Route::put('/{schoolClass}/status', [SchoolClassController::class, 'updateStatus'])
            ->name('api.schools.classes.status');

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

        Route::put('/{subject}', [SubjectController::class, 'update'])
            ->name('api.schools.subjects.update');

        Route::put('/{subject}/status', [SubjectController::class, 'updateStatus'])
            ->name('api.schools.subjects.status');
    });

    // teacher routes
    Route::prefix('schools/{school}/teachers')->middleware('abilities:permit:owner')->group(function () {
        Route::get('/', [TeacherController::class, 'index'])
            ->name('api.schools.teachers.index');

        Route::post('/', [TeacherController::class, 'store'])
            ->name('api.schools.teachers.store');

        Route::get('/{teacher}', [TeacherController::class, 'show'])
            ->name('api.schools.teachers.show');

        Route::get('/{teacher}/subjects', [TeacherController::class, 'subjects'])
            ->name('api.schools.teachers.subjects.index');

        Route::put('/{teacher}', [TeacherController::class, 'update'])
            ->name('api.schools.teachers.update');

        Route::post('/{teacher}/reset-password', [TeacherController::class, 'resetPassword'])
            ->name('api.schools.teachers.reset-password');
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

        Route::get('/current', [AcademicYearController::class, 'current'])
            ->name('api.schools.academic-years.current');

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

    // mark setting routes
    Route::prefix('schools/{school}/mark-settings')->middleware('abilities:permit:owner')->group(function () {
        Route::get('/', [MarkSettingController::class, 'show'])
            ->name('api.schools.mark-settings.show');

        Route::put('/', [MarkSettingController::class, 'update'])
            ->name('api.schools.mark-settings.update');
    });

    // mark routes
    Route::prefix('schools/{school}/marks')->middleware('abilities:permit:owner')->group(function () {
        Route::get('/', [MarkController::class, 'index'])
            ->name('api.schools.marks.index');
    });

    Route::prefix('schools/{school}/class-marks')->middleware('abilities:permit:owner')->group(function () {
        Route::get('/pending', [ClassMarkController::class, 'pending'])
            ->name('api.schools.class-marks.pending');

        Route::get('/recorded', [ClassMarkController::class, 'recorded'])
            ->name('api.schools.class-marks.recorded');

        Route::post('/close', [ClassMarkController::class, 'close'])
            ->name('api.schools.class-marks.close');

        Route::post('/', [ClassMarkController::class, 'store'])
            ->name('api.schools.class-marks.store');

        Route::put('/{mark}', [ClassMarkController::class, 'update'])
            ->name('api.schools.class-marks.update');
    });

    Route::prefix('schools/{school}/exam-marks')->middleware('abilities:permit:owner')->group(function () {
        Route::get('/pending', [ExamMarkController::class, 'pending'])
            ->name('api.schools.exam-marks.pending');

        Route::get('/recorded', [ExamMarkController::class, 'recorded'])
            ->name('api.schools.exam-marks.recorded');

        Route::post('/close', [ExamMarkController::class, 'close'])
            ->name('api.schools.exam-marks.close');

        Route::put('/', [ExamMarkController::class, 'upsert'])
            ->name('api.schools.exam-marks.upsert');
    });

    // student routes
    Route::prefix('schools/{school}/students')->middleware('abilities:permit:owner')->group(function () {
        Route::get('/', [StudentController::class, 'index'])
            ->name('api.schools.students.index');

        Route::post('/', [StudentController::class, 'store'])
            ->name('api.schools.students.store');

        Route::get('/{student}', [StudentController::class, 'show'])
            ->name('api.schools.students.show');

        Route::put('/{student}', [StudentController::class, 'update'])
            ->name('api.schools.students.update');

        Route::get('/{student}/subjects', [StudentController::class, 'subjects'])
            ->name('api.schools.students.subjects.index');
    });

    // student term result routes
    Route::prefix('schools/{school}/student-term-results')->middleware('abilities:permit:owner')->group(function () {
        Route::get('/', [StudentTermResultController::class, 'index'])
            ->name('api.schools.student-term-results.index');
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
