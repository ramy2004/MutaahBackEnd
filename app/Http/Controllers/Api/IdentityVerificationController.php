<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IdentityVerification\StoreIdentityVerificationRequest;
use App\Models\IdentityVerification;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IdentityVerificationController extends Controller
{
    public function store(
        StoreIdentityVerificationRequest $request
    ): JsonResponse {
        $existing = IdentityVerification::where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'processing', 'manual_review'])
            ->latest()
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'لديك طلب تحقق قيد المعالجة حالياً',
                'data' => $this->publicData($existing),
            ], 409);
        }

        $verification = DB::transaction(function () use ($request) {
            return IdentityVerification::create([
                'user_id' => $request->user()->id,
                'id_image_url' => $request->file('id_image')->store('identity-verifications', 'local'),
                'selfie_image_url' => $request->file('selfie_image')->store('identity-verifications', 'local'),
                'status' => 'manual_review',
            ]);
        });

        Notification::create([
            'user_id' => $request->user()->id,
            'type' => 'identity_verification',
            'title' => 'تم استلام طلب التحقق',
            'message' => 'تم استلام صور الهوية وتحويل الطلب للمراجعة اليدوية من الإدارة.',
            'is_read' => false,
            'ref_id' => $verification->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم استلام الصور والطلب بانتظار مراجعة الإدارة',
            'data' => $this->publicData($verification),
        ], 201);
    }

    public function current(Request $request): JsonResponse
    {
        $verification = IdentityVerification::where('user_id', $request->user()->id)
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'data' => $verification ? $this->publicData($verification) : null,
        ]);
    }

    private function publicData(IdentityVerification $verification): array
    {
        return [
            'id' => $verification->id,
            'status' => $verification->status,
            'failure_reason' => $verification->status === 'manual_review'
                ? $verification->failure_reason
                : null,
            'admin_note' => $verification->status === 'rejected'
                ? $verification->admin_note
                : null,
            'reviewed_at' => $verification->reviewed_at,
            'created_at' => $verification->created_at,
        ];
    }
}
