SELECT
  p.animal_type    AS 'Animal Type',
  p.animal_order   AS 'Animal Order',
  p.animal_species AS 'Animal Species',

  COUNT(*) AS 'Admitted Total',

  SUM(CASE WHEN d.universal_shortcode = 'R'   THEN 1 ELSE 0 END) AS 'Released (R)',
  SUM(CASE WHEN d.universal_shortcode = 'T'   THEN 1 ELSE 0 END) AS 'Transferred (T)',
  SUM(CASE WHEN d.universal_shortcode = 'E'   THEN 1 ELSE 0 END) AS 'Euthanised (E)',
  SUM(CASE WHEN d.universal_shortcode = 'D'   THEN 1 ELSE 0 END) AS 'Died After Intake (D)',
  SUM(CASE WHEN a.disposition = 'Died - Within 48 hours' THEN 1 ELSE 0 END) AS 'Died Within 48 Hours',
  SUM(CASE WHEN d.universal_shortcode = 'DOA' THEN 1 ELSE 0 END) AS 'Dead On Admission (DOA)',
  SUM(CASE WHEN d.universal_shortcode = 'IC'  THEN 1 ELSE 0 END) AS 'Held in Captivity (IC)',
  SUM(CASE WHEN d.universal_shortcode = 'PC'  THEN 1 ELSE 0 END) AS 'Long-Term Captive (PC)',

  SUM(CASE WHEN COALESCE(a.disposition,'') = '' THEN 1 ELSE 0 END)
    AS 'Pending / Open',

  SUM(
    CASE
      WHEN COALESCE(a.disposition,'') <> '' AND d.disposition_id IS NULL
      THEN 1 ELSE 0
    END
  ) AS 'Unmapped Disposition'

FROM rescue_admissions a
JOIN rescue_patients p
  ON p.patient_id = a.patient_id

LEFT JOIN rescue_dispositions d
  ON d.disposition = a.disposition

WHERE
  a.centre_id = :centre_id
  AND a.admission_date >= :from_date
  AND a.admission_date < DATE_ADD(CAST(:to_date AS DATE), INTERVAL 1 DAY)
  AND a.disposition_date IS NOT NULL
  AND a.disposition_date >= :from_date
  AND a.disposition_date < DATE_ADD(CAST(:to_date AS DATE), INTERVAL 1 DAY)
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

GROUP BY
  p.animal_type,
  p.animal_order,
  p.animal_species

ORDER BY
  COUNT(*) DESC,
  p.animal_type ASC,
  p.animal_order ASC,
  p.animal_species ASC;
