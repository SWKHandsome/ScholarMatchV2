<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student');
            $table->boolean('is_active')->default(true);
        });
        Schema::create('study_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('income_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('minimum', 12, 2);
            $table->decimal('maximum', 12, 2)->nullable();
            $table->unsignedSmallInteger('reference_year');
            $table->text('source_url');
            $table->boolean('is_active')->default(true);
            $table->unique(['name', 'reference_year']);
            $table->timestamps();
        });
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('citizenship')->nullable();
            $table->string('residence_state')->nullable();
            $table->string('current_education_level')->nullable();
            $table->string('funding_level')->nullable();
            $table->foreignId('study_field_id')->nullable()->constrained()->restrictOnDelete();
            $table->boolean('study_field_undecided')->default(false);
            $table->string('enrolment_status')->nullable();
            $table->string('institution_type')->nullable();
            $table->string('study_location')->nullable();
            $table->decimal('household_income', 12, 2)->nullable();
            $table->boolean('academic_records_complete')->default(false);
            $table->timestamps();
        });
        Schema::create('student_academic_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->string('qualification');
            $table->string('completion_status');
            $table->string('result_type');
            $table->decimal('result_value', 8, 4)->nullable();
            $table->decimal('grading_scale', 8, 4)->nullable();
            $table->string('result_notes')->nullable();
            $table->unsignedSmallInteger('completion_year')->nullable();
            $table->timestamps();
        });
        Schema::create('scholarships', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider')->index();
            $table->text('description');
            $table->text('benefits');
            $table->date('deadline')->index();
            $table->dateTime('closing_at')->nullable();
            $table->text('application_url');
            $table->text('source_url');
            $table->string('status')->default('inactive')->index();
            $table->string('source_status')->default('unreviewed');
            $table->dateTime('last_verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_fictional')->default(false);
            $table->timestamps();
        });
        Schema::create('scholarship_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scholarship_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('criteria');
            $table->timestamps();
        });
        Schema::create('scholarship_academic_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scholarship_id')->constrained()->cascadeOnDelete();
            $table->string('qualification');
            $table->string('completion_status');
            $table->string('result_type');
            $table->decimal('minimum', 8, 4)->nullable();
            $table->decimal('grading_scale', 8, 4)->nullable();
            $table->text('manual_conditions')->nullable();
            $table->timestamps();
        });
        foreach (['study_field', 'income_category'] as $reference) {
            Schema::create('scholarship_'.($reference === 'income_category' ? 'income_categories' : 'study_fields'), function (Blueprint $table) use ($reference) {
                $table->foreignId('scholarship_id')->constrained()->cascadeOnDelete();
                $table->foreignId($reference.'_id')->constrained()->restrictOnDelete();
                $table->primary(['scholarship_id', $reference.'_id']);
            });
        }
        Schema::create('saved_scholarships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scholarship_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'scholarship_id']);
        });
    }

    public function down(): void
    {
        foreach (['saved_scholarships', 'scholarship_income_categories', 'scholarship_study_fields', 'scholarship_academic_routes', 'scholarship_requirements', 'scholarships', 'student_academic_records', 'student_profiles', 'income_categories', 'study_fields'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['role', 'is_active']));
    }
};
