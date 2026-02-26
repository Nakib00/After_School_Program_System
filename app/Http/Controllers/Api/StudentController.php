<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * Class StudentController
 * Handles student management and related data.
 */
class StudentController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of students.
     * Accessible by: super_admin, center_admin, teacher.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Student::with(['user', 'center']);

        // Filter by center if not super admin
        if ($user->role !== 'super_admin') {
            // Find which center the admin/teacher belongs to.
            // Assuming center_admin/teacher has a center_id or we can deduce it.
            // For now, let's assume we filter by center_id if provided in the request or deduced from user.
            if ($request->has('center_id')) {
                $query->where('center_id', $request->center_id);
            }
        }

        $students = $query->get();
        return $this->success($students, 'Students retrieved successfully.');
    }

    /**
     * Store a newly created student.
     * Accessible by: super_admin, center_admin.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'            => 'required|string|max:255',
            'email'           => 'required|string|email|max:255|unique:users',
            'password'        => 'required|string|min:6',
            'center_id'       => 'required|exists:centers,id',
            'parent_id'       => 'nullable|exists:users,id',
            'teacher_id'      => 'nullable|exists:users,id',
            'enrollment_no'   => 'nullable|string|max:50|unique:students',
            'date_of_birth'   => 'nullable|date',
            'grade'           => 'nullable|string|max:20',
            'enrollment_date' => 'nullable|date',
            'subjects'        => 'nullable|array',
            'current_level'   => 'nullable|string|max:20',
            'profile_image'   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'address'         => 'nullable|string|max:255',
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

            // Create User record first
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => 'student',
                'profile_photo_path' => $profile_photo_path,
                'is_active' => true,
                'address' => $request->address,
            ]);

            // Create Student record
            $student = Student::create([
                'user_id'         => $user->id,
                'center_id'       => $request->center_id,
                'parent_id'       => $request->parent_id,
                'teacher_id'      => $request->teacher_id,
                'enrollment_no'   => $request->enrollment_no,
                'date_of_birth'   => $request->date_of_birth,
                'grade'           => $request->grade,
                'enrollment_date' => $request->enrollment_date,
                'subjects'        => $request->subjects,
                'current_level'   => $request->current_level,
                'status'          => 'active',
            ]);

            DB::commit();

            return $this->success($student->load('user'), 'Student created successfully.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create student: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified student.
     * Accessible by: super_admin, center_admin, teacher, parent.
     */
    public function show($id)
    {
        $student = Student::with(['user', 'center', 'parent', 'teacher'])->find($id);

        if (!$student) {
            return $this->error('Student not found.', 404);
        }

        // Parent can only see their own children
        if (auth()->user()->role === 'parent' && $student->parent_id !== auth()->id()) {
            return $this->error('Unauthorized to view this student.', 403);
        }

        return $this->success($student, 'Student retrieved successfully.');
    }

    /**
     * Update the specified student.
     * Accessible by: super_admin, center_admin, teacher.
     */
    public function update(Request $request, $id)
    {
        $student = Student::find($id);

        if (!$student) {
            return $this->error('Student not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'name'            => 'nullable|string|max:255',
            'center_id'       => 'nullable|exists:centers,id',
            'parent_id'       => 'nullable|exists:users,id',
            'teacher_id'      => 'nullable|exists:users,id',
            'enrollment_no'   => 'nullable|string|max:50|unique:students,enrollment_no,' . $id,
            'date_of_birth'   => 'nullable|date',
            'grade'           => 'nullable|string|max:20',
            'enrollment_date' => 'nullable|date',
            'subjects'        => 'nullable|array',
            'current_level'   => 'nullable|string|max:20',
            'status'          => 'nullable|in:active,inactive,completed',
            'profile_image'   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'address'         => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            DB::beginTransaction();

            // Update associated User record if name or profile image is provided
            $userData = [];
            if ($request->has('name')) {
                $userData['name'] = $request->name;
            }
            if ($request->has('address')) {
                $userData['address'] = $request->address;
            }
            $file = $request->file('profile_image') ?? $request->file('profile_photo');
            if ($file) {
                // Delete old image if exists
                if ($student->user->profile_photo_path) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($student->user->profile_photo_path);
                }
                $userData['profile_photo_path'] = $file->store('profile_photos', 'public');
            }

            if (!empty($userData)) {
                $student->user->update($userData);
            }

            // Update Student record
            $student->update($request->all());

            DB::commit();

            return $this->success($student->load('user'), 'Student updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to update student: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified student.
     * Accessible by: super_admin, center_admin.
     */
    public function destroy($id)
    {
        $student = Student::find($id);

        if (!$student) {
            return $this->error('Student not found.', 404);
        }

        try {
            DB::beginTransaction();

            // Store User ID to delete it after student record is gone (or cascade handles it)
            $userId = $student->user_id;

            $student->delete();

            // Delete the base User record as well
            User::where('id', $userId)->delete();

            DB::commit();

            return $this->success([], 'Student and associated user deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to delete student: ' . $e->getMessage(), 500);
        }
    }

    /**
     * See single student progress.
     * Accessible by: all roles.
     */
    public function progress($id)
    {
        $student = Student::find($id);
        if (!$student) return $this->error('Student not found.', 404);

        $progress = $student->progress()->with(['subject', 'level'])->get();
        return $this->success($progress, 'Student progress retrieved successfully.');
    }

    /**
     * See student assignments.
     * Accessible by: super_admin, center_admin, teacher, parent.
     */
    public function assignments($id)
    {
        $student = Student::find($id);
        if (!$student) return $this->error('Student not found.', 404);

        // Access check for parent
        if (auth()->user()->role === 'parent' && $student->parent_id !== auth()->id()) {
            return $this->error('Unauthorized.', 403);
        }

        $assignments = $student->assignments()->with('worksheet')->get();
        return $this->success($assignments, 'Student assignments retrieved successfully.');
    }

    /**
     * Student's attendance records.
     * Accessible by: super_admin, center_admin, teacher, parent.
     */
    public function attendance($id)
    {
        $student = Student::find($id);
        if (!$student) return $this->error('Student not found.', 404);

        // Access check for parent
        if (auth()->user()->role === 'parent' && $student->parent_id !== auth()->id()) {
            return $this->error('Unauthorized.', 403);
        }

        $attendance = $student->attendance()->get();
        return $this->success($attendance, 'Student attendance retrieved successfully.');
    }

    /**
     * Student's own assignments.
     * Accessible by: student.
     */
    public function myAssignments(Request $request)
    {
        $user = $request->user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return $this->error('Student profile not found.', 404);
        }

        $assignments = $student->assignments()
            ->with(['worksheet', 'teacher'])
            ->latest()
            ->get();

        return $this->success($assignments, 'Your assignments retrieved successfully.');
    }

    /**
     * Student's fee history.
     * Accessible by: super_admin, center_admin, parent.
     */
    public function fees($id)
    {
        $student = Student::find($id);
        if (!$student) return $this->error('Student not found.', 404);

        // Access check for parent
        if (auth()->user()->role === 'parent' && $student->parent_id !== auth()->id()) {
            return $this->error('Unauthorized.', 403);
        }

        $fees = $student->fees()->get();
        return $this->success($fees, 'Student fee history retrieved successfully.');
    }
}
