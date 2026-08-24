<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IdentityVerification;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminIdentityVerificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return $this->forbidden();
        }

        $query = IdentityVerification::with(['user', 'reviewer'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(12),
        ]);
    }

    public function approve(Request $request, IdentityVerification $identityVerification): JsonResponse
    {
        return $this->review($request, $identityVerification, 'approved');
    }

    public function reject(Request $request, IdentityVerification $identityVerification): JsonResponse
    {
        $request->validate([
            'admin_note' => ['required', 'string', 'max:1000'],
        ]);

        return $this->review($request, $identityVerification, 'rejected');
    }

    private function review(
        Request $request,
        IdentityVerification $identityVerification,
        string $status
    ): JsonResponse {
        if (!$this->isAdmin($request)) {
            return $this->forbidden();
        }

        if (!in_array($identityVerification->status, ['manual_review', 'pending'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن مراجعة طلب بهذه الحالة',
            ], 422);
        }

        $identityVerification->update([
            'status' => $status,
            'admin_note' => $request->input('admin_note'),
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        Notification::create([
            'user_id' => $identityVerification->user_id,
            'type' => 'identity_verification',
            'title' => $status === 'approved' ? 'تم قبول التحقق' : 'تم رفض التحقق',
            'message' => $status === 'approved'
                ? 'تم اعتماد هويتك من الإدارة.'
                : 'تم رفض طلب التحقق اليدوي. ' . $request->input('admin_note'),
            'is_read' => false,
            'ref_id' => $identityVerification->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => $status === 'approved' ? 'تم قبول الهوية' : 'تم رفض الهوية',
            'data' => $identityVerification->fresh(['user', 'reviewer']),
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
