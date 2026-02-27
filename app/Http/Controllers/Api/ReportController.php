<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Center;
use App\Models\Fee;
use App\Models\Student;
use App\Models\StudentProgress;
use App\Models\Submission;
use App\Models\Teacher;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Class ReportController
 * Handles analytics, dashboard KPIs, and management reports.
 */
class ReportController extends Controller
{
    use ApiResponse;

    /**
     * Role-scoped dashboard KPIs.
     * Accessible by: all-roles.
     */
    public function dashboardKpis()
    {
        $user = auth()->user();
        $data = ['role' => $user->role];

        switch ($user->role) {
            case 'super_admin':
                $data['stats'] = [
                    'total_centers'  => Center::count(),
                    'total_active_students' => Student::where('status', 'active')->count(),
                    'total_teachers' => Teacher::count(),
                    'revenue_this_month' => Fee::where('month', now()->format('Y-m'))
                        ->where('status', 'paid')
                        ->sum('amount'),
                ];
                break;

            case 'center_admin':
                $centerId = $user->center_id;
                $data['stats'] = [
                    'total_students' => Student::where('center_id', $centerId)->where('status', 'active')->count(),
                    'total_teachers' => Teacher::where('center_id', $centerId)->count(),
                    'unpaid_fees'    => Fee::where('center_id', $centerId)->whereIn('status', ['unpaid', 'overdue'])->count(),
                    'today_attendance' => Attendance::where('center_id', $centerId)
                        ->where('date', now()->toDateString())
                        ->where('status', 'present')
                        ->count(),
                ];
                break;

            case 'teacher':
                $data['stats'] = [
                    'my_students' => Student::where('teacher_id', $user->id)->count(),
                    'pending_grades' => Submission::whereHas('assignment', function ($q) use ($user) {
                        $q->where('teacher_id', $user->id);
                    })->where('status', 'submitted')->count(),
                    'avg_student_score' => Submission::whereHas('assignment', function ($q) use ($user) {
                        $q->where('teacher_id', $user->id);
                    })->avg('score') ?? 0,
                ];
                break;

            case 'parent':
                $students = Student::where('parent_id', $user->id)->get();
                $data['stats'] = [
                    'children_count' => $students->count(),
                    'pending_fees'   => Fee::whereIn('student_id', $students->pluck('id'))
                        ->whereIn('status', ['unpaid', 'overdue'])->count(),
                    'avg_progress'   => StudentProgress::whereIn('student_id', $students->pluck('id'))
                        ->avg('average_score') ?? 0,
                ];
                break;

            case 'student':
                $student = $user->student;
                $data['stats'] = [
                    'assignments_pending' => Assignment::where('student_id', $student->id)->where('status', 'pending')->count(),
                    'current_level' => $student->current_level,
                    'last_score' => Submission::where('student_id', $student->id)->orderBy('graded_at', 'desc')->first()->score ?? 0,
                ];
                break;
        }

        return $this->success($data, 'Dashboard KPIs retrieved.');
    }

    /**
     * Center performance report.
     */
    public function centerPerformance(Request $request)
    {
        $centerId = $request->center_id ?: auth()->user()->center_id;
        if (!$centerId) return $this->error('Center ID required.', 400);

        $stats = [
            'student_retention' => Student::where('center_id', $centerId)->count(), // Placeholder logic
            'fee_collection_rate' => $this->calculateFeeRate($centerId),
            'avg_attendance' => Attendance::where('center_id', $centerId)->where('status', 'present')->count() / 1, // Logic simplified
        ];

        return $this->success($stats, 'Center performance report.');
    }

    private function calculateFeeRate($centerId)
    {
        $total = Fee::where('center_id', $centerId)->sum('amount');
        if ($total == 0) return 100;
        $paid = Fee::where('center_id', $centerId)->where('status', 'paid')->sum('amount');
        return ($paid / $total) * 100;
    }

    /**
     * Teacher performance report.
     */
    public function teacherPerformance(Request $request)
    {
        $centerId = $request->center_id ?: auth()->user()->center_id;

        $teachers = Teacher::with('user')
            ->when($centerId, fn($q) => $q->where('center_id', $centerId))
            ->get()
            ->map(function ($teacher) {
                return [
                    'name' => $teacher->user->name,
                    'graded_count' => Submission::where('graded_by', $teacher->user_id)->count(),
                    'avg_feedback_time' => 'N/A', // Potential for future tracking
                    'student_count' => Student::where('teacher_id', $teacher->user_id)->count(),
                ];
            });

        return $this->success($teachers, 'Teacher performance metrics.');
    }

    /**
     * Student detailed report.
     */
    public function studentDetailedReport($id)
    {
        $student = Student::with(['user', 'center', 'fees', 'attendance', 'progress.level', 'assignments.submission'])
            ->find($id);

        if (!$student) return $this->error('Student not found.', 404);

        return $this->success($student, 'Student detailed report.');
    }

    /**
     * Fee collection report (monthly breakdown).
     */
    public function feeCollectionReport(Request $request)
    {
        $centerId = $request->center_id ?: auth()->user()->center_id;

        $report = Fee::select(
            'month',
            DB::raw('SUM(amount) as total_expected'),
            DB::raw('SUM(CASE WHEN status = "paid" THEN amount ELSE 0 END) as total_collected'),
            DB::raw('COUNT(*) as total_records')
        )
            ->when($centerId, fn($q) => $q->where('center_id', $centerId))
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get();

        return $this->success($report, 'Monthly fee collection report.');
    }

    /**
     * Attendance report by month/center.
     */
    public function attendanceReport(Request $request)
    {
        $centerId = $request->center_id ?: auth()->user()->center_id;
        $month = $request->month ?: now()->format('Y-m');

        $report = Attendance::where('center_id', $centerId)
            ->where('date', 'LIKE', "$month%")
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        return $this->success($report, 'Monthly attendance report.');
    }

    /**
     * Level progression report.
     */
    public function levelProgressionReport(Request $request)
    {
        $center_id = $request->center_id ?: auth()->user()->center_id;

        $report = StudentProgress::with(['level', 'subject'])
            ->whereHas('student', function ($q) use ($center_id) {
                if ($center_id) $q->where('center_id', $center_id);
            })
            ->select('level_id', DB::raw('count(*) as student_count'), DB::raw('avg(average_score) as avg_score'))
            ->groupBy('level_id')
            ->get();

        return $this->success($report, 'Level progression metrics.');
    }

    /**
     * Full system report for Super Admin.
     */
    public function fullSystemReport()
    {
        if (auth()->user()->role !== 'super_admin') {
            return $this->error('Unauthorized. Super Admin only.', 403);
        }

        $report = [
            'financial_summary' => [
                'total_revenue' => Fee::where('status', 'paid')->sum('amount'),
                'pending_revenue' => Fee::whereIn('status', ['unpaid', 'overdue'])->sum('amount'),
                'collection_rate' => $this->calculateOverallFeeRate(),
                'monthly_collection' => $this->getMonthlyCollectionSummary(),
            ],
            'academic_summary' => [
                'total_submissions' => Submission::count(),
                'avg_system_score' => Submission::avg('score') ?: 0,
                'total_assignments' => Assignment::count(),
                'submission_rate' => $this->calculateSubmissionRate(),
            ],
            'operational_summary' => [
                'total_centers' => Center::count(),
                'total_students' => Student::count(),
                'total_teachers' => Teacher::count(),
                'avg_students_per_center' => Center::count() > 0 ? Student::count() / Center::count() : 0,
                'center_wise_distribution' => Center::withCount('students')->get()->map(function ($c) {
                    return ['name' => $c->name, 'students' => $c->students_count];
                }),
            ],
            'attendance_summary' => [
                'overall_attendance_rate' => $this->calculateOverallAttendanceRate(),
                'today_attendance' => Attendance::where('date', now()->toDateString())->count(),
            ]
        ];

        return $this->success($report, 'Full system report generated.');
    }

    private function calculateOverallFeeRate()
    {
        $total = Fee::sum('amount');
        if ($total == 0) return 100;
        $paid = Fee::where('status', 'paid')->sum('amount');
        return round(($paid / $total) * 100, 2);
    }

    private function getMonthlyCollectionSummary()
    {
        return Fee::select(
            'month',
            DB::raw('SUM(amount) as total_expected'),
            DB::raw('SUM(CASE WHEN status = "paid" THEN amount ELSE 0 END) as total_collected')
        )
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();
    }

    private function calculateSubmissionRate()
    {
        $totalAssignments = Assignment::count();
        if ($totalAssignments == 0) return 100;
        $totalSubmissions = Submission::count();
        return round(($totalSubmissions / $totalAssignments) * 100, 2);
    }

    private function calculateOverallAttendanceRate()
    {
        $total = Attendance::count();
        if ($total == 0) return 100;
        $present = Attendance::where('status', 'present')->count();
        return round(($present / $total) * 100, 2);
    }
}
