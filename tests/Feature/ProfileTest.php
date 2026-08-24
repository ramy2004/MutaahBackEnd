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

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_returns_real_rental_statistics(): void
    {
        $plan = SubscriptionPlan::create([
            'id' => Str::uuid(),
            'plan_type' => 'standard',
            'price' => 0,
            'max_listings_per_month' => 1,
            'max_rentals_per_month' => 5,
            'commission_rate' => 10,
            'has_detailed_reports' => false,
        ]);
        $owner = $this->user('profile_owner', 'profile_owner@example.com', '0599000091', $plan->id);
        $renter = $this->user('profile_renter', 'profile_renter@example.com', '0599000092', $plan->id);
        $product = Product::create([
            'id' => Str::uuid(),
            'owner_id' => $owner->id,
            'title' => 'Profile camera',
            'description' => 'Camera',
            'category' => 'cameras',
            'product_images' => [],
            'available_dates' => ['2026-09-01'],
            'start_time' => '08:00',
            'end_time' => '22:00',
            'is_all_day' => true,
            'price_per_hour' => 25,
            'deposit_amount' => 50,
            'status' => 'active',
        ]);
        $rental = RentalRequest::create([
            'id' => Str::uuid(),
            'renter_id' => $renter->id,
            'product_id' => $product->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDays(2),
            'owner_status' => 'accepted',
        ]);
        Payment::create([
            'id' => Str::uuid(),
            'rental_id' => $rental->id,
            'payer_id' => $renter->id,
            'price_snapshot' => 25,
            'rental_price_total' => 75,
            'deposit_amount' => 50,
            'grand_total' => 125,
            'receipt_image' => 'payments/profile.jpg',
            'payment_status' => 'verified',
            'paid_at' => now(),
        ]);

        $this->actingAs($owner, 'sanctum');
        $this->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('user.stats.my_products_count', 1)
            ->assertJsonPath('user.stats.my_rentals_count', 0)
            ->assertJsonPath('user.stats.rental_earnings', 75)
            ->assertJsonPath('user.stats.held_deposits', 50);

        $this->actingAs($renter, 'sanctum');
        $this->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('user.stats.my_rentals_count', 1);
    }

    private function user(string $username, string $email, string $phone, string $planId): User
    {
        return User::create([
            'id' => Str::uuid(),
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
