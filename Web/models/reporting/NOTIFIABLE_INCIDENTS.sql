/* NOTIFIABLE_INCIDENTS.sql
   Pattern A parameters:
   :centre_id
   :from_date (YYYY-MM-DD)
   :to_date   (YYYY-MM-DD)

   Sources:
   - Admissions with reportable presenting complaint keywords.
   - Positive laboratory results.
   - Incident log records marked as mass casualty.
*/

SELECT *
FROM (
  SELECT
    'Presenting Complaint' COLLATE utf8mb4_unicode_ci AS `Source Type`,
    a.admission_id                                    AS `Source ID`,
    a.admission_id                                    AS `Admission ID`,
    a.patient_id                                      AS `Patient ID`,
    a.admission_date                                  AS `Event Date`,

    CAST(p.animal_type AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci    AS `Animal Type`,
    CAST(p.animal_order AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci   AS `Animal Order`,
    CAST(p.animal_species AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS `Animal Species`,
    CAST(p.name AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci           AS `Patient Name`,

    CAST(a.presenting_complaint AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS `Presenting Complaint`,

    CASE
      WHEN LOWER(a.presenting_complaint) LIKE '%poison%'
        OR LOWER(a.presenting_complaint) LIKE '%toxin%'
        THEN 'Suspected Poisoning / Toxin'
      WHEN LOWER(a.presenting_complaint) LIKE '%shot%'
        OR LOWER(a.presenting_complaint) LIKE '%gun%'
        OR LOWER(a.presenting_complaint) LIKE '%bullet%'
        THEN 'Suspected Shooting'
      WHEN LOWER(a.presenting_complaint) LIKE '%trap%'
        OR LOWER(a.presenting_complaint) LIKE '%snare%'
        THEN 'Trap / Snare'
      WHEN LOWER(a.presenting_complaint) LIKE '%oil%'
        OR LOWER(a.presenting_complaint) LIKE '%tar%'
        THEN 'Oil / Tar Contamination'
      WHEN LOWER(a.presenting_complaint) LIKE '%electric%'
        OR LOWER(a.presenting_complaint) LIKE '%power line%'
        OR LOWER(a.presenting_complaint) LIKE '%electroc%'
        THEN 'Electrocution / Powerline'
      WHEN LOWER(a.presenting_complaint) LIKE '%attack%'
        OR LOWER(a.presenting_complaint) LIKE '%dog%'
        OR LOWER(a.presenting_complaint) LIKE '%cat%'
        THEN 'Domestic Animal Attack'
      WHEN LOWER(a.presenting_complaint) LIKE '%cruel%'
        OR LOWER(a.presenting_complaint) LIKE '%abuse%'
        OR LOWER(a.presenting_complaint) LIKE '%neglect%'
        THEN 'Suspected Cruelty / Abuse'
      WHEN LOWER(a.presenting_complaint) LIKE '%rta%'
        OR LOWER(a.presenting_complaint) LIKE '%vehicle%'
        OR LOWER(a.presenting_complaint) LIKE '%car%'
        OR LOWER(a.presenting_complaint) LIKE '%road%'
        THEN 'Vehicle Strike / Road Traffic'
      WHEN LOWER(a.presenting_complaint) LIKE '%bite%'
        OR LOWER(a.presenting_complaint) LIKE '%rabies%'
        THEN 'Bite / Rabies Risk'
      WHEN LOWER(a.presenting_complaint) LIKE '%flu%'
        OR LOWER(a.presenting_complaint) LIKE '%avian%'
        OR LOWER(a.presenting_complaint) LIKE '%ai%'
        THEN 'Disease Concern (Avian Flu etc.)'
      ELSE 'Other / Review Required'
    END                                             AS `Notifiable Category (Derived)`,

    CAST(a.collection_location AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS `Collection Location`,
    CAST(a.status AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci              AS `Admission Status`,
    CAST(a.disposition AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci         AS `Disposition`,
    a.disposition_date                              AS `Disposition Date`,

    'Complete journey' COLLATE utf8mb4_unicode_ci AS `Journey Cohort`,

    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS `Lab Test`,
    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS `Lab Category`,
    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS `Lab Result`,

    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS `Incident Reference`,
    NULL AS `Incident Total Casualties`,
    NULL AS `Incident DOA`,
    NULL AS `Incident Reportable Casualties`,
    NULL AS `Incident Mass Casualty`

  FROM rescue_admissions a
  JOIN rescue_patients p
    ON p.patient_id = a.patient_id
  CROSS JOIN (
    SELECT
      CAST(:from_date AS DATE) AS report_from,
      DATE_ADD(CAST(:to_date AS DATE), INTERVAL 1 DAY) AS report_to
  ) report_bounds
  WHERE
    a.centre_id = :centre_id
    AND a.admission_date >= report_bounds.report_from
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
    AND COALESCE(TRIM(a.presenting_complaint), '') <> ''
    AND (
      LOWER(a.presenting_complaint) LIKE '%poison%'
      OR LOWER(a.presenting_complaint) LIKE '%toxin%'
      OR LOWER(a.presenting_complaint) LIKE '%shot%'
      OR LOWER(a.presenting_complaint) LIKE '%gun%'
      OR LOWER(a.presenting_complaint) LIKE '%bullet%'
      OR LOWER(a.presenting_complaint) LIKE '%trap%'
      OR LOWER(a.presenting_complaint) LIKE '%snare%'
      OR LOWER(a.presenting_complaint) LIKE '%oil%'
      OR LOWER(a.presenting_complaint) LIKE '%tar%'
      OR LOWER(a.presenting_complaint) LIKE '%electric%'
      OR LOWER(a.presenting_complaint) LIKE '%power line%'
      OR LOWER(a.presenting_complaint) LIKE '%electroc%'
      OR LOWER(a.presenting_complaint) LIKE '%attack%'
      OR LOWER(a.presenting_complaint) LIKE '%dog%'
      OR LOWER(a.presenting_complaint) LIKE '%cat%'
      OR LOWER(a.presenting_complaint) LIKE '%cruel%'
      OR LOWER(a.presenting_complaint) LIKE '%abuse%'
      OR LOWER(a.presenting_complaint) LIKE '%neglect%'
      OR LOWER(a.presenting_complaint) LIKE '%rta%'
      OR LOWER(a.presenting_complaint) LIKE '%vehicle%'
      OR LOWER(a.presenting_complaint) LIKE '%car%'
      OR LOWER(a.presenting_complaint) LIKE '%road%'
      OR LOWER(a.presenting_complaint) LIKE '%bite%'
      OR LOWER(a.presenting_complaint) LIKE '%rabies%'
      OR LOWER(a.presenting_complaint) LIKE '%flu%'
      OR LOWER(a.presenting_complaint) LIKE '%avian%'
      OR LOWER(a.presenting_complaint) LIKE '%ai%'
    )

  UNION ALL

  SELECT
    'Positive Lab Result' COLLATE utf8mb4_unicode_ci AS `Source Type`,
    l.lab_id                                        AS `Source ID`,
    l.admission_id                                  AS `Admission ID`,
    l.patient_id                                    AS `Patient ID`,
    l.lab_date                                      AS `Event Date`,

    CAST(p.animal_type AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci    AS `Animal Type`,
    CAST(p.animal_order AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci   AS `Animal Order`,
    CAST(p.animal_species AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS `Animal Species`,
    CAST(p.name AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci           AS `Patient Name`,

    CAST(a.presenting_complaint AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS `Presenting Complaint`,
    CAST(CONCAT('Positive Lab: ', COALESCE(t.lab_test, 'Unknown test')) AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS `Notifiable Category (Derived)`,

    CAST(a.collection_location AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS `Collection Location`,
    CAST(a.status AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci              AS `Admission Status`,
    CAST(a.disposition AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci         AS `Disposition`,
    a.disposition_date                              AS `Disposition Date`,

    'Complete journey' COLLATE utf8mb4_unicode_ci AS `Journey Cohort`,

    CAST(t.lab_test AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci     AS `Lab Test`,
    CAST(t.lab_category AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS `Lab Category`,
    CAST(l.lab_result AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci   AS `Lab Result`,

    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS `Incident Reference`,
    NULL AS `Incident Total Casualties`,
    NULL AS `Incident DOA`,
    NULL AS `Incident Reportable Casualties`,
    NULL AS `Incident Mass Casualty`

  FROM rescue_labs l
  JOIN rescue_patients p
    ON p.patient_id = l.patient_id
  JOIN rescue_admissions a
    ON a.admission_id = l.admission_id
  LEFT JOIN rescue_labs_tests t
    ON t.l_test_id = l.lab_test
  CROSS JOIN (
    SELECT
      CAST(:from_date AS DATE) AS report_from,
      DATE_ADD(CAST(:to_date AS DATE), INTERVAL 1 DAY) AS report_to
  ) report_bounds
  WHERE
    l.centre_id = :centre_id
    AND l.is_positive = 1
    AND l.lab_date >= :from_date
    AND l.lab_date < DATE_ADD(CAST(:to_date AS DATE), INTERVAL 1 DAY)
    AND a.admission_date >= report_bounds.report_from
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

  UNION ALL

  SELECT
    'Mass Casualty Incident' COLLATE utf8mb4_unicode_ci AS `Source Type`,
    i.incident_id                                    AS `Source ID`,
    a.admission_id                                    AS `Admission ID`,
    a.patient_id                                      AS `Patient ID`,
    i.incident_date                                    AS `Event Date`,

    CAST(p.animal_type AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci    AS `Animal Type`,
    CAST(p.animal_order AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci   AS `Animal Order`,
    CAST(p.animal_species AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS `Animal Species`,
    CAST(p.name AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci           AS `Patient Name`,

    CAST(a.presenting_complaint AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS `Presenting Complaint`,
    'Mass Casualty Event' COLLATE utf8mb4_unicode_ci AS `Notifiable Category (Derived)`,

    CAST(TRIM(CONCAT_WS(', ',
      NULLIF(i.incident_location_line_1, ''),
      NULLIF(i.incident_location_line_2, ''),
      NULLIF(i.incident_location_city, ''),
      NULLIF(i.incident_location_postcode, '')
    )) AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS `Collection Location`,
    CAST(a.status AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci      AS `Admission Status`,
    CAST(a.disposition AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS `Disposition`,
    a.disposition_date                                                           AS `Disposition Date`,

    'Incident linked casualty' COLLATE utf8mb4_unicode_ci AS `Journey Cohort`,

    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS `Lab Test`,
    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS `Lab Category`,
    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS `Lab Result`,

    CAST(i.incident_centre_ref AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS `Incident Reference`,
    i.incident_total_casualties AS `Incident Total Casualties`,
    i.incident_doa              AS `Incident DOA`,
    COALESCE(i.incident_total_casualties, 0) AS `Incident Reportable Casualties`,
    i.incident_mass_cas         AS `Incident Mass Casualty`

  FROM rescue_incidents i
  LEFT JOIN rescue_incident_related rel
    ON rel.incident_id = i.incident_id
    AND rel.centre_id = i.centre_id
    AND rel.is_deleted = 0
  LEFT JOIN rescue_admissions a
    ON a.admission_id = rel.admission_id
    AND a.centre_id = i.centre_id
  LEFT JOIN rescue_patients p
    ON p.patient_id = a.patient_id
    AND p.centre_id = i.centre_id
  WHERE
    i.centre_id = :centre_id
    AND i.incident_mass_cas = 1
    AND i.incident_date >= :from_date
    AND i.incident_date < DATE_ADD(CAST(:to_date AS DATE), INTERVAL 1 DAY)
) report_rows
ORDER BY
  `Event Date` DESC,
  `Source Type` ASC,
  `Source ID` DESC;
