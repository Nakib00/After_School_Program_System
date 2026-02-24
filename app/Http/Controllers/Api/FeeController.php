<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Models\Student;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Class FeeController
 * Handles student fee management, generation, and payments.
 */
class FeeController extends Controller
{
    use ApiResponse;

    /**
     * List fees with filters.
     * Accessible by: center_admin, parent, super_admin.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Fee::with(['student.user', 'center']);

        // Role-based access control
        if ($user->role === 'parent') {
            $query->whereHas('student', function ($q) use ($user) {
                $q->where('parent_id', $user->id);
            });
        } elseif ($user->role === 'center_admin') {
            $query->where('center_id', $user->center_id);
        }

        // Filters
        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('month')) {
            $query->where('month', $request->month);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $fees = $query->orderBy('month', 'desc')->get();
        return $this->success($fees, 'Fees retrieved successfully.');
    }

    /**
     * Auto-generate monthly fees for all active students.
     * Accessible by: center_admin, super_admin.
     */
    public function generateMonthlyFees(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month'     => 'required|string|regex:/^\d{4}-\d{2}$/', // e.g., 2024-02
            'center_id' => 'nullable|exists:centers,id',
            'due_date'  => 'nullable|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $month = $request->month;
        $centerId = $request->center_id ?: auth()->user()->center_id;
        $dueDate = $request->due_date ?: Carbon::parse($month)->endOfMonth()->toDateString();

        $students = Student::where('status', 'active');
        if ($centerId) {
            $students->where('center_id', $centerId);
        }

        $students = $students->get();
        $createdCount = 0;

        foreach ($students as $student) {
            // Check if fee already exists for this month
            $exists = Fee::where('student_id', $student->id)
                ->where('month', $month)
                ->exists();

            if (!$exists) {
                Fee::create([
                    'student_id' => $student->id,
                    'center_id'  => $student->center_id,
                    'month'      => $month,
                    'amount'     => $student->monthly_fee,
                    'due_date'   => $dueDate,
                    'status'     => 'unpaid',
                ]);
                $createdCount++;
            }
        }

        return $this->success(['created_count' => $createdCount], "Generated fees for $createdCount students.");
    }

    /**
     * Get fee record details.
     * Accessible by: center_admin, parents, super_admin.
     */
    public function show($id)
    {
        $user = auth()->user();
        $fee = Fee::with(['student.user', 'center'])->find($id);

        if (!$fee) {
            return $this->error('Fee record not found.', 404);
        }

        // Access check
        if ($user->role === 'parent' && $fee->student->parent_id !== $user->id) {
            return $this->error('Unauthorized access to this fee record.', 403);
        }

        if ($user->role === 'center_admin' && $fee->center_id !== $user->center_id) {
            return $this->error('Unauthorized access to this center\'s fee record.', 403);
        }

        return $this->success($fee, 'Fee details retrieved.');
    }

    /**
     * Mark fee as paid with payment info.
     * Accessible by: center_admin, super_admin.
     */
    public function markAsPaid(Request $request, $id)
    {
        $fee = Fee::find($id);
        if (!$fee) return $this->error('Fee record not found.', 404);

        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|string|max:50',
            'transaction_id' => 'nullable|string|max:100',
            'paid_date'      => 'nullable|date|before_or_equal:today',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $fee->update([
            'status'         => 'paid',
            'payment_method' => $request->payment_method,
            'transaction_id' => $request->transaction_id,
            'paid_date'      => $request->paid_date ?: now()->toDateString(),
        ]);

        return $this->success($fee, 'Fee marked as paid successfully.');
    }

    /**
     * Mark fee as overdue (can be called by a scheduled job or manually).
     * Accessible by: center_admin, super_admin.
     */
    public function markAsOverdue(Request $request)
    {
        $today = now()->toDateString();
        $count = Fee::where('status', 'unpaid')
            ->where('due_date', '<', $today)
            ->update(['status' => 'overdue']);

        return $this->success(['updated_count' => $count], "Marked $count fees as overdue.");
    }

    /**
     * Fee collection summary report.
     * Accessible by: center_admin, super_admin.
     */
    public function report(Request $request)
    {
        $user = auth()->user();
        $centerId = $request->center_id ?: $user->center_id;

        $query = Fee::query();
        if ($centerId) {
            $query->where('center_id', $centerId);
        }

        $report = $query->select('status', DB::raw('count(*) as count'), DB::raw('sum(amount) as total_amount'))
            ->groupBy('status')
            ->get();

        return $this->success($report, 'Fee collection summary report retrieved.');
    }

    /**
     * All unpaid/overdue fees for center.
     * Accessible by: center_admin, super_admin.
     */
    public function unpaidOverdue(Request $request)
    {
        $user = auth()->user();
        $centerId = $request->center_id ?: $user->center_id;

        $query = Fee::with('student.user')
            ->whereIn('status', ['unpaid', 'overdue']);

        if ($centerId) {
            $query->where('center_id', $centerId);
        }

        $fees = $query->get();
        return $this->success($fees, 'Unpaid and overdue fees retrieved.');
    }
}
