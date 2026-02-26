<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\Assignment;
use App\Models\StudentProgress;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

/**
 * Class SubmissionController
 * Handles student submissions and grading.
 */
class SubmissionController extends Controller
{
    use ApiResponse;

    /**
     * Submit completed worksheet file.
     * Accessible by: student.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'assignment_id'  => 'required|exists:assignments,id|unique:submissions,assignment_id',
            'submitted_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB
            'time_taken_min' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $user = auth()->user();
        $assignment = Assignment::find($request->assignment_id);
        if (!$assignment) return $this->error('Assignment not found.', 404);

        // Determine student_id for submission
        if ($user->role === 'student') {
            $student = $user->student;
            if (!$student || $assignment->student_id !== $student->id) {
                return $this->error('Unauthorized. You can only submit your own assignments.', 403);
            }
            $student_id = $student->id;
        } else {
            // Admins/Teachers submitting on behalf of a student
            $student_id = $assignment->student_id;
        }

        try {
            $file_path = $request->file('submitted_file')->store('submissions', 'public');

            $submission = Submission::create([
                'assignment_id'  => $request->assignment_id,
                'student_id'     => $student_id,
                'submitted_file' => $file_path,
                'submitted_at'   => now(),
                'time_taken_min' => $request->time_taken_min,
                'status'         => 'pending'
            ]);

            // Update assignment status
            $assignment->update(['status' => 'submitted']);

            return $this->success($submission, 'Worksheet submitted successfully.', 201);
        } catch (\Exception $e) {
            return $this->error('Failed to submit worksheet: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Grade: score, error_count, feedback.
     * Accessible by: teacher, super_admin.
     */
    public function grade(Request $request, $id)
    {
        $submission = Submission::find($id);
        if (!$submission) return $this->error('Submission not found.', 404);

        $validator = Validator::make($request->all(), [
            'score'            => 'required|numeric|min:0|max:100',
            'error_count'      => 'nullable|integer|min:0',
            'teacher_feedback' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            DB::beginTransaction();

            $submission->update([
                'score'            => $request->score,
                'error_count'      => $request->error_count ?? 0,
                'teacher_feedback' => $request->teacher_feedback,
                'graded_by'        => auth()->user()->id,
                'graded_at'        => now(),
                'status'           => 'graded'
            ]);

            // Update assignment status
            $submission->assignment->update(['status' => 'graded']);

            // Step 5: Update student_progress table
            $this->updateStudentProgress($submission);

            DB::commit();
            return $this->success($submission, 'Submission graded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to grade submission: ' . $e->getMessage(), 500);
        }
    }

    /**
     * List pending submissions to grade.
     * Accessible by: center_admin, teacher, super_admin.
     */
    public function pendingSubmissions(Request $request)
    {
        $query = Submission::with(['student.user', 'assignment.worksheet'])
            ->where('status', 'pending');

        if ($request->has('center_id')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('center_id', $request->center_id);
            });
        }

        $submissions = $query->paginate($request->get('limit', 15));
        return $this->success($submissions, 'Pending submissions retrieved successfully.');
    }

    /**
     * Get submission for assignment.
     */
    public function show($id)
    {
        $submission = Submission::with(['assignment.worksheet', 'grader'])->find($id);
        if (!$submission) return $this->error('Submission not found.', 404);

        // Accessor 'submitted_file' already returns the full URL
        return $this->success($submission, 'Submission details retrieved successfully.');
    }

    /**
     * Get submission by assignment ID.
     */
    public function getByAssignmentId($assignmentId)
    {
        $assignment = Assignment::find($assignmentId);
        if (!$assignment) return $this->error('Assignment not found.', 404);

        $user = auth()->user();
        if ($user->role === 'student') {
            $student = $user->student;
            if (!$student || $assignment->student_id !== $student->id) {
                return $this->error('Unauthorized. You can only view your own submissions.', 403);
            }
        }

        $submission = Submission::with(['assignment.worksheet', 'grader'])
            ->where('assignment_id', $assignmentId)
            ->first();

        if (!$submission) return $this->error('Submission not found.', 404);

        // Accessor 'submitted_file' already returns the full URL
        return $this->success($submission, 'Submission details retrieved successfully.');
    }

    /**
     * Download submitted file.
     */
    public function download($id)
    {
        $submission = Submission::find($id);
        if (!$submission) return $this->error('Submission not found.', 404);

        $user = auth()->user();
        if ($user->role === 'student') {
            $student = $user->student;
            if (!$student || $submission->student_id !== $student->id) {
                return $this->error('Unauthorized. You can only download your own submissions.', 403);
            }
        }

        // Get the raw value from the database (bypassing the accessor if necessary, 
        // though we need the relative path for Storage::download)
        // Laravel's getRawOriginal can be used if needed, or simply use the attribute 
        // and strip the base URL if the accessor makes it a full URL.
        // Actually, it's better to just use the field directly from the model if possible, 
        // but model attributes go through accessors.

        $filePath = $submission->getRawOriginal('submitted_file');

        if (!Storage::disk('public')->exists($filePath)) {
            return $this->error('File not found on server.', 404);
        }

        return Storage::disk('public')->download($filePath);
    }

    /**
     * Internal method to update student progress after grading.
     */
    private function updateStudentProgress(Submission $submission)
    {
        $assignment = $submission->assignment;
        $worksheet = $assignment->worksheet;

        $progress = StudentProgress::firstOrCreate(
            [
                'student_id' => $submission->student_id,
                'subject_id' => $worksheet->subject_id,
                'level_id'   => $worksheet->level_id,
            ],
            [
                'worksheets_completed' => 0,
                'average_score'        => 0,
                'average_time'         => 0,
                'level_started_at'     => now(),
            ]
        );

        // Get all graded submissions for this student at this level
        $gradedSubmissions = Submission::where('student_id', $submission->student_id)
            ->where('status', 'graded')
            ->whereHas('assignment', function ($q) use ($worksheet) {
                $q->whereHas('worksheet', function ($wq) use ($worksheet) {
                    $wq->where('level_id', $worksheet->level_id);
                });
            })->get();

        $count = $gradedSubmissions->count();
        $totalScore = $gradedSubmissions->sum('score');
        $totalTime = $gradedSubmissions->sum('time_taken_min');

        $progress->update([
            'worksheets_completed' => $count,
            'average_score'        => $count > 0 ? $totalScore / $count : 0,
            'average_time'         => $count > 0 ? $totalTime / $count : 0,
        ]);
    }
}
