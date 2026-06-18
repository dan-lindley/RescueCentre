/* CASE_INDEX.sql
   Required parameters (Pattern A):
   :centre_id
   :from_date  (YYYY-MM-DD)
   :to_date    (YYYY-MM-DD)

   Cohort:
   - Complete patient journeys only.
   - Admission and final disposition must both fall inside the selected report window.
   - Uses inclusive date ranges by applying an exclusive upper bound (to_date + 1 day).
*/

SELECT
  a.admission_id                                   AS admission_id,
  a.patient_id                                     AS patient_id,

  a.admission_date                                 AS admission_date,
  a.status                                         AS admission_status,

  p.name                                           AS patient_name,
  p.animal_type                                    AS animal_type,
  p.animal_order                                   AS animal_order,
  p.animal_species                                 AS animal_species,
  p.sex                                            AS sex,

  a.presenting_complaint                           AS presenting_complaint,
  a.collection_location                            AS collection_location,
  a.location_lat                                   AS location_lat,
  a.location_long                                  AS location_long,

  a.current_location                               AS current_location,
  a.disposition                                    AS disposition,
  a.disposition_date                               AS disposition_date,

  a.weight                                         AS weight,
  a.weight_unit                                    AS weight_unit,

  a.incomplete_fields                              AS incomplete_fields,
  a.date_created                                   AS record_created

FROM rescue_admissions a
JOIN rescue_patients p
  ON p.patient_id = a.patient_id

WHERE
  a.centre_id = :centre_id
  AND a.admission_date >= :from_date
  AND a.admission_date < DATE_ADD(:to_date, INTERVAL 1 DAY)
  AND a.disposition_date IS NOT NULL
  AND a.disposition_date >= :from_date
  AND a.disposition_date < DATE_ADD(:to_date, INTERVAL 1 DAY)
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

ORDER BY
  a.disposition_date ASC,
  a.admission_id ASC;
