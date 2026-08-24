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
        Schema::create('rental_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('renter_id');
            $table->foreign('renter_id')
                    ->references('id')
                    ->on('Users')
                    ->onDelete('cascade');

            $table->uuid('product_id');
            $table->foreign('product_id')
                    ->references('id')
                    ->on('Products')
                    ->onDelete('cascade');

            $table->dateTime('start_time');
            $table->dateTime('end_time');

            $table->enum('owner_status', ['pending', 'accepted', 'rejected'])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_requests');
    }
};
