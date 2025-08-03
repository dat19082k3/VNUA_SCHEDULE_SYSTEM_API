<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tạo bảng user_events để đánh dấu các sự kiện mà người dùng quan tâm
        Schema::create('user_events', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('event_id');
            $table->boolean('is_marked')->default(true); // Người dùng đánh dấu quan tâm
            $table->boolean('is_viewed')->default(false); // Người dùng đã xem sự kiện
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->primary(['user_id', 'event_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_events');
    }
};
