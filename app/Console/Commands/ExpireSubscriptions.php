<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';
    protected $description = 'Expire subscriptions and notify users before expiry';

    public function handle(): int
    {
        Subscription::with('user')->where('status', 'active')->where('expires_at', '<=', now())->each(function (Subscription $subscription) {
            $subscription->update(['status' => 'expired']);
            $standard = SubscriptionPlan::where('plan_type', 'standard')->first();
            if ($standard) $subscription->user->update(['plan_id' => $standard->id]);
            Notification::create(['user_id' => $subscription->user_id, 'type' => 'plan_expired', 'title' => 'انتهى الاشتراك', 'message' => 'انتهى اشتراكك وتمت إعادتك إلى الخطة Standard.', 'is_read' => false, 'ref_id' => $subscription->id]);
        });

        Subscription::with('user')->where('status', 'active')->whereBetween('expires_at', [now(), now()->addDay()])->each(function (Subscription $subscription) {
            Notification::firstOrCreate(['user_id' => $subscription->user_id, 'type' => 'plan_expired', 'ref_id' => $subscription->id, 'message' => 'اقترب انتهاء اشتراكك.'], ['title' => 'اشتراكك على وشك الانتهاء', 'is_read' => false]);
        });
        return self::SUCCESS;
    }
}
