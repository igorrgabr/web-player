<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('local_playlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->text('cover_url')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('playlist_tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('local_playlist_id')->constrained('local_playlists')->cascadeOnDelete();
            $table->foreignId('track_id')->constrained('tracks')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->timestamp('added_at')->useCurrent();

            $table->unique(['local_playlist_id', 'track_id']);
            $table->index(['local_playlist_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playlist_tracks');
        Schema::dropIfExists('local_playlists');
    }
};
