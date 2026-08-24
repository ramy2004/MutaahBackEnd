<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_a_password_reset_link(): void
    {
        $user = $this->makeUser();
        Notification::fake();

        $this->postJson('/api/v1/forgot-password', ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('mail_sent', true);

        Notification::assertSentTo($user, \App\Notifications\ResetPasswordNotification::class);
    }

    public function test_password_reset_request_does_not_reveal_unknown_email(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/forgot-password', ['email' => 'unknown@example.com'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('mail_sent', false);

        Notification::assertNothingSent();
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = $this->makeUser();
        Notification::fake();
        $this->postJson('/api/v1/forgot-password', ['email' => $user->email])
            ->assertOk();

        $token = null;
        Notification::assertSentTo($user, \App\Notifications\ResetPasswordNotification::class, function ($notification) use (&$token) {
            $property = new \ReflectionProperty($notification, 'token');
            $property->setAccessible(true);
            $token = $property->getValue($notification);
            return true;
        });

        $this->postJson('/api/v1/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password_hash));
    }

    private function makeUser(): User
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

        return User::create([
            'id' => Str::uuid(),
            'full_name' => 'Reset User',
            'username' => 'reset_' . Str::random(6),
            'email' => 'reset_' . Str::random(6) . '@example.com',
            'phone' => '0599' . random_int(100000, 999999),
            'password_hash' => Hash::make('oldpassword123'),
            'governorate' => 'gaza',
            'district' => 'Al Rimal',
            'plan_id' => $plan->id,
        ]);
    }
}
