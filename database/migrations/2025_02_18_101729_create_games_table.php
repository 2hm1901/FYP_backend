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
            $table->id();
            $table->foreignId('venue_id')->constrained('venues')->onDelete('cascade');
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $table->date('game_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('max_players');
            $table->integer('current_players')->default(1);
            $table->enum('skill_level_required', ['Newbie', 'Yếu', 'TBY', 'TB', 'TB+', 'Pro']);
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
