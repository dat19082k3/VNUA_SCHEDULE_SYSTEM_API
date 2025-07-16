<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create events table
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('host', 50);
            $table->text('participants')->nullable();
            $table->enum('status', ['pending', 'approved', 'declined'])->default('pending');
            $table->enum('reminder_type', ['none', 'calendar'])->default('none');
            $table->dateTime('reminder_time')->nullable();
            $table->foreignId('creator_id')
                ->constrained('users', 'id')
                ->nullOnDelete()
                ->index()
                ->name('events_creator_id_foreign');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('event_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')
                ->constrained('events', 'id')
                ->onDelete('cascade')
                ->index()
                ->name('event_histories_event_id_foreign');
            $table->foreignId('user_id')
                ->constrained('users', 'id')
                ->nullOnDelete()
                ->index()
                ->name('event_histories_user_id_foreign');
            $table->string('field_name');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamp('changed_at')->useCurrent();
            $table->timestamps();
        });

        // Tạo bảng trung gian event_locations
        Schema::create('event_locations', function (Blueprint $table) {
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('location_id');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
            $table->primary(['event_id', 'location_id']);
            $table->timestamps();
        });

        // Tạo bảng trung gian event_preparers
        Schema::create('event_preparers', function (Blueprint $table) {
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('department_id');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->primary(['event_id', 'department_id']);
            $table->timestamps();
        });

        // Create attachments table
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 255);
            $table->string('file_name', 255);
            $table->string('file_url', 1024);
            $table->string('file_type', 1024)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->foreignId('uploader_id')
                ->nullable()
                ->constrained('users', 'id')
                ->nullOnDelete()
                ->index()
                ->name('attachments_uploader_id_foreign');
            $table->timestamps();
            $table->softDeletes();
        });

        // Create event_attachments table
        Schema::create('event_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')
                ->constrained('events', 'id')
                ->onDelete('cascade')
                ->index()
                ->name('event_attachments_event_id_foreign');
            $table->foreignId('attachment_id')
                ->constrained('attachments', 'id')
                ->onDelete('cascade')
                ->index()
                ->name('event_attachments_attachment_id_foreign');
            $table->timestamp('added_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_attachments');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('events');
        Schema::dropIfExists('event_histories');
    }
};
