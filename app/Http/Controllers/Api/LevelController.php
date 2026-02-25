<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Level;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Class LevelController
 * Handles curriculum level management.
 */
class LevelController extends Controller
{
    use ApiResponse;

    /**
     * List levels (filter: ?subject_id=1).
     * Accessible by: all roles.
     */
    public function index(Request $request)
    {
        $query = Level::with('subject');

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        $levels = $query->orderBy('order_index')->get();
        return $this->success($levels, 'Curriculum levels retrieved successfully.');
    }

    /**
     * Store a newly created curriculum level.
     * Accessible by: super_admin.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject_id'  => 'required|exists:subjects,id',
            'name'        => 'required|string|max:50',
            'order_index' => 'required|integer',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $level = Level::create($request->all());
        $level->load('subject');
        return $this->success($level, 'Curriculum level created successfully.', 201);
    }

    /**
     * Update level info.
     * Accessible by: super_admin.
     */
    public function update(Request $request, $id)
    {
        $level = Level::find($id);
        if (!$level) return $this->error('Level not found.', 404);

        $validator = Validator::make($request->all(), [
            'subject_id'  => 'nullable|exists:subjects,id',
            'name'        => 'nullable|string|max:50',
            'order_index' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $level->update($request->all());
        $level->load('subject');
        return $this->success($level, 'Curriculum level updated successfully.');
    }

    /**
     * Remove level.
     * Accessible by: super_admin.
     */
    public function destroy($id)
    {
        $level = Level::find($id);
        if (!$level) return $this->error('Level not found.', 404);

        $level->delete();
        return $this->success([], 'Curriculum level deleted successfully.');
    }
}
