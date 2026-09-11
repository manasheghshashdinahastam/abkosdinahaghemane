<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminAdvertisementController extends Controller
{
    public function pending(Request $request)
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $pendingStatuses = [Advertisement::STATUS_PENDING_APPROVAL, 'pending'];
        $ads = Advertisement::with(['user:id,name,mobile', 'bank:id,name'])
            ->whereIn('status', $pendingStatuses)
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(15);

        Log::info('Admin pending advertisements listed', [
            'function' => __METHOD__, 'user_id' => $request->user()->id,
            'payload' => $filters, 'trace' => $request->header('X-Request-Id'),
        ]);

        return response()->json($ads);
    }

    public function approve(Request $request, Advertisement $advertisement)
    {
        abort_unless(in_array($advertisement->status, [Advertisement::STATUS_PENDING_APPROVAL, 'pending'], true), 422);
        $advertisement->update(['status' => Advertisement::STATUS_PUBLISHED, 'rejection_reason' => null]);

        Log::info('Admin advertisement approved', [
            'function' => __METHOD__, 'user_id' => $request->user()->id,
            'payload' => ['advertisement_id' => $advertisement->id], 'trace' => $request->header('X-Request-Id'),
        ]);

        return response()->json(['data' => $advertisement->fresh(['user', 'bank'])]);
    }

    public function reject(Request $request, Advertisement $advertisement)
    {
        $payload = $request->validate(['rejection_reason' => ['required', 'string', 'max:2000']]);
        abort_unless(in_array($advertisement->status, [Advertisement::STATUS_PENDING_APPROVAL, 'pending'], true), 422);
        $advertisement->update(['status' => Advertisement::STATUS_REJECTED, 'rejection_reason' => $payload['rejection_reason']]);

        Log::info('Admin advertisement rejected', [
            'function' => __METHOD__, 'user_id' => $request->user()->id,
            'payload' => ['advertisement_id' => $advertisement->id, 'has_rejection_reason' => true], 'trace' => $request->header('X-Request-Id'),
        ]);

        return response()->json(['data' => $advertisement->fresh(['user', 'bank'])]);
    }
}