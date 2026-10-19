<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserPreferencesTable extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('default_volume')->default(80);
            $table->string('repeat_mode', 16)->default('off');
            $table->boolean('shuffle_enabled')->default(false);
            $table->string('preferred_device_id', 128)->nullable();
            $table->string('last_played_track_uri', 128)->nullable();
            $table->json('extra_settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
}
