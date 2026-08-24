<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    public function forgot(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        return response()->json([
            'success' => true,
            'message' => 'إذا كان البريد مسجلاً، سيتم إرسال تعليمات إعادة تعيين كلمة المرور إليه.',
            'mail_sent' => $status === Password::RESET_LINK_SENT,
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password_hash' => Hash::make($password),
                ])->save();

                $user->tokens()->delete();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'success' => false,
                'message' => 'رابط إعادة تعيين كلمة المرور غير صالح أو منتهي الصلاحية.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح، يمكنك تسجيل الدخول الآن.',
        ]);
    }
}
