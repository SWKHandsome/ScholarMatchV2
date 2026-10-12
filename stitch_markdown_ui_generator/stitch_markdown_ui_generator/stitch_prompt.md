# ScholarMatch — Google Stitch Prompts

Use Prompt 1 to establish the screens and visual system. After reviewing the designs, use Prompt 2 to prepare the design-system export. These prompts are grounded in [PRD.md](PRD.md) and [ARCHITECTURE.md](ARCHITECTURE.md).

Google documents native DESIGN.md import and export in its [Stitch announcement](https://blog.google/innovation-and-ai/models-and-research/google-labs/stitch-design-md/). Save the final export in this repository as Design.md.

## Prompt 1 — Design the application

```text
Act as a senior product designer. Create a cohesive, polished, responsive web application design for ScholarMatch, a scholarship decision-support platform for Malaysian students.

PRODUCT AND AUDIENCE
Support both students planning a programme and students already enrolled. Students enter profile information and multiple academic records, discover scholarships, understand eligibility, and save opportunities. Administrators maintain scholarships, requirements, study fields, income categories, and accounts. Applications continue on official provider websites.

The implementation will use Laravel Blade, Bootstrap, HTML, CSS, and small JavaScript interactions. Design reusable components that translate cleanly into that stack. Keep database, hosting, and framework terminology out of student-facing screens.

VISUAL DIRECTION
Use a trustworthy, welcoming academic identity with thoughtful typography, generous spacing, clear hierarchy, and restrained decoration. Make the application feel finished and approachable for students.

Suggested palette:
- Canvas: #F7F8FA; cards: #FFFFFF.
- Navigation and headings: #14243B.
- Primary actions and selected controls: #0F766E.
- Body text: #334155; secondary text: #64748B.
- Eligible: dark green #166534 on #DCFCE7.
- Manual verification: dark amber #92400E on #FEF3C7.
- Not eligible: dark red #991B1B on #FEE2E2.
- N/A: slate #475569 on #F1F5F9.

Use Inter or an accessible sans-serif with system fallbacks. Use approximately 16px body text, 28–32px page headings, an 8px spacing system, subtle borders, restrained shadows, and 10–12px card corners. Create a simple ScholarMatch wordmark with a small academic or bookmark-inspired icon. Maintain accessible contrast and readable focus states.

NAVIGATION
Student: Dashboard, Recommendations, Saved Scholarships, My Profile, Logout.
Admin: Dashboard, Scholarships, Income Categories, Study Fields, Users, Logout.
Use a persistent desktop sidebar and compact header. On mobile, collapse navigation into an accessible menu and stack content. Add Scholarship is a button within Scholarships, not an admin navigation item.

IMPORTANT PRODUCT RULES
1. Overall statuses are Eligible, Not Eligible, and Manual Verification Required.
2. Individual requirements are Met, Not Met, Manual Verification Required, or N/A. Use text and icons alongside colour.
3. N/A means the scholarship imposes no restriction. Missing student information requires verification and clear update guidance.
4. Current education is separate from the study level seeking funding. An SPM graduate may seek bachelor's funding.
5. Students can add multiple qualifications. Scholarships may accept alternative academic routes. Meeting one complete accepted route satisfies the academic criterion.
6. Calculate academic margin only for overall Eligible scholarships with a usable numerical minimum:
   ((student result / required minimum) - 1) × 100.
7. Display the margin as “8.6% above the academic minimum,” with the qualification used. Exactly meeting the minimum is 0% above the minimum. N/A margins are valid.
8. Default sorting is highest academic margin first within eligible scholarships. Eligible scholarships with N/A follow, then Manual Verification Required, then Not Eligible. Deadline breaks equal margins and orders unscored groups.
9. Offer alternative sorting by Deadline, Scholarship Name, and Recently Added while preserving eligibility-group priority.
10. Keep source verification, application status, and student eligibility visually distinct. Academic margin must never be labelled Match Score, Overall Match, or chance of winning.

CORE SCREENS
Start with these six connected screens, sharing one design system:

1. Student Dashboard
Show a personal greeting, profile-completion reminder, Eligible Scholarships, Manual Verification Required, Saved Scholarships, and Upcoming Deadlines. Show three leading eligible scholarships and an upcoming-deadline list. Give recommendations and profile completion clear actions.

2. Recommendations
Make this the central product screen. Show filters for eligibility, field, provider, funding level, institution type, Open/Closed, and Saved. Select “Academic margin: highest first” by default. Show result count, active filters, clear filters, and pagination.
Cards contain name, provider, eligibility badge, requirement summary, benefits, deadline, academic margin, qualification used, Save, and View Details. Give the ranking a short understandable explanation. Include scored Eligible, Eligible with N/A, Manual Verification Required, and Not Eligible examples.

3. Scholarship Details
Show provider, benefits, deadline, application status, source information, eligibility, and requirement explanations. Each explanation compares the student's information with the requirement. Present alternative academic routes and highlight the satisfied route used for ranking. Provide Save and Apply on Official Website actions. Explain unresolved conditions with a relevant next step.

4. My Profile
Use clear sections: Personal Information, Current Education, Academic Records, Programme Seeking Funding, and Household Information. Academic records have Add Qualification, completion status, result type, result, grading scale, edit/remove actions, and a completeness confirmation. Funding information includes study level, field, enrolment status, institution type, and location. Support Planning to Apply, Offer Received, and Already Enrolled. Show conditional fields, inline errors, unsaved changes, and save confirmation. Income uses RM and a maintained category explanation.

5. Admin Scholarships
Show search, provider/status/source-verification filters, Add Scholarship, and a readable table with name, provider, deadline, application status, source verification, last updated, and actions. Include an empty state and deactivation confirmation.

6. Admin Add/Edit Scholarship
Use a dedicated form page with grouped sections for information, academic routes, citizenship, funding level, enrolment, income, fields, institutions, location, residence, other conditions, and source verification. Make No Restriction, Restricted, and Manual Verification explicit. Provide Add Academic Route with qualification, completion requirement, result type, minimum, and scale. Show validation, Save/Update, and Cancel. Show review requirements before activation.

ADDITIONAL SCREENS
Extend the same system to Login, Registration, Saved Scholarships, Admin Dashboard, Admin Scholarship Details, Income Categories, Study Fields, and Users. Bookmarks remain visible when scholarships close. Users supports account inspection and deactivation without unrestricted editing of student academic or income records. Income settings record threshold boundaries, reference year, source, and active status; do not invent official current thresholds.

CONTENT AND STATES
Use clearly fictional providers and scholarships with future demo deadlines. Use RM for benefits. Keep data consistent across screens.
For a sample student with Diploma CGPA 3.80/4.00, a Diploma minimum of 3.00 produces 26.7% above the minimum, and 3.50 produces 8.6%. Place 26.7% first even if the other deadline is sooner. Only use academic routes fully satisfied by the example student. Do not show margins on Manual Verification Required or Not Eligible cards.
Show loading, empty, filtered-no-results, incomplete-profile, validation-error, saved/unsaved, closed-scholarship, and success states. Respect keyboard navigation, visible focus, text labels, accessible contrast, and approximately 44px touch targets.

DELIVERABLES
Produce coordinated high-fidelity screens, a reusable component library, and explicit design tokens. Start with desktop core screens and mobile Dashboard/Recommendations; apply the same responsive rules to remaining pages. Link the main navigation, profile, details, and bookmark journeys where prototype interactions are supported. Preserve the product rules throughout. Prepare the resulting visual system for native DESIGN.md export.
```

## Prompt 2 — Prepare the design-system document

```text
Using the actual ScholarMatch screens and components generated in this project, prepare the native DESIGN.md export. Document the final visual decisions rather than introducing a new style.

Include:
- Brand identity, visual direction, and design rationale.
- Semantic colours with exact hex codes and usage rules.
- Typography families, fallbacks, sizes, weights, line heights, and hierarchy.
- Spacing, layout grids, content widths, breakpoints, and mobile behaviour.
- Borders, radii, shadows, icons, focus treatments, and motion guidance.
- Components: navigation, headers, buttons, cards, badges, requirement rows, margin labels, filters, tables, pagination, forms, academic records/routes, alerts, and confirmations.
- Component states: hover, focus, selected, disabled, loading, error, empty, saved, success, and closed.
- Layout guidance for every student and admin page in the requested screen inventory.
- Accessibility and consistent English interface wording.
- Implementation guidance for Laravel Blade, Bootstrap, CSS variables, and modest JavaScript.

Preserve the distinction between eligibility, academic margin, source verification, and application status. Highest academic margin is the default recommendation order. Missing student information is not N/A. Academic margin is not a winning probability or overall match percentage.

Use Stitch's supported DESIGN.md structure. Include concrete values for the tokens actually used and document reusable components so a developer can reproduce the screens. List any requested screens not yet generated instead of implying they are complete. Provide the file for export; if a file export is unavailable, provide its full Markdown content.
```
