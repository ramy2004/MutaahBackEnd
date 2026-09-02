<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_products_returns_only_products_owned_by_authenticated_user(): void
    {
        $plan = SubscriptionPlan::create([
            'id' => Str::uuid(),
            'plan_type' => 'standard',
            'price' => 0,
            'max_listings_per_month' => 10,
            'max_rentals_per_month' => 10,
            'commission_rate' => 10,
            'has_detailed_reports' => false,
        ]);
        $owner = $this->user('products_owner', $plan->id);
        $otherOwner = $this->user('products_other_owner', $plan->id);

        $ownedProduct = $this->product($owner, 'Owned product', 'frozen');
        $otherProduct = $this->product($otherOwner, 'Other product', 'active');

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/my-products')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', (string) $ownedProduct->id)
            ->assertJsonMissing(['id' => (string) $otherProduct->id]);
    }

    private function user(string $username, string $planId): User
    {
        return User::create([
            'id' => Str::uuid(),
            'full_name' => $username,
            'username' => $username,
            'email' => $username . '@example.com',
            'phone' => '0599' . random_int(100000, 999999),
            'password_hash' => bcrypt('password123'),
            'governorate' => 'gaza',
            'district' => 'Al Rimal',
            'plan_id' => $planId,
        ]);
    }

    private function product(User $owner, string $title, string $status): Product
    {
        return Product::create([
            'id' => Str::uuid(),
            'owner_id' => $owner->id,
            'title' => $title,
            'description' => $title,
            'category' => 'items',
            'product_images' => [],
            'available_dates' => ['2026-09-01'],
            'start_time' => '08:00',
            'end_time' => '22:00',
            'is_all_day' => true,
            'price_per_hour' => 25,
            'deposit_amount' => 50,
            'status' => $status,
        ]);
    }
}
