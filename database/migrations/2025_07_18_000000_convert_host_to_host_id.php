<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        // First ensure we have at least one admin user
        $admin = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->first();

        if (!$admin) {
            $admin = User::first();
        }

        if (!$admin) {
            // Create a default admin if no users exist
            $admin = User::create([
                'name' => 'Admin',
                'email' => 'admin@vnua.edu.vn',
                'password' => bcrypt('password'),
            ]);
        }

        // Add host_id column if it doesn't exist yet
        if (!Schema::hasColumn('events', 'host_id')) {
            Schema::table('events', function (Blueprint $table) {
                $table->foreignId('host_id')
                    ->nullable()
                    ->constrained('users', 'id')
                    ->nullOnDelete()
                    ->after('end_time');
            });
        }

        // Update existing records to use the admin as host
        DB::table('events')->update(['host_id' => $admin->id]);

        // Drop the old column
        if (Schema::hasColumn('events', 'host')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('host');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the host column
        if (!Schema::hasColumn('events', 'host')) {
            Schema::table('events', function (Blueprint $table) {
                $table->string('host', 50)->nullable()->after('end_time');
            });
        }

        // Convert host_id back to host string
        $events = DB::table('events')->get();

        foreach ($events as $event) {
            if ($event->host_id) {
                $user = User::find($event->host_id);
                if ($user) {
                    DB::table('events')
                        ->where('id', $event->id)
                        ->update(['host' => $user->name]);
                } else {
                    DB::table('events')
                        ->where('id', $event->id)
                        ->update(['host' => 'Unknown']);
                }
            }
        }

        // Drop the host_id column
        if (Schema::hasColumn('events', 'host_id')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropForeign(['host_id']);
                $table->dropColumn('host_id');
            });
        }
    }
};
