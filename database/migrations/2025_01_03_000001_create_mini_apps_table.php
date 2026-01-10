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
        Schema::create('mini_apps', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->string('slug')->unique();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->text('konten');
            $table->text('style');
            $table->text('functionality')->nullable();
            $table->json('images')->nullable();
            $table->json('chats')->nullable();
            $table->longText('html');
            $table->string('category')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('status')->default('AKTIF');
            $table->string('user_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mini_apps');
    }
};