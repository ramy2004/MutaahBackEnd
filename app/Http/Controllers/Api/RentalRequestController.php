<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RentalRequest\StoreRentalRequest;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Product;
use App\Models\RentalRequest;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RentalRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = RentalRequest::with(['product.owner', 'renter'])
            ->where('renter_id', $request->user()->id)
            ->latest();

        return response()->json([
            'success' => true,
            'data' => $query->paginate(12),
        ]);
    }

    public function myRequests(Request $request)
    {
        $requests = RentalRequest::with(['product.owner', 'renter'])
            ->where('product_id', function ($query) use ($request) {
                $query->select('id')
                    ->from('Products')
                    ->where('owner_id', $request->user()->id);
            })
            ->orWhere('renter_id', $request->user()->id)
            ->latest()
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    public function store(StoreRentalRequest $request, SubscriptionService $subscriptions): JsonResponse
    {
        if (!$subscriptions->canCreateRental($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'لقد وصلت إلى الحد الشهري لطلبات التأجير في خطتك الحالية',
            ], 422);
        }

        $product = Product::findOrFail($request->product_id);

        if ($product->owner_id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك طلب تأجير منتجك الخاص',
            ], 422);
        }

        if ($product->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'هذا المنتج غير متاح حالياً',
            ], 422);
        }

        $start = Carbon::parse($request->start_time);
        $end = Carbon::parse($request->end_time);

        $conflict = RentalRequest::where('product_id', $product->id)
            ->where('owner_status', 'accepted')
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_time', [$start, $end])
                    ->orWhereBetween('end_time', [$start, $end])
                    ->orWhere(function ($range) use ($start, $end) {
                        $range->where('start_time', '<=', $start)
                            ->where('end_time', '>=', $end);
                    });
            })
            ->exists();

        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => 'هذا المنتج غير متاح خلال الفترة المحددة',
            ], 409);
        }

        $rentalRequest = RentalRequest::create([
            'renter_id' => $request->user()->id,
            'product_id' => $product->id,
            'start_time' => $start,
            'end_time' => $end,
            'owner_status' => 'pending',
        ]);

        $subscriptions->recordRental($request->user());

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال طلب التأجير بنجاح',
            'data' => $rentalRequest->load(['product.owner', 'renter']),
        ], 201);
    }

    public function updateStatus(Request $request, RentalRequest $rentalRequest): JsonResponse
    {
        if ($request->user()->id !== $rentalRequest->product->owner_id) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتحديث هذا الطلب',
            ], 403);
        }

        $request->validate([
            'status' => ['required', 'in:accepted,rejected'],
        ]);

        $rentalRequest->update([
            'owner_status' => $request->status,
        ]);

        $title = $request->status === 'accepted' ? 'تم قبول طلب التأجير' : 'تم رفض طلب التأجير';
        $message = $request->status === 'accepted'
            ? 'تم قبول طلبك لتأجير "' . $rentalRequest->product->title . '".'
            : 'تم رفض طلبك لتأجير "' . $rentalRequest->product->title . '".';

        Notification::create([
            'user_id' => $rentalRequest->renter_id,
            'type' => 'rental_status',
            'title' => $title,
            'message' => $message,
            'is_read' => false,
            'ref_id' => $rentalRequest->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => $title,
            'data' => $rentalRequest->fresh()->load(['product.owner', 'renter']),
        ]);
    }

    public function cancel(Request $request, RentalRequest $rentalRequest): JsonResponse
    {
        if ($request->user()->id !== $rentalRequest->renter_id) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بإلغاء هذا الطلب',
            ], 403);
        }

        if ($rentalRequest->owner_status !== 'accepted') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء طلب غير مقبول',
            ], 422);
        }

        if ($rentalRequest->start_time->lte(now())) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء التأجير بعد بدايته',
            ], 422);
        }

        $payment = $rentalRequest->payments()
            ->where('payment_status', 'verified')
            ->latest()
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد دفع مؤكد لهذا الطلب',
            ], 422);
        }

        $feePercentage = (float) config('rental.cancellation_fee_percentage', 20);
        $grandTotal = (float) $payment->grand_total;
        $cancellationFee = round($grandTotal * ($feePercentage / 100), 2);
        $refundAmount = round($grandTotal - $cancellationFee, 2);

        DB::transaction(function () use ($request, $rentalRequest, $payment, $cancellationFee, $refundAmount) {
            $rentalRequest->update([
                'owner_status' => 'cancelled',
                'cancelled_by' => $request->user()->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $request->input('reason'),
                'cancellation_fee' => $cancellationFee,
                'refund_amount' => $refundAmount,
                'refund_status' => 'pending',
            ]);

            $payment->update([
                'payment_status' => 'partially_refunded',
                'cancellation_fee' => $cancellationFee,
                'refund_amount' => $refundAmount,
                'refund_status' => 'pending',
            ]);

            Notification::create([
                'user_id' => $rentalRequest->renter_id,
                'type' => 'payment_update',
                'title' => 'تم إلغاء طلب التأجير',
                'message' => 'تم قبول الإلغاء. رسوم الإلغاء ' . number_format($cancellationFee, 2) . ' والمبلغ المسترد ' . number_format($refundAmount, 2) . '.',
                'is_read' => false,
                'ref_id' => $rentalRequest->id,
            ]);

            Notification::create([
                'user_id' => $rentalRequest->product->owner_id,
                'type' => 'rental_status',
                'title' => 'تم إلغاء طلب التأجير',
                'message' => 'قام المستأجر بإلغاء طلب تأجير منتجك.',
                'is_read' => false,
                'ref_id' => $rentalRequest->id,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء التأجير وسيتم رد المبلغ بعد خصم رسوم الإلغاء',
            'data' => [
                'rental_request_id' => $rentalRequest->id,
                'status' => 'cancelled',
                'cancellation_fee_percentage' => $feePercentage,
                'cancellation_fee' => $cancellationFee,
                'refund_amount' => $refundAmount,
                'refund_status' => 'pending',
            ],
        ]);
    }
}
