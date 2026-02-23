<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * Class AuthController
 * Handles authentication and registration for all user roles.
 */
class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register a new user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        // Define registration validation rules
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role'     => 'required|in:super_admin,center_admin,teacher,parent,student',
            'phone'    => 'nullable|string|max:20',
            'address'  => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            DB::beginTransaction();

            // Create base User
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => $request->role,
                'phone'    => $request->phone,
                'address'  => $request->address,
                'is_active' => true,
            ]);

            // Handle role-specific data if needed
            // For example, if we need to create entry in teachers or students table
            if ($request->role === 'student') {
                Student::create([
                    'user_id' => $user->id,
                    // other default fields for student
                ]);
            } elseif ($request->role === 'teacher') {
                Teacher::create([
                    'user_id' => $user->id,
                    // other default fields for teacher
                ]);
            }

            DB::commit();

            // Generate token for the newly registered user
            $token = JWTAuth::fromUser($user);

            return $this->success([
                'user'  => $user,
                'token' => $token,
            ], 'User registered successfully.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Registration failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Login user and return token.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return $this->error('Invalid credentials.', 401);
        }

        return $this->success([
            'user'  => auth()->user(),
            'token' => $token,
        ], 'Login successful.');
    }

    /**
     * Get authenticated user profile.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile()
    {
        return $this->success(auth()->user());
    }

    /**
     * Logout user (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();
        return $this->success([], 'Successfully logged out.');
    }
}
