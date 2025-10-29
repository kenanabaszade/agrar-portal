<?php
 
namespace App\Http\Controllers;
 
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    
    public function index(Request $request)
    {
        $query = User::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Filter by user type
        if ($request->filled('user_type')) {
            $query->where('user_type', $request->get('user_type'));
        }

        // Filter by region
        if ($request->filled('region')) {
            $query->where('region', $request->get('region'));
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->get('is_active') === 'true');
        }

        // Filter by email verification
        if ($request->filled('email_verified')) {
            $query->where('email_verified', $request->get('email_verified') === 'true');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if (in_array($sortBy, ['first_name', 'last_name', 'email', 'user_type', 'created_at', 'last_login_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = min($request->get('per_page', 20), 100);
        $users = $query->paginate($perPage);

        // Transform each user with additional info
        $users->getCollection()->transform(function ($user) {
            return [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'user_type' => $user->user_type,
                'region' => $user->region,
                'is_active' => $user->is_active,
                'email_verified' => $user->email_verified,
                'two_factor_enabled' => $user->two_factor_enabled,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'last_login_at' => $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : null,
                'full_name' => $user->first_name . ' ' . $user->last_name,
            ];
        });

        return response()->json([
            'users' => $users,
            'summary' => [
                'total_users' => $users->total(),
                'active_users' => $users->getCollection()->where('is_active', true)->count(),
                'verified_users' => $users->getCollection()->where('email_verified', true)->count(),
                'user_types' => $users->getCollection()->groupBy('user_type')->map->count(),
            ]
        ]);
    }

    /**
     * Get simple user list with email and nickname
     */
    public function simpleList(Request $request)
    {
        $users = User::select('id', 'first_name', 'last_name', 'username', 'email')
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        $userList = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'email' => $user->email,
                'nickname' => $user->username ?: $user->first_name . ' ' . $user->last_name,
                'full_name' => $user->first_name . ' ' . $user->last_name,
            ];
        });

        // Add placeholder option at the beginning
        $userList->prepend([
            'id' => null,
            'email' => '',
            'nickname' => 'İstifadəçi seçin...',
            'full_name' => 'İstifadəçi seçin...'
        ]);

        return response()->json([
            'success' => true,
            'users' => $userList,
            'total' => $userList->count() - 1 // Exclude placeholder from count
        ]);
    }

    /**
     * Get trainers list with id and name
     */
    public function trainersList(Request $request)
    {
        $trainers = User::select('id', 'first_name', 'last_name')
            ->where('user_type', 'trainer')
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        $trainerList = $trainers->map(function ($trainer) {
            return [
                'id' => $trainer->id,
                'name' => $trainer->first_name . ' ' . $trainer->last_name,
            ];
        });

        // Add placeholder option at the beginning
        $trainerList->prepend([
            'id' => null,
            'name' => 'Təlimçi seçin...'
        ]);

        return response()->json([
            'success' => true,
            'trainers' => $trainerList,
            'total' => $trainerList->count() - 1 // Exclude placeholder from count
        ]);
    }

    /**
     * Get categories list with id and name
     */
    public function categoriesList(Request $request)
    {
        $categories = \App\Models\Category::select('id', 'name')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $categoryList = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
            ];
        });

        // Add placeholder option at the beginning
        $categoryList->prepend([
            'id' => null,
            'name' => 'Kateqoriya seçin...'
        ]);

        return response()->json([
            'success' => true,
            'categories' => $categoryList,
            'total' => $categoryList->count() - 1 // Exclude placeholder from count
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255', 'unique:users,username'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:50'],
            'how_did_you_hear' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8'],
            'user_type' => ['required', 'in:farmer,trainer,admin,agronom,veterinary,government,entrepreneur,researcher'],
            'two_factor_enabled' => ['boolean'],
        ]);

        // Create the user
        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'username' => $validated['username'] ?? null,
            'father_name' => $validated['father_name'] ?? null,
            'region' => $validated['region'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'how_did_you_hear' => $validated['how_did_you_hear'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password_hash' => Hash::make($validated['password']),
            'user_type' => $validated['user_type'],
            'two_factor_enabled' => $validated['two_factor_enabled'] ?? false,
            'email_verified' => true, // Admin-created users are automatically verified
        ]);

        // Send welcome email with credentials to the new user
        $user->notify(new \App\Notifications\UserCreatedNotification(
            $validated['email'],
            $validated['password'],
            $request->user()->first_name . ' ' . $request->user()->last_name
        ));

        return response()->json([
            'message' => 'User created successfully and welcome email sent',
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
            'user_type' => ['nullable', 'in:farmer,trainer,admin,agronom,veterinary,government,entrepreneur,researcher'],
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

    /**
     * Get user statistics for dashboard
     */
    public function getStats(Request $request)
    {
        $now = now();
        $lastMonth = $now->copy()->subMonth();
        $currentMonth = $now->copy()->startOfMonth();
        $lastMonthStart = $lastMonth->copy()->startOfMonth();
        $lastMonthEnd = $lastMonth->copy()->endOfMonth();

        // Current statistics
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $newRegistrations = User::where('created_at', '>=', $currentMonth)->count();
        $farmers = User::where('user_type', 'farmer')->count();

        // Last month statistics for growth calculation
        $totalUsersLastMonth = User::where('created_at', '<=', $lastMonthEnd)->count();
        $activeUsersLastMonth = User::where('is_active', true)
            ->where('created_at', '<=', $lastMonthEnd)->count();
        $newRegistrationsLastMonth = User::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();
        $farmersLastMonth = User::where('user_type', 'farmer')
            ->where('created_at', '<=', $lastMonthEnd)->count();

        // Calculate growth rates
        $totalUsersGrowth = $totalUsersLastMonth > 0 
            ? round((($totalUsers - $totalUsersLastMonth) / $totalUsersLastMonth) * 100, 1) 
            : 0;
        $activeUsersGrowth = $activeUsersLastMonth > 0 
            ? round((($activeUsers - $activeUsersLastMonth) / $activeUsersLastMonth) * 100, 1) 
            : 0;
        $newRegistrationsGrowth = $newRegistrationsLastMonth > 0 
            ? round((($newRegistrations - $newRegistrationsLastMonth) / $newRegistrationsLastMonth) * 100, 1) 
            : 0;
        $farmersGrowth = $farmersLastMonth > 0 
            ? round((($farmers - $farmersLastMonth) / $farmersLastMonth) * 100, 1) 
            : 0;

        // User type breakdown
        $userTypeBreakdown = User::selectRaw('user_type, COUNT(*) as count')
            ->groupBy('user_type')
            ->get()
            ->pluck('count', 'user_type');

        // Recent activity (last 7 days)
        $recentRegistrations = User::where('created_at', '>=', $now->copy()->subDays(7))->count();
        $recentActiveUsers = User::where('last_login_at', '>=', $now->copy()->subDays(7))->count();

        // Regional distribution
        $regionalDistribution = User::selectRaw('region, COUNT(*) as count')
            ->whereNotNull('region')
            ->groupBy('region')
            ->orderBy('count', 'desc')
            ->get();

        return response()->json([
            'overview' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'new_registrations' => $newRegistrations,
                'farmers' => $farmers,
            ],
            'growth_rates' => [
                'total_users_growth' => $totalUsersGrowth,
                'active_users_growth' => $activeUsersGrowth,
                'new_registrations_growth' => $newRegistrationsGrowth,
                'farmers_growth' => $farmersGrowth,
            ],
            'user_type_breakdown' => [
                'farmers' => $userTypeBreakdown->get('farmer', 0),
                'trainers' => $userTypeBreakdown->get('trainer', 0),
                'admins' => $userTypeBreakdown->get('admin', 0),
            ],
            'recent_activity' => [
                'registrations_last_7_days' => $recentRegistrations,
                'active_users_last_7_days' => $recentActiveUsers,
            ],
            'regional_distribution' => $regionalDistribution,
            'comparison' => [
                'last_month' => [
                    'total_users' => $totalUsersLastMonth,
                    'active_users' => $activeUsersLastMonth,
                    'new_registrations' => $newRegistrationsLastMonth,
                    'farmers' => $farmersLastMonth,
                ]
            ]
        ]);
    }
}
 
 

