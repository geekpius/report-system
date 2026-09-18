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
        Schema::table('schools', function (Blueprint $table) {
            if (! Schema::hasColumn('schools', 'status')) {
                $table->string('status')->default('active')->after('email');
            }

            if (! Schema::hasColumn('schools', 'in_session')) {
                $table->boolean('in_session')->default(false)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (Schema::hasColumn('schools', 'in_session')) {
                $table->dropColumn('in_session');
            }

            if (Schema::hasColumn('schools', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
