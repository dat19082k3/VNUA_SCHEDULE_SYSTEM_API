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
        Schema::create('users', function (Blueprint $table) {
                    $table->id();
                    $table->string('avatar')->nullable();
                    $table->string('first_name',50)->nullable();
                    $table->string('last_name',50)->nullable();
                    $table->string('email')->unique();
                    $table->string('phone', 10)->nullable();
                    $table->string('password');
                    $table->timestamp('email_verified_at')->nullable();
                    $table->tinyInteger('status')->default(1);
                    $table->boolean('protected')->default(0);
                    $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
                    $table->timestamps();
                    $table->softDeletes();
                });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->primary(['user_id', 'role_id']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
