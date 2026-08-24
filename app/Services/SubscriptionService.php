<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Carbon;

class SubscriptionService
{
    public function activeSubscription(User $user): ?Subscription
    {
        $subscription = $user->subscriptions()
            ->with('plan')
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latest('starts_at')
            ->first();

        if ($subscription) {
            return $subscription;
        }

        return null;
    }

    public function planFor(User $user): SubscriptionPlan
    {
        $subscription = $this->activeSubscription($user);

        return $subscription?->plan
            ?? SubscriptionPlan::where('plan_type', 'standard')->firstOrFail();
    }

    public function resetUsageIfNeeded(User $user): void
    {
        if (!$user->usage_reset_at || Carbon::parse($user->usage_reset_at)->isPast()) {
            $user->forceFill([
                'listings_used_this_month' => 0,
                'rentals_used_this_month' => 0,
                'usage_reset_at' => now()->addMonth(),
            ])->save();
        }
    }

    public function canCreateListing(User $user): bool
    {
        $this->resetUsageIfNeeded($user);
        $plan = $this->planFor($user);
        $subscription = $this->activeSubscription($user);
        $used = $subscription?->listings_used ?? $user->listings_used_this_month;

        return $used < $plan->max_listings_per_month;
    }

    public function canCreateRental(User $user): bool
    {
        $this->resetUsageIfNeeded($user);
        $plan = $this->planFor($user);
        $subscription = $this->activeSubscription($user);
        $used = $subscription?->rentals_used ?? $user->rentals_used_this_month;

        return $used < $plan->max_rentals_per_month;
    }

    public function recordListing(User $user): void
    {
        $this->incrementUsage($user, 'listings_used', 'listings_used_this_month');
    }

    public function recordRental(User $user): void
    {
        $this->incrementUsage($user, 'rentals_used', 'rentals_used_this_month');
    }

    private function incrementUsage(User $user, string $subscriptionColumn, string $userColumn): void
    {
        $subscription = $this->activeSubscription($user);
        if ($subscription) {
            $subscription->increment($subscriptionColumn);
            return;
        }

        $this->resetUsageIfNeeded($user);
        $user->increment($userColumn);
    }
}
