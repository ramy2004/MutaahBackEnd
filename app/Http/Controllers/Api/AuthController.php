<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        // إسناد الخطة الافتراضية للعميل عند التسجيل
        // إنشاء خطة standard تلقائياً إذا لم تكن موجودة
        $defaultPlan = SubscriptionPlan::firstOrCreate(
            ['plan_type' => 'standard'],
            [
                'price'                  => 0.00,
                'max_listings_per_month' => 1,
                'max_rentals_per_month'  => 5,
                'commission_rate'        => 10.00,
                'has_detailed_reports'   => false,
            ]
        );

        $user = User::create([
            'id'          => (string) Str::uuid(),
            'full_name'   => $request->full_name,
            'username'    => $request->username,
            'email'       => $request->email,
            'phone'       => $request->phone,
            'password_hash' => Hash::make($request->password),
            'governorate' => $request->governorate,
            'district'    => $request->district,
            'plan_id'     => $defaultPlan->id,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'تم إنشاء الحساب بنجاح',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $loginInput = $request->login;

        // البحث باستخدام البريد الإلكتروني أو اسم المستخدم
        $user = User::where('email', $loginInput)
            ->orWhere('username', $loginInput)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return response()->json([
                'message' => 'بيانات الدخول غير صحيحة',
            ], 401);
        }

        if ($user->user_status === 'suspended') {
            return response()->json([
                'message' => 'هذا الحساب معطل حالياً',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'تم تسجيل الدخول بنجاح',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }
}
