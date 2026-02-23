<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Class SubjectController
 * Handles curriculum subject management.
 */
class SubjectController extends Controller
{
    use ApiResponse;

    /**
     * List all active subjects.
     * Accessible by: All Roles.
     */
    public function index()
    {
        $subjects = Subject::where('is_active', true)->get();
        return $this->success($subjects, 'Active subjects retrieved successfully.');
    }

    /**
     * List all subjects (including inactive).
     * Accessible by: super_admin.
     */
    public function listAll()
    {
        $subjects = Subject::all();
        return $this->success($subjects, 'All subjects retrieved successfully.');
    }

    /**
     * Store a newly created subject.
     * Accessible by: super_admin.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:100|unique:subjects',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $subject = Subject::create($request->all());
        return $this->success($subject, 'Subject created successfully.', 201);
    }

    /**
     * Display the specified subject.
     * Accessible by: super_admin.
     */
    public function show($id)
    {
        $subject = Subject::with('levels')->find($id);
        if (!$subject) return $this->error('Subject not found.', 404);

        return $this->success($subject, 'Subject details retrieved successfully.');
    }

    /**
     * Update the specified subject.
     * Accessible by: super_admin.
     */
    public function update(Request $request, $id)
    {
        $subject = Subject::find($id);
        if (!$subject) return $this->error('Subject not found.', 404);

        $validator = Validator::make($request->all(), [
            'name'        => 'nullable|string|max:100|unique:subjects,name,' . $id,
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $subject->update($request->all());
        return $this->success($subject, 'Subject updated successfully.');
    }

    /**
     * Toggle subject active status (flip between active/inactive).
     * Accessible by: super_admin.
     */
    public function toggleStatus($id)
    {
        $subject = Subject::find($id);
        if (!$subject) return $this->error('Subject not found.', 404);

        $subject->update(['is_active' => !$subject->is_active]);
        $status = $subject->is_active ? 'activated' : 'deactivated';

        return $this->success($subject, "Subject {$status} successfully.");
    }
}
