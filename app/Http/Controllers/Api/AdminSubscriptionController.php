<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!$this->isAdmin($request)) return $this->forbidden();
        $query = Subscription::with(['user', 'plan', 'reviewer'])->latest();
        if ($request->filled('status')) $query->where('status', $request->status);
        return response()->json(['success' => true, 'data' => $query->paginate(12)]);
    }

    public function approve(Request $request, Subscription $subscription): JsonResponse
    {
        if (!$this->isAdmin($request)) return $this->forbidden();
        if ($subscription->status !== 'pending') return response()->json(['success' => false, 'message' => 'لا يمكن مراجعة هذا الطلب'], 422);

        DB::transaction(function () use ($request, $subscription) {
            Subscription::where('user_id', $subscription->user_id)
                ->where('status', 'active')
                ->where('id', '!=', $subscription->id)
                ->update(['status' => 'expired']);

            $subscription->update([
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => now()->addMonth(),
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
            $subscription->user->update(['plan_id' => $subscription->plan_id]);
        });

        $this->notify($subscription, 'تم قبول الاشتراك', 'تم تفعيل اشتراكك لمدة شهر.');
        return response()->json(['success' => true, 'message' => 'تم تفعيل الاشتراك', 'data' => $subscription->fresh()->load('plan')]);
    }

    public function reject(Request $request, Subscription $subscription): JsonResponse
    {
        if (!$this->isAdmin($request)) return $this->forbidden();
        $request->validate(['admin_note' => ['required', 'string', 'max:1000']]);
        if ($subscription->status !== 'pending') return response()->json(['success' => false, 'message' => 'لا يمكن مراجعة هذا الطلب'], 422);
        $subscription->update(['status' => 'rejected', 'admin_note' => $request->admin_note, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        $this->notify($subscription, 'تم رفض الاشتراك', 'تم رفض طلب الاشتراك: ' . $request->admin_note);
        return response()->json(['success' => true, 'message' => 'تم رفض الاشتراك', 'data' => $subscription->fresh()->load('plan')]);
    }

    private function notify(Subscription $subscription, string $title, string $message): void
    {
        Notification::create(['user_id' => $subscription->user_id, 'type' => 'payment_update', 'title' => $title, 'message' => $message, 'is_read' => false, 'ref_id' => $subscription->id]);
    }
    private function isAdmin(Request $request): bool
    {
        return $request->user()?->role === 'admin';
    }
    private function forbidden(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'غير مصرح لك بتنفيذ هذا الإجراء'], 403);
    }
}
