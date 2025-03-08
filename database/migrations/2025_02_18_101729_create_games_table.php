<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('games', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('venue_id')->constrained('venues')->onDelete('cascade');
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $table->string('court_number');
            $table->string('game_date');
            $table->string('start_time');
            $table->string('end_time');
            $table->string('max_players');
            $table->string('current_players')->default(1);
            $table->string('skill_level_required');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
