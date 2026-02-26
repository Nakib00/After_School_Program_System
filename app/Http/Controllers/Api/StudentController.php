<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Models\Submission;
use App\Models\StudentProgress;
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
        $query = Student::with(['user', 'center', 'teacher.teacher']);

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
        $student = Student::with(['user', 'center', 'parent', 'teacher.teacher'])->find($id);
        if (!$student) return $this->error('Student not found.', 404);

        $user = auth()->user();

        // Parent can only see their own children
        if ($user->role === 'parent' && $student->parent_id !== $user->id) {
            return $this->error('Unauthorized to view this student.', 403);
        }

        // Teacher can only see their own students
        if ($user->role === 'teacher' && $student->teacher_id !== $user->id) {
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

        $user = auth()->user();

        // Parent can only see their own children
        if ($user->role === 'parent' && $student->parent_id !== $user->id) {
            return $this->error('Unauthorized.', 403);
        }

        // Teacher can only see their own students
        if ($user->role === 'teacher' && $student->teacher_id !== $user->id) {
            return $this->error('Unauthorized.', 403);
        }

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

        $user = auth()->user();

        // Access check for parent
        if ($user->role === 'parent' && $student->parent_id !== $user->id) {
            return $this->error('Unauthorized.', 403);
        }

        // Teacher can only see their own students
        if ($user->role === 'teacher' && $student->teacher_id !== $user->id) {
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

        $user = auth()->user();

        // Access check for parent
        if ($user->role === 'parent' && $student->parent_id !== $user->id) {
            return $this->error('Unauthorized.', 403);
        }

        // Teacher can only see their own students
        if ($user->role === 'teacher' && $student->teacher_id !== $user->id) {
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
    /**
     * Student dashboard data.
     * Accessible by: student.
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $student = Student::with(['user', 'center', 'teacher.teacher'])->where('user_id', $user->id)->first();

        if (!$student) {
            return $this->error('Student profile not found.', 404);
        }

        // Stats
        $stats = [
            'total_assignments' => $student->assignments()->count(),
            'pending_assignments' => $student->assignments()->where('status', 'assigned')->count(),
            'submitted_assignments' => $student->assignments()->where('status', 'submitted')->count(),
            'graded_assignments' => $student->assignments()->where('status', 'graded')->count(),
        ];

        // Recent Assignments
        $recentAssignments = $student->assignments()
            ->with(['worksheet', 'teacher'])
            ->latest()
            ->limit(5)
            ->get();

        // Progress
        $progress = $student->progress()->with(['subject', 'level'])->get();

        return $this->success([
            'student'           => $student,
            'stats'             => $stats,
            'recent_assignments' => $recentAssignments,
            'progress'          => $progress
        ], 'Dashboard data retrieved successfully.');
    }

    /**
     * Get detailed reports for all children of a parent.
     * Accessible by: parent.
     */
    public function childrenReports(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'parent') {
            return $this->error('Unauthorized. Only parents can access this endpoint.', 403);
        }

        $students = Student::with(['user', 'center', 'teacher.teacher'])
            ->where('parent_id', $user->id)
            ->get();

        if ($students->isEmpty()) {
            return $this->success([], 'No children found for this parent.');
        }

        $reports = $students->map(function ($student) {
            return $this->getDetailedReport($student);
        });

        return $this->success($reports, 'Children reports retrieved successfully.');
    }

    /**
     * Get fee details for all children of a parent.
     * Accessible by: parent.
     */
    public function childrenFees(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'parent') {
            return $this->error('Unauthorized. Only parents can access this endpoint.', 403);
        }

        $students = Student::with(['user', 'fees'])
            ->where('parent_id', $user->id)
            ->get();

        if ($students->isEmpty()) {
            return $this->success([], 'No children found for this parent.');
        }

        $feesSummary = $students->map(function ($student) {
            return [
                'student_info' => [
                    'id'            => $student->id,
                    'name'          => $student->user->name,
                    'enrollment_no' => $student->enrollment_no,
                ],
                'fees' => $student->fees
            ];
        });

        return $this->success($feesSummary, 'Children fees retrieved successfully.');
    }

    /**
     * Get fee history for a student.
     * Accessible by: super_admin, center_admin, parent.
     */
    public function fees($id)
    {
        $student = Student::find($id);
        if (!$student) return $this->error('Student not found.', 404);

        $user = auth()->user();

        // Access check for parent
        if ($user->role === 'parent' && $student->parent_id !== $user->id) {
            return $this->error('Unauthorized.', 403);
        }

        // Access check for admin/super admin
        if (!in_array($user->role, ['super_admin', 'center_admin', 'parent'])) {
            return $this->error('Unauthorized.', 403);
        }

        $fees = $student->fees()->latest('month')->get();
        return $this->success($fees, 'Student fee history retrieved successfully.');
    }

    /**
     * Get assignments, worksheets, and submission/grade info for all children of a parent.
     * Accessible by: parent.
     */
    public function childrenAssignments(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'parent') {
            return $this->error('Unauthorized. Only parents can access this endpoint.', 403);
        }

        $students = Student::with(['user', 'assignments.worksheet', 'assignments.submission', 'assignments.teacher'])
            ->where('parent_id', $user->id)
            ->get();

        if ($students->isEmpty()) {
            return $this->success([], 'No children found for this parent.');
        }

        $assignmentsSummary = $students->map(function ($student) {
            return [
                'student_info' => [
                    'id'            => $student->id,
                    'name'          => $student->user->name,
                    'enrollment_no' => $student->enrollment_no,
                ],
                'assignments' => $student->assignments
            ];
        });

        return $this->success($assignmentsSummary, 'Children assignments retrieved successfully.');
    }

    /**
     * Get attendance records for all children of a parent.
     * Accessible by: parent.
     */
    public function childrenAttendance(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'parent') {
            return $this->error('Unauthorized. Only parents can access this endpoint.', 403);
        }

        $students = Student::with(['user', 'attendance'])
            ->where('parent_id', $user->id)
            ->get();

        if ($students->isEmpty()) {
            return $this->success([], 'No children found for this parent.');
        }

        $attendanceSummary = $students->map(function ($student) {
            return [
                'student_info' => [
                    'id'            => $student->id,
                    'name'          => $student->user->name,
                    'enrollment_no' => $student->enrollment_no,
                ],
                'attendance' => $student->attendance
            ];
        });

        return $this->success($attendanceSummary, 'Children attendance retrieved successfully.');
    }

    /**
     * Get detailed reports for a student.
     * Accessible by: student, parent, teacher, super_admin, center_admin.
     */
    public function reports($id)
    {
        $student = Student::with(['user', 'center', 'teacher.teacher', 'parent'])->find($id);
        if (!$student) return $this->error('Student not found.', 404);

        $user = auth()->user();

        // Authorization check
        if ($user->role === 'student' && $student->user_id !== $user->id) {
            return $this->error('Unauthorized. You can only view your own reports.', 403);
        }
        if ($user->role === 'parent' && $student->parent_id !== $user->id) {
            return $this->error('Unauthorized. You can only view your own children\'s reports.', 403);
        }
        if ($user->role === 'teacher' && $student->teacher_id !== $user->id) {
            return $this->error('Unauthorized. You can only view your own students\' reports.', 403);
        }

        $report = $this->getDetailedReport($student);
        return $this->success($report, 'Student report retrieved successfully.');
    }

    /**
     * Internal method to aggregate detailed student report data.
     */
    private function getDetailedReport($student)
    {
        // Attendance Stats
        $attendanceData = $student->attendance();
        $totalAttendance = $attendanceData->count();
        $presentCount = (clone $attendanceData)->where('status', 'present')->count();
        $absentCount = (clone $attendanceData)->where('status', 'absent')->count();
        $lateCount = (clone $attendanceData)->where('status', 'late')->count();

        $attendance = [
            'total_sessions'   => $totalAttendance,
            'present'          => $presentCount,
            'absent'           => $absentCount,
            'late'             => $lateCount,
            'attendance_rate'  => $totalAttendance > 0 ? round(($presentCount / $totalAttendance) * 100, 2) : 0,
        ];

        // Assignment Summary
        $assignments = [
            'total'     => $student->assignments()->count(),
            'assigned'  => $student->assignments()->where('status', 'assigned')->count(),
            'submitted' => $student->assignments()->where('status', 'submitted')->count(),
            'graded'    => $student->assignments()->where('status', 'graded')->count(),
        ];

        // Progress Details
        $progress = $student->progress()
            ->with(['subject', 'level'])
            ->get()
            ->map(function ($p) {
                return [
                    'subject'              => $p->subject->name ?? 'N/A',
                    'level'                => $p->level->level_name ?? 'N/A',
                    'worksheets_completed' => $p->worksheets_completed,
                    'average_score'        => $p->average_score,
                    'is_level_complete'    => $p->is_level_complete,
                    'started_at'           => $p->level_started_at ? $p->level_started_at->format('Y-m-d') : null,
                    'completed_at'         => $p->level_completed_at ? $p->level_completed_at->format('Y-m-d') : null,
                ];
            });

        // Recent Performance (Graded Submissions)
        $performance = Submission::where('student_id', $student->id)
            ->where('status', 'graded')
            ->with(['assignment.worksheet'])
            ->latest('graded_at')
            ->limit(10)
            ->get()
            ->map(function ($s) {
                return [
                    'worksheet'   => $s->assignment->worksheet->title ?? 'N/A',
                    'score'       => $s->score,
                    'errors'      => $s->error_count,
                    'graded_at'   => $s->graded_at ? $s->graded_at->format('Y-m-d') : null,
                    'feedback'    => $s->teacher_feedback,
                ];
            });

        return [
            'student_info' => [
                'id'            => $student->id,
                'name'          => $student->user->name,
                'enrollment_no' => $student->enrollment_no,
                'grade'         => $student->grade,
                'center'        => $student->center->name ?? 'N/A',
            ],
            'attendance'  => $attendance,
            'assignments' => $assignments,
            'progress'    => $progress,
            'performance' => $performance,
        ];
    }
}
