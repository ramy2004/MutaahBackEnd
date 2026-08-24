<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('plan_id');
            $table->string('status')->default('pending');
            $table->decimal('price_paid', 8, 2)->default(0);
            $table->string('receipt_image');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('listings_used')->default(0);
            $table->unsignedInteger('rentals_used')->default(0);
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('Users')->cascadeOnDelete();
            $table->foreign('plan_id')->references('id')->on('SubscriptionPlans')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('Users')->nullOnDelete();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
