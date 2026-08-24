<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_requests', function (Blueprint $table) {
            $table->string('owner_status')->default('pending')->change();
            $table->uuid('cancelled_by')->nullable()->after('owner_status');
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
            $table->text('cancellation_reason')->nullable()->after('cancelled_at');
            $table->decimal('cancellation_fee', 8, 2)->nullable()->after('cancellation_reason');
            $table->decimal('refund_amount', 8, 2)->nullable()->after('cancellation_fee');
            $table->string('refund_status')->nullable()->after('refund_amount');
        });

        Schema::table('rental_requests', function (Blueprint $table) {
            $table->foreign('cancelled_by')
                ->references('id')
                ->on('Users')
                ->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_status')->default('pending')->change();
            $table->decimal('cancellation_fee', 8, 2)->nullable()->after('payment_status');
            $table->decimal('refund_amount', 8, 2)->nullable()->after('cancellation_fee');
            $table->string('refund_status')->nullable()->after('refund_amount');
        });
    }

    public function down(): void
    {
        Schema::table('rental_requests', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn([
                'cancelled_by',
                'cancelled_at',
                'cancellation_reason',
                'cancellation_fee',
                'refund_amount',
                'refund_status',
            ]);
            $table->enum('owner_status', ['pending', 'accepted', 'rejected'])
                ->default('pending')
                ->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['cancellation_fee', 'refund_amount', 'refund_status']);
            $table->enum('payment_status', ['pending', 'verified', 'failed'])
                ->default('pending')
                ->change();
        });
    }
};
