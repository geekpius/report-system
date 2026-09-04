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
        Schema::create('mark_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->unique()->constrained('schools')->cascadeOnDelete();
            $table->string('scoring_mode')->default('total_score');
            $table->decimal('class_score_percent', 5, 2)->default(50);
            $table->decimal('exam_score_percent', 5, 2)->default(50);
            $table->decimal('class_score_max', 5, 2)->default(0);
            $table->decimal('home_assignment_max', 5, 2)->default(0);
            $table->decimal('project_max', 5, 2)->default(0);
            $table->decimal('class_test_max', 5, 2)->default(0);
            $table->decimal('division_total', 5, 2)->default(0);
            $table->decimal('division_total_percent', 5, 2)->default(0);
            $table->decimal('exam_allocation_percent', 5, 2)->default(50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mark_settings');
    }
};
