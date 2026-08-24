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
        Schema::create('Users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('password_hash');
            $table->string('avatar')->nullable()->comment('image');
            $table->enum('governorate', ['north', 'gaza', 'middle', 'khanyonis', 'rafah']);
            $table->string('district');
            $table->boolean('email_verified')->default(false);
            $table->enum('user_status', ['active', 'suspended'])->default('active');
            $table->enum('role', ['user', 'admin'])->default('user');

            $table->uuid('plan_id');
            $table->foreign('plan_id')->references('id')->on('SubscriptionPlans')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Users');
    }
};
