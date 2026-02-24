<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Class AttendanceController
 * Handles student attendance marking and reporting.
 */
class AttendanceController extends Controller
{
    use ApiResponse;

    /**
     * Get attendance history with filters.
     * Accessible by: center_admin, teacher, parents, super_admin.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Attendance::with(['student.user', 'marker']);

        // Role-based filtering
        if ($user->role === 'parent') {
            $query->whereHas('student', function ($q) use ($user) {
                $q->where('parent_id', $user->id);
            });
        } elseif ($user->role === 'teacher') {
            $query->whereHas('student', function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            });
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('month')) {
            $date = Carbon::parse($request->month);
            $query->whereMonth('date', $date->month)
                ->whereYear('date', $date->year);
        }

        $attendance = $query->orderBy('date', 'desc')->get();
        return $this->success($attendance, 'Attendance history retrieved successfully.');
    }

    /**
     * Mark bulk attendance for a date.
     * Accessible by: center_admin, teacher, super_admin.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'center_id'     => 'required|exists:centers,id',
            'date'          => 'required|date|before_or_equal:today',
            'attendance'    => 'required|array',
            'attendance.*.student_id' => 'required|exists:students,id',
            'attendance.*.status'     => 'required|in:present,absent,late',
            'attendance.*.notes'      => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            DB::beginTransaction();

            foreach ($request->attendance as $record) {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $record['student_id'],
                        'date'       => $request->date,
                    ],
                    [
                        'center_id' => $request->center_id,
                        'status'    => $record['status'],
                        'notes'     => $record['notes'] ?? null,
                        'marked_by' => auth()->user()->id,
                    ]
                );
            }

            DB::commit();
            return $this->success([], 'Attendance marked successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to mark attendance: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Correct an attendance record.
     * Accessible by: center_admin, teacher, super_admin.
     */
    public function update(Request $request, $id)
    {
        $attendance = Attendance::find($id);
        if (!$attendance) return $this->error('Attendance record not found.', 404);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:present,absent,late',
            'notes'  => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $attendance->update($request->only(['status', 'notes']));
        return $this->success($attendance, 'Attendance record updated successfully.');
    }

    /**
     * Monthly attendance summary report.
     * Accessible by: center_admin, super_admin.
     */
    public function monthlySummary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'center_id' => 'required|exists:centers,id',
            'month'     => 'required|string', // e.g., "2024-02"
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $date = Carbon::parse($request->month);
        $summary = Attendance::where('center_id', $request->center_id)
            ->whereMonth('date', $date->month)
            ->whereYear('date', $date->year)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        return $this->success($summary, 'Monthly attendance summary retrieved.');
    }

    /**
     * Today's attendance summary for center.
     * Accessible by: center_admin, teacher, super_admin.
     */
    public function todayAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'center_id' => 'required|exists:centers,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $today = Carbon::today()->toDateString();
        $records = Attendance::with('student.user')
            ->where('center_id', $request->center_id)
            ->where('date', $today)
            ->get();

        return $this->success($records, "Today's attendance for center.");
    }
}
