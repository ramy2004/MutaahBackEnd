<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IdentityVerification;
use App\Models\Payment;
use App\Models\Product;
use App\Models\RentalRequest;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بدخول لوحة الإدارة',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'users_count' => User::count(),
                    'active_products_count' => Product::where('status', 'active')->count(),
                    'pending_rental_requests_count' => RentalRequest::where('owner_status', 'pending')->count(),
                    'pending_payments_count' => Payment::where('payment_status', 'pending')->count(),
                    'pending_subscriptions_count' => Subscription::where('status', 'pending')->count(),
                    'active_subscriptions_count' => Subscription::where('status', 'active')
                        ->where('expires_at', '>', now())
                        ->count(),
                    'manual_identity_reviews_count' => IdentityVerification::where('status', 'manual_review')->count(),
                ],
                'recent_rental_requests' => RentalRequest::with(['renter', 'product'])
                    ->latest()
                    ->limit(10)
                    ->get(),
                'pending_payments' => Payment::with(['payer', 'rental.product'])
                    ->where('payment_status', 'pending')
                    ->latest()
                    ->limit(10)
                    ->get(),
                'identity_reviews' => IdentityVerification::with('user')
                    ->where('status', 'manual_review')
                    ->latest()
                    ->limit(10)
                    ->get(),
                'pending_subscriptions' => Subscription::with(['user', 'plan'])
                    ->where('status', 'pending')
                    ->latest()
                    ->limit(10)
                    ->get(),
            ],
        ]);
    }
}
