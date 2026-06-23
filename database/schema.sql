-- Rescue Centre Lite schema
-- Multi-user, single-centre local install.
-- Install-safe: creates missing tables only.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS rescue_centres (
    rescue_id INT AUTO_INCREMENT PRIMARY KEY,
    rescue_name VARCHAR(190) NOT NULL,
    centre_type VARCHAR(80) NULL,
    owner_id INT NULL,
    email VARCHAR(190) NULL,
    office_tel VARCHAR(60) NULL,
    mobile VARCHAR(60) NULL,
    `24_hour` VARCHAR(60) NULL,
    address_line_one VARCHAR(190) NULL,
    address_line_two VARCHAR(190) NULL,
    city VARCHAR(120) NULL,
    county VARCHAR(120) NULL,
    postcode VARCHAR(30) NULL,
    country_code CHAR(2) NULL,
    coordinates VARCHAR(80) NULL,
    centre_lat DECIMAL(10,7) NULL,
    centre_long DECIMAL(10,7) NULL,
    accepting_admissions VARCHAR(30) NOT NULL DEFAULT 'Yes',
    closed_message TEXT NULL,
    species_accepted TEXT NULL,
    opening_hours TEXT NULL,
    ngo_parameter VARCHAR(190) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rescue_centres_country (country_code),
    INDEX idx_rescue_centres_name (rescue_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_centre_meta (
    meta_id INT AUTO_INCREMENT PRIMARY KEY,
    centre_id INT NOT NULL,
    centre_type VARCHAR(80) NULL,
    centre_bio TEXT NULL,
    centre_logo VARCHAR(255) NULL,
    centre_profile_image VARCHAR(255) NULL,
    centre_cover_image VARCHAR(255) NULL,
    cover_offset INT NOT NULL DEFAULT 0,
    handover_declaration_text TEXT NULL,
    custom_colour CHAR(7) NULL,
    reporting_from DATE NULL,
    reporting_to DATE NULL,
    single_species_prefill TINYINT(1) NOT NULL DEFAULT 0,
    single_species_default_species VARCHAR(190) NULL,
    mfa_enabled TINYINT(1) NOT NULL DEFAULT 0,
    mfa_totp_enabled TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_centre_meta_centre (centre_id),
    INDEX idx_centre_meta_centre (centre_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    centre_id INT NOT NULL,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(40) NOT NULL DEFAULT 'Member',
    rescue_role INT NULL,
    first_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NULL,
    activation_code VARCHAR(100) NOT NULL DEFAULT 'activated',
    reset_code VARCHAR(100) NULL,
    approved TINYINT(1) NOT NULL DEFAULT 1,
    onboarded TINYINT(1) NOT NULL DEFAULT 1,
    dark_mode TINYINT(1) NOT NULL DEFAULT 0,
    my_patients_per_page INT NOT NULL DEFAULT 25,
    remember_me_code VARCHAR(255) NULL,
    last_seen DATETIME NULL,
    registered DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    totp_enabled TINYINT(1) NOT NULL DEFAULT 0,
    totp_secret VARCHAR(255) NULL,
    totp_confirmed_at DATETIME NULL,
    ngo_id INT NULL,
    ngo_ok TINYINT(1) NOT NULL DEFAULT 0,
    vet_id INT NULL,
    vet_ok TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_accounts_username (username),
    UNIQUE KEY uq_accounts_email (email),
    INDEX idx_accounts_centre (centre_id),
    INDEX idx_accounts_role (role),
    INDEX idx_accounts_rescue_role (rescue_role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    centre_id INT NOT NULL,
    role_name VARCHAR(120) NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_rescue_role (centre_id, role_name),
    INDEX idx_roles_centre (centre_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_permissions (
    permission_id INT AUTO_INCREMENT PRIMARY KEY,
    permission_key VARCHAR(190) NOT NULL,
    description VARCHAR(255) NULL,
    `type` VARCHAR(40) NOT NULL DEFAULT 'action',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_permission_key (permission_key),
    INDEX idx_permissions_type (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_role_permissions (
    role_permission_id INT AUTO_INCREMENT PRIMARY KEY,
    centre_id INT NOT NULL,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    allow TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_role_permission (centre_id, role_id, permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_user_permissions (
    user_permission_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    permission_id INT NOT NULL,
    allow TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_user_permission (user_id, permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lite_settings (
    setting_key VARCHAR(120) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_animal_orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    animal_order VARCHAR(120) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_animal_order (animal_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_animal_types (
    type_id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(120) NOT NULL,
    animal_order VARCHAR(120) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_type_name (type_name),
    INDEX idx_type_order (animal_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_animal_species (
    species_id INT AUTO_INCREMENT PRIMARY KEY,
    species_name VARCHAR(190) NOT NULL,
    scientific_name VARCHAR(190) NULL,
    animal_type VARCHAR(120) NULL,
    animal_order VARCHAR(120) NULL,
    gbif_id VARCHAR(80) NULL,
    iucn_status VARCHAR(80) NULL,
    reference VARCHAR(255) NULL,
    species_weight_from DECIMAL(10,2) NULL,
    species_weight_to DECIMAL(10,2) NULL,
    species_weight_unit VARCHAR(20) NULL,
    species_measurement_from DECIMAL(10,2) NULL,
    species_measurement_to DECIMAL(10,2) NULL,
    species_measurement_standard DECIMAL(10,2) NULL,
    species_measurement_unit VARCHAR(20) NULL,
    UNIQUE KEY uq_species_name (species_name),
    INDEX idx_species_type (animal_type),
    INDEX idx_species_order (animal_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_dispositions (
    disposition_id INT AUTO_INCREMENT PRIMARY KEY,
    disposition VARCHAR(190) NOT NULL,
    final_status VARCHAR(80) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_disposition (disposition)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_presenting_complaints (
    complaint_id INT AUTO_INCREMENT PRIMARY KEY,
    complaint VARCHAR(190) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_complaint (complaint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_severity_score (
    severity_id INT AUTO_INCREMENT PRIMARY KEY,
    severity_text VARCHAR(190) NOT NULL,
    severity_score INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_patients (
    patient_id INT AUTO_INCREMENT PRIMARY KEY,
    centre_id INT NOT NULL,
    name VARCHAR(190) NULL,
    ringed VARCHAR(20) NULL,
    ring_number VARCHAR(100) NULL,
    microchipped VARCHAR(20) NULL,
    microchip_number VARCHAR(100) NULL,
    animal_type VARCHAR(120) NULL,
    animal_order VARCHAR(120) NULL,
    animal_species VARCHAR(190) NULL,
    sex VARCHAR(60) NULL,
    status VARCHAR(80) NOT NULL DEFAULT 'Captive',
    state VARCHAR(80) NOT NULL DEFAULT 'To Admit',
    staff_wp_id INT NULL,
    transfer_id INT NOT NULL DEFAULT 0,
    created_by INT NULL,
    approx_dob DATE NULL,
    date_added DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    incomplete_fields JSON NULL,
    mobile_create_key VARCHAR(100) NULL,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    deleted_at DATETIME NULL,
    INDEX idx_patients_centre (centre_id),
    INDEX idx_patients_species (animal_species),
    INDEX idx_patients_status (status),
    INDEX idx_patients_state (state),
    INDEX idx_patients_deleted (is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_admissions (
    admission_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    centre_id INT NOT NULL,
    staff_wp_id INT NULL,
    admission_date DATETIME NULL,
    date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(80) NULL,
    disposition VARCHAR(190) NULL,
    survived TINYINT(1) NULL,
    current_location VARCHAR(190) NULL,
    current_location_id INT NULL,
    time_to_admission VARCHAR(80) NULL,
    collection_location VARCHAR(255) NULL,
    location_lat DECIMAL(10,7) NULL,
    location_long DECIMAL(10,7) NULL,
    finder_id INT NULL,
    finder_name VARCHAR(190) NULL,
    finder_tel VARCHAR(80) NULL,
    consent_to_update VARCHAR(40) NULL,
    passphrase VARCHAR(120) NULL,
    age_on_admission VARCHAR(100) NULL,
    dehydrated VARCHAR(20) NULL,
    starved VARCHAR(20) NULL,
    weight DECIMAL(10,3) NULL,
    weight_unit VARCHAR(20) NULL,
    measurement DECIMAL(10,3) NULL,
    measurement_unit VARCHAR(20) NULL,
    ss_text VARCHAR(190) NULL,
    severity_score INT NULL,
    bcs_text VARCHAR(190) NULL,
    bc_score INT NULL,
    presenting_complaint VARCHAR(255) NULL,
    hpc TEXT NULL,
    on_examination TEXT NULL,
    w_temp DECIMAL(10,2) NULL,
    w_wind DECIMAL(10,2) NULL,
    w_humidity DECIMAL(10,2) NULL,
    w_rainfall DECIMAL(10,2) NULL,
    w_freetext TEXT NULL,
    species_score INT NULL,
    age_score INT NULL,
    disposition_date DATETIME NULL,
    disposition_user VARCHAR(120) NULL,
    disposition_centre VARCHAR(190) NULL,
    disposition_comment TEXT NULL,
    euthanasia_method VARCHAR(190) NULL,
    incomplete_fields JSON NULL,
    mobile_create_key VARCHAR(100) NULL,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    deleted_at DATETIME NULL,
    INDEX idx_admissions_patient (patient_id),
    INDEX idx_admissions_centre (centre_id),
    INDEX idx_admissions_status (status),
    INDEX idx_admissions_location (current_location_id),
    INDEX idx_admissions_date (admission_date),
    INDEX idx_admissions_deleted (is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_zones (
    zone_id INT AUTO_INCREMENT PRIMARY KEY,
    centre_id INT NOT NULL,
    zone_name VARCHAR(190) NOT NULL,
    zone_notes TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_zones_centre (centre_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_areas (
    area_id INT AUTO_INCREMENT PRIMARY KEY,
    centre_id INT NOT NULL,
    zone_id INT NULL,
    area_name VARCHAR(190) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_areas_centre (centre_id),
    INDEX idx_areas_zone (zone_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    centre_id INT NOT NULL,
    area_id INT NULL,
    location_area VARCHAR(190) NULL,
    location_name VARCHAR(190) NOT NULL,
    location_type VARCHAR(80) NULL,
    max_occupancy INT NULL,
    deleted TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_locations_centre (centre_id),
    INDEX idx_locations_area (area_id),
    INDEX idx_locations_deleted (deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_finders (
    finder_id INT AUTO_INCREMENT PRIMARY KEY,
    centre_id INT NOT NULL,
    finder_name VARCHAR(190) NULL,
    finder_tel VARCHAR(80) NULL,
    finder_email VARCHAR(190) NULL,
    finder_address_line1 VARCHAR(190) NULL,
    finder_town VARCHAR(120) NULL,
    finder_postcode VARCHAR(30) NULL,
    preferred_contact_method VARCHAR(80) NULL,
    has_donated TINYINT(1) NOT NULL DEFAULT 0,
    gift_aid_consent TINYINT(1) NOT NULL DEFAULT 0,
    mobile_create_key VARCHAR(100) NULL,
    deleted TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_finders_centre (centre_id),
    INDEX idx_finders_name (finder_name),
    INDEX idx_finders_tel (finder_tel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_finder_admissions (
    finder_admission_id INT AUTO_INCREMENT PRIMARY KEY,
    finder_id INT NOT NULL,
    admission_id INT NOT NULL,
    patient_id INT NOT NULL,
    centre_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_finder_admission (finder_id, admission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_signatures (
    signature_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    admission_id INT NOT NULL,
    centre_id INT NOT NULL,
    user_id INT NULL,
    signature_data LONGTEXT NULL,
    refused TINYINT(1) NOT NULL DEFAULT 0,
    signed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_signature_admission (patient_id, admission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_images (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    centre_id INT NOT NULL,
    image_url VARCHAR(255) NULL,
    file_name VARCHAR(255) NULL,
    is_legacy TINYINT(1) NOT NULL DEFAULT 0,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    INDEX idx_images_patient (patient_id),
    INDEX idx_images_centre (centre_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_notes_patients (
    note_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    message TEXT NOT NULL,
    author VARCHAR(190) NULL,
    date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted TINYINT(1) NOT NULL DEFAULT 0,
    public TINYINT(1) NOT NULL DEFAULT 0,
    image_id INT NULL,
    INDEX idx_notes_patient (patient_id),
    INDEX idx_notes_date (date),
    INDEX idx_notes_deleted (deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_treatments (
    treatment_given_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    treatment VARCHAR(255) NOT NULL,
    treatment_free_text TEXT NULL,
    done_by VARCHAR(190) NULL,
    date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_treatments_patient (patient_id),
    INDEX idx_treatments_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_labs (
    lab_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    centre_id INT NOT NULL,
    admission_id INT NOT NULL,
    lab_date DATETIME NULL,
    sample_type VARCHAR(190) NULL,
    lab_result TEXT NULL,
    reported_by VARCHAR(190) NULL,
    lab_test VARCHAR(190) NULL,
    is_positive TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_labs_patient (patient_id),
    INDEX idx_labs_admission (admission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_observations (
    observation_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    admission_id INT NOT NULL,
    user_id INT NULL,
    obs_severity_score INT NULL,
    obs_severity_text VARCHAR(190) NULL,
    obs_bcs_score INT NULL,
    obs_bcs_text VARCHAR(190) NULL,
    obs_age_score INT NULL,
    obs_age_text VARCHAR(190) NULL,
    obs_notes TEXT NULL,
    obs_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_observations_patient (patient_id),
    INDEX idx_observations_admission (admission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_weights (
    weight_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    weight DECIMAL(10,3) NOT NULL,
    weight_unit VARCHAR(20) NULL,
    date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_weights_patient (patient_id),
    INDEX idx_weights_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_measurements (
    measurement_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    measurement DECIMAL(10,3) NOT NULL,
    measurement_unit VARCHAR(20) NULL,
    date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_measurements_patient (patient_id),
    INDEX idx_measurements_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_prescriptions (
    prescription_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    centre_id INT NOT NULL,
    admission_id INT NOT NULL,
    user_id INT NULL,
    medication VARCHAR(190) NOT NULL,
    dose VARCHAR(80) NULL,
    dose_type VARCHAR(80) NULL,
    duration VARCHAR(80) NULL,
    frequency VARCHAR(120) NULL,
    route VARCHAR(120) NULL,
    by_weight TINYINT(1) NOT NULL DEFAULT 0,
    date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_prescriptions_patient (patient_id),
    INDEX idx_prescriptions_admission (admission_id),
    INDEX idx_prescriptions_deleted (is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_medications_given (
    medication_given_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    centre_id INT NOT NULL,
    medication_given VARCHAR(190) NOT NULL,
    dose VARCHAR(80) NULL,
    dose_type VARCHAR(80) NULL,
    vol_given VARCHAR(80) NULL,
    exp_given VARCHAR(80) NULL,
    batch_given VARCHAR(120) NULL,
    stock_item_used INT NULL,
    pack_used INT NULL,
    given_by VARCHAR(190) NULL,
    given_by_id INT NULL,
    date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_meds_given_patient (patient_id),
    INDEX idx_meds_given_centre (centre_id),
    INDEX idx_meds_given_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_medications (
    medication_id INT AUTO_INCREMENT PRIMARY KEY,
    medication_name VARCHAR(190) NOT NULL,
    common_name VARCHAR(190) NULL,
    class VARCHAR(120) NULL,
    description TEXT NULL,
    contraindications TEXT NULL,
    cautions TEXT NULL,
    dose VARCHAR(120) NULL,
    route VARCHAR(120) NULL,
    side_effects TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_medication_name (medication_name),
    INDEX idx_medication_class (class)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_stock_forms (
    stock_form_id INT AUTO_INCREMENT PRIMARY KEY,
    form_name VARCHAR(120) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_stock_medication (
    medication_profile_id INT AUTO_INCREMENT PRIMARY KEY,
    centre_id INT NOT NULL,
    medication INT NULL,
    stock_form_id INT NULL,
    concentration_dose VARCHAR(80) NULL,
    concentration_dose_type VARCHAR(80) NULL,
    concentration_volume VARCHAR(80) NULL,
    concentration_volume_type VARCHAR(80) NULL,
    mgml VARCHAR(80) NULL,
    pack_quantity INT NULL,
    reorder_level INT NULL,
    use_within INT NULL,
    user_id INT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_stock_centre (centre_id),
    INDEX idx_stock_medication (medication)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_medication_trans (
    med_trans_id INT AUTO_INCREMENT PRIMARY KEY,
    centre_id INT NOT NULL,
    med_profile_id INT NULL,
    batch_number VARCHAR(120) NULL,
    packs_in INT NULL,
    est_volume DECIMAL(10,2) NULL,
    expiry DATE NULL,
    reason_destroyed TEXT NULL,
    user_id INT NULL,
    date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_med_trans_centre (centre_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_medication_packs (
    pack_id INT AUTO_INCREMENT PRIMARY KEY,
    med_trans_id INT NOT NULL,
    amount_remaining DECIMAL(10,2) NULL,
    date_opened DATETIME NULL,
    date_finished DATETIME NULL,
    status VARCHAR(80) NOT NULL DEFAULT 'Available',
    INDEX idx_med_packs_trans (med_trans_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_frequencies (frequency_id INT AUTO_INCREMENT PRIMARY KEY, frequency VARCHAR(120) NOT NULL, sort_order INT NOT NULL DEFAULT 0, UNIQUE KEY uq_frequency (frequency)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_frequency_times (frequency_time_id INT AUTO_INCREMENT PRIMARY KEY, frequency_id INT NOT NULL, time_value TIME NOT NULL, sort_order INT NOT NULL DEFAULT 0, INDEX idx_frequency_times_frequency (frequency_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_labs_tests (lab_test_id INT AUTO_INCREMENT PRIMARY KEY, lab_test VARCHAR(190) NOT NULL, active TINYINT(1) NOT NULL DEFAULT 1, UNIQUE KEY uq_lab_test (lab_test)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_sample_types (sample_type_id INT AUTO_INCREMENT PRIMARY KEY, sample_type VARCHAR(190) NOT NULL, active TINYINT(1) NOT NULL DEFAULT 1, UNIQUE KEY uq_sample_type (sample_type)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_tasks (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    centre_id INT NULL,
    task_name VARCHAR(190) NOT NULL,
    task_description TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_tasks_centre (centre_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_tasks_patients (
    task_pt_id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    patient_id INT NOT NULL,
    status VARCHAR(80) NOT NULL DEFAULT 'Waiting',
    set_date_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    set_by INT NULL,
    completed_by INT NULL,
    completed_date_time DATETIME NULL,
    INDEX idx_tasks_patients_patient (patient_id),
    INDEX idx_tasks_patients_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_triages (triage_id INT AUTO_INCREMENT PRIMARY KEY, patient_id INT NOT NULL, admission_id INT NOT NULL, centre_id INT NOT NULL, recorded_by INT NULL, triage_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, age VARCHAR(120) NULL, class VARCHAR(120) NULL, ss VARCHAR(120) NULL, bcs VARCHAR(120) NULL, INDEX idx_triages_patient (patient_id), INDEX idx_triages_admission (admission_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_triage_flows (flow_id INT AUTO_INCREMENT PRIMARY KEY, centre_id INT NULL, flow_name VARCHAR(190) NOT NULL, start_question_id INT NULL, question_order_json JSON NULL, is_global TINYINT(1) NOT NULL DEFAULT 0, active TINYINT(1) NOT NULL DEFAULT 1, updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_triage_questions (question_id INT AUTO_INCREMENT PRIMARY KEY, centre_id INT NULL, question_text TEXT NOT NULL, answer_type VARCHAR(80) NOT NULL DEFAULT 'single', default_next_question_id INT NULL, help_text TEXT NULL, is_global TINYINT(1) NOT NULL DEFAULT 0, active TINYINT(1) NOT NULL DEFAULT 1, updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_triage_answers (answer_id INT AUTO_INCREMENT PRIMARY KEY, question_id INT NOT NULL, answer_label VARCHAR(190) NOT NULL, answer_value VARCHAR(190) NULL, next_question_id INT NULL, advice_id INT NULL, action_type VARCHAR(80) NULL, priority_score INT NOT NULL DEFAULT 0, end_triage TINYINT(1) NOT NULL DEFAULT 0, sort_order INT NOT NULL DEFAULT 0, active TINYINT(1) NOT NULL DEFAULT 1, updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, INDEX idx_triage_answers_question (question_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_triage_advice (advice_id INT AUTO_INCREMENT PRIMARY KEY, advice_title VARCHAR(190) NOT NULL, advice_text TEXT NULL, active TINYINT(1) NOT NULL DEFAULT 1, updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_triage_calls (call_id INT AUTO_INCREMENT PRIMARY KEY, centre_id INT NOT NULL, caller_name VARCHAR(190) NULL, caller_tel VARCHAR(80) NULL, call_notes TEXT NULL, created_by INT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_feeding_events (
    feeding_event_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    admission_id INT NULL,
    centre_id INT NOT NULL,
    diet_item_id INT NULL,
    feed_type VARCHAR(80) NULL,
    feed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    offered_value DECIMAL(10,2) NULL,
    offered_unit VARCHAR(40) NULL,
    consumed_value DECIMAL(10,2) NULL,
    consumed_unit VARCHAR(40) NULL,
    remaining_value DECIMAL(10,2) NULL,
    remaining_percent DECIMAL(5,2) NULL,
    is_estimated TINYINT(1) NOT NULL DEFAULT 0,
    status VARCHAR(80) NULL,
    notes TEXT NULL,
    created_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_feeding_patient (patient_id),
    INDEX idx_feeding_centre (centre_id),
    INDEX idx_feeding_at (feed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_diet_items (
    diet_item_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    type VARCHAR(80) NULL,
    category VARCHAR(80) NULL,
    default_unit VARCHAR(40) NULL,
    shelf_life_days INT NULL,
    kcal_per_g DECIMAL(10,3) NULL,
    kcal_per_ml DECIMAL(10,3) NULL,
    notes TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_diet_item (name),
    INDEX idx_diet_type (type),
    INDEX idx_diet_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_centre_diet_items (centre_diet_item_id INT AUTO_INCREMENT PRIMARY KEY, centre_id INT NOT NULL, diet_item_id INT NOT NULL, is_enabled TINYINT(1) NOT NULL DEFAULT 1, use_within_days INT NULL, notes TEXT NULL, UNIQUE KEY uq_centre_diet_item (centre_id, diet_item_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_transfers_log (transfer_log_id INT AUTO_INCREMENT PRIMARY KEY, patient_id INT NOT NULL, admission_id INT NULL, centre_id INT NULL, event_type VARCHAR(80) NOT NULL, event_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, from_location_id INT NULL, to_location_id INT NULL, disposition_id INT NULL, notes TEXT NULL, created_by_user_id INT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_transfers_patient (patient_id), INDEX idx_transfers_admission (admission_id), INDEX idx_transfers_event (event_type)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_logs (log_id BIGINT AUTO_INCREMENT PRIMARY KEY, centre_id INT NULL, user_id INT NULL, module VARCHAR(120) NULL, action VARCHAR(190) NOT NULL, endpoint VARCHAR(255) NULL, request_method VARCHAR(20) NULL, old_data JSON NULL, new_data JSON NULL, ip_address VARCHAR(80) NULL, user_agent VARCHAR(255) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_logs_centre (centre_id), INDEX idx_logs_action (action), INDEX idx_logs_created (created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_archive (archive_id BIGINT AUTO_INCREMENT PRIMARY KEY, source_table VARCHAR(120) NOT NULL, source_id BIGINT NULL, centre_id INT NULL, archived_data JSON NULL, archived_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, archived_by INT NULL, INDEX idx_archive_source (source_table, source_id), INDEX idx_archive_centre (centre_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_backgrounds (background_id INT AUTO_INCREMENT PRIMARY KEY, centre_id INT NULL, background_name VARCHAR(190) NOT NULL, image_path VARCHAR(255) NOT NULL, active TINYINT(1) NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_diagrams (diagram_id INT AUTO_INCREMENT PRIMARY KEY, patient_id INT NOT NULL, centre_id INT NOT NULL, user_id INT NULL, background_used VARCHAR(255) NULL, canvas_width INT NULL, canvas_height INT NULL, label_data JSON NULL, diagram_png LONGTEXT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, INDEX idx_diagrams_patient (patient_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_certificate_templates (template_id INT AUTO_INCREMENT PRIMARY KEY, centre_id INT NULL, template_name VARCHAR(190) NOT NULL DEFAULT 'Default', layout_admin LONGTEXT NULL, layout_recipient LONGTEXT NULL, updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, INDEX idx_certificate_centre (centre_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_centre_friends (friend_id INT AUTO_INCREMENT PRIMARY KEY, centre_a_id INT NOT NULL, centre_b_id INT NOT NULL, status VARCHAR(40) NOT NULL DEFAULT 'pending', requested_by_centre_id INT NULL, requested_by_user_id INT NULL, responded_by_user_id INT NULL, requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, responded_at DATETIME NULL, UNIQUE KEY uq_centre_friend_pair (centre_a_id, centre_b_id), INDEX idx_centre_friends_status (status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_groups (group_id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(190) NOT NULL, description TEXT NULL, visibility VARCHAR(40) NOT NULL DEFAULT 'private', created_by_centre_id INT NOT NULL, created_by_user_id INT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_groups_centre (created_by_centre_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_group_members (group_member_id INT AUTO_INCREMENT PRIMARY KEY, group_id INT NOT NULL, centre_id INT NOT NULL, role VARCHAR(80) NOT NULL DEFAULT 'member', status VARCHAR(40) NOT NULL DEFAULT 'pending', requested_by_user_id INT NULL, invited_by_user_id INT NULL, approved_by_user_id INT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uq_group_centre (group_id, centre_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_patient_shares (share_id INT AUTO_INCREMENT PRIMARY KEY, patient_id INT NOT NULL, owner_centre_id INT NOT NULL, target_centre_id INT NULL, target_account_id INT NULL, group_id INT NULL, share_type VARCHAR(80) NOT NULL DEFAULT 'view', status VARCHAR(40) NOT NULL DEFAULT 'active', shared_by_account_id INT NULL, revoked_by_account_id INT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, revoked_at DATETIME NULL, INDEX idx_patient_shares_patient (patient_id), INDEX idx_patient_shares_target (target_centre_id, target_account_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_vets (practice_id INT AUTO_INCREMENT PRIMARY KEY, practice_name VARCHAR(190) NOT NULL, email VARCHAR(190) NULL, phone VARCHAR(80) NULL, address TEXT NULL, status VARCHAR(40) NOT NULL DEFAULT 'Active', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_vets_status (status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_vet_centres (rel_id INT AUTO_INCREMENT PRIMARY KEY, centre_id INT NOT NULL, practice_id INT NOT NULL, status VARCHAR(40) NOT NULL DEFAULT 'pending', requested_by_side VARCHAR(40) NULL, requested_by_account_id INT NULL, approved_by_account_id INT NULL, revoked_by_account_id INT NULL, requested_at DATETIME NULL, approved_at DATETIME NULL, revoked_at DATETIME NULL, include_all TINYINT(1) NOT NULL DEFAULT 1, UNIQUE KEY uq_vet_centre (centre_id, practice_id), INDEX idx_vet_centres_status (status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_message_threads (thread_id INT AUTO_INCREMENT PRIMARY KEY, created_by_user_id INT NOT NULL, created_by_centre_id INT NOT NULL, thread_type VARCHAR(40) NOT NULL DEFAULT 'direct', subject VARCHAR(190) NULL, priority VARCHAR(40) NULL, replies_allowed TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_message_threads_updated (updated_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_message_participants (participant_id INT AUTO_INCREMENT PRIMARY KEY, thread_id INT NOT NULL, user_id INT NOT NULL, centre_id INT NOT NULL, is_sender TINYINT(1) NOT NULL DEFAULT 0, last_read_at DATETIME NULL, archived_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_message_participant (thread_id, user_id), INDEX idx_message_participant_inbox (user_id, archived_at), INDEX idx_message_participant_centre (centre_id, thread_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_message_entries (message_id INT AUTO_INCREMENT PRIMARY KEY, thread_id INT NOT NULL, sender_user_id INT NOT NULL, sender_centre_id INT NOT NULL, body TEXT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME NULL, INDEX idx_message_entries_thread (thread_id, created_at), INDEX idx_message_entries_sender (sender_user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_incidents (incident_id INT AUTO_INCREMENT PRIMARY KEY, centre_id INT NOT NULL, user_id INT NULL, incident_centre_ref VARCHAR(120) NULL, incident_date DATETIME NULL, incident_location_line_1 VARCHAR(190) NULL, incident_location_line_2 VARCHAR(190) NULL, incident_location_city VARCHAR(120) NULL, incident_location_postcode VARCHAR(30) NULL, incident_total_casualties INT NULL, incident_mass_cas TINYINT(1) NOT NULL DEFAULT 0, incident_doa TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, INDEX idx_incidents_centre (centre_id), INDEX idx_incidents_date (incident_date)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_incident_related (related_id INT AUTO_INCREMENT PRIMARY KEY, incident_id INT NOT NULL, admission_id INT NULL, finder_id INT NULL, centre_id INT NOT NULL, user_id INT NULL, is_deleted TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_incident_related_incident (incident_id), INDEX idx_incident_related_admission (admission_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_partner_types (partner_type_id INT AUTO_INCREMENT PRIMARY KEY, partner_type VARCHAR(120) NOT NULL, active TINYINT(1) NOT NULL DEFAULT 1, UNIQUE KEY uq_partner_type (partner_type)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_partner_log (partner_log_id INT AUTO_INCREMENT PRIMARY KEY, patient_id INT NOT NULL, admission_id INT NULL, centre_id INT NOT NULL, user_id INT NULL, partner_type VARCHAR(120) NULL, log_number VARCHAR(120) NULL, log_notes TEXT NULL, is_crime TINYINT(1) NOT NULL DEFAULT 0, date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_partner_patient (patient_id), INDEX idx_partner_admission (admission_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_staff_profiles (id INT AUTO_INCREMENT PRIMARY KEY, centre_id INT NOT NULL, account_id INT NULL, display_name VARCHAR(190) NOT NULL, role_type VARCHAR(120) NULL, email VARCHAR(190) NULL, phone VARCHAR(80) NULL, deleted TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, INDEX idx_staff_profiles_centre (centre_id), INDEX idx_staff_profiles_account (account_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_duty_recurrence_rules (recurrence_rule_id INT AUTO_INCREMENT PRIMARY KEY, centre_id INT NOT NULL, staff_profile_id INT NOT NULL, area_id INT NULL, role_type VARCHAR(120) NULL, frequency VARCHAR(40) NOT NULL DEFAULT 'weekly', day_of_week TINYINT NULL, start_time TIME NULL, end_time TIME NULL, starts_on DATE NULL, ends_on DATE NULL, notes TEXT NULL, active TINYINT(1) NOT NULL DEFAULT 1, updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, INDEX idx_duty_rules_centre (centre_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_duty_shifts (shift_id INT AUTO_INCREMENT PRIMARY KEY, centre_id INT NOT NULL, staff_profile_id INT NOT NULL, area_id INT NULL, recurrence_rule_id INT NULL, role_type VARCHAR(120) NULL, shift_date DATE NOT NULL, start_time TIME NULL, end_time TIME NULL, notes TEXT NULL, deleted TINYINT(1) NOT NULL DEFAULT 0, updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, INDEX idx_duty_shifts_centre_date (centre_id, shift_date)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_duty_task_recurrence_rules (recurrence_rule_id INT AUTO_INCREMENT PRIMARY KEY, centre_id INT NOT NULL, staff_profile_id INT NULL, area_id INT NULL, frequency VARCHAR(40) NOT NULL DEFAULT 'weekly', day_of_week TINYINT NULL, due_time TIME NULL, starts_on DATE NULL, ends_on DATE NULL, task_title VARCHAR(190) NOT NULL, task_notes TEXT NULL, priority VARCHAR(40) NULL, active TINYINT(1) NOT NULL DEFAULT 1, updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, INDEX idx_duty_task_rules_centre (centre_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_duty_tasks (duty_task_id INT AUTO_INCREMENT PRIMARY KEY, centre_id INT NOT NULL, staff_profile_id INT NULL, area_id INT NULL, recurrence_rule_id INT NULL, task_date DATE NOT NULL, due_time TIME NULL, task_title VARCHAR(190) NOT NULL, task_notes TEXT NULL, priority VARCHAR(40) NULL, status VARCHAR(40) NOT NULL DEFAULT 'open', completed_by_account_id INT NULL, completed_at DATETIME NULL, deleted TINYINT(1) NOT NULL DEFAULT 0, updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, INDEX idx_duty_tasks_centre_date (centre_id, task_date), INDEX idx_duty_tasks_status (status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_cohorts (cohort_id INT AUTO_INCREMENT PRIMARY KEY, centre_id INT NOT NULL, cohort_name VARCHAR(190) NOT NULL, location_id INT NULL, location_key VARCHAR(190) NULL, location_label VARCHAR(190) NULL, notes TEXT NULL, status VARCHAR(40) NOT NULL DEFAULT 'active', created_by INT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, ended_by INT NULL, ended_at DATETIME NULL, INDEX idx_cohorts_centre (centre_id), INDEX idx_cohorts_location (location_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_cohort_members (cohort_member_id INT AUTO_INCREMENT PRIMARY KEY, cohort_id INT NOT NULL, patient_id INT NOT NULL, joined_by INT NULL, joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, left_by INT NULL, left_at DATETIME NULL, leave_reason TEXT NULL, INDEX idx_cohort_members_cohort (cohort_id), INDEX idx_cohort_members_patient (patient_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_cohort_feeding_logs (log_id INT AUTO_INCREMENT PRIMARY KEY, cohort_id INT NOT NULL, food_item_id INT NULL, amount_in DECIMAL(10,2) NULL, amount_out DECIMAL(10,2) NULL, amount_unit VARCHAR(40) NULL, fed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, logged_by INT NULL, notes TEXT NULL, INDEX idx_cohort_feeding_cohort (cohort_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_cohort_care_notes (cohort_note_id INT AUTO_INCREMENT PRIMARY KEY, cohort_id INT NOT NULL, note_text TEXT NOT NULL, created_by INT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_cohort_notes_cohort (cohort_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_reports_modules (report_module_id INT AUTO_INCREMENT PRIMARY KEY, centre_id INT NULL, module_key VARCHAR(120) NOT NULL, module_name VARCHAR(190) NOT NULL, enabled TINYINT(1) NOT NULL DEFAULT 1, sort_order INT NOT NULL DEFAULT 0, UNIQUE KEY uq_report_module (centre_id, module_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_modules (module_id INT AUTO_INCREMENT PRIMARY KEY, module_key VARCHAR(120) NOT NULL, module_name VARCHAR(190) NOT NULL, version VARCHAR(40) NULL, enabled TINYINT(1) NOT NULL DEFAULT 1, installed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_module_key (module_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_mfa_challenges (challenge_id INT AUTO_INCREMENT PRIMARY KEY, centre_id INT NULL, user_id INT NOT NULL, target_id VARCHAR(190) NULL, method VARCHAR(40) NOT NULL, purpose VARCHAR(80) NOT NULL, code_hash VARCHAR(255) NOT NULL, attempts INT NOT NULL DEFAULT 0, status VARCHAR(40) NOT NULL DEFAULT 'pending', expires_at DATETIME NOT NULL, used_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_mfa_user_status (user_id, status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_tickets (ticket_id INT AUTO_INCREMENT PRIMARY KEY, centre_id INT NULL, centre_name VARCHAR(190) NULL, subject VARCHAR(190) NOT NULL, description TEXT NULL, priority VARCHAR(40) NULL, progress VARCHAR(40) NULL, duplicate_of INT NULL, admin_thread TEXT NULL, is_hidden TINYINT(1) NOT NULL DEFAULT 0, updated_by_admin_id INT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, last_activity_at DATETIME NULL, INDEX idx_tickets_centre (centre_id), INDEX idx_tickets_progress (progress)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_boards (board_id INT AUTO_INCREMENT PRIMARY KEY, centre_id INT NULL, board_name VARCHAR(190) NOT NULL, description TEXT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_posts (post_id INT AUTO_INCREMENT PRIMARY KEY, board_id INT NULL, parent_id INT NULL, user_id INT NOT NULL, title VARCHAR(190) NULL, body TEXT NOT NULL, status VARCHAR(40) NOT NULL DEFAULT 'active', last_activity_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_posts_board (board_id), INDEX idx_posts_parent (parent_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_tags (tag_id INT AUTO_INCREMENT PRIMARY KEY, label VARCHAR(120) NOT NULL, slug VARCHAR(120) NOT NULL, UNIQUE KEY uq_tag_slug (slug)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_post_tags (post_id INT NOT NULL, tag_id INT NOT NULL, PRIMARY KEY (post_id, tag_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_learning_courses (course_id INT AUTO_INCREMENT PRIMARY KEY, owner_centre_id INT NULL, title VARCHAR(190) NOT NULL, description TEXT NULL, visibility VARCHAR(40) NOT NULL DEFAULT 'centre', pass_mark_percent INT NOT NULL DEFAULT 80, max_attempts INT NULL, is_active TINYINT(1) NOT NULL DEFAULT 1, created_by_user_id INT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, INDEX idx_learning_courses_centre (owner_centre_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_learning_pages (page_id INT AUTO_INCREMENT PRIMARY KEY, course_id INT NOT NULL, page_title VARCHAR(190) NOT NULL, page_content LONGTEXT NULL, page_type VARCHAR(60) NOT NULL DEFAULT 'content', media_url VARCHAR(255) NULL, sort_order INT NOT NULL DEFAULT 0, is_required TINYINT(1) NOT NULL DEFAULT 1, is_active TINYINT(1) NOT NULL DEFAULT 1, INDEX idx_learning_pages_course (course_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_learning_assessments (assessment_id INT AUTO_INCREMENT PRIMARY KEY, course_id INT NOT NULL, title VARCHAR(190) NOT NULL, instructions TEXT NULL, is_active TINYINT(1) NOT NULL DEFAULT 1, INDEX idx_learning_assessments_course (course_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_learning_questions (question_id INT AUTO_INCREMENT PRIMARY KEY, assessment_id INT NOT NULL, question_text TEXT NOT NULL, question_type VARCHAR(60) NOT NULL DEFAULT 'single_choice', sort_order INT NOT NULL DEFAULT 0, is_active TINYINT(1) NOT NULL DEFAULT 1, updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, INDEX idx_learning_questions_assessment (assessment_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_learning_answers (answer_id INT AUTO_INCREMENT PRIMARY KEY, question_id INT NOT NULL, answer_text TEXT NOT NULL, is_correct TINYINT(1) NOT NULL DEFAULT 0, sort_order INT NOT NULL DEFAULT 0, INDEX idx_learning_answers_question (question_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_learning_question_correct_options (option_id INT AUTO_INCREMENT PRIMARY KEY, question_id INT NOT NULL, answer_id INT NOT NULL, UNIQUE KEY uq_learning_correct_option (question_id, answer_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_learning_user_courses (user_course_id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, centre_id INT NULL, course_id INT NOT NULL, status VARCHAR(40) NOT NULL DEFAULT 'not_started', current_page_id INT NULL, progress_percent DECIMAL(5,2) NOT NULL DEFAULT 0, started_at DATETIME NULL, completed_at DATETIME NULL, passed_at DATETIME NULL, last_accessed_at DATETIME NULL, attempt_count INT NOT NULL DEFAULT 0, assessment_score DECIMAL(5,2) NULL, assessment_status VARCHAR(40) NULL, certificate_earned TINYINT(1) NOT NULL DEFAULT 0, updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uq_learning_user_course (user_id, course_id), INDEX idx_learning_user_courses_course (course_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_learning_page_progress (progress_id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, course_id INT NOT NULL, page_id INT NOT NULL, completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_learning_page_progress (user_id, course_id, page_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_learning_user_answers (user_answer_id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, course_id INT NOT NULL, assessment_id INT NOT NULL, question_id INT NOT NULL, answer_id INT NULL, answer_text TEXT NULL, is_correct TINYINT(1) NULL, attempt_no INT NOT NULL DEFAULT 1, answered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_learning_user_answers_user (user_id, course_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_learning_certificates (certificate_id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, course_id INT NOT NULL, certificate_code VARCHAR(120) NOT NULL, issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_learning_certificate_code (certificate_code)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_learning_assignments (assignment_id INT AUTO_INCREMENT PRIMARY KEY, centre_id INT NOT NULL, course_id INT NULL, assignable_type VARCHAR(40) NULL, assignable_id INT NULL, role_id INT NULL, user_id INT NULL, due_date DATE NULL, created_by INT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_learning_assignments_centre (centre_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_learning_suites (suite_id INT AUTO_INCREMENT PRIMARY KEY, owner_centre_id INT NULL, title VARCHAR(190) NOT NULL, description TEXT NULL, visibility VARCHAR(40) NOT NULL DEFAULT 'centre', is_active TINYINT(1) NOT NULL DEFAULT 1, created_by_user_id INT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, INDEX idx_learning_suites_centre (owner_centre_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rescue_learning_suite_courses (suite_course_id INT AUTO_INCREMENT PRIMARY KEY, suite_id INT NOT NULL, course_id INT NOT NULL, sort_order INT NOT NULL DEFAULT 0, UNIQUE KEY uq_learning_suite_course (suite_id, course_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO rescue_animal_orders (animal_order, sort_order) VALUES ('Mammal', 10), ('Bird', 20), ('Reptile', 30), ('Amphibian', 40), ('Other', 99);
INSERT IGNORE INTO rescue_animal_types (type_name, animal_order, sort_order) VALUES ('Bat', 'Mammal', 10), ('Mammal', 'Mammal', 20), ('Bird', 'Bird', 30), ('Reptile', 'Reptile', 40), ('Amphibian', 'Amphibian', 50), ('Other', 'Other', 99);
INSERT IGNORE INTO rescue_animal_species (species_name, scientific_name, animal_type, animal_order, species_weight_unit, species_measurement_unit) VALUES ('Unknown species', NULL, 'Other', 'Other', 'g', 'mm'), ('Common pipistrelle', 'Pipistrellus pipistrellus', 'Bat', 'Mammal', 'g', 'mm'), ('Soprano pipistrelle', 'Pipistrellus pygmaeus', 'Bat', 'Mammal', 'g', 'mm'), ('Brown long-eared bat', 'Plecotus auritus', 'Bat', 'Mammal', 'g', 'mm');
INSERT IGNORE INTO rescue_dispositions (disposition, final_status, sort_order) VALUES ('Held in captivity', 'Active', 10), ('Long-term Captive', 'Active', 20), ('Released', 'Closed', 30), ('Transferred Out', 'Closed', 40), ('Died - Euthanised', 'Closed', 50), ('Died - within 48 hours', 'Closed', 60), ('Died - after 48 hours', 'Closed', 70), ('Died - on admission', 'Closed', 80), ('Review', 'Review', 90);
INSERT IGNORE INTO rescue_labs_tests (lab_test) VALUES ('Microscopy'), ('Culture'), ('PCR'), ('Other');
INSERT IGNORE INTO rescue_sample_types (sample_type) VALUES ('Faeces'), ('Swab'), ('Blood'), ('Tissue'), ('Other');
INSERT IGNORE INTO rescue_frequencies (frequency, sort_order) VALUES ('Once daily', 10), ('Twice daily', 20), ('Three times daily', 30), ('As required', 90);
INSERT IGNORE INTO rescue_diet_items (name, type, category, default_unit) VALUES ('Mealworms', 'solid', 'invertebrate', 'g'), ('Formula', 'liquid', 'milk/formula', 'ml'), ('Water', 'liquid', 'water', 'ml');
INSERT IGNORE INTO rescue_partner_types (partner_type) VALUES ('Police'), ('RSPCA'), ('Vet'), ('Local Authority'), ('Other');

CREATE TABLE IF NOT EXISTS rescue_orgs (
    org_id INT AUTO_INCREMENT PRIMARY KEY,
    org_name VARCHAR(190) NOT NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(80) NULL,
    county VARCHAR(120) NULL,
    country VARCHAR(80) NULL,
    country_code CHAR(2) NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'Active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_orgs_status (status),
    INDEX idx_orgs_country (country_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_time_admission (
    time_admission_id INT AUTO_INCREMENT PRIMARY KEY,
    time_to_admission VARCHAR(120) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_time_to_admission (time_to_admission)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_words (
    word_id INT AUTO_INCREMENT PRIMARY KEY,
    word_key VARCHAR(120) NOT NULL,
    word_value VARCHAR(255) NOT NULL,
    word_group VARCHAR(120) NULL,
    language_code VARCHAR(10) NOT NULL DEFAULT 'en',
    active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_rescue_word (word_key, language_code),
    INDEX idx_rescue_words_group (word_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO rescue_time_admission (time_to_admission, sort_order) VALUES ('Immediate', 10), ('Within 1 hour', 20), ('1-4 hours', 30), ('4-12 hours', 40), ('12-24 hours', 50), ('Over 24 hours', 60), ('Unknown', 90);
