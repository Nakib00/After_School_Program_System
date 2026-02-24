<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Fee;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * Class CenterController
 * Handles CRUD operations and statistics for centers.
 */
class CenterController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the centers.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $centers = Center::with('admin:id,name,email')->get();
        return $this->success($centers, 'Centers retrieved successfully.');
    }

    /**
     * Store a newly created center in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:150',
            'admin_id' => 'required|exists:users,id',
            'address'  => 'nullable|string',
            'city'     => 'nullable|string|max:100',
            'phone'    => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $data = $request->all();
            $center = Center::create($data);
            return $this->success($center, 'Center created successfully.', 201);
        } catch (\Exception $e) {
            return $this->error('Failed to create center: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified center.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $center = Center::with('admin:id,name,email')->find($id);

        if (!$center) {
            return $this->error('Center not found.', 404);
        }

        return $this->success($center, 'Center retrieved successfully.');
    }

    /**
     * Update the specified center in storage.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $center = Center::find($id);

        if (!$center) {
            return $this->error('Center not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'name'      => 'nullable|string|max:150',
            'admin_id'  => 'nullable|exists:users,id',
            'address'   => 'nullable|string',
            'city'      => 'nullable|string|max:100',
            'phone'     => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $data = $request->all();
            $center->update($data);
            return $this->success($center, 'Center updated successfully.');
        } catch (\Exception $e) {
            return $this->error('Failed to update center: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified center from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $center = Center::find($id);

        if (!$center) {
            return $this->error('Center not found.', 404);
        }

        try {
            $center->delete();
            return $this->success([], 'Center deleted successfully.');
        } catch (\Exception $e) {
            return $this->error('Failed to delete center: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get statistics for a specific center or all centers.
     *
     * @param Request $request
     * @param int|null $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats(Request $request, $id = null)
    {
        $user = $request->user();

        // If center admin, they can only see their own center stats
        if ($user->role === 'center_admin') {
            // Find center where this user is admin
            $center = Center::where('admin_id', $user->id)->first();
            if (!$center) {
                return $this->error('You are not assigned to any center.', 403);
            }
            $id = $center->id;
        }

        try {
            $query = Center::query();
            if ($id) {
                $query->where('id', $id);
            }

            $centerIds = $query->pluck('id');

            $stats = [
                'total_centers'  => count($centerIds),
                'total_students' => Student::whereIn('center_id', $centerIds)->count(),
                'total_teachers' => Teacher::whereIn('center_id', $centerIds)->count(),
                'total_revenue'  => Fee::whereIn('center_id', $centerIds)->where('status', 'paid')->sum('amount'),
            ];

            return $this->success($stats, 'Statistics retrieved successfully.');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve statistics: ' . $e->getMessage(), 500);
        }
    }
}
