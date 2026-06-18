/* TREATMENT_CARE_LOG.sql
   Pattern A parameters:
   :centre_id
   :from_date (YYYY-MM-DD)
   :to_date   (YYYY-MM-DD)

   Fixes UNION collation issues by forcing a single collation for all text outputs.
*/

SELECT
  x.event_date        AS `Event Date`,
  x.event_type        AS `Event Type`,
  x.patient_id        AS `Patient ID`,
  x.patient_name      AS `Patient Name`,
  x.animal_type       AS `Animal Type`,
  x.animal_order      AS `Animal Order`,
  x.animal_species    AS `Animal Species`,
  x.summary           AS `Summary`,
  x.details           AS `Details`,
  x.recorded_by       AS `Recorded By`,
  x.public_flag       AS `Public`,
  x.image_id          AS `Image ID`
FROM
(
  /* ---------------------------
     Treatments
  --------------------------- */
  SELECT
    t.date AS event_date,

    'Treatment' COLLATE utf8mb4_unicode_ci AS event_type,

    p.patient_id AS patient_id,

    CAST(p.name AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS patient_name,
    CAST(p.animal_type AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS animal_type,
    CAST(p.animal_order AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS animal_order,
    CAST(p.animal_species AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS animal_species,

    CAST(t.treatment AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS summary,
    CAST(t.treatment_free_text AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS details,
    CAST(t.done_by AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS recorded_by,

    CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS public_flag,
    NULL AS image_id

  FROM rescue_treatments t
  JOIN rescue_patients p
    ON p.patient_id = t.patient_id
  WHERE
    p.centre_id = :centre_id
    AND t.date >= :from_date
    AND t.date < DATE_ADD(CAST(:to_date AS DATE), INTERVAL 1 DAY)

  UNION ALL

  /* ---------------------------
     Care Notes
  --------------------------- */
  SELECT
    n.date AS event_date,

    'Care Note' COLLATE utf8mb4_unicode_ci AS event_type,

    p.patient_id AS patient_id,

    CAST(p.name AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS patient_name,
    CAST(p.animal_type AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS animal_type,
    CAST(p.animal_order AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS animal_order,
    CAST(p.animal_species AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS animal_species,

    'Note' COLLATE utf8mb4_unicode_ci AS summary,
    CAST(n.message AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS details,
    CAST(n.author AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS recorded_by,

    CAST(n.public AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS public_flag,
    n.image_id AS image_id

  FROM rescue_notes_patients n
  JOIN rescue_patients p
    ON p.patient_id = n.patient_id
  WHERE
    p.centre_id = :centre_id
    AND COALESCE(n.deleted, 0) = 0
    AND n.date >= :from_date
    AND n.date < DATE_ADD(CAST(:to_date AS DATE), INTERVAL 1 DAY)

) x
ORDER BY
  x.event_date DESC,
  x.patient_id DESC;
