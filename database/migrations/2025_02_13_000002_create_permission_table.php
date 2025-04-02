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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('protected')->default(0);
            $table->foreignId('parent_id')->nullable()->constrained('roles')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('permission_group_code', 50);
            $table->string('permission_type_code', 50);
            $table->timestamps();
        });

        Schema::create('permission_roles', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('permission_groups', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('code', 50)->unique()->comment('Mã nhóm quyền hạn');
            $table->string('name', 255)->comment('Tên nhóm quyền hạn');
            $table->string('description', 255)->nullable()->comment('Mô tả nhóm quyền hạn');
            $table->string('parent_code', 50)->nullable()->comment('Mã nhóm quyền cha');
            $table->timestamps();

            // Add an index to the 'code' column
            $table->index('code');

            // Khóa ngoại tới chính nó (nếu dùng tree structure)
            $table->foreign('parent_code')->references('code')->on('permission_groups')->nullOnDelete();
        });

        Schema::create('permission_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->comment('Mã loại quyền hạn');
            $table->string('name', 255)->comment('Tên loại quyền hạn');
            $table->integer('position')->comment('Vị trí loại quyền hạn');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('permission_roles');
        Schema::dropIfExists('permission_types');
        Schema::dropIfExists('permission_groups');
    }
};
