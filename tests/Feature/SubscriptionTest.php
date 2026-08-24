<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_subscription_receipt_and_admin_can_approve_it(): void
    {
        [$standard, $plus, $user, $admin] = $this->usersAndPlans();

        $this->actingAs($user, 'sanctum');
        $response = $this->post('/api/v1/subscriptions', [
            'plan_id' => $plus->id,
            'receipt_image' => UploadedFile::fake()->createWithContent('receipt.jpg', $this->tinyJpeg()),
        ], ['Accept' => 'application/json']);

        $subscriptionId = $response->assertStatus(201)->json('data.id');
        $this->actingAs($admin, 'sanctum');
        $this->patchJson('/api/v1/admin/subscriptions/' . $subscriptionId . '/approve')
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('subscriptions', ['id' => $subscriptionId, 'status' => 'active']);
        $this->assertDatabaseHas('Users', ['id' => $user->id, 'plan_id' => $plus->id]);
    }

    public function test_approving_new_subscription_expires_previous_active_subscription(): void
    {
        [$standard, $plus, $user, $admin] = $this->usersAndPlans();
        $old = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plus->id,
            'status' => 'active',
            'price_paid' => 29,
            'receipt_image' => 'old.jpg',
            'starts_at' => now()->subDays(10),
            'expires_at' => now()->addDays(20),
        ]);
        $new = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plus->id,
            'status' => 'pending',
            'price_paid' => 29,
            'receipt_image' => 'new.jpg',
        ]);

        $this->actingAs($admin, 'sanctum');
        $this->patchJson('/api/v1/admin/subscriptions/' . $new->id . '/approve')->assertOk();

        $this->assertDatabaseHas('subscriptions', ['id' => $old->id, 'status' => 'expired']);
        $this->assertDatabaseHas('subscriptions', ['id' => $new->id, 'status' => 'active']);
        $this->assertSame(1, Subscription::where('user_id', $user->id)->where('status', 'active')->count());
    }

    public function test_regular_user_cannot_access_admin_subscription_routes(): void
    {
        [$standard, $plus, $user] = $this->usersAndPlans();
        $this->actingAs($user, 'sanctum');

        $this->getJson('/api/v1/admin/subscriptions')->assertForbidden();
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plus->id,
            'status' => 'pending',
            'price_paid' => 29,
            'receipt_image' => 'subscriptions/receipt.jpg',
        ]);
        $this->patchJson('/api/v1/admin/subscriptions/' . $subscription->id . '/approve')->assertForbidden();
    }

    private function usersAndPlans(): array
    {
        $standard = $this->plan('standard', 1, 5);
        $plus = $this->plan('plus', 3, 10, 29);
        $user = $this->user('subscription_user', 'subscription@example.com', '0599000051', $standard->id);
        $admin = $this->user('subscription_admin', 'subscription_admin@example.com', '0599000052', $standard->id, 'admin');
        return [$standard, $plus, $user, $admin];
    }

    private function plan(string $type, int $listings, int $rentals, float $price = 0): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'id' => Str::uuid()->toString(),
            'plan_type' => $type,
            'price' => $price,
            'max_listings_per_month' => $listings,
            'max_rentals_per_month' => $rentals,
            'commission_rate' => 10,
            'has_detailed_reports' => false,
        ]);
    }

    private function user(string $username, string $email, string $phone, string $planId, string $role = 'user'): User
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
            'role' => $role,
        ]);
    }

    private function tinyJpeg(): string
    {
        return base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAH/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAEFAqf/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/AYf/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/AYf/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAY/Aqf/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/IV//2gAMAwEAAgADAAAAEP/EABQRAQAAAAAAAAAAAAAAAAAAD/2gAIAQMBAT8QH//EABQRAQAAAAAAAAAAAAAAAAAAD/2gAIAQIBAT8QH//EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAT8QH//Z');
    }
}
