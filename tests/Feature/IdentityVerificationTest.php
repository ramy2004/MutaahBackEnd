<?php

namespace Tests\Feature;

use App\Models\IdentityVerification;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

class IdentityVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_identity_images_and_view_pending_status(): void
    {
        [$user] = $this->users();
        $this->actingAs($user, 'sanctum');
        $response = $this->post('/api/v1/identity-verifications', [
            'id_image' => UploadedFile::fake()->createWithContent('id.jpg', $this->tinyJpeg()),
            'selfie_image' => UploadedFile::fake()->createWithContent('selfie.jpg', $this->tinyJpeg()),
        ], ['Accept' => 'application/json']);

        $id = $response->assertStatus(201)->assertJsonPath('data.status', 'manual_review')->json('data.id');
        $this->getJson('/api/v1/identity-verifications/current')
            ->assertOk()->assertJsonPath('data.id', $id)->assertJsonPath('data.status', 'manual_review');
        $this->assertDatabaseHas('IdentityVerifications', ['id' => $id, 'status' => 'manual_review']);
    }

    public function test_admin_can_approve_manual_identity_review_and_regular_user_is_forbidden(): void
    {
        [$user, $admin] = $this->users();
        $verification = IdentityVerification::create([
            'user_id' => $user->id,
            'id_image_url' => 'identity/id.jpg',
            'selfie_image_url' => 'identity/selfie.jpg',
            'status' => 'manual_review',
            'failure_reason' => 'ID image is blurry',
        ]);
        $this->actingAs($user, 'sanctum');
        $this->getJson('/api/v1/admin/identity-verifications')->assertForbidden();
        $this->actingAs($admin, 'sanctum');
        $this->patchJson('/api/v1/admin/identity-verifications/' . $verification->id . '/approve')
            ->assertOk()->assertJsonPath('data.status', 'approved');
        $this->assertDatabaseHas('IdentityVerifications', ['id' => $verification->id, 'status' => 'approved']);
    }

    private function users(): array
    {
        $plan = SubscriptionPlan::create(['id' => Str::uuid(), 'plan_type' => 'standard', 'price' => 0, 'max_listings_per_month' => 1, 'max_rentals_per_month' => 5, 'commission_rate' => 10, 'has_detailed_reports' => false]);
        $user = User::create(['id' => Str::uuid(), 'full_name' => 'Verification User', 'username' => 'verification_user', 'email' => 'verification@example.com', 'phone' => '0599000081', 'password_hash' => bcrypt('password123'), 'governorate' => 'gaza', 'district' => 'Al Rimal', 'plan_id' => $plan->id]);
        $admin = User::create(['id' => Str::uuid(), 'full_name' => 'Verification Admin', 'username' => 'verification_admin', 'email' => 'verification_admin@example.com', 'phone' => '0599000082', 'password_hash' => bcrypt('password123'), 'governorate' => 'gaza', 'district' => 'Al Rimal', 'plan_id' => $plan->id, 'role' => 'admin']);
        return [$user, $admin];
    }

    private function tinyJpeg(): string
    {
        return base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAH/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAEFAqf/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/AYf/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/AYf/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAY/Aqf/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/IV//2gAMAwEAAgADAAAAEP/EABQRAQAAAAAAAAAAAAAAAAAAD/2gAIAQMBAT8QH//EABQRAQAAAAAAAAAAAAAAAAAAD/2gAIAQIBAT8QH//EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAT8QH//Z');
    }
}
