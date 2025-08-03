<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tạo bảng users
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sso_id')->unique()->nullable();
            $table->string('user_name')->unique()->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('hometown')->nullable();
            $table->string('status')->default('active');
            $table->string('role_sso')->nullable();
            $table->boolean('protected')->default(false);
            $table->string('code')->nullable();
            $table->foreignId('primary_department_id')
                ->nullable()
                ->constrained('departments')
                ->onDelete('set null')
                ->name('users_primary_department_id_foreign');
            $table->foreignId('faculty_id')
                ->nullable()
                ->constrained('faculties')
                ->onDelete('set null')
                ->name('users_faculty_id_foreign');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_departments');
        Schema::dropIfExists('user_events');
        Schema::dropIfExists('users');
    }
};
