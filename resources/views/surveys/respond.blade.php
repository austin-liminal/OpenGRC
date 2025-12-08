@extends('surveys.layout')

@section('title', $survey->display_title)

@section('content')
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    {{-- Header --}}
    <div class="bg-primary-600 text-white p-6">
        <h1 class="text-2xl font-bold">{{ $survey->display_title }}</h1>
        @if($survey->template->description)
            <div class="mt-2 text-primary-100 prose prose-invert max-w-none">
                {!! $survey->description ?? $survey->template->description !!}
            </div>
        @endif
        @if($survey->due_date)
            <p class="mt-3 text-sm text-primary-200">
                <span class="font-medium">Due:</span> {{ $survey->due_date->format('F j, Y') }}
            </p>
        @endif
    </div>

    {{-- Error message --}}
    @if(session('error'))
        <div class="bg-red-50 border-l-4 border-red-500 p-4 m-4">
            <p class="text-red-700">{{ session('error') }}</p>
        </div>
    @endif

    {{-- Form --}}
    <form action="{{ route('survey.submit', $survey->access_token) }}" method="POST" id="survey-form">
        @csrf
        <div class="p-6 space-y-8">
            @foreach($survey->template->questions as $index => $question)
                @php
                    $existingAnswer = $answers->get($question->id);
                    $answerValue = old("answers.{$question->id}") ?? ($existingAnswer?->answer_value ?? null);
                    $existingComment = old("comments.{$question->id}") ?? ($existingAnswer?->comment ?? '');
                    $hasError = session('errors')[$question->id] ?? false;
                @endphp

                <div class="border-b border-gray-200 pb-6 last:border-0 {{ $hasError ? 'bg-red-50 -mx-6 px-6 py-4' : '' }}">
                    <label class="block text-sm font-medium text-gray-900 mb-2">
                        {{ $index + 1 }}. {{ $question->question_text }}
                        @if($question->is_required)
                            <span class="text-red-500">*</span>
                        @endif
                    </label>

                    @if($question->help_text)
                        <p class="text-sm text-gray-500 mb-3">{{ $question->help_text }}</p>
                    @endif

                    @if($hasError)
                        <p class="text-sm text-red-600 mb-2">{{ $hasError }}</p>
                    @endif

                    @switch($question->question_type->value)
                        @case('text')
                            <input
                                type="text"
                                name="answers[{{ $question->id }}]"
                                value="{{ $answerValue }}"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                {{ $question->is_required ? 'required' : '' }}
                            >
                            @break

                        @case('long_text')
                            <textarea
                                name="answers[{{ $question->id }}]"
                                rows="4"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                {{ $question->is_required ? 'required' : '' }}
                            >{{ $answerValue }}</textarea>
                            @break

                        @case('boolean')
                            <div class="flex gap-6">
                                <label class="inline-flex items-center">
                                    <input
                                        type="radio"
                                        name="answers[{{ $question->id }}]"
                                        value="1"
                                        class="rounded-full border-gray-300 text-primary-600 focus:ring-primary-500"
                                        {{ $answerValue === true || $answerValue === '1' || $answerValue === 1 ? 'checked' : '' }}
                                        {{ $question->is_required ? 'required' : '' }}
                                    >
                                    <span class="ml-2 text-gray-700">Yes</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input
                                        type="radio"
                                        name="answers[{{ $question->id }}]"
                                        value="0"
                                        class="rounded-full border-gray-300 text-primary-600 focus:ring-primary-500"
                                        {{ $answerValue === false || $answerValue === '0' || $answerValue === 0 ? 'checked' : '' }}
                                    >
                                    <span class="ml-2 text-gray-700">No</span>
                                </label>
                            </div>
                            @break

                        @case('single_choice')
                            <div class="space-y-2">
                                @foreach($question->options ?? [] as $optionIndex => $option)
                                    <label class="flex items-center">
                                        <input
                                            type="radio"
                                            name="answers[{{ $question->id }}]"
                                            value="{{ $option['label'] }}"
                                            class="rounded-full border-gray-300 text-primary-600 focus:ring-primary-500"
                                            {{ $answerValue === $option['label'] ? 'checked' : '' }}
                                            {{ $question->is_required ? 'required' : '' }}
                                        >
                                        <span class="ml-2 text-gray-700">{{ $option['label'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @break

                        @case('multiple_choice')
                            <div class="space-y-2">
                                @php
                                    $selectedOptions = is_array($answerValue) ? $answerValue : [];
                                @endphp
                                @foreach($question->options ?? [] as $optionIndex => $option)
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            name="answers[{{ $question->id }}][]"
                                            value="{{ $option['label'] }}"
                                            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                            {{ in_array($option['label'], $selectedOptions) ? 'checked' : '' }}
                                        >
                                        <span class="ml-2 text-gray-700">{{ $option['label'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @break

                        @case('file')
                            <div class="space-y-3" id="file-question-{{ $question->id }}">
                                {{-- File upload area --}}
                                <div class="file-upload-area border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-primary-400 transition-colors cursor-pointer"
                                     data-question-id="{{ $question->id }}">
                                    <input
                                        type="file"
                                        name="file_{{ $question->id }}"
                                        data-question-id="{{ $question->id }}"
                                        class="file-upload hidden"
                                        id="file-input-{{ $question->id }}"
                                    >
                                    <label for="file-input-{{ $question->id }}" class="cursor-pointer">
                                        <svg class="mx-auto h-10 w-10 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <p class="mt-2 text-sm text-gray-600">
                                            <span class="text-primary-600 font-medium">Click to upload</span> or drag and drop
                                        </p>
                                        <p class="mt-1 text-xs text-gray-500">Max file size: 10MB</p>
                                    </label>
                                </div>

                                {{-- Upload progress indicator --}}
                                <div class="upload-progress hidden" id="progress-{{ $question->id }}">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                                            <div class="progress-bar bg-primary-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                                        </div>
                                        <span class="progress-text text-sm text-gray-600">0%</span>
                                    </div>
                                </div>

                                {{-- Uploaded files list --}}
                                <div class="uploaded-files space-y-2" id="files-{{ $question->id }}">
                                    @if($existingAnswer && $existingAnswer->attachments->count() > 0)
                                        @foreach($existingAnswer->attachments as $attachment)
                                            <div class="file-item flex items-center justify-between bg-gray-50 rounded-lg p-3" data-attachment-id="{{ $attachment->id }}">
                                                <div class="flex items-center gap-3">
                                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-700">{{ $attachment->file_name }}</p>
                                                        <p class="text-xs text-gray-500">{{ $attachment->formatted_file_size }}</p>
                                                    </div>
                                                </div>
                                                <button type="button"
                                                        class="delete-file text-red-500 hover:text-red-700 p-1"
                                                        data-attachment-id="{{ $attachment->id }}"
                                                        title="Delete file">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                            @break
                    @endswitch

                    {{-- Additional Comments Field --}}
                    @if($question->allow_comments)
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-600 mb-1">
                                Additional Comments (optional)
                            </label>
                            <textarea
                                name="comments[{{ $question->id }}]"
                                rows="2"
                                placeholder="Add any additional information or context..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                            >{{ $existingComment }}</textarea>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Actions --}}
        <div class="bg-gray-50 px-6 py-4 flex justify-between items-center">
            <button
                type="button"
                id="save-progress"
                class="text-gray-600 hover:text-gray-800 text-sm font-medium"
            >
                Save Progress
            </button>
            <button
                type="submit"
                class="bg-primary-600 text-white px-6 py-2 rounded-md hover:bg-primary-700 font-medium"
            >
                Submit Survey
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('survey-form');
    const saveBtn = document.getElementById('save-progress');
    const csrfToken = '{{ csrf_token() }}';
    const surveyToken = '{{ $survey->access_token }}';

    // Save progress functionality
    saveBtn.addEventListener('click', function() {
        const formData = new FormData(form);
        const answers = {};
        const comments = {};

        for (let [key, value] of formData.entries()) {
            if (key.startsWith('answers[')) {
                const match = key.match(/answers\[(\d+)\](\[\])?/);
                if (match) {
                    if (match[2]) {
                        // Multiple choice (array)
                        if (!answers[match[1]]) answers[match[1]] = [];
                        answers[match[1]].push(value);
                    } else {
                        answers[match[1]] = value;
                    }
                }
            } else if (key.startsWith('comments[')) {
                const match = key.match(/comments\[(\d+)\]/);
                if (match) {
                    comments[match[1]] = value;
                }
            }
        }

        fetch('{{ route('survey.save', $survey->access_token) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ answers: answers, comments: comments })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Progress saved successfully!', 'success');
            } else {
                showNotification('Error saving progress: ' + (data.error || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            showNotification('Error saving progress', 'error');
            console.error(error);
        });
    });

    // File upload handling
    document.querySelectorAll('.file-upload').forEach(input => {
        input.addEventListener('change', function() {
            const questionId = this.dataset.questionId;
            const file = this.files[0];
            if (!file) return;

            // Check file size (10MB max)
            if (file.size > 10 * 1024 * 1024) {
                showNotification('File size exceeds 10MB limit', 'error');
                this.value = '';
                return;
            }

            uploadFile(questionId, file);
        });
    });

    // Drag and drop handling
    document.querySelectorAll('.file-upload-area').forEach(area => {
        const questionId = area.dataset.questionId;

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            area.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            area.addEventListener(eventName, () => {
                area.classList.add('border-primary-500', 'bg-primary-50');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            area.addEventListener(eventName, () => {
                area.classList.remove('border-primary-500', 'bg-primary-50');
            });
        });

        area.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];
                if (file.size > 10 * 1024 * 1024) {
                    showNotification('File size exceeds 10MB limit', 'error');
                    return;
                }
                uploadFile(questionId, file);
            }
        });
    });

    // File upload function
    function uploadFile(questionId, file) {
        const progressContainer = document.getElementById('progress-' + questionId);
        const progressBar = progressContainer.querySelector('.progress-bar');
        const progressText = progressContainer.querySelector('.progress-text');

        progressContainer.classList.remove('hidden');

        const formData = new FormData();
        formData.append('file', file);
        formData.append('question_id', questionId);

        const xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percentComplete = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percentComplete + '%';
                progressText.textContent = percentComplete + '%';
            }
        });

        xhr.addEventListener('load', () => {
            progressContainer.classList.add('hidden');
            progressBar.style.width = '0%';

            if (xhr.status === 200) {
                const data = JSON.parse(xhr.responseText);
                if (data.success) {
                    addFileToList(questionId, data.attachment_id, data.file_name, data.file_size);
                    showNotification('File uploaded successfully', 'success');
                } else {
                    showNotification('Error uploading file: ' + (data.error || 'Unknown error'), 'error');
                }
            } else {
                showNotification('Error uploading file', 'error');
            }
        });

        xhr.addEventListener('error', () => {
            progressContainer.classList.add('hidden');
            progressBar.style.width = '0%';
            showNotification('Error uploading file', 'error');
        });

        xhr.open('POST', '{{ route('survey.upload', $survey->access_token) }}');
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        xhr.send(formData);
    }

    // Add file to list after upload
    function addFileToList(questionId, attachmentId, fileName, fileSize) {
        const filesContainer = document.getElementById('files-' + questionId);

        const fileItem = document.createElement('div');
        fileItem.className = 'file-item flex items-center justify-between bg-gray-50 rounded-lg p-3';
        fileItem.dataset.attachmentId = attachmentId;

        fileItem.innerHTML = `
            <div class="flex items-center gap-3">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <div>
                    <p class="text-sm font-medium text-gray-700">${escapeHtml(fileName)}</p>
                    <p class="text-xs text-gray-500">${fileSize}</p>
                </div>
            </div>
            <button type="button"
                    class="delete-file text-red-500 hover:text-red-700 p-1"
                    data-attachment-id="${attachmentId}"
                    title="Delete file">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        `;

        filesContainer.appendChild(fileItem);

        // Add delete handler
        fileItem.querySelector('.delete-file').addEventListener('click', function() {
            deleteFile(attachmentId, fileItem);
        });
    }

    // Delete file handling
    document.querySelectorAll('.delete-file').forEach(btn => {
        btn.addEventListener('click', function() {
            const attachmentId = this.dataset.attachmentId;
            const fileItem = this.closest('.file-item');
            deleteFile(attachmentId, fileItem);
        });
    });

    function deleteFile(attachmentId, fileItem) {
        if (!confirm('Are you sure you want to delete this file?')) {
            return;
        }

        fetch(`/survey/${surveyToken}/file/${attachmentId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                _method: 'DELETE'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                fileItem.remove();
                showNotification('File deleted successfully', 'success');
            } else {
                showNotification('Error deleting file: ' + (data.error || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            showNotification('Error deleting file', 'error');
            console.error(error);
        });
    }

    // Notification helper
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transition-opacity duration-300 ${
            type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
        }`;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // HTML escape helper
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
@endsection
