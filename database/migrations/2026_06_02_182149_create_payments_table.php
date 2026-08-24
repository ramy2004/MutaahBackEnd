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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('rental_id');
            $table->foreign('rental_id')
                    ->references('id')
                    ->on('rental_requests')
                    ->onDelete('cascade');

            $table->uuid('payer_id');
            $table->foreign('payer_id')
                    ->references('id')
                    ->on('Users')
                    ->onDelete('cascade');

            $table->decimal('price_snapshot', 8, 2);
            $table->decimal('rental_price_total', 8, 2);
            $table->decimal('deposit_amount', 8, 2);
            $table->decimal('grand_total', 8, 2);

            $table->string('receipt_image')->comment('image');
            $table->enum('payment_status', ['pending', 'verified', 'failed'])->default('pending');
            $table->timestamp('paid_at')->nullable();

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
