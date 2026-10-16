<?php

namespace App\Services\Auth;

use App\Models\OtpCode;
use Illuminate\Support\Carbon;

class OtpService
{
    public function create(string $phone, array $meta = [], string $purpose = 'login'): OtpCode
    {
        OtpCode::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->delete();

        $code = app()->environment('production')
            ? (string) random_int(100000, 999999)
            : '123456';

        return OtpCode::query()->create([
            'phone' => $phone,
            'purpose' => $purpose,
            'code' => $code,
            'expires_at' => now()->addMinutes(5),
            'meta' => $meta,
        ]);
    }

    public function verify(string $phone, string $code, string $purpose = 'login'): ?OtpCode
    {
        $otp = OtpCode::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (! $otp) {
            return null;
        }

        if ($otp->expires_at->isPast() || $otp->code !== $code) {
            return null;
        }

        $otp->forceFill([
            'verified_at' => Carbon::now(),
        ])->save();

        return $otp;
    }
}
