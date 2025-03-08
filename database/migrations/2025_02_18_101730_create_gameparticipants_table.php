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
    Schema::create('game_participants', function (Blueprint $table) {
            $table->id();
            $table->string('game_id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); 
            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
            $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_participants');
    }
};
