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
        Schema::create('SubscriptionPlans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('plan_type', ['standard', 'plus', 'pro']);
            $table->decimal('price', 8, 2);
            $table->integer('max_listings_per_month');
            $table->integer('max_rentals_per_month');
            $table->integer('listings_count_this_month')->default(0);
            $table->integer('rentals_count_this_month')->default(0);
            $table->decimal('commission_rate', 5, 2);
            $table->boolean('has_detailed_reports')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('SubscriptionPlans');
    }
};
