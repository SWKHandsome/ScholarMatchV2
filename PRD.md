# ScholarMatch — Product Requirements Document

- **Version:** 1.0
- **Status:** Ready for implementation
- **Date:** 12 October 2026
- **Audience:** Student developer, lecturer, and project reviewers
- **Delivery:** Local demonstration followed by Azure deployment

## 1. Product overview

ScholarMatch helps Malaysian students discover scholarships whose recorded requirements they satisfy. Students maintain their education, academic, study, and household information. The system evaluates that information against administrator-maintained scholarship rules, explains each decision, and orders eligible opportunities by academic margin.

The product supports students planning their next programme and students already enrolled. Current education and the programme seeking funding are separate concepts. An SPM graduate seeking bachelor’s funding must not fail a degree-level funding requirement solely because they have not started university.

ScholarMatch provides decision support, not scholarship selection. Eligibility means the recorded compulsory requirements are satisfied. Academic margin measures proportional performance above an academic minimum; it does not represent overall compatibility or a probability of receiving an award.

## 2. Problem, objectives, and success measures

Scholarship information is distributed across provider websites and uses different qualifications, income conditions, fields, and enrolment rules. Students repeatedly inspect this information and can overlook opportunities or misunderstand restrictions. Administrators need a consistent way to record requirements without inventing conditions absent from the source.

The first release must make eligibility understandable, accept different academic pathways, prioritise eligible opportunities, and remain affordable for student coursework. Explanations must connect the student’s information to the provider’s recorded requirement.

Release success requires all agreed eligibility scenarios to pass automated checks, every published scholarship to have a source reference, and every evaluated criterion to return an explanation. Students must complete the registration, profile, recommendation, and bookmark journey locally and online. Ranking must reproduce the documented calculation without stale results. Hosting must remain within confirmed student benefits or available credit, with no intentional out-of-pocket expenditure.

## 3. Users and product scope

| User | Need | Main journey |
| --- | --- | --- |
| Prospective student | Find funding for an intended programme | Record qualifications, select intended study, inspect recommendations |
| Enrolled student | Find funding for current study | Record programme and results, check requirements, save opportunities |
| Administrator | Maintain dependable scholarship information | Review sources, configure rules, publish and update records |

The release includes registration, authentication, profiles, multiple academic records, scholarship administration, eligibility explanations, academic-margin ranking, filters, bookmarks, dashboards, and reference-data management.

Applications take place on official provider websites. ScholarMatch stores application links rather than application submissions. Award prediction, machine learning, automated scholarship scraping, document uploads, payment processing, provider accounts, messaging, and notification campaigns are outside the initial scope. Unsupported requirements remain visible through manual verification instead of being silently discarded.

## 4. Roles, navigation, and access

Students register using name, email, password, and password confirmation. Registration always assigns the student role. Administrators are provisioned through a controlled setup process. Both roles can log in and log out; inactive accounts cannot access authenticated functions.

Student navigation contains Dashboard, Recommendations, Saved Scholarships, My Profile, and Logout. Administrator navigation contains Dashboard, Scholarships, Income Categories, Study Fields, Users, and Logout. Add Scholarship is an action inside Scholarships, not a separate navigation destination.

Students manage only their own profile, academic records, and bookmarks. Administrator access requires server-side role authorization. Administrators may inspect profile completeness and deactivate accounts, but cannot freely rewrite student academic results or household income.

## 5. Student profile requirements

### Personal and current education information

The profile records citizenship, state of residence, and current education level. Name and email belong to the account. Current levels include secondary school, pre-university, diploma, undergraduate, and postgraduate. State matching uses residence; birthplace or family-origin conditions require manual verification unless separately supported.

### Multiple academic records

An Add Qualification action allows multiple records. Each contains qualification type, completion status, result type, result value, applicable grading scale, and optional completion year. Supported qualification labels include SPM, STPM, UEC, A Level, matriculation, foundation, diploma, bachelor’s degree, master’s degree, and Other.

Initial automatic comparisons support simple counts of As and CGPA values on explicitly compatible scales. Completion status distinguishes in-progress study from completed qualifications. Specific subjects, distinctions between grade categories, or unsupported result formats require manual verification. Students confirm whether their academic records are complete; absent qualifications cannot be treated as definite failures while relevant information remains incomplete.

### Programme seeking funding

Students describe one funding programme using study level, current or intended field, enrolment status, institution type, and study location. Enrolment statuses are Planning to Apply, Offer Received, and Already Enrolled. Institution types include public university, private university, college, and Other. Study location is Malaysia, overseas, or undecided.

Prospective students describe their intended programme; enrolled students describe their current programme seeking funding. Unknown institutions or fields remain unknown. Not yet enrolled is an enrolment condition, not an institution type.

### Household information and completeness

Students provide monthly household income in Malaysian ringgit. The system derives the applicable income category from maintained thresholds. Zero income is valid and differs from missing income. Profile reminders identify missing information without blocking scholarship browsing. Completeness indicates information supplied, not eligibility; a fully completed profile may contain valid undecided answers that prevent particular evaluations.

## 6. Scholarship administration

Scholarship records contain name, provider, description, benefits, application deadline, application URL, source URL, application status, source verification status, and last verified date. Statuses are Open, Closed, and Inactive. Inactive records are hidden from student recommendations. Incomplete requirement configurations remain inactive until reviewed.

The Scholarships page provides search, provider and status filters, source verification filters, deadline sorting, and View, Edit, Deactivate, and Delete actions. Deactivation is preferred when historical information should remain available. Add and Edit use the same dedicated form.

The form separates basic information, academic routes, citizenship, funding level, enrolment, income, fields, institutions, location, residence, additional conditions, and source verification. Each criterion explicitly selects No Restriction, Restricted, or Manual Verification where appropriate. Unreviewed blank values cannot imply unrestricted eligibility.

Restricted criteria accept applicable sets of values. An offer condition can accept Offer Received and Already Enrolled, while an enrolled-only condition accepts only Already Enrolled. Income rules support accepted categories or explicit numeric limits. Additional qualitative conditions store explanatory text. Source verification confirms scholarship information was checked; it is separate from evaluating an individual student.

The administrator dashboard displays total scholarships, open and closed records, source reviews due, registered students, approaching deadlines, and recent changes. The Users page shows name, email, role, account status, profile completeness, and joining date. Source-review counts must remain distinct from student manual-verification counts. Updating source verification records who performed the review and when, without implying that a student's supporting documents were checked.

## 7. Academic routes and eligibility rules

A scholarship may accept alternative academic routes, such as SPM with eight As, STPM with CGPA 3.50, or Diploma with CGPA 3.50. Each route records qualification, required completion status, result type, minimum, grading scale, and any unresolved conditions. All conditions within one route must pass; routes are alternatives joined by OR.

The academic criterion passes when any route is fully satisfied. A failed SPM route cannot cancel a successful Diploma route. If no route passes and relevant information is missing or unassessable, academic verification remains unresolved. Complete information confirming every accepted route fails produces an academic failure. No academic restriction produces N/A.

Every criterion returns one result:

| Result | Meaning | Presentation |
| --- | --- | --- |
| Met | Recorded information satisfies the condition | Check mark and explanation |
| Not Met | Information confirms a compulsory failure | Cross and explanation |
| Manual Verification Required | Information is missing, ambiguous, or judgement-dependent | Attention indicator and next step |
| Not Applicable | Reviewed scholarship information imposes no restriction | N/A and explanation |

Overall evaluation first checks for definite compulsory failures. Any such failure makes the scholarship Not Eligible, even when another condition is unresolved. Otherwise, any unresolved condition produces Manual Verification Required. Otherwise, all compulsory criteria pass and the result is Eligible. N/A criteria do not affect the decision.

Missing student information never becomes N/A. Students receive profile-update guidance for missing facts and provider-check guidance for qualitative requirements. Incompatible grading scales are not automatically converted. Each explanation identifies the requirement, relevant student information, and reason for the result.

## 8. Academic-margin ranking

Only scholarships evaluated as Eligible receive a usable academic margin. For each fully satisfied academic route with a positive numerical minimum and comparable result:

```text
Academic margin = ((student result / required minimum) - 1) × 100
```

CGPA 3.80 against a minimum of 3.50 produces approximately 8.6% above the minimum. Exactly meeting the minimum produces 0%, which remains eligible. Values are not capped. Calculate using full precision and round only for display.

When several routes are satisfied, use the highest proportional margin and display its qualification and requirement. A route containing unresolved compulsory conditions cannot contribute a score. No academic requirement, zero minimum, or an unassessable numerical comparison produces N/A rather than an invented value.

Default ordering is Eligible with calculated margin descending, Eligible with N/A by nearest deadline, Manual Verification Required by nearest deadline, and Not Eligible by nearest deadline. Equal margins use nearest deadline, then a stable record identifier. The formula favours greater proportional clearance of the minimum, including lower thresholds; interface wording must preserve this meaning.

Students may choose deadline, scholarship name, or recently added sorting. Sorting occurs consistently across the complete filtered result set before pagination. Alternative sorting preserves eligibility-group priority.

For example, two eligible scholarships requiring CGPA 3.50 and 3.00 produce margins of 8.6% and 26.7% for a student with 3.80. The latter appears first. An eligible unrestricted scholarship follows scored opportunities. A student failing citizenship receives Not Eligible regardless of an otherwise high academic result.

## 9. Student pages and interactions

Recommendation cards show scholarship name, provider, eligibility status, requirement summary, benefits, deadline, academic margin when available, Save, and View Details. Filters include eligibility, field, provider, funding level, institution type, application status, and saved status. Default results show open, active scholarships.

Details show all requirement explanations, accepted academic routes, the route used for ranking, official source, last verification date, and an Apply on Official Website link. Links must open the recorded provider destination.

The dashboard shows profile completion, open eligible scholarships, manual-verification cases, saved scholarships, upcoming deadlines, and three to five leading eligible opportunities. Counts use the same evaluation logic as recommendations.

Saved Scholarships shows current eligibility, deadline, open or closed status, and saved date. Students can view, remove, or follow application links. Saving does not change eligibility. Closed saved opportunities remain visible, and profile or scholarship changes update their evaluations.

## 10. Reference data and persistence

Administrators manage study fields and income categories. Fields use a controlled list to prevent inconsistent labels. Income categories store name, minimum, maximum, reference year, source reference, and active status. Active ranges have explicit boundaries without accidental overlaps or gaps. Fixed scholarship income limits are evaluated directly; category conditions use the applicable reference configuration.

Core tables are users, student_profiles, student_academic_records, scholarships, scholarship_requirements, scholarship_academic_routes, study_fields, scholarship_study_fields, income_categories, scholarship_income_categories, and saved_scholarships.

A user has one profile, a profile has many academic records, and a scholarship has one requirements record and many academic routes. Restricted fields and categories use relationships. Enforce unique profiles and bookmarks, valid foreign keys, and consistent deactivation behaviour. Empty relationships imply no restriction only when the explicit criterion mode confirms it.

Eligibility and margin must reflect current inputs. Changes to profiles, requirements, or income configurations invalidate affected cached results.

Bookmark actions are reversible and idempotent: repeated saves create one bookmark, and repeated removals leave it absent. Deactivated study fields remain attached to historical records but cannot be newly selected. Students see guidance when a previously selected value needs replacement.

## 11. Architecture, security, and quality

Use Laravel, PHP, Blade, Bootstrap, JavaScript where needed, MySQL, Eloquent, and Git. Centralise decisions in ScholarshipEligibilityService, returning overall status, criterion results, explanations, academic route results, and ranking information. Reuse scholarship cards, badges, requirement indicators, academic forms, navigation, and dashboard components.

Validate requests server-side and enforce password hashing, CSRF protection, role permissions, and record ownership. Escape rendered content and restrict external links to permitted HTTP or HTTPS URLs. Keep credentials outside version control. Public deployment disables debug output and uses HTTPS.

Do not collect identity-card numbers, exact addresses, bank statements, salary documents, or parental personal details. Limit exposure of income and academic information to authorized functions. Validation rejects negative income, invalid counts, results exceeding declared scales, contradictory rules, and incomplete restricted selections.

Use text alongside colour and icons, labelled controls, keyboard-accessible actions, and layouts usable on phones and desktops. Target recommendation responses within two seconds on a warmed local environment using the demonstration dataset; document measured results rather than assuming free-host performance.

## 12. Local development and Azure deployment

Develop locally before provisioning cloud resources. Install compatible PHP, Composer, and MySQL, configure local environment variables, run migrations and seeders, and serve the application on localhost. Match local PHP requirements with the selected Azure runtime.

The deployment target is Azure App Service on Linux using Free F1 where available, plus Azure Database for MySQL Flexible Server. Use the provider’s HTTPS address. Configure Laravel’s public document root and environment settings correctly. Keep database connections encrypted and restrict access appropriately.

Azure for Students provides limited credit and eligible free allowances, not guaranteed permanent free hosting. Before deployment, verify subscription type, remaining credit, expiration, region availability, and MySQL benefit eligibility. Target zero out-of-pocket cost, retain spending protection, and avoid pay-as-you-go upgrades. Free App Service quotas and database benefit periods must be recorded in deployment notes.

Deployment notes must identify whether the account is Azure for Students or the more limited Starter subscription.

Development remains independent of Azure availability. Preserve database exports and reproducible migrations. Avoid mandatory continuously running workers for the initial release. Deadline visibility must work during requests without depending on paid background services.

## 13. Acceptance and delivery

Test complete eligibility, definite failures, missing information, unrestricted fields, incompatible scales, alternative routes, multiple satisfied routes, exact minima, zero minima, ranking ties, and N/A ordering. Confirm prospective degree applicants are evaluated against funding level, while explicit offer or enrolment restrictions remain enforceable.

Test income boundaries, configuration updates, bookmark uniqueness, account deactivation, unauthorized access, changing eligibility, expired deadlines, and ordering across pagination. Date-only deadlines remain open through the stated date in the Asia/Kuala_Lumpur timezone; provider-specific closing times take precedence when recorded.

Prepare one administrator, ten test students, and thirty to fifty scholarships covering expected outcomes. Fictional data must be labelled. Published real records require reviewed official sources.

Implement environment setup, migrations, authentication, reference data, profiles, scholarship administration, eligibility, ranking, student pages, and deployment in that order. Local acceptance precedes online release. Delivery includes source code, setup instructions, passing tests, sample credentials for the demonstration, and deployment documentation.

Before online release, confirm login, logout, profile edits, administrator changes, bookmarks, and application links work through HTTPS. Document the chosen timezone, runtime versions, database configuration, benefit expiry, and backup procedure. Demo credentials must belong to sample accounts and contain no production secrets. The release is ready only when the local acceptance suite passes, database migrations are reproducible, critical access failures are resolved, and the deployed environment produces the same eligibility and ranking results as localhost for the shared fixture dataset.

## 14. Hosting references

- [Azure for Students benefits](https://azure.microsoft.com/en-us/free/students/)
- [Azure student offer terms](https://azure.microsoft.com/en-us/pricing/offers/ms-azr-0170p/)
- [PHP deployment on Azure App Service](https://learn.microsoft.com/en-us/azure/app-service/quickstart-php)
- [Azure App Service limits](https://learn.microsoft.com/en-us/azure/azure-resource-manager/management/azure-subscription-service-limits#azure-app-service-limits)

Hosting terms were reviewed on 12 October 2026 and must be checked against the actual subscription before resources are created.
