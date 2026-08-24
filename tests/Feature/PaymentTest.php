<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Product;
use App\Models\RentalRequest;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_renter_can_submit_receipt_and_admin_can_verify_payment(): void
    {
        $plan = SubscriptionPlan::create(['id' => Str::uuid(), 'plan_type' => 'standard', 'price' => 0, 'max_listings_per_month' => 1, 'max_rentals_per_month' => 5, 'commission_rate' => 10, 'has_detailed_reports' => false]);
        $owner = $this->user('payment_owner', 'payment_owner@example.com', '0599000061', $plan->id);
        $renter = $this->user('payment_renter', 'payment_renter@example.com', '0599000062', $plan->id);
        $admin = $this->user('payment_admin', 'payment_admin@example.com', '0599000063', $plan->id, 'admin');
        $product = Product::create(['id' => Str::uuid(), 'owner_id' => $owner->id, 'title' => 'Payment camera', 'description' => 'Camera', 'category' => 'cameras', 'product_images' => [], 'available_dates' => ['2026-09-01'], 'start_time' => '08:00', 'end_time' => '22:00', 'is_all_day' => true, 'price_per_hour' => 25, 'deposit_amount' => 50, 'status' => 'active']);
        $rental = RentalRequest::create(['id' => Str::uuid(), 'renter_id' => $renter->id, 'product_id' => $product->id, 'start_time' => now()->addDay(), 'end_time' => now()->addDays(2), 'owner_status' => 'accepted']);

        $this->actingAs($renter, 'sanctum');
        $id = $this->post('/api/v1/payments', [
            'rental_id' => (string) $rental->id,
            'price_snapshot' => 25,
            'rental_price_total' => 50,
            'deposit_amount' => 50,
            'grand_total' => 100,
            'receipt_image' => UploadedFile::fake()->createWithContent('receipt.jpg', $this->tinyJpeg()),
        ], ['Accept' => 'application/json'])->assertStatus(201)->json('data.id');

        $this->actingAs($admin, 'sanctum');
        $this->patchJson('/api/v1/admin/payments/' . $id . '/verify')
            ->assertOk()->assertJsonPath('data.payment_status', 'verified');
        $this->assertDatabaseHas('payments', ['id' => $id, 'payment_status' => 'verified']);
    }

    public function test_regular_user_cannot_access_admin_payment_routes(): void
    {
        $plan = SubscriptionPlan::create(['id' => Str::uuid(), 'plan_type' => 'standard', 'price' => 0, 'max_listings_per_month' => 1, 'max_rentals_per_month' => 5, 'commission_rate' => 10, 'has_detailed_reports' => false]);
        $user = $this->user('payment_regular', 'payment_regular@example.com', '0599000071', $plan->id);
        $this->actingAs($user, 'sanctum');
        $this->getJson('/api/v1/admin/payments')->assertForbidden();
        $owner = $this->user('payment_regular_owner', 'payment_regular_owner@example.com', '0599000072', $plan->id);
        $renter = $this->user('payment_regular_renter', 'payment_regular_renter@example.com', '0599000073', $plan->id);
        $product = Product::create(['id' => Str::uuid(), 'owner_id' => $owner->id, 'title' => 'Permission camera', 'description' => 'Camera', 'category' => 'cameras', 'product_images' => [], 'available_dates' => ['2026-09-01'], 'start_time' => '08:00', 'end_time' => '22:00', 'is_all_day' => true, 'price_per_hour' => 25, 'deposit_amount' => 50, 'status' => 'active']);
        $rental = RentalRequest::create(['id' => Str::uuid(), 'renter_id' => $renter->id, 'product_id' => $product->id, 'start_time' => now()->addDay(), 'end_time' => now()->addDays(2), 'owner_status' => 'accepted']);
        $payment = Payment::create(['id' => Str::uuid(), 'rental_id' => $rental->id, 'payer_id' => $renter->id, 'price_snapshot' => 25, 'rental_price_total' => 50, 'deposit_amount' => 50, 'grand_total' => 100, 'receipt_image' => 'payments/receipt.jpg', 'payment_status' => 'pending']);
        $this->patchJson('/api/v1/admin/payments/' . $payment->id . '/verify')->assertForbidden();
    }

    private function user(string $username, string $email, string $phone, string $planId, string $role = 'user'): User
    {
        return User::create(['id' => Str::uuid(), 'full_name' => $username, 'username' => $username, 'email' => $email, 'phone' => $phone, 'password_hash' => bcrypt('password123'), 'governorate' => 'gaza', 'district' => 'Al Rimal', 'plan_id' => $planId, 'role' => $role]);
    }

    private function tinyJpeg(): string
    {
        return base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAH/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAEFAqf/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/AYf/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/AYf/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAY/Aqf/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/IV//2gAMAwEAAgADAAAAEP/EABQRAQAAAAAAAAAAAAAAAAAAD/2gAIAQMBAT8QH//EABQRAQAAAAAAAAAAAAAAAAAAD/2gAIAQIBAT8QH//EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAT8QH//Z');
    }
}
