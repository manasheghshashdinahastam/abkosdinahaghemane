<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminKycController extends Controller
{
    public function pending(Request $request)
    {
        $verifications = UserVerification::with('user:id,name,mobile,national_code')
            ->where('status', UserVerification::STATUS_PENDING)
            ->latest()
            ->paginate(20);

        Log::info('Admin pending KYC listed', [
            'function' => __METHOD__, 'user_id' => $request->user()->id,
            'payload' => [], 'trace' => $request->header('X-Request-Id'),
        ]);

        return response()->json($verifications);
    }
}