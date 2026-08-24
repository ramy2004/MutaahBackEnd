<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'plan_type'              => 'standard',
                'price'                  => 0.00,
                'max_listings_per_month' => 1,
                'max_rentals_per_month'  => 5,
                'commission_rate'        => 10.00,
                'has_detailed_reports'   => false,
            ],
            [
                'plan_type'              => 'plus',
                'price'                  => 29.00,
                'max_listings_per_month' => 3,
                'max_rentals_per_month'  => 10,
                'commission_rate'        => 5.00,
                'has_detailed_reports'   => false,
            ],
            [
                'plan_type'              => 'pro',
                'price'                  => 69.00,
                'max_listings_per_month' => 8,
                'max_rentals_per_month'  => 20,
                'commission_rate'        => 2.50,
                'has_detailed_reports'   => true,
            ],
        ];

        foreach ($plans as $plan) {
            $subscriptionPlan = SubscriptionPlan::firstOrNew([
                'plan_type' => $plan['plan_type'],
            ]);

            if (!$subscriptionPlan->exists) {
                $subscriptionPlan->id = (string) Str::uuid();
            }

            $subscriptionPlan->fill($plan)->save();
        }
    }
}
