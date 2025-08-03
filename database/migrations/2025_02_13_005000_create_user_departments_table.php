<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tạo bảng trung gian users_departments (quan hệ nhiều-nhiều giữa users và departments)
        Schema::create('user_departments', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('department_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->primary(['user_id', 'department_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_departments');
    }
};
