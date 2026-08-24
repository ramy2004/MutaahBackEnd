<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\RentalRequest;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_rental_request_for_active_product(): void
    {
        $plan = SubscriptionPlan::create([
            'id' => Str::uuid()->toString(),
            'plan_type' => 'standard',
            'price' => 0.00,
            'max_listings_per_month' => 1,
            'max_rentals_per_month' => 5,
            'commission_rate' => 10.00,
            'has_detailed_reports' => false,
        ]);

        $owner = User::create([
            'id' => Str::uuid()->toString(),
            'full_name' => 'Owner User',
            'username' => 'owner_user',
            'email' => 'owner@example.com',
            'phone' => '0599000001',
            'password_hash' => bcrypt('password123'),
            'governorate' => 'gaza',
            'district' => 'Al Rimal',
            'plan_id' => $plan->id,
        ]);

        $renter = User::create([
            'id' => Str::uuid()->toString(),
            'full_name' => 'Renter User',
            'username' => 'renter_user',
            'email' => 'renter@example.com',
            'phone' => '0599000002',
            'password_hash' => bcrypt('password123'),
            'governorate' => 'gaza',
            'district' => 'Al Rimal',
            'plan_id' => $plan->id,
        ]);

        $product = Product::create([
            'id' => Str::uuid()->toString(),
            'owner_id' => $owner->id,
            'title' => 'Camera for rent',
            'description' => 'Good camera',
            'category' => 'cameras',
            'product_images' => ['https://example.com/image.jpg'],
            'available_dates' => ['2026-09-01', '2026-09-02'],
            'start_time' => '08:00',
            'end_time' => '22:00',
            'is_all_day' => true,
            'price_per_hour' => 25.00,
            'deposit_amount' => 200.00,
            'status' => 'active',
        ]);

        $this->actingAs($renter, 'sanctum');

        $response = $this->postJson('/api/v1/rental-requests', [
            'product_id' => $product->id,
            'start_time' => '2026-09-01 10:00:00',
            'end_time' => '2026-09-01 12:00:00',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.product_id', $product->id)
            ->assertJsonPath('data.renter_id', $renter->id)
            ->assertJsonPath('data.owner_status', 'pending');
    }

    public function test_owner_cannot_delete_product_with_current_or_future_rental(): void
    {
        $plan = SubscriptionPlan::create([
            'id' => Str::uuid()->toString(),
            'plan_type' => 'standard',
            'price' => 0.00,
            'max_listings_per_month' => 1,
            'max_rentals_per_month' => 5,
            'commission_rate' => 10.00,
            'has_detailed_reports' => false,
        ]);

        $owner = User::create([
            'id' => Str::uuid()->toString(),
            'full_name' => 'Owner',
            'username' => 'owner_delete',
            'email' => 'owner_delete@example.com',
            'phone' => '0599000011',
            'password_hash' => bcrypt('password123'),
            'governorate' => 'gaza',
            'district' => 'Al Rimal',
            'plan_id' => $plan->id,
        ]);
        $renter = User::create([
            'id' => Str::uuid()->toString(),
            'full_name' => 'Renter',
            'username' => 'renter_delete',
            'email' => 'renter_delete@example.com',
            'phone' => '0599000012',
            'password_hash' => bcrypt('password123'),
            'governorate' => 'gaza',
            'district' => 'Al Rimal',
            'plan_id' => $plan->id,
        ]);
        $product = Product::create([
            'id' => Str::uuid()->toString(),
            'owner_id' => $owner->id,
            'title' => 'Rented camera',
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
        RentalRequest::create([
            'id' => Str::uuid()->toString(),
            'renter_id' => $renter->id,
            'product_id' => $product->id,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'owner_status' => 'accepted',
        ]);

        $this->actingAs($owner, 'sanctum');

        $this->deleteJson('/api/v1/products/' . $product->id)
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('Products', ['id' => $product->id]);
    }

    public function test_owner_can_delete_product_without_active_rental(): void
    {
        $plan = SubscriptionPlan::create([
            'id' => Str::uuid()->toString(),
            'plan_type' => 'standard',
            'price' => 0.00,
            'max_listings_per_month' => 1,
            'max_rentals_per_month' => 5,
            'commission_rate' => 10.00,
            'has_detailed_reports' => false,
        ]);
        $owner = User::create([
            'id' => Str::uuid()->toString(),
            'full_name' => 'Owner',
            'username' => 'owner_remove',
            'email' => 'owner_remove@example.com',
            'phone' => '0599000021',
            'password_hash' => bcrypt('password123'),
            'governorate' => 'gaza',
            'district' => 'Al Rimal',
            'plan_id' => $plan->id,
        ]);
        $product = Product::create([
            'id' => Str::uuid()->toString(),
            'owner_id' => $owner->id,
            'title' => 'Available camera',
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

        $this->actingAs($owner, 'sanctum');

        $this->deleteJson('/api/v1/products/' . $product->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('Products', ['id' => $product->id]);
    }
}
