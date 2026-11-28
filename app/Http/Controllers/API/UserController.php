<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::with('roleRecord');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->query('role')) {
            $query->whereHas(
                'roleRecord',
                fn ($q) => $q->where('code', $role)
            );
        }

        $users = $query->orderBy('full_name')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $data['password'] = Hash::make($data['password']);
        $data['status'] = $data['status'] ?? true;

        $user = User::create($data);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => $user->load('roleRecord'),
        ], 201);
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $data = $request->validated();

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => $user->load('roleRecord'),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        if ($user->sales()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete user with existing sales records.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }
}
