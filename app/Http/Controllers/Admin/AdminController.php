<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\UserVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user()->load('roles.permissions');
        $permissions = $user->getAllPermissions()->pluck('name')->unique()->values()->all();

        Log::info('Admin profile viewed', [
            'function' => __METHOD__, 'user_id' => $user->id,
            'payload' => [], 'trace' => $request->header('X-Request-Id'),
        ]);

        return response()->json([
            'user' => $user,
            'roles' => $user->getRoleNames()->values()->all(),
            'permissions' => $permissions,
        ]);
    }

    public function dashboard(Request $request)
    {
        $stats = [
            'pending_ads' => Advertisement::where('status', 'pending')->count(),
            'daily_transactions' => 0,
        ];

        Log::info('Admin dashboard viewed', [
            'function' => __METHOD__, 'user_id' => $request->user()->id,
            'payload' => [], 'trace' => $request->header('X-Request-Id'),
        ]);

        return response()->json(['data' => $stats]);
    }

    public function operatorStats(Request $request)
    {
        $pendingStatuses = [Advertisement::STATUS_PENDING_APPROVAL, 'pending'];
        $stats = [
            'pending_ads_count' => Advertisement::whereIn('status', $pendingStatuses)->count(),
            'pending_kyc_count' => UserVerification::where('status', UserVerification::STATUS_PENDING)->count(),
            'today_approved_ads' => Advertisement::where('status', Advertisement::STATUS_PUBLISHED)->whereDate('updated_at', today())->count(),
            'today_rejected_ads' => Advertisement::where('status', Advertisement::STATUS_REJECTED)->whereDate('updated_at', today())->count(),
            'recent_pending_ads' => Advertisement::with(['user:id,name,mobile', 'bank:id,name'])
                ->whereIn('status', $pendingStatuses)
                ->latest()
                ->take(5)
                ->get(),
        ];

        Log::info('Operator dashboard stats viewed', [
            'function' => __METHOD__, 'user_id' => $request->user()->id,
            'payload' => [], 'trace' => $request->header('X-Request-Id'),
        ]);

        return response()->json($stats);
    }
}