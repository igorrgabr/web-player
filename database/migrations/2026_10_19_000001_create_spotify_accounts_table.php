<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpotifyAccountsTable extends Migration
{
    public function up(): void
    {
        Schema::create('spotify_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('spotify_user_id', 100)->unique();
            $table->string('display_name', 150);
            $table->string('email', 255)->nullable();
            $table->text('avatar_url')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('product_type', 30);
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('token_expires_at')->index();
            $table->text('scopes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spotify_accounts');
    }
}
