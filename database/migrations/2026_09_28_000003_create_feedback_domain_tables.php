<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questionnaire_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('questionnaire_template_id')->nullable()->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('course_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('questionnaire_template_id')->nullable()->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('course_session_number')->unique();
            $table->date('start_date');
            $table->date('end_date');
            // NULL follows the date window; open/closed is an explicit manual override.
            $table->enum('evaluation_status', ['open', 'closed'])->nullable();
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->text('question_text');
            $table->enum('type', ['single_choice', 'free_text']);
            $table->boolean('allows_comment')->default(false);
            $table->timestamps();
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('option_text');
            $table->timestamps();
        });

        Schema::create('questionnaire_template_question', function (Blueprint $table) {
            $table->foreignId('questionnaire_template_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedInteger('position');
            $table->primary(['questionnaire_template_id', 'question_id']);
            $table->unique(['questionnaire_template_id', 'position']);
        });

        Schema::create('feedback_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_session_id')->unique()->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->char('code', 6)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->restrictOnDelete();
            $table->foreignId('question_option_id')->nullable()->constrained()->restrictOnDelete();
            $table->text('answer_text')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['feedback_form_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
        Schema::dropIfExists('feedback_forms');
        Schema::dropIfExists('questionnaire_template_question');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('course_sessions');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('questionnaire_templates');
    }
};
