# Survey Platform Implementation Plan

## Overview

Create a survey platform for OpenGRC that allows users to:
1. Create reusable survey templates with arbitrary fields
2. Send surveys to 3rd parties (external respondents)
3. Use surveys as internal checklists
4. Collect responses with various question types

## Data Model Architecture

### Core Entities

```
┌─────────────────────┐      ┌─────────────────────┐
│   SurveyTemplate    │      │    SurveyQuestion   │
├─────────────────────┤      ├─────────────────────┤
│ id                  │──────│ id                  │
│ title               │ 1:M  │ survey_template_id  │
│ description         │      │ question_text       │
│ status              │      │ question_type       │
│ created_by_id       │      │ options (JSON)      │
│ is_public           │      │ is_required         │
│ timestamps          │      │ sort_order          │
└─────────────────────┘      │ help_text           │
                             │ timestamps          │
                             └─────────────────────┘

┌─────────────────────┐      ┌─────────────────────┐
│       Survey        │      │    SurveyAnswer     │
├─────────────────────┤      ├─────────────────────┤
│ id                  │──────│ id                  │
│ survey_template_id  │ 1:M  │ survey_id           │
│ title (override)    │      │ survey_question_id  │
│ status              │      │ answer_value (JSON) │
│ respondent_email    │      │ file_path           │
│ respondent_name     │      │ timestamps          │
│ assigned_to_id      │      └─────────────────────┘
│ due_date            │
│ access_token        │      ┌─────────────────────┐
│ completed_at        │      │  SurveyAttachment   │
│ created_by_id       │      ├─────────────────────┤
│ timestamps          │      │ id                  │
└─────────────────────┘      │ survey_answer_id    │
                             │ file_name           │
                             │ file_path           │
                             │ file_size           │
                             │ timestamps          │
                             └─────────────────────┘
```

### Relationships
- **SurveyTemplate** → has many → **SurveyQuestion**
- **SurveyTemplate** → has many → **Survey** (instances)
- **Survey** → has many → **SurveyAnswer**
- **SurveyQuestion** → has many → **SurveyAnswer**
- **SurveyAnswer** → has many → **SurveyAttachment** (for file uploads)

## Question Types (Enum: QuestionType)

| Type | Description | Storage |
|------|-------------|---------|
| `TEXT` | Single-line text input | String in answer_value |
| `LONG_TEXT` | Multi-line textarea | String in answer_value |
| `FILE` | File upload | File path stored, metadata in answer_value |
| `SINGLE_CHOICE` | Radio buttons (one selection) | Selected option key in answer_value |
| `MULTIPLE_CHOICE` | Checkboxes (multi-select) | Array of selected option keys |
| `BOOLEAN` | Yes/No or True/False toggle | Boolean in answer_value |

## Status Enums

### SurveyTemplateStatus
- `DRAFT` - Template is being created
- `ACTIVE` - Template can be used to create surveys
- `ARCHIVED` - Template is no longer available for new surveys

### SurveyStatus
- `DRAFT` - Survey created but not sent
- `SENT` - Survey sent to respondent
- `IN_PROGRESS` - Respondent has started answering
- `COMPLETED` - All required questions answered
- `EXPIRED` - Past due date without completion

## Implementation Steps

### Phase 1: Database & Models

#### 1.1 Create Migrations
1. `create_survey_templates_table.php`
   - id, title, description, status, is_public, created_by_id, timestamps, soft_deletes

2. `create_survey_questions_table.php`
   - id, survey_template_id (FK), question_text, question_type (enum), options (JSON), is_required, sort_order, help_text, timestamps

3. `create_surveys_table.php`
   - id, survey_template_id (FK), title, description, status, respondent_email, respondent_name, assigned_to_id (FK nullable), due_date, access_token (unique), completed_at, created_by_id, timestamps, soft_deletes

4. `create_survey_answers_table.php`
   - id, survey_id (FK), survey_question_id (FK), answer_value (JSON), timestamps

5. `create_survey_attachments_table.php`
   - id, survey_answer_id (FK), file_name, file_path, file_size, uploaded_by, timestamps

#### 1.2 Create Enums
1. `app/Enums/QuestionType.php` - TEXT, LONG_TEXT, FILE, SINGLE_CHOICE, MULTIPLE_CHOICE, BOOLEAN
2. `app/Enums/SurveyTemplateStatus.php` - DRAFT, ACTIVE, ARCHIVED
3. `app/Enums/SurveyStatus.php` - DRAFT, SENT, IN_PROGRESS, COMPLETED, EXPIRED

#### 1.3 Create Models
1. `app/Models/SurveyTemplate.php`
   - Relationships: questions(), surveys(), createdBy()
   - Traits: HasFactory, SoftDeletes, LogsActivity

2. `app/Models/SurveyQuestion.php`
   - Relationships: template(), answers()
   - Cast options to array
   - Traits: HasFactory, LogsActivity

3. `app/Models/Survey.php`
   - Relationships: template(), answers(), assignedTo(), createdBy()
   - Accessors for progress calculation
   - Traits: HasFactory, SoftDeletes, LogsActivity

4. `app/Models/SurveyAnswer.php`
   - Relationships: survey(), question(), attachments()
   - Cast answer_value to array/JSON
   - Traits: HasFactory, LogsActivity

5. `app/Models/SurveyAttachment.php`
   - Relationships: answer()
   - Traits: HasFactory

### Phase 2: Filament Resources

#### 2.1 SurveyTemplateResource
**Location:** `app/Filament/Resources/SurveyTemplateResource.php`

**Form Schema:**
- Title (TextInput, required)
- Description (RichEditor)
- Status (Select, SurveyTemplateStatus enum)
- Is Public (Toggle)
- Questions (Repeater with nested fields):
  - Question Text (TextInput, required)
  - Question Type (Select, QuestionType enum)
  - Options (KeyValue or Repeater, visible for SINGLE_CHOICE/MULTIPLE_CHOICE)
  - Is Required (Toggle)
  - Help Text (TextInput)
  - Sort Order (auto-managed)

**Table Columns:**
- Title (searchable)
- Description (limit, toggleable)
- Status (badge)
- Questions Count
- Surveys Count
- Created By
- Created At

**Actions:**
- Create Survey from Template
- Duplicate Template
- Archive Template

**Relation Managers:**
- QuestionsRelationManager
- SurveysRelationManager

#### 2.2 SurveyResource
**Location:** `app/Filament/Resources/SurveyResource.php`

**Form Schema:**
- Template (Select, required, disabled after creation)
- Title Override (TextInput)
- Description Override (Textarea)
- Respondent Email (Email)
- Respondent Name (TextInput)
- Assigned To (Select, User)
- Due Date (DatePicker)
- Status (Select, SurveyStatus)

**Table Columns:**
- Title
- Template
- Respondent (email/name)
- Status (badge with colors)
- Progress (percentage)
- Due Date
- Completed At
- Created By

**Actions:**
- Send Survey (generates access token, sends email)
- View Responses
- Resend Notification
- Mark as Complete

**Relation Managers:**
- AnswersRelationManager (read-only view of responses)

### Phase 3: Public Survey Response Page

#### 3.1 Create Response Controller
**Location:** `app/Http/Controllers/SurveyResponseController.php`

**Routes:**
- `GET /survey/{token}` - Display survey form
- `POST /survey/{token}` - Submit survey responses
- `POST /survey/{token}/save` - Save progress (partial)

#### 3.2 Create Response Views
**Location:** `resources/views/surveys/`
- `respond.blade.php` - Main survey form
- `components/question-*.blade.php` - Question type components
- `thank-you.blade.php` - Completion page
- `expired.blade.php` - Expired survey page

### Phase 4: Localization

#### 4.1 Create Language Files
- `lang/en/survey.php`
- `lang/es/survey.php` (if needed)

### Phase 5: Permissions & Authorization

#### 5.1 Create Permissions
- `Read Survey Templates`
- `Create Survey Templates`
- `Update Survey Templates`
- `Delete Survey Templates`
- `Read Surveys`
- `Create Surveys`
- `Update Surveys`
- `Delete Surveys`

#### 5.2 Update Seeder
Add survey permissions to `database/seeders/RolesAndPermissionsSeeder.php`

---

## Files to Create

### Migrations (5 files)
1. `database/migrations/YYYY_MM_DD_000001_create_survey_templates_table.php`
2. `database/migrations/YYYY_MM_DD_000002_create_survey_questions_table.php`
3. `database/migrations/YYYY_MM_DD_000003_create_surveys_table.php`
4. `database/migrations/YYYY_MM_DD_000004_create_survey_answers_table.php`
5. `database/migrations/YYYY_MM_DD_000005_create_survey_attachments_table.php`

### Enums (3 files)
1. `app/Enums/QuestionType.php`
2. `app/Enums/SurveyTemplateStatus.php`
3. `app/Enums/SurveyStatus.php`

### Models (5 files)
1. `app/Models/SurveyTemplate.php`
2. `app/Models/SurveyQuestion.php`
3. `app/Models/Survey.php`
4. `app/Models/SurveyAnswer.php`
5. `app/Models/SurveyAttachment.php`

### Filament Resources (2 resources + pages + relation managers)
1. `app/Filament/Resources/SurveyTemplateResource.php`
2. `app/Filament/Resources/SurveyTemplateResource/Pages/ListSurveyTemplates.php`
3. `app/Filament/Resources/SurveyTemplateResource/Pages/CreateSurveyTemplate.php`
4. `app/Filament/Resources/SurveyTemplateResource/Pages/EditSurveyTemplate.php`
5. `app/Filament/Resources/SurveyTemplateResource/Pages/ViewSurveyTemplate.php`
6. `app/Filament/Resources/SurveyTemplateResource/RelationManagers/QuestionsRelationManager.php`
7. `app/Filament/Resources/SurveyTemplateResource/RelationManagers/SurveysRelationManager.php`
8. `app/Filament/Resources/SurveyResource.php`
9. `app/Filament/Resources/SurveyResource/Pages/ListSurveys.php`
10. `app/Filament/Resources/SurveyResource/Pages/CreateSurvey.php`
11. `app/Filament/Resources/SurveyResource/Pages/EditSurvey.php`
12. `app/Filament/Resources/SurveyResource/Pages/ViewSurvey.php`
13. `app/Filament/Resources/SurveyResource/RelationManagers/AnswersRelationManager.php`

### Localization (1 file)
1. `lang/en/survey.php`

### Total: ~25 files

---

## Navigation Structure

Surveys will appear in the navigation under a new "Surveys" group:
- **Survey Templates** - Manage reusable templates
- **Surveys** - Manage survey instances and view responses

---

## Notes

1. **Access Tokens**: Surveys use unique access tokens for unauthenticated access by external respondents
2. **File Storage**: Uses the same storage driver pattern as existing FileAttachment model
3. **Activity Logging**: All models use Spatie Activity Log for audit trails
4. **Soft Deletes**: Templates and Surveys use soft deletes for data retention
5. **Internal Checklists**: When `assigned_to_id` is set and `respondent_email` is null, the survey functions as an internal checklist
