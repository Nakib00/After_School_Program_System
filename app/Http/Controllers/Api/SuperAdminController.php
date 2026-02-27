<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\User;
use App\Models\Subject;
use App\Models\Level;
use App\Models\Fee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SuperAdminController extends Controller
{
    /**
     * Get super admin dashboard statistics
     */
    public function dashboard()
    {
        $stats = [
            'total_centers' => Center::count(),
            'total_teachers' => Teacher::count(),
            'total_students' => Student::count(),
            'total_parents' => User::where('role', 'parent')->count(),
            'total_subjects' => Subject::count(),
            'total_levels' => Level::count(),
            'total_center_admins' => User::where('role', 'center_admin')->count(),
            'is_active_users' => User::where('is_active', true)->count(),
            'inactive_users' => User::where('is_active', false)->count(),
        ];

        // Revenue Chart Data (Last 6 Months)
        $revenueChart = Fee::select([
            DB::raw('SUM(amount) as total'),
            DB::raw("DATE_FORMAT(paid_date, '%b %Y') as month"),
            DB::raw("DATE_FORMAT(paid_date, '%Y-%m') as sort_key")
        ])
            ->where('status', 'paid')
            ->whereNotNull('paid_date')
            ->where('paid_date', '>=', Carbon::now()->subMonths(6))
            ->groupBy(DB::raw("DATE_FORMAT(paid_date, '%Y-%m')"), DB::raw("DATE_FORMAT(paid_date, '%b %Y')"))
            ->orderBy('sort_key', 'asc')
            ->get();

        // Students per Center
        $studentsPerCenter = Center::withCount('students')
            ->get()
            ->map(function ($center) {
                return [
                    'name' => $center->name,
                    'student_count' => $center->students_count,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => array_merge($stats, [
                'revenue_chart' => $revenueChart,
                'students_per_center' => $studentsPerCenter,
            ]),
        ]);
    }

    /**
     * Toggle user active status.
     * Accessible by: super_admin.
     */
    public function toggleUserStatus($id)
    {
        $user = User::findOrFail($id);

        // Prevent super admin from deactivating themselves
        if ($user->id === auth()->id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot deactivate your own account.'
            ], 400);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'User status updated successfully.',
            'data' => [
                'id' => $user->id,
                'is_active' => $user->is_active
            ]
        ]);
    }

    /**
     * Delete a center admin with safety check.
     * Accessible by: super_admin.
     */
    public function deleteCenterAdmin($id)
    {
        $user = User::where('role', 'center_admin')->findOrFail($id);

        // Check if assigned to a center
        if ($user->center()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'first remove form the center than you can delet the center admin'
            ], 400);
        }

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Center admin deleted successfully.'
        ]);
    }
}
