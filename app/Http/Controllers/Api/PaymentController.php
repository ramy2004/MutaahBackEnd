<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\RentalRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = Payment::with(['rental.product.owner', 'payer'])
            ->where('payer_id', $request->user()->id)
            ->latest()
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    public function adminIndex(Request $request)
    {
        if (!$this->isAdmin($request)) {
            return $this->forbidden();
        }

        $payments = Payment::with(['rental.product.owner', 'rental.renter', 'payer'])
            ->whereHas('rental', function ($query) {
                $query->whereNotNull('id');
            })
            ->latest()
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'rental_id' => ['required', 'string', 'exists:rental_requests,id'],
            'price_snapshot' => ['required', 'numeric', 'min:0'],
            'rental_price_total' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'grand_total' => ['required', 'numeric', 'min:0'],
            'receipt_image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $rental = RentalRequest::with('product')->findOrFail($request->rental_id);

        if ((string) $rental->renter_id !== (string) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك دفع طلب لا يخصك',
            ], 403);
        }

        $path = $request->file('receipt_image')->store('payments', 'public');

        $payment = Payment::create([
            'rental_id' => $rental->id,
            'payer_id' => $request->user()->id,
            'price_snapshot' => $request->price_snapshot,
            'rental_price_total' => $request->rental_price_total,
            'deposit_amount' => $request->deposit_amount,
            'grand_total' => $request->grand_total,
            'receipt_image' => $path,
            'payment_status' => 'pending',
            'paid_at' => now(),
        ]);

        Notification::create([
            'user_id' => $request->user()->id,
            'type' => 'payment_update',
            'title' => 'تم إرسال الدفعة',
            'message' => 'تم رفع إيصال الدفع بنجاح، بانتظار تأكيد الإدارة.',
            'is_read' => false,
            'ref_id' => $payment->id,
        ]);

        Notification::create([
            'user_id' => $rental->product->owner_id,
            'type' => 'payment_update',
            'title' => 'طلب دفع جديد',
            'message' => 'لديك طلب دفع جديد لإيصال تأجير يحتاج مراجعة.',
            'is_read' => false,
            'ref_id' => $payment->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الدفع بنجاح، بانتظار تأكيد الإدارة',
            'data' => $payment->load(['rental.product.owner', 'payer']),
        ], 201);
    }

    public function verify(Request $request, Payment $payment): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return $this->forbidden();
        }

        $payment->update([
            'payment_status' => 'verified',
            'paid_at' => now(),
        ]);

        Notification::create([
            'user_id' => $payment->payer_id,
            'type' => 'payment_update',
            'title' => 'تم تأكيد الدفع',
            'message' => 'تمت الموافقة على الدفع الخاص بطلب التأجير.',
            'is_read' => false,
            'ref_id' => $payment->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تأكيد الدفع بنجاح',
            'data' => $payment->fresh()->load(['rental.product.owner', 'payer']),
        ]);
    }

    public function reject(Request $request, Payment $payment): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return $this->forbidden();
        }

        $payment->update([
            'payment_status' => 'failed',
        ]);

        Notification::create([
            'user_id' => $payment->payer_id,
            'type' => 'payment_update',
            'title' => 'تم رفض الدفع',
            'message' => 'تم رفض إيصال الدفع، يرجى إعادة المحاولة أو التواصل مع الدعم.',
            'is_read' => false,
            'ref_id' => $payment->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض الدفع',
            'data' => $payment->fresh()->load(['rental.product.owner', 'payer']),
        ]);
    }

    private function isAdmin(Request $request): bool
    {
        return $request->user()?->role === 'admin';
    }

    private function forbidden(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'غير مصرح لك بتنفيذ هذا الإجراء',
        ], 403);
    }
}
