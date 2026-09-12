<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminAdvertisementController extends Controller
{
    public function show(Request $request, Advertisement $advertisement)
    {
        abort_unless(in_array($advertisement->status, [Advertisement::STATUS_PENDING_APPROVAL, 'pending'], true), 404);
        $advertisement->load(['user:id,name,mobile,is_verified', 'bank:id,name', 'bankPlan:id,title,interest_rate', 'location:id,name,parent_id', 'location.parent:id,name']);

        Log::info('Admin pending advertisement viewed', [
            'function' => __METHOD__, 'user_id' => $request->user()->id,
            'payload' => ['advertisement_id' => $advertisement->id], 'trace' => $request->header('X-Request-Id'),
        ]);

        return response()->json(['data' => $advertisement]);
    }

    public function pending(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'bank_id' => ['nullable', 'integer', 'exists:banks,id'],
            'bank' => ['nullable', 'integer', 'exists:banks,id'],
            'deal_type' => ['nullable', 'in:supply,demand'],
            'type' => ['nullable', 'in:supply,demand'],
            'amount_range' => ['nullable', 'in:under_50,50_200,over_200'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $bankId = $filters['bank'] ?? $filters['bank_id'] ?? null;
        $dealType = $filters['deal_type'] ?? $filters['type'] ?? null;
        $pendingStatuses = [Advertisement::STATUS_PENDING_APPROVAL, 'pending'];
        $ads = Advertisement::with(['user:id,name,mobile,is_verified', 'bank:id,name', 'bankPlan:id,title', 'location:id,name,parent_id', 'location.parent:id,name'])
            ->whereIn('status', $pendingStatuses)
            ->when($bankId, fn ($query) => $query->where('bank_id', $bankId))
            ->when($dealType, fn ($query) => $query->where('type', $dealType))
            ->when($filters['amount_range'] ?? null, function ($query, $range) {
                return match ($range) {
                    'under_50' => $query->where('loan_amount', '<', 50_000_000),
                    '50_200' => $query->whereBetween('loan_amount', [50_000_000, 200_000_000]),
                    'over_200' => $query->where('loan_amount', '>', 200_000_000),
                    default => $query,
                };
            })
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%")->orWhere('mobile', 'like', "%{$search}%"));

                    if (ctype_digit((string) $search)) {
                        $query->orWhere('id', (int) $search);
                    }
                });
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 10);

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

        return response()->json(['data' => $advertisement->fresh(['user', 'bank', 'bankPlan', 'location.parent'])]);
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