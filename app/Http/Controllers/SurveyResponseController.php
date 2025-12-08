<?php

namespace App\Http\Controllers;

use App\Enums\SurveyStatus;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SurveyResponseController extends Controller
{
    /**
     * Get the storage disk based on settings.
     */
    protected function getStorageDisk(): string
    {
        return setting('storage.driver', 'private');
    }

    /**
     * Display the survey form for public response.
     */
    public function show(string $token)
    {
        $survey = Survey::where('access_token', $token)
            ->with(['template.questions' => fn ($q) => $q->orderBy('sort_order')])
            ->first();

        if (! $survey) {
            return view('surveys.not-found');
        }

        if ($survey->isLinkExpired()) {
            return view('surveys.expired', compact('survey'));
        }

        if ($survey->status === SurveyStatus::COMPLETED) {
            return view('surveys.completed', compact('survey'));
        }

        // Load existing answers
        $answers = $survey->answers()->with('attachments')->get()->keyBy('survey_question_id');

        return view('surveys.respond', compact('survey', 'answers'));
    }

    /**
     * Save survey responses (partial or complete).
     */
    public function save(Request $request, string $token)
    {
        $survey = Survey::where('access_token', $token)
            ->with(['template.questions'])
            ->first();

        if (! $survey) {
            return response()->json(['error' => 'Survey not found'], 404);
        }

        if ($survey->isLinkExpired()) {
            return response()->json(['error' => 'Survey link has expired'], 403);
        }

        if ($survey->status === SurveyStatus::COMPLETED) {
            return response()->json(['error' => 'Survey already completed'], 403);
        }

        // Update status to in progress if it was sent
        if ($survey->status === SurveyStatus::SENT || $survey->status === SurveyStatus::DRAFT) {
            $survey->update(['status' => SurveyStatus::IN_PROGRESS]);
        }

        $answers = $request->input('answers', []);
        $comments = $request->input('comments', []);

        foreach ($survey->template->questions as $question) {
            $questionId = $question->id;
            $answerValue = $answers[$questionId] ?? null;
            $commentValue = $comments[$questionId] ?? null;

            // Skip if no answer and no comment provided
            if (($answerValue === null || $answerValue === '' || $answerValue === []) &&
                ($commentValue === null || $commentValue === '')) {
                continue;
            }

            $data = [];
            if ($answerValue !== null && $answerValue !== '' && $answerValue !== []) {
                $data['answer_value'] = $answerValue;
            }
            if ($commentValue !== null && $question->allow_comments) {
                $data['comment'] = $commentValue;
            }

            if (! empty($data)) {
                SurveyAnswer::updateOrCreate(
                    [
                        'survey_id' => $survey->id,
                        'survey_question_id' => $questionId,
                    ],
                    $data
                );
            }
        }

        return response()->json(['success' => true, 'message' => 'Progress saved']);
    }

    /**
     * Submit the survey (mark as complete).
     */
    public function submit(Request $request, string $token)
    {
        $survey = Survey::where('access_token', $token)
            ->with(['template.questions'])
            ->first();

        if (! $survey) {
            return redirect()->route('survey.not-found');
        }

        if ($survey->isLinkExpired()) {
            return redirect()->route('survey.show', $token)->with('error', 'Survey link has expired');
        }

        if ($survey->status === SurveyStatus::COMPLETED) {
            return redirect()->route('survey.show', $token)->with('error', 'Survey already completed');
        }

        $answers = $request->input('answers', []);
        $comments = $request->input('comments', []);
        $errors = [];

        // Validate required questions and save answers
        foreach ($survey->template->questions as $question) {
            $questionId = $question->id;
            $answerValue = $answers[$questionId] ?? null;
            $commentValue = $comments[$questionId] ?? null;

            // Build the data to save
            $data = [];
            if ($answerValue !== null && $answerValue !== '' && $answerValue !== []) {
                $data['answer_value'] = $answerValue;
            }
            if ($commentValue !== null && $question->allow_comments) {
                $data['comment'] = $commentValue;
            }

            // Save the answer if we have data
            if (! empty($data)) {
                SurveyAnswer::updateOrCreate(
                    [
                        'survey_id' => $survey->id,
                        'survey_question_id' => $questionId,
                    ],
                    $data
                );
            }

            // Check required - for file questions, check if attachments exist
            if ($question->is_required) {
                $isEmpty = $answerValue === null || $answerValue === '' || $answerValue === [];

                // For file questions, check if there are existing attachments
                if ($isEmpty && $question->question_type->value === 'file') {
                    $existingAnswer = SurveyAnswer::where('survey_id', $survey->id)
                        ->where('survey_question_id', $questionId)
                        ->first();
                    if ($existingAnswer && $existingAnswer->attachments()->count() > 0) {
                        $isEmpty = false;
                    }
                }

                if ($isEmpty) {
                    $errors[$questionId] = 'This question is required';
                }
            }
        }

        if (! empty($errors)) {
            return redirect()->route('survey.show', $token)
                ->withInput()
                ->with('errors', $errors)
                ->with('error', 'Please answer all required questions');
        }

        // Mark survey as complete
        $survey->update([
            'status' => SurveyStatus::COMPLETED,
            'completed_at' => now(),
        ]);

        return view('surveys.thank-you', compact('survey'));
    }

    /**
     * Handle file upload for survey answers.
     */
    public function uploadFile(Request $request, string $token)
    {
        $survey = Survey::where('access_token', $token)->first();

        if (! $survey || $survey->isLinkExpired() || $survey->status === SurveyStatus::COMPLETED) {
            return response()->json(['error' => 'Cannot upload file'], 403);
        }

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'question_id' => 'required|integer',
        ]);

        $file = $request->file('file');
        $questionId = $request->input('question_id');

        // Update status to in progress if it was sent
        if ($survey->status === SurveyStatus::SENT || $survey->status === SurveyStatus::DRAFT) {
            $survey->update(['status' => SurveyStatus::IN_PROGRESS]);
        }

        // Generate unique filename with prefix to prevent overwrites
        $prefix = Str::random(8);
        $originalName = $file->getClientOriginalName();
        $storedFileName = $prefix.'-'.$originalName;

        // Store the file using the configured storage driver
        $disk = $this->getStorageDisk();
        $path = $file->storeAs('survey-responses', $storedFileName, $disk);

        // Create or get the answer
        $answer = SurveyAnswer::updateOrCreate(
            [
                'survey_id' => $survey->id,
                'survey_question_id' => $questionId,
            ],
            [
                'answer_value' => ['file_uploaded' => true],
            ]
        );

        // Create attachment record
        $attachment = $answer->attachments()->create([
            'file_name' => $originalName,
            'file_path' => $path,
            'file_size' => $file->getSize(),
        ]);

        return response()->json([
            'success' => true,
            'attachment_id' => $attachment->id,
            'file_name' => $attachment->file_name,
            'file_size' => $this->formatFileSize($attachment->file_size),
        ]);
    }

    /**
     * Delete a file attachment.
     */
    public function deleteFile(Request $request, string $token, int $attachmentId)
    {
        try {
            $survey = Survey::where('access_token', $token)->first();

            if (! $survey) {
                return response()->json(['error' => 'Survey not found'], 404);
            }

            if ($survey->isLinkExpired()) {
                return response()->json(['error' => 'Survey link has expired'], 403);
            }

            if ($survey->status === SurveyStatus::COMPLETED) {
                return response()->json(['error' => 'Survey is already completed'], 403);
            }

            // Find the attachment and verify it belongs to this survey
            $attachment = SurveyAttachment::whereHas('answer', function ($query) use ($survey) {
                $query->where('survey_id', $survey->id);
            })->find($attachmentId);

            if (! $attachment) {
                return response()->json(['error' => 'Attachment not found'], 404);
            }

            // Delete the file from storage
            $disk = $this->getStorageDisk();
            if (Storage::disk($disk)->exists($attachment->file_path)) {
                Storage::disk($disk)->delete($attachment->file_path);
            }

            // Delete the attachment record
            $attachment->delete();

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete file: '.$e->getMessage()], 500);
        }
    }

    /**
     * Format file size for display.
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
