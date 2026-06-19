/* MEDICATION_LOG.sql
   Pattern A parameters:
   :centre_id
   :from_date (YYYY-MM-DD)
   :to_date   (YYYY-MM-DD)

   Scope:
   - Complete admission journeys where admission and disposition both fall inside the reporting window.
   - Includes exception cohorts so the report can explain what was excluded.
   - Includes admissions with and without medication so disposition comparison has a baseline.
   - Medication rows are linked to the admission stay window where possible.
*/

SELECT
  m.med_adm_id              AS `Medication Record ID`,
  a.patient_id              AS `Patient ID`,
  a.admission_id            AS `Admission ID`,

  p.name                    AS `Patient Name`,
  p.animal_type             AS `Animal Type`,
  p.animal_order            AS `Animal Order`,
  p.animal_species          AS `Animal Species`,

  COALESCE(m.medication_given, 'No medication') AS `Medication`,
  CASE
    WHEN m.med_adm_id IS NULL THEN 'No medication'
    ELSE COALESCE(
      rm_stock.class,
      (
        SELECT rm_text.class
        FROM rescue_medications rm_text
        WHERE LOWER(TRIM(rm_text.medication_name)) COLLATE utf8mb4_unicode_ci = LOWER(TRIM(m.medication_given)) COLLATE utf8mb4_unicode_ci
           OR LOWER(TRIM(rm_text.common_name)) COLLATE utf8mb4_unicode_ci = LOWER(TRIM(m.medication_given)) COLLATE utf8mb4_unicode_ci
        LIMIT 1
      ),
      'Unclassified'
    )
  END                       AS `Medication Class`,
  m.dose                    AS `Dose`,
  m.dose_type               AS `Dose Unit`,
  m.vol_given               AS `Volume Given`,

  m.date                    AS `Date Given`,
  DATE_FORMAT(m.date, '%Y-%m') AS `Medication Month`,
  m.given_by                AS `Given By`,

  m.batch_given             AS `Batch`,
  m.exp_given               AS `Expiry Date`,
  m.stock_item_used         AS `Stock Item ID`,
  m.pack_used               AS `Pack Used`,

  a.admission_date          AS `Admission Date`,
  a.presenting_complaint    AS `Presenting Complaint`,
  a.disposition             AS `Disposition`,
  a.disposition_date        AS `Disposition Date`,

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
  END                       AS `Journey Cohort`

FROM rescue_admissions a
JOIN rescue_patients p
  ON p.patient_id = a.patient_id

LEFT JOIN rescue_medications_given m
  ON m.patient_id = a.patient_id
  AND m.centre_id = a.centre_id
  AND m.date >= a.admission_date
  AND (
    a.disposition_date IS NULL
    OR m.date <= a.disposition_date
  )

LEFT JOIN rescue_medication_trans mt
  ON mt.med_trans_id = m.stock_item_used

LEFT JOIN rescue_stock_medication sm
  ON sm.medication_profile_id = mt.med_profile_id

LEFT JOIN rescue_medications rm_stock
  ON rm_stock.medication_id = sm.medication

CROSS JOIN (
  SELECT
    CAST(:from_date AS DATE) AS report_from,
    DATE_ADD(CAST(:to_date AS DATE), INTERVAL 1 DAY) AS report_to
) report_bounds

WHERE
  a.centre_id = :centre_id
  AND (
    (
      LOWER(TRIM(a.disposition)) IN (
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
      AND (
        (
          a.admission_date >= report_bounds.report_from
          AND a.admission_date < report_bounds.report_to
          AND a.disposition_date IS NOT NULL
          AND a.disposition_date >= report_bounds.report_from
          AND a.disposition_date < report_bounds.report_to
        )
        OR (
          a.admission_date >= report_bounds.report_from
          AND a.admission_date < report_bounds.report_to
          AND a.disposition_date IS NOT NULL
          AND a.disposition_date >= report_bounds.report_to
        )
      )
    )
    OR (
      a.admission_date < report_bounds.report_from
      AND (a.disposition_date IS NULL OR a.disposition_date >= report_bounds.report_from)
    )
  )

ORDER BY
  a.admission_date DESC,
  a.admission_id DESC,
  m.date DESC,
  m.med_adm_id DESC;
