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
        Schema::create('Products', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('owner_id');
            $table->foreign('owner_id')
                    ->references('id')
                    ->on('Users')
                    ->onDelete('cascade');

            $table->string('title');
            $table->text('description');

            $table->enum('category', [
                'cameras',
                'clothes',
                'electronics',
                'items',
                'camping',
                'medical items',
                'instruments',
                'books',
                'house items'
            ]);

            $table->json('product_images')->comment('image');
            $table->json('available_dates');

            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_all_day')->default(false);

            $table->decimal('price_per_hour', 8, 2);
            $table->decimal('deposit_amount', 8, 2);

            $table->enum('status', ['pending', 'active', 'frozen', 'deleted']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Products');
    }
};
