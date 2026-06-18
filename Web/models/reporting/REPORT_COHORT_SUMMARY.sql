/* REPORT_COHORT_SUMMARY.sql
   Pattern A parameters:
   :centre_id
   :from_date (YYYY-MM-DD)
   :to_date   (YYYY-MM-DD)

   Base report cohort layer:
   - Complete journeys: admission and final disposition both inside the report period.
   - Active before report start: admitted before the period and still active during/after the period start.
   - Disposition after report end: admitted inside the period but final disposition occurs after the period.
*/

SELECT
  COUNT(DISTINCT CASE
    WHEN a.admission_date >= report_bounds.report_from
      AND a.admission_date < report_bounds.report_to
      AND a.disposition_date IS NOT NULL
      AND a.disposition_date >= report_bounds.report_from
      AND a.disposition_date < report_bounds.report_to
      AND LOWER(TRIM(a.disposition)) IN (
        'released',
        'r',
        'died - within 48 hours',
        'died within 48 hours',
        'died - on admission',
        'died on admission',
        'dead on admission',
        'doa',
        'died - euthanised',
        'died euthanised',
        'euthanised',
        'euthanized',
        'died after 48 hours',
        'died - after 48 hours',
        'transferred to another rescue',
        'transferred to another rescue centre',
        'transferred out',
        'transferred'
      )
    THEN a.admission_id
  END) AS `Complete Patient Journeys`,

  COUNT(DISTINCT CASE
    WHEN a.admission_date < report_bounds.report_from
      AND (
        a.disposition_date IS NULL
        OR a.disposition_date >= report_bounds.report_from
      )
    THEN a.admission_id
  END) AS `Active Before Report Start`,

  COUNT(DISTINCT CASE
    WHEN a.admission_date >= report_bounds.report_from
      AND a.admission_date < report_bounds.report_to
      AND a.disposition_date IS NOT NULL
      AND a.disposition_date >= report_bounds.report_to
    THEN a.admission_id
  END) AS `Disposition After Report End`,

  COUNT(DISTINCT CASE
    WHEN a.admission_date >= report_bounds.report_from
      AND a.admission_date < report_bounds.report_to
    THEN a.admission_id
  END) AS `Admissions Started In Period`

FROM rescue_admissions a
CROSS JOIN (
  SELECT
    CAST(:from_date AS DATE) AS report_from,
    DATE_ADD(CAST(:to_date AS DATE), INTERVAL 1 DAY) AS report_to
) report_bounds

WHERE
  a.centre_id = :centre_id
  AND (
    a.admission_date < report_bounds.report_to
    OR a.disposition_date >= report_bounds.report_from
  );
