<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('marks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignUuid('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignUuid('student_class_enrollment_id')->constrained('student_class_enrollments')->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignUuid('term_id')->constrained('terms')->cascadeOnDelete();
            $table->decimal('class_score', 5, 2);
            $table->decimal('home_assignment_score', 5, 2);
            $table->decimal('project_score', 5, 2);
            $table->decimal('class_test_score', 5, 2);
            $table->decimal('continuous_assessment_score', 5, 2);
            $table->decimal('continuous_assessment_contribution', 5, 2);
            $table->decimal('exam_score', 5, 2);
            $table->decimal('exam_contribution', 5, 2);
            $table->decimal('total_score', 5, 2);
            $table->string('grade')->nullable();
            $table->string('grade_remark')->nullable();
            $table->foreignUuid('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_class_enrollment_id', 'subject_id', 'term_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marks');
    }
};
