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
    Schema::create('payments', function (Blueprint $table) {
        $table->id();
        $table->string('booking_id', 24); // MongoDB ObjectId as string
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->decimal('amount', 10, 2);
        $table->enum('payment_method', ['paypal', 'momo', 'zalopay']);
        $table->timestamp('payment_date')->default(DB::raw('CURRENT_TIMESTAMP'));
        $table->enum('status', ['Thành công', 'Thất bại', 'Đang xử lý']);
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
