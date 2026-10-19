<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorite_tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('track_id')->constrained('tracks')->cascadeOnDelete();
            $table->timestamp('favorited_at')->useCurrent();

            $table->unique(['user_id', 'track_id']);
            $table->index(['user_id', 'favorited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_tracks');
    }
};
