<?php
 
namespace App\Http\Controllers;
 
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    
    public function index()
    {
        return User::paginate(20);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255', 'unique:users,username'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8'],
            'user_type' => ['required', 'in:farmer,trainer,admin'],
            'two_factor_enabled' => ['boolean'],
        ]);

        // Create the user
        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'username' => $validated['username'] ?? null,
            'father_name' => $validated['father_name'] ?? null,
            'region' => $validated['region'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password_hash' => Hash::make($validated['password']),
            'user_type' => $validated['user_type'],
            'two_factor_enabled' => $validated['two_factor_enabled'] ?? false,
            'email_verified' => true, // Admin-created users are automatically verified
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
        ], 201);
    }

    public function show(User $user)
    {
        return $user;
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'user_type' => ['nullable', 'in:farmer,trainer,admin'],
            'is_active' => ['boolean'],
            'password' => ['nullable', 'string', 'min:8'],
            'two_factor_enabled' => ['sometimes', 'boolean'],
        ]);

        if (isset($validated['password'])) {
            $validated['password_hash'] = Hash::make($validated['password']);
            unset($validated['password']);
        }

        $user->update($validated);
        return $user;
    }

    public function toggleTwoFactor(Request $request, User $user)
    {
        $validated = $request->validate([
            'two_factor_enabled' => ['required', 'boolean'],
        ]);

        $updateData = [
            'two_factor_enabled' => $validated['two_factor_enabled'],
        ];

        // If disabling 2FA, clear any existing OTP codes
        if (!$validated['two_factor_enabled']) {
            $updateData['otp_code'] = null;
            $updateData['otp_expires_at'] = null;
        }

        $user->update($updateData);

        return response()->json([
            'message' => '2FA setting updated successfully',
            'user' => $user,
        ], 200);
    }

    public function destroy(Request $request, User $user)
    {
        // Check if user is trying to delete themselves
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'You cannot delete your own account'
            ], 400);
        }

        // Check if user is the last admin
        if ($user->user_type === 'admin') {
            $adminCount = User::where('user_type', 'admin')->count();
            if ($adminCount <= 1) {
                return response()->json([
                    'message' => 'Cannot delete the last admin user'
                ], 400);
            }
        }

        // Delete user's tokens
        $user->tokens()->delete();

        // Delete user's email change requests
        $user->emailChangeRequests()->delete();

        // Delete the user
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ], 200);
    }
}
 
 

