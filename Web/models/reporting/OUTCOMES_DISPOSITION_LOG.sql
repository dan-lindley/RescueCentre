/* OUTCOMES_DISPOSITION_LOG.sql
   Pattern A parameters:
   :centre_id
   :from_date (YYYY-MM-DD)
   :to_date   (YYYY-MM-DD)

   Scope:
   - Admissions received within the reporting window (admission_date).
   - Shows disposition info if/when set.

   Notes:
   - Days in care calculated only when disposition_date is present.
*/

SELECT
  a.admission_id                                   AS `Admission ID`,
  a.patient_id                                     AS `Patient ID`,

  a.admission_date                                 AS `Admission Date (Start)`,
  a.disposition_date                               AS `Disposition Date (End)`,

  CASE
    WHEN a.disposition_date IS NULL OR a.admission_date IS NULL THEN NULL
    ELSE DATEDIFF(a.disposition_date, a.admission_date)
  END                                              AS `Days in Care`,

  p.name                                           AS `Patient Name`,
  p.animal_type                                    AS `Animal Type`,
  p.animal_order                                   AS `Animal Order`,
  p.animal_species                                 AS `Animal Species`,
  p.sex                                            AS `Sex`,

  a.status                                         AS `Admission Status`,

  a.disposition                                    AS `Disposition (Text)`,

  d.rescuecentre_shortcode                          AS `Rescue Shortcode`,
  d.universal_shortcode                             AS `Universal Shortcode`,

  a.collection_location                             AS `Collection Location`,
  a.presenting_complaint                            AS `Presenting Complaint`,

  CASE
    WHEN a.admission_date >= report_bounds.report_from
      AND a.admission_date < report_bounds.report_to
      AND a.disposition_date IS NOT NULL
      AND a.disposition_date >= report_bounds.report_from
      AND a.disposition_date < report_bounds.report_to
      THEN 'Complete journey'
    WHEN a.admission_date < report_bounds.report_from
      AND (a.disposition_date IS NULL OR a.disposition_date >= report_bounds.report_from)
      THEN 'Active before report start'
    WHEN a.admission_date >= report_bounds.report_from
      AND a.admission_date < report_bounds.report_to
      AND a.disposition_date IS NOT NULL
      AND a.disposition_date >= report_bounds.report_to
      THEN 'Disposition after report end'
    ELSE 'Excluded'
  END                                               AS `Journey Cohort`

FROM rescue_admissions a
JOIN rescue_patients p
  ON p.patient_id = a.patient_id

LEFT JOIN rescue_dispositions d
  ON d.disposition = a.disposition

CROSS JOIN (
  SELECT
    CAST(:from_date AS DATE) AS report_from,
    DATE_ADD(CAST(:to_date AS DATE), INTERVAL 1 DAY) AS report_to
) report_bounds

WHERE
  a.centre_id = :centre_id
  AND (
    (
      a.admission_date >= report_bounds.report_from
      AND a.admission_date < report_bounds.report_to
    )
    OR (
      a.admission_date < report_bounds.report_from
      AND (a.disposition_date IS NULL OR a.disposition_date >= report_bounds.report_from)
    )
  )

ORDER BY
  a.admission_date DESC,
  a.admission_id DESC;
