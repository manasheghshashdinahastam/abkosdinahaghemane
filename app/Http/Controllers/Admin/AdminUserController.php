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
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $users = User::with('roles:id,name')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")->orWhere('mobile', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(20);

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