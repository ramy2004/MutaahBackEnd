<?php

namespace App\Services;

use App\Models\IdentityVerification;

class IdentityVerificationService
{
    public function createPending(IdentityVerification $verification): IdentityVerification
    {
        return $verification->updateOrFail([
            'status' => 'manual_review',
        ]) ? $verification->fresh() : $verification;
    }
}
