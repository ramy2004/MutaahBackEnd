<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\UpdateProfileRequest;
use App\Models\IdentityVerification;
use App\Models\Payment;
use App\Models\RentalRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    // 1. عرض بيانات الملف الشخصي والملخص المالي والإحصائيات
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('subscriptionPlan');

        // إحصائيات منتجات المستخدم
        $productsCount = $user->products()->count();
        $acceptedRentalsCount = RentalRequest::where('renter_id', $user->id)
            ->where('owner_status', 'accepted')
            ->count();
        $rentalEarnings = Payment::where('payment_status', 'verified')
            ->whereHas('rental.product', function ($query) use ($user) {
                $query->where('owner_id', $user->id);
            })
            ->sum('rental_price_total');
        $heldDeposits = Payment::where('payment_status', 'verified')
            ->whereHas('rental', function ($query) {
                $query->where('owner_status', 'accepted')
                    ->where('end_time', '>=', now());
            })
            ->whereHas('rental.product', function ($query) use ($user) {
                $query->where('owner_id', $user->id);
            })
            ->sum('deposit_amount');

        return response()->json([
            'user' => [
                'id'                => $user->id,
                'full_name'         => $user->full_name,
                'username'          => $user->username,
                'email'             => $user->email,
                'phone'             => $user->phone,
                'governorate'       => $user->governorate,
                'district'          => $user->district,
                'avatar'            => $user->avatar ? asset('storage/' . $user->avatar) : null,
                'is_verified'       => IdentityVerification::where('user_id', $user->id)
                    ->whereIn('status', ['verified', 'approved'])
                    ->exists(),
                'subscription_plan' => $user->subscriptionPlan ? $user->subscriptionPlan->plan_type : 'standard',
                'stats' => [
                    'my_products_count' => $productsCount,
                    'my_rentals_count' => $acceptedRentalsCount,
                    'rental_earnings'   => (float) $rentalEarnings,
                    'held_deposits'     => (float) $heldDeposits,
                ]
            ]
        ]);
    }

    // 2. تحديث بيانات الملف الشخصي
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $data = [
            'full_name'   => $request->full_name,
            'username'    => $request->username,
            'email'       => $request->email,
            'phone'       => $request->phone,
            'governorate' => $request->governorate,
            'district'    => $request->district,
        ];

        // تغيير كلمة السر إذا تم إدخالها
        if ($request->filled('password')) {
            $data['password_hash'] = Hash::make($request->password);
        }

        // رفع الصورة الشخصية إن وجدت
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        $user->update($data);

        return response()->json([
            'message' => 'تم تحديث البيانات بنجاح',
            'user'    => $user,
        ]);
    }
}
