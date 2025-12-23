<?php

use App\Enums\QuestionType;
use App\Enums\RiskImpact;
use App\Enums\SurveyTemplateStatus;
use App\Enums\SurveyType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create the survey template
        $templateId = DB::table('survey_templates')->insertGetId([
            'title' => 'Vendor Security Survey (Internal)',
            'description' => '<p>This internal assessment is designed to evaluate a vendor\'s security posture using publicly available information. Complete this survey based on your research of the vendor\'s website, trust center, and other public sources.</p>',
            'status' => SurveyTemplateStatus::ACTIVE->value,
            'type' => SurveyType::VENDOR_ASSESSMENT->value,
            'created_by_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Define all questions with their categories and evidence sources
        $questions = [
            // Corporate & Jurisdictional Transparency (Question 1)
            [
                'category' => 'Corporate & Jurisdictional Transparency',
                'question_text' => 'Does the vendor publicly disclose its legal entity name, country of incorporation, and primary operating jurisdiction?',
                'help_text' => 'Evidence source: Vendor website, legal or about pages',
                'risk_weight' => 7,
            ],

            // Data Locale & Residency (Questions 2-3)
            [
                'category' => 'Data Locale & Residency',
                'question_text' => 'Does the vendor publicly disclose where customer data is stored or processed (countries or regions)?',
                'help_text' => 'Evidence source: Trust center, data residency documentation',
                'risk_weight' => 7,
            ],
            [
                'category' => 'Data Locale & Residency',
                'question_text' => 'Is the vendor\'s primary data processing located in jurisdictions with strong data protection and rule-of-law frameworks (e.g., US, EU, UK, Canada)?',
                'help_text' => 'Evidence source: Infrastructure or hosting disclosures',
                'risk_weight' => 7,
            ],

            // Security Program Visibility (Questions 4-5)
            [
                'category' => 'Security Program Visibility',
                'question_text' => 'Does the vendor maintain a publicly accessible security or trust center describing cybersecurity controls?',
                'help_text' => 'Evidence source: Vendor trust or security webpage',
                'risk_weight' => 7,
            ],
            [
                'category' => 'Security Program Visibility',
                'question_text' => 'Does the vendor explicitly state the use of encryption for customer data both in transit and at rest?',
                'help_text' => 'Evidence source: Security documentation or FAQs',
                'risk_weight' => 7,
            ],

            // Identity & Access Management (Question 6)
            [
                'category' => 'Identity & Access Management',
                'question_text' => 'Does the vendor publicly state that multi-factor authentication is enforced for privileged or administrative access?',
                'help_text' => 'Evidence source: Security documentation or compliance statements',
                'risk_weight' => 7,
            ],

            // Monitoring & Detection (Question 7)
            [
                'category' => 'Monitoring & Detection',
                'question_text' => 'Does the vendor describe the use of logging, monitoring, or security event detection capabilities?',
                'help_text' => 'Evidence source: Security or operations documentation',
                'risk_weight' => 7,
            ],

            // Vulnerability & Patch Management (Question 8)
            [
                'category' => 'Vulnerability & Patch Management',
                'question_text' => 'Does the vendor state that vulnerabilities are regularly scanned for and remediated based on risk?',
                'help_text' => 'Evidence source: Security documentation, trust center',
                'risk_weight' => 7,
            ],

            // Incident Response Transparency (Question 9)
            [
                'category' => 'Incident Response Transparency',
                'question_text' => 'Does the vendor publicly describe an incident response and customer breach notification process?',
                'help_text' => 'Evidence source: Security documentation, terms, or FAQs',
                'risk_weight' => 7,
            ],

            // Certifications & Attestations (Questions 10-11)
            [
                'category' => 'Certifications & Attestations',
                'question_text' => 'Does the vendor publicly claim a current SOC 2, ISO 27001, or equivalent independent security certification?',
                'help_text' => 'Evidence source: Trust center, compliance statements',
                'risk_weight' => 7,
            ],
            [
                'category' => 'Certifications & Attestations',
                'question_text' => 'Does the vendor specify the scope of its security certification or audit (e.g., systems, services, regions)?',
                'help_text' => 'Evidence source: Audit summaries or trust documentation',
                'risk_weight' => 6,
            ],

            // Third-Party & Supply Chain Risk (Questions 12-13)
            [
                'category' => 'Third-Party & Supply Chain Risk',
                'question_text' => 'Does the vendor publicly disclose the use of subprocessors or critical third-party service providers?',
                'help_text' => 'Evidence source: Subprocessor lists or privacy disclosures',
                'risk_weight' => 6,
            ],
            [
                'category' => 'Third-Party & Supply Chain Risk',
                'question_text' => 'Does the vendor state that subprocessors are subject to security or compliance requirements?',
                'help_text' => 'Evidence source: Subprocessor documentation or trust center',
                'risk_weight' => 6,
            ],

            // Security History & Reputation (Question 14)
            [
                'category' => 'Security History & Reputation',
                'question_text' => 'Is there no evidence of unresolved, undisclosed, or poorly handled material security incidents in public reporting?',
                'help_text' => 'Evidence source: Reputable news outlets, breach databases',
                'risk_weight' => 7,
            ],

            // Operational Security Maturity (Question 15)
            [
                'category' => 'Operational Security Maturity',
                'question_text' => 'Is there public evidence of ongoing security investment or maturity (e.g., regular security updates, disclosures, or security roles)?',
                'help_text' => 'Evidence source: Blogs, job postings, public communications',
                'risk_weight' => 6,
            ],
        ];

        // Insert all questions
        foreach ($questions as $index => $question) {
            DB::table('survey_questions')->insert([
                'survey_template_id' => $templateId,
                'question_text' => $question['question_text'],
                'question_type' => QuestionType::BOOLEAN->value,
                'help_text' => $question['category'].' | '.$question['help_text'],
                'is_required' => true,
                'allow_comments' => true,
                'sort_order' => $index + 1,
                'risk_weight' => $question['risk_weight'],
                'risk_impact' => RiskImpact::POSITIVE->value, // Yes = reduces risk for all these questions
                'options' => null,
                'option_scores' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Find the template
        $template = DB::table('survey_templates')
            ->where('title', 'Vendor Security Survey (Internal)')
            ->first();

        if ($template) {
            // Delete the questions
            DB::table('survey_questions')
                ->where('survey_template_id', $template->id)
                ->delete();

            // Delete the template
            DB::table('survey_templates')
                ->where('id', $template->id)
                ->delete();
        }
    }
};
