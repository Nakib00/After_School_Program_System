<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Student;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Class TeacherController
 * Handles teacher management and student assignments.
 */
class TeacherController extends Controller
{
    use ApiResponse;

    /**
     * List teachers for a center.
     * Accessible by: super_admin, center_admin.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Teacher::with('user');

        if ($user->role === 'center_admin') {
            // Usually a center admin is assigned to one or more centers.
            // For now, if center_id is in request, use it. 
            // Better yet, if we had a center_id on User model for admins.
            if ($request->has('center_id')) {
                $query->where('center_id', $request->center_id);
            }
        } elseif ($user->role === 'super_admin') {
            if ($request->has('center_id')) {
                $query->where('center_id', $request->center_id);
            }
        }

        $teachers = $query->get();
        return $this->success($teachers, 'Teachers retrieved successfully.');
    }

    /**
     * Create a newly created teacher account.
     * Accessible by: super_admin, center_admin.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'email'         => 'required|string|email|max:255|unique:users',
            'password'      => 'required|string|min:6',
            'center_id'     => 'required|exists:centers,id',
            'employee_id'   => 'nullable|string|max:50|unique:teachers',
            'qualification' => 'nullable|string|max:150',
            'join_date'     => 'nullable|date',
            'phone'         => 'nullable|string|max:20',
            'address'       => 'nullable|string|max:255',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            DB::beginTransaction();

            $profile_photo_path = null;
            $file = $request->file('profile_photo');
            if ($file) {
                $profile_photo_path = $file->store('profile_photos', 'public');
            }

            // Create base User
            $user = User::create([
                'name'               => $request->name,
                'email'              => $request->email,
                'password'           => Hash::make($request->password),
                'role'               => 'teacher',
                'phone'              => $request->phone,
                'address'            => $request->address,
                'profile_photo_path' => $profile_photo_path,
                'is_active'          => true,
            ]);

            // Create Teacher record
            $teacher = Teacher::create([
                'user_id'       => $user->id,
                'center_id'     => $request->center_id,
                'employee_id'   => $request->employee_id,
                'qualification' => $request->qualification,
                'join_date'     => $request->join_date,
            ]);

            DB::commit();

            return $this->success($teacher->load('user'), 'Teacher account created successfully.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create teacher: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display teacher details.
     * Accessible by: super_admin, center_admin.
     */
    public function show($id)
    {
        $teacher = Teacher::with(['user', 'center'])->find($id);

        if (!$teacher) {
            return $this->error('Teacher not found.', 404);
        }

        return $this->success($teacher, 'Teacher details retrieved successfully.');
    }

    /**
     * Update teacher info.
     * Accessible by: super_admin, center_admin.
     */
    public function update(Request $request, $id)
    {
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return $this->error('Teacher not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'name'          => 'nullable|string|max:255',
            'center_id'     => 'nullable|exists:centers,id',
            'employee_id'   => 'nullable|string|max:50|unique:teachers,employee_id,' . $id,
            'qualification' => 'nullable|string|max:150',
            'join_date'     => 'nullable|date',
            'phone'         => 'nullable|string|max:20',
            'address'       => 'nullable|string|max:255',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            DB::beginTransaction();

            $userData = $request->only(['name', 'phone', 'address']);

            $file = $request->file('profile_image') ?? $request->file('profile_photo');
            if ($file) {
                if ($teacher->user->profile_photo_path) {
                    Storage::disk('public')->delete($teacher->user->profile_photo_path);
                }
                $userData['profile_photo_path'] = $file->store('profile_photos', 'public');
            }

            if (!empty($userData)) {
                $teacher->user->update($userData);
            }

            $teacher->update($request->only(['center_id', 'employee_id', 'qualification', 'join_date']));

            DB::commit();

            return $this->success($teacher->load('user'), 'Teacher info updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to update teacher: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Students assigned to teacher.
     * Accessible by: super_admin, center_admin, teacher.
     */
    public function assignedStudents(Request $request, $id)
    {
        // Find teacher by user_id
        $teacher = Teacher::where('user_id', $id)->first();
        if (!$teacher) return $this->error('Teacher not found.', 404);

        // Security check for the teacher themselves
        // $id is the teacher's user_id
        if (auth()->user()->role === 'teacher' && auth()->user()->id != $id) {
            return $this->error('Unauthorized.', 403);
        }

        // Get students assigned to this teacher (by user_id)
        $students = Student::with('user')->where('teacher_id', $id)->get();

        return $this->success($students, 'Assigned students retrieved successfully.');
    }

    /**
     * Assign students to teacher.
     * Accessible by: super_admin, center_admin.
     */
    public function assignStudent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'teacher_id'  => 'required|exists:teachers,id',
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $teacher = Teacher::find($request->teacher_id);

            // Assigning by updating the teacher_id in students table
            Student::whereIn('id', $request->student_ids)
                ->update(['teacher_id' => $teacher->user_id]);

            return $this->success([], 'Students assigned to teacher successfully.');
        } catch (\Exception $e) {
            return $this->error('Failed to assign students: ' . $e->getMessage(), 500);
        }
    }
}
