<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of users with pagination.
     */
    public function index(Request $request): JsonResponse
    {
        // Only admin can view all users
        if (Gate::denies('viewAny', User::class)) {
            return $this->error(['You do not have permission to view users.'], 403);
        }

        $query = User::query();

        // Filter by role if provided
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        return $this->success([
            'users' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
        ], ['Users retrieved successfully.']);
    }

    /**
     * Display the specified user.
     */
    public function show(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return $this->error(['User not found.'], 404);
        }

        // Check authorization
        if (Gate::denies('view', $user)) {
            return $this->error(['You do not have permission to view this user.'], 403);
        }

        return $this->success([
            'user' => $user,
        ], ['User retrieved successfully.']);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): JsonResponse
    {
        // Only admin can create users
        if (Gate::denies('create', User::class)) {
            return $this->error(['You do not have permission to create users.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', 'min:8'],
            'role' => ['required', 'in:user,admin,hotel_owner'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'email_verified_at' => now(), // Auto-verify for admin-created users
        ]);

        return $this->success(
            ['user' => $user],
            ['User created successfully.'],
            201
        );
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return $this->error(['User not found.'], 404);
        }

        // Check authorization
        if (Gate::denies('update', $user)) {
            return $this->error(['You do not have permission to update this user.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $id],
            'role' => ['sometimes', 'in:user,admin,hotel_owner'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        $data = $validator->validated();
        $user->update($data);

        return $this->success(
            ['user' => $user->fresh()],
            ['User updated successfully.']
        );
    }

    /**
     * Remove the specified user.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return $this->error(['User not found.'], 404);
        }

        // Only admin can delete users
        if (Gate::denies('delete', $user)) {
            return $this->error(['You do not have permission to delete this user.'], 403);
        }

        $user->delete();

        return $this->success(
            null,
            ['User deleted successfully.']
        );
    }
}

