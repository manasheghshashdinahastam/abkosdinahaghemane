<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'string', 'max:60'],
            'verification_status' => ['nullable', 'in:verified,pending,rejected'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $users = User::with('roles:id,name')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")->orWhere('mobile', 'like', "%{$search}%");
            })
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', $role)))
            ->when($filters['verification_status'] ?? null, function ($query, $status) {
                return $status === 'verified'
                    ? $query->where('is_verified', true)
                    : $query->where('is_verified', false);
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 10);

        Log::info('Admin users listed', [
            'function' => __METHOD__, 'user_id' => $request->user()->id,
            'payload' => $filters, 'trace' => $request->header('X-Request-Id'),
        ]);

        return response()->json($users);
    }

    public function updateStatus(Request $request, User $user)
    {
        $payload = $request->validate(['is_banned' => ['required', 'boolean']]);
        $user->update(['is_banned' => $payload['is_banned']]);

        Log::info('Admin user status updated', [
            'function' => __METHOD__, 'user_id' => $request->user()->id,
            'payload' => ['target_user_id' => $user->id, 'is_banned' => $payload['is_banned']], 'trace' => $request->header('X-Request-Id'),
        ]);

        return response()->json(['data' => $user->fresh('roles')]);
    }
}