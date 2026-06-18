# Learning Module Structure

Lightweight internal e-learning module for the existing Rescue Centre dashboard.

## Scope

This is not a standalone LMS. It is a module under `new/modules/learning` and is loaded by:

- `module.php?module=learning&view=learner_dashboard`
- `module.php?module=learning&view=admin_dashboard`

The module uses existing accounts, rescue centres, permissions, module enablement, dashboard navigation, and `home.php` widgets.

## Files

- `module.json`  
  Module metadata and nav links.

- `INSTALL.SQL`  
  Full lightweight schema for courses, pages, assessments, suites, assignments, and learner progress.

- `learning.css`  
  Module-specific layout only: course builder columns, learner course navigation, learning content display, and answer option layout. Universal controls still come from `core.css`.

- `controllers/learning_lib.php`  
  Shared module helpers, schema bootstrap, permissions, course/progress/assessment functions.

- `controllers/learning_handler.php`  
  POST-only action endpoint for create/update/enrol/progress/assessment actions.

- `controllers/home_widgets.php`  
  Optional `home.php` provider showing assigned learning.

- `views/admin_dashboard.php`  
  Admin landing page for courses and management overview.

- `views/edit_course.php`  
  Course builder with top tabs for Details, Content Pages, Assessment, and Settings.

- `views/learner_dashboard.php`  
  Learner landing page showing assigned courses and progress.

- `views/take_course.php`  
  Learner course player.

- `views/course_results.php`  
  Completion/result view.

## Database Tables

- `rescue_learning_courses`
- `rescue_learning_pages`
- `rescue_learning_assessments`
- `rescue_learning_questions`
- `rescue_learning_answers`
- `rescue_learning_suites`
- `rescue_learning_suite_courses`
- `rescue_learning_assignments`
- `rescue_learning_user_courses`

## Relationships

- A course belongs to `owner_centre_id` and was created by an existing account.
- A course has many pages.
- A course has zero or one assessment.
- An assessment has many questions.
- A question has many answers.
- A suite belongs to a centre and has many courses through `rescue_learning_suite_courses`.
- An assignment targets either a role or a user.
- A learner's course state is tracked in `rescue_learning_user_courses`.

## Workflow

Admin:

1. Enable the module for the centre.
2. Open Learning Management.
3. Create a course.
4. Open the course and use the top tabs to add pages, assessment setup, and questions.
5. Assign courses or suites to users/roles.

Learner:

1. Sees assigned learning on `home.php` and in My Learning.
2. Opens a course.
3. Reads pages and resumes from last progress point.
4. Completes assessment.
5. Receives certificate eligibility after passing.

## Implementation Notes

- Keep visual styling on universal `core.css` classes where possible.
- Keep module CSS only for genuinely learning-specific layouts.
- Use `rc-tabs`, `rc-panel`, `rc-card`, `rc-stat`, `rc-table`, `xform`, `rc-alert`, `rc-badge`, and `rc-progress` before adding any new class.
- Use `learning_handler.php` for POST actions.
- Use `learning_lib.php` for shared data access and permission checks.
- Keep certificate generation as a later service/controller so the first module stays light.

## Future Expansion

- Add a dedicated attempts table if detailed answer history becomes necessary.
- Add certificate renderer for image/PDF output.
- Add assignment expansion job that creates `rescue_learning_user_courses` records for role targets.
- Add richer reporting once basic usage is stable.
