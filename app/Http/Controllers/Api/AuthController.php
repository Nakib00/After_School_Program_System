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
use Illuminate\Support\Facades\Storage;
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
            'role'     => 'required|in:super_admin,center_admin,parents',
            'phone'    => 'nullable|string|max:20',
            'address'  => 'nullable|string|max:255',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            DB::beginTransaction();

            $profile_photo_path = null;
            $file = $request->file('profile_image') ?? $request->file('profile_photo');
            if ($file) {
                $profile_photo_path = $file->store('profile_photos', 'public');
            }

            // Create base User
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => $request->role,
                'phone'    => $request->phone,
                'address'  => $request->address,
                'profile_photo_path' => $profile_photo_path,
                'is_active' => true,
            ]);

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

    /**
     * Update authenticated user profile.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'name'    => 'nullable|string|max:255',
            'phone'   => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $data = $request->only(['name', 'phone', 'address']);

            // Handle profile image upload
            $file = $request->file('profile_image') ?? $request->file('profile_photo');
            if ($file) {
                // Delete old image if exists
                if ($user->profile_photo_path) {
                    Storage::disk('public')->delete($user->profile_photo_path);
                }

                $path = $file->store('profile_photos', 'public');
                $data['profile_photo_path'] = $path;
            }

            $user->update($data);

            return $this->success($user, 'Profile updated successfully.');
        } catch (\Exception $e) {
            return $this->error('Failed to update profile: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Change authenticated user password.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        // Check if current password matches
        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Current password does not match.', 422);
        }

        try {
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            return $this->success([], 'Password changed successfully.');
        } catch (\Exception $e) {
            return $this->error('Failed to change password: ' . $e->getMessage(), 500);
        }
    }
}
