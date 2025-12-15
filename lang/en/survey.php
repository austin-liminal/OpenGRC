<?php

return [
    // Survey Manager Page
    'manager' => [
        'navigation' => [
            'label' => 'Survey Manager',
        ],
        'title' => 'Survey Manager',
        'tabs' => [
            'surveys' => 'Surveys',
            'templates' => 'Templates',
        ],
        'info' => [
            'heading' => 'How Vendor Surveys Work',
            'step1_title' => 'Create a Template',
            'step1_desc' => 'Build a reusable survey template with your vendor assessment questions. Configure risk weights for automated scoring.',
            'step2_title' => 'Activate the Template',
            'step2_desc' => 'Set the template status to "Active" to make it available for sending to vendors.',
            'step3_title' => 'Send to Vendor',
            'step3_desc' => 'From the Vendors tab, select a vendor and send them a survey. They\'ll receive an email invitation with a secure link.',
            'step4_title' => 'Review Responses',
            'step4_desc' => 'Once completed, review responses and risk scores. The vendor\'s overall risk score is automatically calculated.',
        ],
    ],

    // Survey Template
    'template' => [
        'navigation' => [
            'label' => 'Survey Templates',
            'group' => 'Surveys',
        ],
        'model' => [
            'label' => 'Survey Template',
            'plural_label' => 'Survey Templates',
        ],
        'form' => [
            'title' => [
                'label' => 'Title',
            ],
            'description' => [
                'label' => 'Description',
            ],
            'status' => [
                'label' => 'Status',
            ],
            'is_public' => [
                'label' => 'Public Template',
                'helper' => 'Public templates can be used by other organizations.',
            ],
            'questions' => [
                'description' => 'Add questions to your survey template. You can reorder questions by dragging them.',
                'question_text' => 'Question',
                'question_type' => 'Question Type',
                'is_required' => 'Required',
                'allow_comments' => 'Allow Comments',
                'allow_comments_helper' => 'Let respondents add additional context to their answer.',
                'help_text' => 'Help Text (optional)',
                'options' => 'Options',
            ],
        ],
        'table' => [
            'columns' => [
                'title' => 'Title',
                'status' => 'Status',
                'questions_count' => 'Questions',
                'surveys_count' => 'Surveys',
                'is_public' => 'Public',
                'created_by' => 'Created By',
                'created_at' => 'Created At',
                'updated_at' => 'Updated At',
            ],
            'filters' => [
                'status' => 'Status',
                'is_public' => 'Public',
            ],
            'empty_state' => [
                'heading' => 'No survey templates',
                'description' => 'Get started by creating your first survey template.',
            ],
        ],
        'infolist' => [
            'section_title' => 'Template Details',
        ],
        'actions' => [
            'create_survey' => 'Create Survey',
            'duplicate' => 'Duplicate',
        ],
    ],

    // Survey
    'survey' => [
        'navigation' => [
            'label' => 'Surveys',
            'group' => 'Surveys',
        ],
        'model' => [
            'label' => 'Survey',
            'plural_label' => 'Surveys',
        ],
        'form' => [
            'template' => [
                'label' => 'Template',
            ],
            'title' => [
                'label' => 'Title Override',
                'helper' => 'Leave blank to use the template title.',
            ],
            'description' => [
                'label' => 'Description Override',
                'helper' => 'Leave blank to use the template description.',
            ],
            'status' => [
                'label' => 'Status',
            ],
            'respondent' => [
                'description' => 'For external surveys, provide the respondent\'s email. For internal checklists, assign to a user.',
            ],
            'respondent_email' => [
                'label' => 'Respondent Email',
                'helper' => 'For external respondents.',
            ],
            'respondent_name' => [
                'label' => 'Respondent Name',
            ],
            'assigned_to' => [
                'label' => 'Assigned To',
                'helper' => 'For internal checklists.',
            ],
            'due_date' => [
                'label' => 'Due Date',
            ],
            'expiration_date' => [
                'label' => 'Link Expiration',
                'helper' => 'After this date, the survey link will no longer work.',
            ],
            'link' => [
                'label' => 'Survey Link',
                'description' => 'Use this link to share the survey with respondents.',
            ],
        ],
        'table' => [
            'columns' => [
                'title' => 'Title',
                'template' => 'Template',
                'respondent' => 'Respondent',
                'status' => 'Status',
                'progress' => 'Progress',
                'due_date' => 'Due Date',
                'completed_at' => 'Completed At',
                'created_by' => 'Created By',
                'created_at' => 'Created At',
            ],
            'filters' => [
                'status' => 'Status',
                'template' => 'Template',
                'assigned_to' => 'Assigned To',
            ],
            'empty_state' => [
                'heading' => 'No surveys',
                'description' => 'Create a survey from an active template to get started.',
            ],
        ],
        'infolist' => [
            'section_title' => 'Survey Details',
        ],
        'actions' => [
            'copy_link' => 'Copy Link',
            'mark_complete' => 'Mark Complete',
            'send' => 'Send Survey',
            'resend' => 'Resend Notification',
            'send_invitation' => 'Send Invitation',
            'send_invitation_modal' => [
                'heading' => 'Send Survey Invitation',
                'description' => 'This will send an email invitation to :email with a link to complete the survey.',
                'submit' => 'Send Email',
            ],
            'resend_invitation' => 'Resend Invitation',
            'resend_invitation_modal' => [
                'heading' => 'Resend Survey Invitation',
                'description' => 'This will resend the survey invitation email to :email.',
                'submit' => 'Resend Email',
            ],
        ],
        'notifications' => [
            'invitation_sent' => [
                'title' => 'Invitation Sent',
                'body' => 'Survey invitation email has been sent to :email.',
            ],
            'invitation_failed' => [
                'title' => 'Failed to Send Invitation',
            ],
        ],
        'answers' => [
            'columns' => [
                'question' => 'Question',
                'type' => 'Type',
                'answer' => 'Answer',
                'comment' => 'Comment',
                'answered_at' => 'Answered At',
            ],
        ],
    ],
];
