<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Subscription\StoreSubscriptionRequest;
use App\Models\Notification;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function plans(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => SubscriptionPlan::orderBy('price')->get()]);
    }

    public function current(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscriptions()->with('plan')->where('status', 'active')->where('expires_at', '>', now())->latest('starts_at')->first();
        $plan = $subscription?->plan ?? SubscriptionPlan::where('plan_type', 'standard')->firstOrFail();

        return response()->json(['success' => true, 'data' => [
            'plan' => $plan,
            'subscription' => $subscription,
            'listings_used' => $subscription?->listings_used ?? $user->listings_used_this_month,
            'rentals_used' => $subscription?->rentals_used ?? $user->rentals_used_this_month,
        ]]);
    }

    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($request->plan_id);
        if ($plan->plan_type === 'standard') {
            return response()->json(['success' => false, 'message' => 'الخطة Standard مجانية ولا تحتاج طلب اشتراك'], 422);
        }

        $hasPending = $request->user()->subscriptions()->where('status', 'pending')->exists();
        if ($hasPending) {
            return response()->json(['success' => false, 'message' => 'لديك طلب اشتراك بانتظار المراجعة'], 409);
        }

        $subscription = Subscription::create([
            'user_id' => $request->user()->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'price_paid' => $plan->price,
            'receipt_image' => $request->file('receipt_image')->store('subscriptions', 'public'),
        ]);

        Notification::create([
            'user_id' => $request->user()->id,
            'type' => 'payment_update',
            'title' => 'تم إرسال طلب الاشتراك',
            'message' => 'تم استلام إيصال الاشتراك وبانتظار موافقة الإدارة.',
            'is_read' => false,
            'ref_id' => $subscription->id,
        ]);

        return response()->json(['success' => true, 'message' => 'تم إرسال طلب الاشتراك', 'data' => $subscription->load('plan')], 201);
    }
}
