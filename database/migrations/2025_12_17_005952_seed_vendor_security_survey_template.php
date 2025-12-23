<?php

use App\Enums\QuestionType;
use App\Enums\RiskImpact;
use App\Enums\SurveyTemplateStatus;
use App\Enums\SurveyType;
use App\Models\SurveyQuestion;
use App\Models\SurveyTemplate;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Only seeds if users exist (upgrade scenario).
     * Fresh installs are handled by VendorSurveyTemplatesSeeder.
     */
    public function up(): void
    {
        $user = User::first();

        if (! $user) {
            // Fresh install - seeder will handle this after UserSeeder runs
            return;
        }

        $template = SurveyTemplate::updateOrCreate(
            ['title' => 'Vendor Security Survey'],
            [
                'description' => '<p>This survey assesses the security posture of third-party vendors to help identify and manage risks associated with external partnerships. Please answer all questions honestly and completely.</p>',
                'status' => SurveyTemplateStatus::ACTIVE,
                'type' => SurveyType::VENDOR_ASSESSMENT,
                'created_by_id' => $user->id,
            ]
        );

        $questions = [
            // Governance & Security Program (Questions 1-3)
            [
                'category' => 'Governance & Security Program',
                'question_text' => 'Does your organization maintain a formally documented information security program approved by executive leadership?',
                'risk_weight' => 5,
            ],
            [
                'category' => 'Governance & Security Program',
                'question_text' => 'Is information security oversight assigned to a dedicated role or function (e.g., CISO or security team)?',
                'risk_weight' => 5,
            ],
            [
                'category' => 'Governance & Security Program',
                'question_text' => 'Are security policies reviewed and updated at least annually?',
                'risk_weight' => 5,
            ],

            // Data Protection & Privacy (Questions 4-6)
            [
                'category' => 'Data Protection & Privacy',
                'question_text' => 'Will your organization store, process, or transmit our non-public data only as contractually authorized?',
                'risk_weight' => 5,
            ],
            [
                'category' => 'Data Protection & Privacy',
                'question_text' => 'Is all sensitive or confidential data encrypted both in transit and at rest?',
                'risk_weight' => 5,
            ],
            [
                'category' => 'Data Protection & Privacy',
                'question_text' => 'Is access to our data restricted to personnel with a legitimate business need?',
                'risk_weight' => 5,
            ],

            // Identity & Access Management (Questions 7-9)
            [
                'category' => 'Identity & Access Management',
                'question_text' => 'Is multi-factor authentication enforced for all administrative and privileged access?',
                'risk_weight' => 5,
            ],
            [
                'category' => 'Identity & Access Management',
                'question_text' => 'Are user accounts promptly disabled or removed upon role change or termination?',
                'risk_weight' => 5,
            ],
            [
                'category' => 'Identity & Access Management',
                'question_text' => 'Are access rights reviewed on a regular and documented basis?',
                'risk_weight' => 5,
            ],

            // Infrastructure & Operations Security (Questions 10-12)
            [
                'category' => 'Infrastructure & Operations Security',
                'question_text' => 'Are systems hosting our data protected by up-to-date endpoint security or equivalent controls?',
                'risk_weight' => 5,
            ],
            [
                'category' => 'Infrastructure & Operations Security',
                'question_text' => 'Are critical security patches applied within defined timeframes based on risk?',
                'risk_weight' => 5,
            ],
            [
                'category' => 'Infrastructure & Operations Security',
                'question_text' => 'Are systems continuously logged and monitored for security events and suspicious activity?',
                'risk_weight' => 5,
            ],

            // Incident Response & Resilience (Questions 13-15)
            [
                'category' => 'Incident Response & Resilience',
                'question_text' => 'Do you maintain a documented incident response plan that includes security incidents?',
                'risk_weight' => 5,
            ],
            [
                'category' => 'Incident Response & Resilience',
                'question_text' => 'Are customers notified of security incidents affecting their data within a defined timeframe?',
                'risk_weight' => 5,
            ],
            [
                'category' => 'Incident Response & Resilience',
                'question_text' => 'Do you perform regular data backups and test restoration procedures?',
                'risk_weight' => 5,
            ],

            // Third-Party & Supply Chain Risk (Questions 16-17)
            [
                'category' => 'Third-Party & Supply Chain Risk',
                'question_text' => 'Are subcontractors or downstream vendors required to meet security standards comparable to your own?',
                'risk_weight' => 5,
            ],
            [
                'category' => 'Third-Party & Supply Chain Risk',
                'question_text' => 'Do you formally assess security risk before onboarding new third-party vendors?',
                'risk_weight' => 5,
            ],

            // Compliance & Assurance (Questions 18-19)
            [
                'category' => 'Compliance & Assurance',
                'question_text' => 'Have you completed an independent security or compliance assessment within the last 12 months?',
                'risk_weight' => 5,
            ],
            [
                'category' => 'Compliance & Assurance',
                'question_text' => 'Can you provide security assurance documentation upon request?',
                'risk_weight' => 5,
            ],

            // Legal, Risk & Accountability (Question 20)
            [
                'category' => 'Legal, Risk & Accountability',
                'question_text' => 'Do you maintain cyber or information security insurance appropriate to the services provided?',
                'risk_weight' => 5,
            ],
        ];

        foreach ($questions as $index => $question) {
            SurveyQuestion::updateOrCreate(
                [
                    'survey_template_id' => $template->id,
                    'question_text' => $question['question_text'],
                ],
                [
                    'question_type' => QuestionType::BOOLEAN,
                    'help_text' => $question['category'],
                    'is_required' => true,
                    'allow_comments' => true,
                    'sort_order' => $index + 1,
                    'risk_weight' => $question['risk_weight'],
                    'risk_impact' => RiskImpact::POSITIVE,
                    'options' => null,
                    'option_scores' => null,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $template = SurveyTemplate::where('title', 'Vendor Security Survey')->first();

        if ($template) {
            SurveyQuestion::where('survey_template_id', $template->id)->delete();
            $template->delete();
        }
    }
};
