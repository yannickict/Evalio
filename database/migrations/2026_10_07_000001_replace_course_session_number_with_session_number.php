<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_sessions', function (Blueprint $table) {
            $table->unsignedInteger('session_number')->nullable();
        });

        $numbers = [];
        foreach (DB::table('course_sessions')->orderBy('id')->get(['id', 'course_id']) as $session) {
            $numbers[$session->course_id] = ($numbers[$session->course_id] ?? 0) + 1;
            DB::table('course_sessions')->where('id', $session->id)->update([
                'session_number' => $numbers[$session->course_id],
            ]);
        }

        Schema::table('course_sessions', function (Blueprint $table) {
            $table->dropUnique(['course_session_number']);
            $table->dropColumn('course_session_number');
            $table->unsignedInteger('session_number')->nullable(false)->change();
            $table->unique(['course_id', 'session_number']);
        });
    }

    public function down(): void
    {
        Schema::table('course_sessions', function (Blueprint $table) {
            $table->string('course_session_number')->nullable();
        });

        foreach (DB::table('course_sessions')->orderBy('id')->get(['id']) as $session) {
            DB::table('course_sessions')->where('id', $session->id)->update([
                'course_session_number' => sprintf('COURSE.%04d', $session->id),
            ]);
        }

        Schema::table('course_sessions', function (Blueprint $table) {
            $table->dropUnique(['course_id', 'session_number']);
            $table->dropColumn('session_number');
            $table->string('course_session_number')->nullable(false)->change();
            $table->unique('course_session_number');
        });
    }
};
