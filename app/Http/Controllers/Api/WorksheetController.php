<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Worksheet;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

/**
 * Class WorksheetController
 * Handles worksheet management and PDF uploads.
 */
class WorksheetController extends Controller
{
    use ApiResponse;

    /**
     * List worksheets (?subject_id=&level_id=).
     * Accessible by: teacher, center_admin, super_admin.
     */
    public function index(Request $request)
    {
        $query = Worksheet::query();

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->has('level_id')) {
            $query->where('level_id', $request->level_id);
        }

        $worksheets = $query->get();
        return $this->success($worksheets, 'Worksheets retrieved successfully.');
    }

    /**
     * Upload new worksheet with PDF file.
     * Accessible by: super_admin, center_admin, teacher.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject_id'         => 'required|exists:subjects,id',
            'level_id'           => 'required|exists:levels,id',
            'title'              => 'required|string|max:200',
            'worksheet_no'       => 'nullable|string|max:50',
            'description'        => 'nullable|string',
            'pdf_file'           => 'required|mimes:pdf|max:10240', // 10MB max
            'total_marks'        => 'nullable|integer',
            'time_limit_minutes' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $file_path = $request->file('pdf_file')->store('worksheets', 'public');

            $worksheet = Worksheet::create([
                'subject_id'         => $request->subject_id,
                'level_id'           => $request->level_id,
                'title'              => $request->title,
                'worksheet_no'       => $request->worksheet_no,
                'description'        => $request->description,
                'file_path'          => $file_path,
                'total_marks'        => $request->total_marks ?? 100,
                'time_limit_minutes' => $request->time_limit_minutes,
                'created_by'         => auth()->id(),
            ]);

            return $this->success($worksheet, 'Worksheet uploaded successfully.', 201);
        } catch (\Exception $e) {
            return $this->error('Failed to upload worksheet: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Worksheet details + file URL.
     * Accessible by: all roles.
     */
    public function show($id)
    {
        $worksheet = Worksheet::with(['subject', 'level'])->find($id);
        if (!$worksheet) return $this->error('Worksheet not found.', 404);

        // Accessor 'file_path' already returns the full URL
        return $this->success($worksheet, 'Worksheet details retrieved successfully.');
    }

    /**
     * Update worksheet metadata.
     * Accessible by: super_admin, center_admin, teacher.
     */
    public function update(Request $request, $id)
    {
        $worksheet = Worksheet::find($id);
        if (!$worksheet) return $this->error('Worksheet not found.', 404);

        $validator = Validator::make($request->all(), [
            'title'              => 'nullable|string|max:200',
            'worksheet_no'       => 'nullable|string|max:50',
            'description'        => 'nullable|string',
            'pdf_file'           => 'nullable|mimes:pdf|max:10240',
            'total_marks'        => 'nullable|integer',
            'time_limit_minutes' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $data = $request->only(['title', 'worksheet_no', 'description', 'total_marks', 'time_limit_minutes']);

            if ($request->hasFile('pdf_file')) {
                // Delete old file
                if ($worksheet->getRawOriginal('file_path')) {
                    Storage::disk('public')->delete($worksheet->getRawOriginal('file_path'));
                }
                $data['file_path'] = $request->file('pdf_file')->store('worksheets', 'public');
            }

            $worksheet->update($data);
            return $this->success($worksheet, 'Worksheet updated successfully.');
        } catch (\Exception $e) {
            return $this->error('Failed to update worksheet: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove worksheet.
     * Accessible by: super_admin, center_admin.
     */
    public function destroy($id)
    {
        $worksheet = Worksheet::find($id);
        if (!$worksheet) return $this->error('Worksheet not found.', 404);

        if ($worksheet->getRawOriginal('file_path')) {
            Storage::disk('public')->delete($worksheet->getRawOriginal('file_path'));
        }

        $worksheet->delete();
        return $this->success([], 'Worksheet deleted successfully.');
    }

    /**
     * Download worksheet PDF.
     * Accessible by: all roles.
     */
    public function download($id)
    {
        $worksheet = Worksheet::find($id);
        // Use getRawOriginal to get the relative path for Storage operations
        $rawPath = $worksheet ? $worksheet->getRawOriginal('file_path') : null;

        if (!$worksheet || !$rawPath) {
            return $this->error('File not found.', 404);
        }

        return Storage::disk('public')->download($rawPath, $worksheet->title . '.pdf');
    }
}
