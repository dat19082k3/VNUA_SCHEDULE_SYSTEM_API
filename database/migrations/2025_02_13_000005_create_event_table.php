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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->text('description')->nullable();
            $table->string('location', 255)->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('host', 50);
            $table->string('participants', 255)->nullable();
            $table->enum('reminder_type', ['none', 'calendar'])->default('none');
            $table->dateTime('reminder_time')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('file_url', 1024);
            $table->string('file_type', 50)->nullable();
            $table->foreignId('uploader_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('event_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('attachment_id')->constrained('attachments')->onDelete('cascade');
            $table->timestamp('added_at');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('event_attachments');
        Schema::dropIfExists('event_attendees');
    }
};
