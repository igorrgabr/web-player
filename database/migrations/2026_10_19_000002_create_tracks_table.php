<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTracksTable extends Migration
{
    public function up(): void
    {
        Schema::create('tracks', function (Blueprint $table) {
            $table->id();
            $table->string('spotify_track_id', 64)->unique();
            $table->string('spotify_track_uri', 128)->index();
            $table->string('title', 255);
            $table->string('artist_name', 255);
            $table->string('spotify_artist_id', 64)->nullable()->index();
            $table->string('album_name', 255);
            $table->string('spotify_album_id', 64)->nullable()->index();
            $table->text('album_cover_url')->nullable();
            $table->unsignedInteger('duration_ms');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracks');
    }
}

