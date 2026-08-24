<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Product;
use App\Models\RentalRequest;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_renter_can_cancel_verified_rental_with_cancellation_fee(): void
    {
        $plan = SubscriptionPlan::create([
            'id' => Str::uuid()->toString(),
            'plan_type' => 'standard',
            'price' => 0,
            'max_listings_per_month' => 1,
            'max_rentals_per_month' => 5,
            'commission_rate' => 10,
            'has_detailed_reports' => false,
        ]);

        $owner = $this->makeUser($plan->id, 'cancel_owner', 'cancel_owner@example.com', '0599000031');
        $renter = $this->makeUser($plan->id, 'cancel_renter', 'cancel_renter@example.com', '0599000032');
        $product = Product::create([
            'id' => Str::uuid()->toString(),
            'owner_id' => $owner->id,
            'title' => 'Cancellation camera',
            'description' => 'Camera',
            'category' => 'cameras',
            'product_images' => [],
            'available_dates' => ['2026-09-01'],
            'start_time' => '08:00',
            'end_time' => '22:00',
            'is_all_day' => true,
            'price_per_hour' => 25,
            'deposit_amount' => 200,
            'status' => 'active',
        ]);
        $rentalRequest = RentalRequest::create([
            'id' => Str::uuid()->toString(),
            'renter_id' => $renter->id,
            'product_id' => $product->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDays(2),
            'owner_status' => 'accepted',
        ]);
        $payment = Payment::create([
            'id' => Str::uuid()->toString(),
            'rental_id' => $rentalRequest->id,
            'payer_id' => $renter->id,
            'price_snapshot' => 25,
            'rental_price_total' => 50,
            'deposit_amount' => 50,
            'grand_total' => 100,
            'receipt_image' => 'payments/receipt.jpg',
            'payment_status' => 'verified',
            'paid_at' => now(),
        ]);

        $this->actingAs($renter, 'sanctum');

        $this->patchJson('/api/v1/rental-requests/' . $rentalRequest->id . '/cancel', [
            'reason' => 'لم أعد بحاجة إلى المنتج',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancellation_fee', 20)
            ->assertJsonPath('data.refund_amount', 80)
            ->assertJsonPath('data.refund_status', 'pending');

        $this->assertDatabaseHas('rental_requests', [
            'id' => $rentalRequest->id,
            'owner_status' => 'cancelled',
            'cancellation_fee' => 20,
            'refund_amount' => 80,
            'refund_status' => 'pending',
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'payment_status' => 'partially_refunded',
            'cancellation_fee' => 20,
            'refund_amount' => 80,
            'refund_status' => 'pending',
        ]);
    }

    private function makeUser(string $planId, string $username, string $email, string $phone): User
    {
        return User::create([
            'id' => Str::uuid()->toString(),
            'full_name' => $username,
            'username' => $username,
            'email' => $email,
            'phone' => $phone,
            'password_hash' => bcrypt('password123'),
            'governorate' => 'gaza',
            'district' => 'Al Rimal',
            'plan_id' => $planId,
        ]);
    }
}
