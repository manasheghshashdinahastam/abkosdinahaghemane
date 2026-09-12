<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

    public function showMedia(Request $request, UserVerification $kyc, string $type)
    {
        $fields = [
            'front' => 'national_card_front_path',
            'back' => 'national_card_back_path',
            'residence' => 'residence_document_path',
        ];
        abort_unless(isset($fields[$type]) && $kyc->{$fields[$type]}, 404);

        $path = $kyc->{$fields[$type]};
        abort_unless(Storage::disk('local')->exists($path), 404);

        Log::info('Admin KYC media viewed', [
            'function' => __METHOD__, 'user_id' => $request->user()?->id,
            'payload' => ['kyc_id' => $kyc->id, 'type' => $type], 'trace' => $request->header('X-Request-Id'),
        ]);

        return Storage::disk('local')->response($path);
    }
}