<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Users', function (Blueprint $table) {
            $table->unsignedInteger('listings_used_this_month')->default(0)->after('plan_id');
            $table->unsignedInteger('rentals_used_this_month')->default(0)->after('listings_used_this_month');
            $table->timestamp('usage_reset_at')->nullable()->after('rentals_used_this_month');
        });
    }

    public function down(): void
    {
        Schema::table('Users', function (Blueprint $table) {
            $table->dropColumn(['listings_used_this_month', 'rentals_used_this_month', 'usage_reset_at']);
        });
    }
};
