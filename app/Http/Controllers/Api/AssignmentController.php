<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Student;
use App\Models\Worksheet;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * Class AssignmentController
 * Handles student assignments by teachers.
 */
class AssignmentController extends Controller
{
    use ApiResponse;

    /**
     * List assignments (role-scoped).
     * Accessible by: center_admin, teacher, student, super_admin.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Assignment::with(['student.user', 'worksheet', 'teacher']);

        if ($user->role === 'teacher') {
            $query->where('teacher_id', $user->id);
        } elseif ($user->role === 'student') {
            $query->whereHas('student', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($user->role === 'center_admin') {
            // Filter by center if needed
            if ($request->has('center_id')) {
                $query->whereHas('student', function ($q) use ($request) {
                    $q->where('center_id', $request->center_id);
                });
            }
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $assignments = $query->paginate($request->get('limit', 15));
        return $this->success($assignments, 'Assignments retrieved successfully.');
    }

    /**
     * Assign worksheet to student (supports bulk).
     * Accessible by: teacher, super_admin.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'worksheet_id'  => 'required|exists:worksheets,id',
            'student_ids'   => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'due_date'      => 'nullable|date|after_or_equal:today',
            'notes'         => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            DB::beginTransaction();

            $assignments = [];
            foreach ($request->student_ids as $student_id) {
                $assignments[] = Assignment::create([
                    'student_id'    => $student_id,
                    'worksheet_id'  => $request->worksheet_id,
                    'teacher_id'    => auth()->user()->id,
                    'assigned_date' => now(),
                    'due_date'      => $request->due_date,
                    'status'        => 'assigned',
                    'notes'         => $request->notes,
                ]);
            }

            DB::commit();
            return $this->success($assignments, count($assignments) . ' assignments created successfully.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create assignments: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Assignment details.
     * Accessible by: All Roles.
     */
    public function show($id)
    {
        $assignment = Assignment::with(['student.user', 'worksheet', 'teacher', 'submission'])->find($id);
        if (!$assignment) return $this->error('Assignment not found.', 404);

        return $this->success($assignment, 'Assignment details retrieved successfully.');
    }

    /**
     * Update due date or notes.
     * Accessible by: teacher, super_admin.
     */
    public function update(Request $request, $id)
    {
        $assignment = Assignment::find($id);
        if (!$assignment) return $this->error('Assignment not found.', 404);

        $validator = Validator::make($request->all(), [
            'due_date' => 'nullable|date|after_or_equal:assigned_date',
            'notes'    => 'nullable|string',
            'status'   => 'nullable|in:assigned,submitted,graded,returned'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $assignment->update($request->only(['due_date', 'notes', 'status']));
        return $this->success($assignment, 'Assignment updated successfully.');
    }

    /**
     * Cancel unsubmitted assignment.
     * Accessible by: center_admin, teacher, super_admin.
     */
    public function destroy($id)
    {
        $assignment = Assignment::find($id);
        if (!$assignment) return $this->error('Assignment not found.', 404);

        if ($assignment->status !== 'assigned') {
            return $this->error('Cannot cancel an assignment that has already been submitted or graded.', 422);
        }

        $assignment->delete();
        return $this->success([], 'Assignment cancelled successfully.');
    }
}
