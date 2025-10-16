-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 16, 2025 at 08:06 PM
-- Server version: 10.11.14-MariaDB-cll-lve-log
-- PHP Version: 8.3.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `newsomew_wp191`
--
CREATE DATABASE IF NOT EXISTS `local_rescue` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;
USE `local_rescue`;

-- --------------------------------------------------------

--
-- Table structure for table `lat_search`
--

CREATE TABLE `lat_search` (
  `centre_id` int(8) NOT NULL,
  `query` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `network_cons`
--

CREATE TABLE `network_cons` (
  `net_con_id` int(8) NOT NULL,
  `centre_id` int(8) NOT NULL DEFAULT 0,
  `network_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `outcodepostcodes`
--

CREATE TABLE `outcodepostcodes` (
  `id` int(11) NOT NULL,
  `outcode` varchar(8) NOT NULL,
  `lat` decimal(10,7) NOT NULL,
  `lng` decimal(10,7) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `postcodelatlng`
--

CREATE TABLE `postcodelatlng` (
  `id` int(11) NOT NULL,
  `postcode` varchar(8) NOT NULL,
  `country_code` varchar(4) NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_admissions`
--

CREATE TABLE `rescue_admissions` (
  `admission_id` int(8) NOT NULL,
  `patient_id` int(8) NOT NULL,
  `admission_date` datetime NOT NULL,
  `age_on_admission` varchar(255) NOT NULL,
  `presenting_complaint` varchar(255) NOT NULL,
  `dehydrated` varchar(255) NOT NULL,
  `starved` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `collection_location` varchar(255) NOT NULL,
  `finder_id` int(11) NOT NULL,
  `finder_name` varchar(255) NOT NULL,
  `disposition` varchar(255) NOT NULL,
  `weight` int(8) NOT NULL,
  `weight_unit` varchar(255) NOT NULL,
  `measurement` int(8) NOT NULL,
  `measurement_unit` varchar(255) NOT NULL,
  `centre_id` int(8) NOT NULL,
  `staff_wp_id` int(8) NOT NULL,
  `time_to_admission` varchar(20) DEFAULT NULL,
  `date_created` datetime NOT NULL,
  `current_location` varchar(255) DEFAULT NULL,
  `owner_id` int(8) DEFAULT NULL,
  `transfer_id` int(8) DEFAULT 0,
  `survived` int(1) DEFAULT 0,
  `w_temp` int(8) DEFAULT 0,
  `w_wind` int(8) DEFAULT 0,
  `w_humidity` int(4) DEFAULT 0,
  `w_freetext` varchar(255) DEFAULT NULL,
  `severity_score` int(2) DEFAULT 99,
  `finder_tel` varchar(11) DEFAULT NULL,
  `disposition_date` datetime DEFAULT NULL,
  `consent_to_update` int(1) DEFAULT 0,
  `disposition_user` int(8) DEFAULT NULL,
  `disposition_centre` int(8) DEFAULT NULL,
  `disposition_comment` longtext DEFAULT NULL,
  `euthanasia_method` varchar(255) DEFAULT NULL,
  `hpc` longtext DEFAULT NULL,
  `on_examination` longtext DEFAULT NULL,
  `ss_text` varchar(255) DEFAULT NULL,
  `bc_score` int(2) DEFAULT 99,
  `bcs_text` varchar(255) DEFAULT NULL,
  `species_score` int(2) DEFAULT 99,
  `age_score` int(2) DEFAULT 99,
  `location_lat` varchar(18) DEFAULT NULL,
  `location_long` varchar(18) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_alerts`
--

CREATE TABLE `rescue_alerts` (
  `alert_id` int(8) NOT NULL,
  `alert_message` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `alert_type` varchar(255) DEFAULT NULL,
  `is_closed` varchar(255) DEFAULT NULL,
  `centre_id` int(8) DEFAULT 0,
  `patient_id` int(8) DEFAULT 0,
  `is_deleted` int(1) DEFAULT 0,
  `is_active` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_animal_orders`
--

CREATE TABLE `rescue_animal_orders` (
  `order_id` int(8) NOT NULL,
  `order_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_animal_species`
--

CREATE TABLE `rescue_animal_species` (
  `species_id` int(8) NOT NULL,
  `species_name` varchar(255) NOT NULL,
  `scientific_name` varchar(255) NOT NULL,
  `animal_type` varchar(255) NOT NULL,
  `species_weight_from` decimal(8,2) NOT NULL,
  `species_weight_to` decimal(8,2) NOT NULL,
  `species_weight_unit` text NOT NULL,
  `species_measurement_from` decimal(8,2) NOT NULL,
  `species_measurement_to` decimal(8,2) NOT NULL,
  `species_measurement_unit` text NOT NULL,
  `reference` text NOT NULL,
  `species_measurement_standard` text DEFAULT NULL,
  `iucn_status` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_animal_types`
--

CREATE TABLE `rescue_animal_types` (
  `type_id` int(11) NOT NULL,
  `type_name` varchar(255) NOT NULL,
  `animal_order` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_areas`
--

CREATE TABLE `rescue_areas` (
  `area_id` int(8) NOT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `area_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_centres`
--

CREATE TABLE `rescue_centres` (
  `rescue_id` int(8) NOT NULL,
  `rescue_name` varchar(255) NOT NULL,
  `owner_id` int(8) NOT NULL,
  `centre_type` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `office_tel` varchar(255) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `24_hour` varchar(255) NOT NULL,
  `address_line_one` varchar(255) NOT NULL,
  `address_line_two` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `postcode` varchar(255) NOT NULL,
  `coordinates` varchar(255) NOT NULL,
  `accepting_admissions` varchar(255) NOT NULL,
  `closed_message` varchar(255) NOT NULL,
  `species_accepted` varchar(255) NOT NULL,
  `opening_hours` text NOT NULL,
  `ngo_parameter` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_connections`
--

CREATE TABLE `rescue_connections` (
  `connection_id` int(255) NOT NULL,
  `to_centre` int(8) DEFAULT NULL,
  `approved` varchar(5) DEFAULT NULL,
  `from_centre` int(8) DEFAULT NULL,
  `centre_id` int(8) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_dose_size`
--

CREATE TABLE `rescue_dose_size` (
  `dose_id` int(8) NOT NULL,
  `dose_name` varchar(255) DEFAULT NULL,
  `dose_short_name` varchar(3) DEFAULT NULL,
  `dose_multiplier` float DEFAULT NULL,
  `dose_divider` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_finders`
--

CREATE TABLE `rescue_finders` (
  `finder_id` int(8) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `tel` varchar(255) NOT NULL,
  `update_preference` varchar(255) NOT NULL,
  `update_frequency` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_frequencies`
--

CREATE TABLE `rescue_frequencies` (
  `frequency_id` int(8) NOT NULL,
  `frequency` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_frequency_times`
--

CREATE TABLE `rescue_frequency_times` (
  `frequency_time_id` int(8) NOT NULL,
  `frequency_id` int(11) DEFAULT NULL,
  `time` time DEFAULT NULL,
  `frequency_name` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_incidents`
--

CREATE TABLE `rescue_incidents` (
  `incident_id` int(8) NOT NULL,
  `incident_date` date DEFAULT NULL,
  `incident_location_line_1` varchar(255) DEFAULT NULL,
  `incident_location_line_2` varchar(255) DEFAULT NULL,
  `incident_location_city` varchar(255) DEFAULT NULL,
  `incident_location_postcode` varchar(255) DEFAULT NULL,
  `incident_centre_ref` varchar(255) DEFAULT NULL,
  `incident_total_casualties` int(8) DEFAULT NULL,
  `incident_doa` int(8) DEFAULT NULL,
  `incident_mass_cas` int(1) DEFAULT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `user_id` int(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_incident_related`
--

CREATE TABLE `rescue_incident_related` (
  `inc_rel_id` int(8) NOT NULL,
  `incident_id` int(8) DEFAULT NULL,
  `partner_id` int(8) DEFAULT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `admission_id` int(8) DEFAULT NULL,
  `user_id` int(8) DEFAULT NULL,
  `is_deleted` int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_injury_record`
--

CREATE TABLE `rescue_injury_record` (
  `injury_id` int(8) NOT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `patient_id` int(8) DEFAULT NULL,
  `admission_id` int(8) DEFAULT NULL,
  `injury_site` varchar(255) DEFAULT NULL,
  `injury_type` varchar(255) DEFAULT NULL,
  `injury_notes` longtext DEFAULT NULL,
  `triage_id` int(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_labs`
--

CREATE TABLE `rescue_labs` (
  `lab_id` int(8) NOT NULL,
  `lab_date` datetime DEFAULT NULL,
  `sample_type` int(8) DEFAULT NULL,
  `lab_result` varchar(255) DEFAULT NULL,
  `reported_by` varchar(255) DEFAULT NULL,
  `admission_id` int(8) DEFAULT NULL,
  `patient_id` int(8) DEFAULT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `lab_test` int(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_labs_tests`
--

CREATE TABLE `rescue_labs_tests` (
  `l_test_id` int(8) NOT NULL,
  `lab_test` varchar(255) DEFAULT NULL,
  `lab_category` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_locations`
--

CREATE TABLE `rescue_locations` (
  `location_id` int(11) NOT NULL,
  `centre_id` int(11) NOT NULL,
  `location_name` varchar(255) NOT NULL,
  `location_type` varchar(255) NOT NULL,
  `max_occupancy` float DEFAULT NULL,
  `deleted` int(1) DEFAULT 0,
  `location_area` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_measurements`
--

CREATE TABLE `rescue_measurements` (
  `weight_id` int(8) NOT NULL,
  `patient_id` int(8) NOT NULL,
  `date` datetime NOT NULL,
  `measurement` decimal(8,2) NOT NULL,
  `measurement_unit` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_medications`
--

CREATE TABLE `rescue_medications` (
  `medication_id` int(8) NOT NULL,
  `medication_name` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `common_name` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `contraindications` longtext DEFAULT NULL,
  `cautions` longtext DEFAULT NULL,
  `dose` varchar(255) DEFAULT NULL,
  `side_effects` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_medications_given`
--

CREATE TABLE `rescue_medications_given` (
  `med_adm_id` int(8) NOT NULL,
  `patient_id` int(8) NOT NULL,
  `medication_given` text NOT NULL,
  `dose` float(8,2) NOT NULL,
  `date` datetime NOT NULL,
  `centre_id` int(8) NOT NULL,
  `given_by` varchar(255) DEFAULT NULL,
  `dose_type` varchar(255) DEFAULT NULL,
  `stock_item_used` int(8) DEFAULT 0,
  `given_by_id` int(8) DEFAULT NULL,
  `vol_given` int(8) DEFAULT NULL,
  `batch_given` varchar(255) DEFAULT NULL,
  `exp_given` date DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='this table is used to record admin of medication';

-- --------------------------------------------------------

--
-- Table structure for table `rescue_medication_trans`
--

CREATE TABLE `rescue_medication_trans` (
  `med_trans_id` int(8) NOT NULL,
  `date` date DEFAULT NULL,
  `med_profile_id` int(8) DEFAULT NULL,
  `packs_in` float(8,2) DEFAULT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `user_id` int(8) DEFAULT NULL,
  `batch_number` varchar(20) DEFAULT NULL,
  `expiry` date DEFAULT NULL,
  `date_opened` date DEFAULT NULL,
  `date_finished` date DEFAULT NULL,
  `est_volume` int(4) DEFAULT NULL,
  `amount_destroyed` int(4) DEFAULT NULL,
  `reason_destroyed` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_messages`
--

CREATE TABLE `rescue_messages` (
  `message_id` int(8) NOT NULL,
  `from_centre` int(8) DEFAULT NULL,
  `to_centre` int(8) DEFAULT NULL,
  `message_sent` datetime DEFAULT NULL,
  `message` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_month_data`
--

CREATE TABLE `rescue_month_data` (
  `month_id` int(8) NOT NULL,
  `month_name` varchar(12) DEFAULT NULL,
  `month` date DEFAULT NULL,
  `count` int(1) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_networks`
--

CREATE TABLE `rescue_networks` (
  `network_id` int(11) NOT NULL,
  `network_name` varchar(255) DEFAULT NULL,
  `network_description` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_net_chat`
--

CREATE TABLE `rescue_net_chat` (
  `chat_id` int(10) NOT NULL,
  `from_centre` int(8) DEFAULT NULL,
  `chat_sent` datetime DEFAULT NULL,
  `chat` longtext DEFAULT NULL,
  `chat_user` varchar(255) DEFAULT NULL,
  `network_id` int(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_notes_patients`
--

CREATE TABLE `rescue_notes_patients` (
  `note_id` int(8) NOT NULL,
  `patient_id` int(8) NOT NULL,
  `message` text NOT NULL,
  `author` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `deleted` int(8) NOT NULL,
  `public` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_orgs`
--

CREATE TABLE `rescue_orgs` (
  `org_id` int(8) NOT NULL,
  `org_name` varchar(255) DEFAULT NULL,
  `org_address` mediumtext DEFAULT NULL,
  `org_valid_until` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_partner_log`
--

CREATE TABLE `rescue_partner_log` (
  `p_log_id` int(8) NOT NULL,
  `partner_type` int(8) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `log_number` varchar(255) DEFAULT NULL,
  `log_notes` longtext DEFAULT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `patient_id` int(8) DEFAULT NULL,
  `user_id` int(8) DEFAULT NULL,
  `admission_id` int(8) DEFAULT NULL,
  `is_crime` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_partner_types`
--

CREATE TABLE `rescue_partner_types` (
  `p_type_id` int(8) NOT NULL,
  `partner_type` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_patients`
--

CREATE TABLE `rescue_patients` (
  `patient_id` int(8) NOT NULL,
  `name` varchar(255) NOT NULL,
  `ringed` varchar(255) NOT NULL,
  `ring_number` varchar(255) NOT NULL,
  `microchipped` varchar(255) NOT NULL,
  `microchip_number` varchar(255) NOT NULL,
  `animal_type` varchar(255) NOT NULL,
  `animal_order` varchar(255) NOT NULL,
  `animal_species` varchar(255) NOT NULL,
  `sex` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `staff_wp_id` int(8) NOT NULL,
  `centre_id` int(8) NOT NULL,
  `date_added` datetime NOT NULL,
  `species_id` int(8) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `transfer_id` int(8) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_prescriptions`
--

CREATE TABLE `rescue_prescriptions` (
  `prescription_id` int(8) NOT NULL,
  `patient_id` int(8) DEFAULT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `admission_id` int(8) DEFAULT NULL,
  `medication` varchar(255) DEFAULT NULL,
  `dose` float(8,2) DEFAULT NULL,
  `dose_type` varchar(255) DEFAULT NULL,
  `duration` float DEFAULT NULL,
  `frequency` varchar(255) DEFAULT NULL,
  `frequency_id` int(8) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `route` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_presenting_complaints`
--

CREATE TABLE `rescue_presenting_complaints` (
  `pc_id` int(8) NOT NULL,
  `prsenting_complaint` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_query`
--

CREATE TABLE `rescue_query` (
  `q_id` int(8) NOT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `q_from` date DEFAULT NULL,
  `q_to` date DEFAULT NULL,
  `q_date` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_roles`
--

CREATE TABLE `rescue_roles` (
  `role_id` int(8) NOT NULL,
  `role_name` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_sample_types`
--

CREATE TABLE `rescue_sample_types` (
  `s_type_id` int(8) NOT NULL,
  `sample_type` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_search_user`
--

CREATE TABLE `rescue_search_user` (
  `search_email` varchar(255) DEFAULT NULL,
  `centre_id` int(8) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_severity_score`
--

CREATE TABLE `rescue_severity_score` (
  `ss_id` int(8) NOT NULL,
  `ss_category` varchar(255) DEFAULT NULL,
  `ss_description` varchar(255) DEFAULT NULL,
  `ss_value` int(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_stock_medication`
--

CREATE TABLE `rescue_stock_medication` (
  `medication_profile_id` int(8) NOT NULL,
  `medication` int(255) DEFAULT NULL,
  `concentration_dose` float(8,2) DEFAULT NULL,
  `concentration_volume` float(8,2) DEFAULT NULL,
  `pack_quantity` float(8,2) DEFAULT NULL,
  `reorder_level` float(8,2) DEFAULT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `user_id` int(8) DEFAULT NULL,
  `concentration_dose_type` varchar(5) DEFAULT NULL,
  `concentration_volume_type` varchar(12) DEFAULT NULL,
  `use_within` int(3) DEFAULT NULL,
  `mgml` float(8,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_treatments`
--

CREATE TABLE `rescue_treatments` (
  `treatment_given_id` int(8) NOT NULL,
  `patient_id` int(8) DEFAULT NULL,
  `treatment` varchar(255) DEFAULT NULL,
  `treatment_free_text` varchar(255) DEFAULT NULL,
  `done_by` varchar(255) DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_triages`
--

CREATE TABLE `rescue_triages` (
  `triage_id` int(8) NOT NULL,
  `admission_id` int(8) DEFAULT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `patient_id` int(8) DEFAULT NULL,
  `triage_date` datetime DEFAULT NULL,
  `care_form_used` varchar(255) DEFAULT NULL,
  `hpc` longtext DEFAULT NULL,
  `on_examination` longtext DEFAULT NULL,
  `age` int(1) DEFAULT NULL,
  `class` int(1) DEFAULT NULL,
  `ss` int(1) DEFAULT NULL,
  `bcs` int(1) DEFAULT NULL,
  `recorded_by` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_weights`
--

CREATE TABLE `rescue_weights` (
  `weight_id` int(8) NOT NULL,
  `patient_id` int(8) NOT NULL,
  `date` datetime NOT NULL,
  `weight` decimal(8,2) NOT NULL DEFAULT 0.00,
  `weight_unit` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_words`
--

CREATE TABLE `rescue_words` (
  `word_1` varchar(255) DEFAULT NULL,
  `word_2` varchar(255) DEFAULT NULL,
  `word_3` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_actionscheduler_actions`
--

CREATE TABLE `wpxp_actionscheduler_actions` (
  `action_id` bigint(20) UNSIGNED NOT NULL,
  `hook` varchar(191) NOT NULL,
  `status` varchar(20) NOT NULL,
  `scheduled_date_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  `scheduled_date_local` datetime DEFAULT '0000-00-00 00:00:00',
  `args` varchar(191) DEFAULT NULL,
  `schedule` longtext DEFAULT NULL,
  `group_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `last_attempt_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  `last_attempt_local` datetime DEFAULT '0000-00-00 00:00:00',
  `claim_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `extended_args` varchar(8000) DEFAULT NULL,
  `priority` tinyint(3) UNSIGNED NOT NULL DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_actionscheduler_claims`
--

CREATE TABLE `wpxp_actionscheduler_claims` (
  `claim_id` bigint(20) UNSIGNED NOT NULL,
  `date_created_gmt` datetime DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_actionscheduler_groups`
--

CREATE TABLE `wpxp_actionscheduler_groups` (
  `group_id` bigint(20) UNSIGNED NOT NULL,
  `slug` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_actionscheduler_logs`
--

CREATE TABLE `wpxp_actionscheduler_logs` (
  `log_id` bigint(20) UNSIGNED NOT NULL,
  `action_id` bigint(20) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `log_date_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  `log_date_local` datetime DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_commentmeta`
--

CREATE TABLE `wpxp_commentmeta` (
  `meta_id` bigint(20) UNSIGNED NOT NULL,
  `comment_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_comments`
--

CREATE TABLE `wpxp_comments` (
  `comment_ID` bigint(20) UNSIGNED NOT NULL,
  `comment_post_ID` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `comment_author` tinytext NOT NULL,
  `comment_author_email` varchar(100) NOT NULL DEFAULT '',
  `comment_author_url` varchar(200) NOT NULL DEFAULT '',
  `comment_author_IP` varchar(100) NOT NULL DEFAULT '',
  `comment_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_content` text NOT NULL,
  `comment_karma` int(11) NOT NULL DEFAULT 0,
  `comment_approved` varchar(20) NOT NULL DEFAULT '1',
  `comment_agent` varchar(255) NOT NULL DEFAULT '',
  `comment_type` varchar(20) NOT NULL DEFAULT 'comment',
  `comment_parent` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_connections`
--

CREATE TABLE `wpxp_connections` (
  `id` bigint(20) NOT NULL,
  `ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `date_added` tinytext NOT NULL,
  `ordo` int(11) NOT NULL DEFAULT 0,
  `entry_type` tinytext NOT NULL,
  `visibility` tinytext NOT NULL,
  `slug` varchar(255) NOT NULL DEFAULT '',
  `family_name` tinytext NOT NULL,
  `honorific_prefix` tinytext NOT NULL,
  `first_name` tinytext NOT NULL,
  `middle_name` tinytext NOT NULL,
  `last_name` tinytext NOT NULL,
  `honorific_suffix` tinytext NOT NULL,
  `title` tinytext NOT NULL,
  `organization` tinytext NOT NULL,
  `department` tinytext NOT NULL,
  `contact_first_name` tinytext NOT NULL,
  `contact_last_name` tinytext NOT NULL,
  `addresses` longtext NOT NULL,
  `phone_numbers` longtext NOT NULL,
  `email` longtext NOT NULL,
  `im` longtext NOT NULL,
  `social` longtext NOT NULL,
  `links` longtext NOT NULL,
  `dates` longtext NOT NULL,
  `birthday` tinytext NOT NULL,
  `anniversary` tinytext NOT NULL,
  `bio` longtext NOT NULL,
  `notes` longtext NOT NULL,
  `excerpt` text NOT NULL,
  `options` longtext NOT NULL,
  `added_by` bigint(20) NOT NULL,
  `edited_by` bigint(20) NOT NULL,
  `owner` bigint(20) NOT NULL,
  `user` bigint(20) NOT NULL,
  `status` varchar(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_connections_address`
--

CREATE TABLE `wpxp_connections_address` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `entry_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `order` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `preferred` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `type` tinytext NOT NULL,
  `line_1` tinytext NOT NULL,
  `line_2` tinytext NOT NULL,
  `line_3` tinytext NOT NULL,
  `line_4` tinytext NOT NULL,
  `district` tinytext NOT NULL,
  `county` tinytext NOT NULL,
  `city` tinytext NOT NULL,
  `state` tinytext NOT NULL,
  `zipcode` tinytext NOT NULL,
  `country` tinytext NOT NULL,
  `latitude` decimal(15,12) DEFAULT NULL,
  `longitude` decimal(15,12) DEFAULT NULL,
  `visibility` tinytext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_connections_date`
--

CREATE TABLE `wpxp_connections_date` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `entry_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `order` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `preferred` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `type` tinytext NOT NULL,
  `date` date NOT NULL DEFAULT '0000-00-00',
  `visibility` tinytext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_connections_email`
--

CREATE TABLE `wpxp_connections_email` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `entry_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `order` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `preferred` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `type` tinytext NOT NULL,
  `address` tinytext NOT NULL,
  `visibility` tinytext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_connections_link`
--

CREATE TABLE `wpxp_connections_link` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `entry_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `order` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `preferred` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `type` tinytext NOT NULL,
  `title` tinytext NOT NULL,
  `url` tinytext NOT NULL,
  `target` tinytext NOT NULL,
  `follow` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `image` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `logo` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `visibility` tinytext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_connections_messenger`
--

CREATE TABLE `wpxp_connections_messenger` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `entry_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `order` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `preferred` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `type` tinytext NOT NULL,
  `uid` tinytext NOT NULL,
  `visibility` tinytext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_connections_meta`
--

CREATE TABLE `wpxp_connections_meta` (
  `meta_id` bigint(20) UNSIGNED NOT NULL,
  `entry_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_connections_phone`
--

CREATE TABLE `wpxp_connections_phone` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `entry_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `order` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `preferred` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `type` tinytext NOT NULL,
  `number` tinytext NOT NULL,
  `visibility` tinytext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_connections_social`
--

CREATE TABLE `wpxp_connections_social` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `entry_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `order` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `preferred` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `type` tinytext NOT NULL,
  `url` tinytext NOT NULL,
  `visibility` tinytext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_connections_terms`
--

CREATE TABLE `wpxp_connections_terms` (
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL DEFAULT '',
  `slug` varchar(200) NOT NULL DEFAULT '',
  `term_group` bigint(10) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_connections_term_meta`
--

CREATE TABLE `wpxp_connections_term_meta` (
  `meta_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_connections_term_relationships`
--

CREATE TABLE `wpxp_connections_term_relationships` (
  `entry_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `term_taxonomy_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `term_order` int(11) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_connections_term_taxonomy`
--

CREATE TABLE `wpxp_connections_term_taxonomy` (
  `term_taxonomy_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) NOT NULL DEFAULT 0,
  `taxonomy` varchar(32) NOT NULL DEFAULT '',
  `description` longtext NOT NULL,
  `parent` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `count` bigint(20) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_e_events`
--

CREATE TABLE `wpxp_e_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `event_data` text DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_e_notes`
--

CREATE TABLE `wpxp_e_notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `route_url` text DEFAULT NULL COMMENT 'Clean url where the note was created.',
  `route_title` varchar(255) DEFAULT NULL,
  `route_post_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'The post id of the route that the note was created on.',
  `post_id` bigint(20) UNSIGNED DEFAULT NULL,
  `element_id` varchar(60) DEFAULT NULL COMMENT 'The Elementor element ID the note is attached to.',
  `parent_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `author_id` bigint(20) UNSIGNED DEFAULT NULL,
  `author_display_name` varchar(250) DEFAULT NULL COMMENT 'Save the author name when the author was deleted.',
  `status` varchar(20) NOT NULL DEFAULT 'publish',
  `position` text DEFAULT NULL COMMENT 'A JSON string that represents the position of the note inside the element in percentages. e.g. {x:10, y:15}',
  `content` longtext DEFAULT NULL,
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `last_activity_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_e_notes_users_relations`
--

CREATE TABLE `wpxp_e_notes_users_relations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(60) NOT NULL COMMENT 'The relation type between user and note (e.g mention, watch, read).',
  `note_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_e_submissions`
--

CREATE TABLE `wpxp_e_submissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(60) DEFAULT NULL,
  `hash_id` varchar(60) NOT NULL,
  `main_meta_id` bigint(20) UNSIGNED NOT NULL COMMENT 'Id of main field. to represent the main meta field',
  `post_id` bigint(20) UNSIGNED NOT NULL,
  `referer` varchar(500) NOT NULL,
  `referer_title` varchar(300) DEFAULT NULL,
  `element_id` varchar(20) NOT NULL,
  `form_name` varchar(60) NOT NULL,
  `campaign_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_ip` varchar(46) NOT NULL,
  `user_agent` text NOT NULL,
  `actions_count` int(11) DEFAULT 0,
  `actions_succeeded_count` int(11) DEFAULT 0,
  `status` varchar(20) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `meta` text DEFAULT NULL,
  `created_at_gmt` datetime NOT NULL,
  `updated_at_gmt` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_e_submissions_actions_log`
--

CREATE TABLE `wpxp_e_submissions_actions_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `submission_id` bigint(20) UNSIGNED NOT NULL,
  `action_name` varchar(60) NOT NULL,
  `action_label` varchar(60) DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `log` text DEFAULT NULL,
  `created_at_gmt` datetime NOT NULL,
  `updated_at_gmt` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_e_submissions_values`
--

CREATE TABLE `wpxp_e_submissions_values` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `submission_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `key` varchar(60) DEFAULT NULL,
  `value` longtext DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_links`
--

CREATE TABLE `wpxp_links` (
  `link_id` bigint(20) UNSIGNED NOT NULL,
  `link_url` varchar(255) NOT NULL DEFAULT '',
  `link_name` varchar(255) NOT NULL DEFAULT '',
  `link_image` varchar(255) NOT NULL DEFAULT '',
  `link_target` varchar(25) NOT NULL DEFAULT '',
  `link_description` varchar(255) NOT NULL DEFAULT '',
  `link_visible` varchar(20) NOT NULL DEFAULT 'Y',
  `link_owner` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
  `link_rating` int(11) NOT NULL DEFAULT 0,
  `link_updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `link_rel` varchar(255) NOT NULL DEFAULT '',
  `link_notes` mediumtext NOT NULL,
  `link_rss` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_loginizer_logs`
--

CREATE TABLE `wpxp_loginizer_logs` (
  `username` varchar(255) NOT NULL DEFAULT '',
  `time` int(10) NOT NULL DEFAULT 0,
  `count` int(10) NOT NULL DEFAULT 0,
  `lockout` int(10) NOT NULL DEFAULT 0,
  `ip` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_login_log`
--

CREATE TABLE `wpxp_login_log` (
  `id` int(11) NOT NULL,
  `ip` varchar(50) NOT NULL,
  `msg` varchar(255) NOT NULL,
  `l_added` datetime NOT NULL,
  `l_status` enum('success','failed','blocked') NOT NULL,
  `l_type` enum('new','old') NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_moodle_enrollment`
--

CREATE TABLE `wpxp_moodle_enrollment` (
  `id` mediumint(9) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `expire_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `act_cnt` int(5) NOT NULL DEFAULT 1,
  `suspended` int(5) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_options`
--

CREATE TABLE `wpxp_options` (
  `option_id` bigint(20) UNSIGNED NOT NULL,
  `option_name` varchar(191) NOT NULL DEFAULT '',
  `option_value` longtext NOT NULL,
  `autoload` varchar(20) NOT NULL DEFAULT 'yes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_pmpro_discount_codes`
--

CREATE TABLE `wpxp_pmpro_discount_codes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(32) NOT NULL,
  `starts` date NOT NULL,
  `expires` date NOT NULL,
  `uses` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_pmpro_discount_codes_levels`
--

CREATE TABLE `wpxp_pmpro_discount_codes_levels` (
  `code_id` bigint(20) UNSIGNED NOT NULL,
  `level_id` int(11) UNSIGNED NOT NULL,
  `initial_payment` decimal(18,8) NOT NULL DEFAULT 0.00000000,
  `billing_amount` decimal(18,8) NOT NULL DEFAULT 0.00000000,
  `cycle_number` int(11) NOT NULL DEFAULT 0,
  `cycle_period` enum('Day','Week','Month','Year') DEFAULT 'Month',
  `billing_limit` int(11) NOT NULL COMMENT 'After how many cycles should billing stop?',
  `trial_amount` decimal(18,8) NOT NULL DEFAULT 0.00000000,
  `trial_limit` int(11) NOT NULL DEFAULT 0,
  `expiration_number` int(10) UNSIGNED NOT NULL,
  `expiration_period` enum('Hour','Day','Week','Month','Year') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_pmpro_discount_codes_uses`
--

CREATE TABLE `wpxp_pmpro_discount_codes_uses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_pmpro_memberships_categories`
--

CREATE TABLE `wpxp_pmpro_memberships_categories` (
  `membership_id` int(11) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_pmpro_memberships_pages`
--

CREATE TABLE `wpxp_pmpro_memberships_pages` (
  `membership_id` int(11) UNSIGNED NOT NULL,
  `page_id` bigint(20) UNSIGNED NOT NULL,
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_pmpro_memberships_users`
--

CREATE TABLE `wpxp_pmpro_memberships_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `membership_id` int(11) UNSIGNED NOT NULL,
  `code_id` bigint(20) UNSIGNED NOT NULL,
  `initial_payment` decimal(18,8) NOT NULL,
  `billing_amount` decimal(18,8) NOT NULL,
  `cycle_number` int(11) NOT NULL,
  `cycle_period` enum('Day','Week','Month','Year') NOT NULL DEFAULT 'Month',
  `billing_limit` int(11) NOT NULL,
  `trial_amount` decimal(18,8) NOT NULL,
  `trial_limit` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `startdate` datetime NOT NULL,
  `enddate` datetime DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_pmpro_membership_levelmeta`
--

CREATE TABLE `wpxp_pmpro_membership_levelmeta` (
  `meta_id` bigint(20) UNSIGNED NOT NULL,
  `pmpro_membership_level_id` int(11) UNSIGNED NOT NULL,
  `meta_key` varchar(255) NOT NULL,
  `meta_value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_pmpro_membership_levels`
--

CREATE TABLE `wpxp_pmpro_membership_levels` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `confirmation` longtext NOT NULL,
  `initial_payment` decimal(18,8) NOT NULL DEFAULT 0.00000000,
  `billing_amount` decimal(18,8) NOT NULL DEFAULT 0.00000000,
  `cycle_number` int(11) NOT NULL DEFAULT 0,
  `cycle_period` enum('Day','Week','Month','Year') DEFAULT 'Month',
  `billing_limit` int(11) NOT NULL COMMENT 'After how many cycles should billing stop?',
  `trial_amount` decimal(18,8) NOT NULL DEFAULT 0.00000000,
  `trial_limit` int(11) NOT NULL DEFAULT 0,
  `allow_signups` tinyint(4) NOT NULL DEFAULT 1,
  `expiration_number` int(10) UNSIGNED NOT NULL,
  `expiration_period` enum('Hour','Day','Week','Month','Year') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_pmpro_membership_ordermeta`
--

CREATE TABLE `wpxp_pmpro_membership_ordermeta` (
  `meta_id` bigint(20) UNSIGNED NOT NULL,
  `pmpro_membership_order_id` int(11) UNSIGNED NOT NULL,
  `meta_key` varchar(255) NOT NULL,
  `meta_value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_pmpro_membership_orders`
--

CREATE TABLE `wpxp_pmpro_membership_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(32) NOT NULL,
  `session_id` varchar(64) NOT NULL DEFAULT '',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `membership_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `paypal_token` varchar(64) NOT NULL DEFAULT '',
  `billing_name` varchar(128) NOT NULL DEFAULT '',
  `billing_street` varchar(128) NOT NULL DEFAULT '',
  `billing_city` varchar(128) NOT NULL DEFAULT '',
  `billing_state` varchar(32) NOT NULL DEFAULT '',
  `billing_zip` varchar(16) NOT NULL DEFAULT '',
  `billing_country` varchar(128) NOT NULL,
  `billing_phone` varchar(32) NOT NULL,
  `subtotal` varchar(16) NOT NULL DEFAULT '',
  `tax` varchar(16) NOT NULL DEFAULT '',
  `couponamount` varchar(16) NOT NULL DEFAULT '',
  `checkout_id` bigint(20) NOT NULL DEFAULT 0,
  `certificate_id` int(11) NOT NULL DEFAULT 0,
  `certificateamount` varchar(16) NOT NULL DEFAULT '',
  `total` varchar(16) NOT NULL DEFAULT '',
  `payment_type` varchar(64) NOT NULL DEFAULT '',
  `cardtype` varchar(32) NOT NULL DEFAULT '',
  `accountnumber` varchar(32) NOT NULL DEFAULT '',
  `expirationmonth` char(2) NOT NULL DEFAULT '',
  `expirationyear` varchar(4) NOT NULL DEFAULT '',
  `status` varchar(32) NOT NULL DEFAULT '',
  `gateway` varchar(64) NOT NULL,
  `gateway_environment` varchar(64) NOT NULL,
  `payment_transaction_id` varchar(64) NOT NULL,
  `subscription_transaction_id` varchar(32) NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `affiliate_id` varchar(32) NOT NULL,
  `affiliate_subid` varchar(32) NOT NULL,
  `notes` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_pmxi_files`
--

CREATE TABLE `wpxp_pmxi_files` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `import_id` bigint(20) UNSIGNED NOT NULL,
  `name` text DEFAULT NULL,
  `path` text DEFAULT NULL,
  `registered_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_pmxi_hash`
--

CREATE TABLE `wpxp_pmxi_hash` (
  `hash` binary(16) NOT NULL,
  `post_id` bigint(20) UNSIGNED NOT NULL,
  `import_id` smallint(5) UNSIGNED NOT NULL,
  `post_type` varchar(32) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_pmxi_history`
--

CREATE TABLE `wpxp_pmxi_history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `import_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('manual','processing','trigger','continue','cli','') NOT NULL DEFAULT '',
  `time_run` text DEFAULT NULL,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `summary` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_pmxi_images`
--

CREATE TABLE `wpxp_pmxi_images` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `attachment_id` bigint(20) UNSIGNED NOT NULL,
  `image_url` varchar(900) NOT NULL DEFAULT '',
  `image_filename` varchar(900) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_pmxi_imports`
--

CREATE TABLE `wpxp_pmxi_imports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parent_import_id` bigint(20) NOT NULL DEFAULT 0,
  `name` text DEFAULT NULL,
  `friendly_name` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(32) NOT NULL DEFAULT '',
  `feed_type` enum('xml','csv','zip','gz','') NOT NULL DEFAULT '',
  `path` text DEFAULT NULL,
  `xpath` text DEFAULT NULL,
  `options` longtext DEFAULT NULL,
  `registered_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `root_element` varchar(255) DEFAULT '',
  `processing` tinyint(1) NOT NULL DEFAULT 0,
  `executing` tinyint(1) NOT NULL DEFAULT 0,
  `triggered` tinyint(1) NOT NULL DEFAULT 0,
  `queue_chunk_number` bigint(20) NOT NULL DEFAULT 0,
  `first_import` timestamp NOT NULL DEFAULT current_timestamp(),
  `count` bigint(20) NOT NULL DEFAULT 0,
  `imported` bigint(20) NOT NULL DEFAULT 0,
  `created` bigint(20) NOT NULL DEFAULT 0,
  `updated` bigint(20) NOT NULL DEFAULT 0,
  `skipped` bigint(20) NOT NULL DEFAULT 0,
  `deleted` bigint(20) NOT NULL DEFAULT 0,
  `canceled` tinyint(1) NOT NULL DEFAULT 0,
  `canceled_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `failed` tinyint(1) NOT NULL DEFAULT 0,
  `failed_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `settings_update_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_activity` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `iteration` bigint(20) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_pmxi_posts`
--

CREATE TABLE `wpxp_pmxi_posts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `post_id` bigint(20) UNSIGNED NOT NULL,
  `import_id` bigint(20) UNSIGNED NOT NULL,
  `unique_key` text DEFAULT NULL,
  `product_key` text DEFAULT NULL,
  `iteration` bigint(20) NOT NULL DEFAULT 0,
  `specified` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_pmxi_templates`
--

CREATE TABLE `wpxp_pmxi_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `options` longtext DEFAULT NULL,
  `scheduled` varchar(64) NOT NULL DEFAULT '',
  `name` varchar(200) NOT NULL DEFAULT '',
  `title` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `is_keep_linebreaks` tinyint(1) NOT NULL DEFAULT 0,
  `is_leave_html` tinyint(1) NOT NULL DEFAULT 0,
  `fix_characters` tinyint(1) NOT NULL DEFAULT 0,
  `meta` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_podsrel`
--

CREATE TABLE `wpxp_podsrel` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pod_id` int(10) UNSIGNED DEFAULT NULL,
  `field_id` int(10) UNSIGNED DEFAULT NULL,
  `item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `related_pod_id` int(10) UNSIGNED DEFAULT NULL,
  `related_field_id` int(10) UNSIGNED DEFAULT NULL,
  `related_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `weight` smallint(5) UNSIGNED DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_postmeta`
--

CREATE TABLE `wpxp_postmeta` (
  `meta_id` bigint(20) UNSIGNED NOT NULL,
  `post_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_posts`
--

CREATE TABLE `wpxp_posts` (
  `ID` bigint(20) UNSIGNED NOT NULL,
  `post_author` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `post_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content` longtext NOT NULL,
  `post_title` text NOT NULL,
  `post_excerpt` text NOT NULL,
  `post_status` varchar(20) NOT NULL DEFAULT 'publish',
  `comment_status` varchar(20) NOT NULL DEFAULT 'open',
  `ping_status` varchar(20) NOT NULL DEFAULT 'open',
  `post_password` varchar(255) NOT NULL DEFAULT '',
  `post_name` varchar(200) NOT NULL DEFAULT '',
  `to_ping` text NOT NULL,
  `pinged` text NOT NULL,
  `post_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_modified_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content_filtered` longtext NOT NULL,
  `post_parent` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `guid` varchar(255) NOT NULL DEFAULT '',
  `menu_order` int(11) NOT NULL DEFAULT 0,
  `post_type` varchar(20) NOT NULL DEFAULT 'post',
  `post_mime_type` varchar(100) NOT NULL DEFAULT '',
  `comment_count` bigint(20) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_promag_email_templates`
--

CREATE TABLE `wpxp_promag_email_templates` (
  `id` int(11) NOT NULL,
  `tmpl_name` varchar(600) NOT NULL,
  `email_subject` varchar(255) NOT NULL,
  `email_body` longtext DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_promag_fields`
--

CREATE TABLE `wpxp_promag_fields` (
  `field_id` int(11) NOT NULL,
  `field_name` varchar(255) NOT NULL,
  `field_desc` longtext DEFAULT NULL,
  `field_type` varchar(255) NOT NULL,
  `field_options` longtext DEFAULT NULL,
  `field_icon` int(11) DEFAULT NULL,
  `associate_group` int(11) NOT NULL DEFAULT 0,
  `associate_section` int(11) NOT NULL DEFAULT 0,
  `show_in_signup_form` int(11) NOT NULL DEFAULT 0,
  `is_required` int(11) NOT NULL DEFAULT 0,
  `is_editable` int(11) NOT NULL DEFAULT 0,
  `display_on_profile` int(11) NOT NULL DEFAULT 0,
  `display_on_group` int(11) NOT NULL DEFAULT 0,
  `visibility` int(11) NOT NULL DEFAULT 0,
  `ordering` int(11) NOT NULL,
  `field_key` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_promag_friends`
--

CREATE TABLE `wpxp_promag_friends` (
  `id` int(11) NOT NULL,
  `user1` int(11) NOT NULL,
  `user2` int(11) NOT NULL,
  `created_date` datetime NOT NULL,
  `action_date` datetime NOT NULL,
  `status` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_promag_groups`
--

CREATE TABLE `wpxp_promag_groups` (
  `id` int(11) NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `group_desc` longtext DEFAULT NULL,
  `group_icon` int(11) DEFAULT NULL,
  `is_group_limit` int(11) NOT NULL DEFAULT 0,
  `group_limit` int(11) NOT NULL DEFAULT 0,
  `group_limit_message` longtext DEFAULT NULL,
  `associate_role` varchar(255) NOT NULL,
  `is_group_leader` int(11) NOT NULL DEFAULT 0,
  `leader_username` varchar(255) NOT NULL,
  `group_leaders` longtext DEFAULT NULL,
  `leader_rights` longtext DEFAULT NULL,
  `group_slug` varchar(255) DEFAULT NULL,
  `show_success_message` int(11) NOT NULL DEFAULT 0,
  `success_message` longtext DEFAULT NULL,
  `group_options` longtext DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_promag_group_requests`
--

CREATE TABLE `wpxp_promag_group_requests` (
  `id` int(11) NOT NULL,
  `gid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `options` longtext DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_promag_group_update_request`
--

CREATE TABLE `wpxp_promag_group_update_request` (
  `id` int(11) NOT NULL,
  `group_name` varchar(255) DEFAULT NULL,
  `group_desc` longtext DEFAULT NULL,
  `group_icon` int(11) DEFAULT NULL,
  `gid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `options` longtext DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_promag_msg_conversation`
--

CREATE TABLE `wpxp_promag_msg_conversation` (
  `m_id` int(11) NOT NULL,
  `s_id` int(11) NOT NULL,
  `t_id` int(11) NOT NULL,
  `content` longtext DEFAULT NULL,
  `timestamp` datetime NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `msg_desc` longtext DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_promag_msg_threads`
--

CREATE TABLE `wpxp_promag_msg_threads` (
  `t_id` int(11) NOT NULL,
  `s_id` int(11) NOT NULL,
  `r_id` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `thread_desc` longtext DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_promag_notification`
--

CREATE TABLE `wpxp_promag_notification` (
  `id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `sid` int(11) NOT NULL,
  `rid` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  `description` longtext DEFAULT NULL,
  `status` int(11) NOT NULL,
  `meta` longtext DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_promag_paypal_log`
--

CREATE TABLE `wpxp_promag_paypal_log` (
  `id` int(11) NOT NULL,
  `txn_id` varchar(600) NOT NULL,
  `log` longtext NOT NULL,
  `posted_date` datetime NOT NULL,
  `gid` int(11) NOT NULL,
  `status` varchar(255) NOT NULL,
  `invoice` varchar(255) NOT NULL,
  `amount` int(11) NOT NULL,
  `currency` varchar(255) NOT NULL,
  `pay_processor` varchar(255) NOT NULL,
  `pay_type` varchar(255) NOT NULL,
  `uid` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_promag_sections`
--

CREATE TABLE `wpxp_promag_sections` (
  `id` int(11) NOT NULL,
  `gid` int(11) NOT NULL,
  `section_name` varchar(600) NOT NULL,
  `ordering` int(11) NOT NULL DEFAULT 0,
  `section_options` longtext DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_rank_math_internal_links`
--

CREATE TABLE `wpxp_rank_math_internal_links` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `url` varchar(255) NOT NULL,
  `post_id` bigint(20) UNSIGNED NOT NULL,
  `target_post_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(8) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_rank_math_internal_meta`
--

CREATE TABLE `wpxp_rank_math_internal_meta` (
  `object_id` bigint(20) UNSIGNED NOT NULL,
  `internal_link_count` int(10) UNSIGNED DEFAULT 0,
  `external_link_count` int(10) UNSIGNED DEFAULT 0,
  `incoming_link_count` int(10) UNSIGNED DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_routiz_messages`
--

CREATE TABLE `wpxp_routiz_messages` (
  `id` mediumint(9) NOT NULL,
  `conversation_id` mediumint(9) NOT NULL,
  `sender_id` mediumint(9) NOT NULL,
  `text` longtext DEFAULT NULL,
  `system` mediumint(9) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_routiz_notifications`
--

CREATE TABLE `wpxp_routiz_notifications` (
  `id` mediumint(9) NOT NULL,
  `user_id` mediumint(9) NOT NULL,
  `code` tinytext NOT NULL,
  `meta` longtext DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_routiz_user_packages`
--

CREATE TABLE `wpxp_routiz_user_packages` (
  `id` mediumint(9) NOT NULL,
  `user_id` mediumint(9) NOT NULL,
  `order_id` mediumint(9) NOT NULL,
  `package_id` mediumint(9) NOT NULL,
  `package_duration` mediumint(9) NOT NULL,
  `package_limit` mediumint(9) NOT NULL,
  `listings_attached` mediumint(9) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_routiz_views`
--

CREATE TABLE `wpxp_routiz_views` (
  `id` mediumint(9) NOT NULL,
  `listing_id` mediumint(9) NOT NULL,
  `count` mediumint(9) NOT NULL,
  `datetime` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_routiz_visits`
--

CREATE TABLE `wpxp_routiz_visits` (
  `id` mediumint(9) NOT NULL,
  `listing_id` mediumint(9) NOT NULL,
  `identity` varchar(64) NOT NULL,
  `ip` varchar(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_routiz_wallet`
--

CREATE TABLE `wpxp_routiz_wallet` (
  `user_id` mediumint(9) NOT NULL,
  `balance` text NOT NULL,
  `spent` text NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_routiz_wallet_payouts`
--

CREATE TABLE `wpxp_routiz_wallet_payouts` (
  `id` mediumint(9) NOT NULL,
  `user_id` mediumint(9) NOT NULL,
  `amount` text NOT NULL,
  `payment_method` text NOT NULL,
  `address` text NOT NULL,
  `status` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_routiz_wallet_transactions`
--

CREATE TABLE `wpxp_routiz_wallet_transactions` (
  `id` mediumint(9) NOT NULL,
  `user_id` mediumint(9) NOT NULL,
  `order_id` mediumint(9) DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `source` varchar(255) DEFAULT NULL,
  `amount` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_swpm_membership_meta_tbl`
--

CREATE TABLE `wpxp_swpm_membership_meta_tbl` (
  `id` int(11) NOT NULL,
  `level_id` int(11) NOT NULL,
  `meta_key` varchar(255) NOT NULL,
  `meta_label` varchar(255) DEFAULT NULL,
  `meta_value` text DEFAULT NULL,
  `meta_type` varchar(255) NOT NULL DEFAULT 'text',
  `meta_default` text DEFAULT NULL,
  `meta_context` varchar(255) NOT NULL DEFAULT 'default'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_swpm_membership_tbl`
--

CREATE TABLE `wpxp_swpm_membership_tbl` (
  `id` int(11) NOT NULL,
  `alias` varchar(127) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'subscriber',
  `permissions` tinyint(4) NOT NULL DEFAULT 0,
  `subscription_period` varchar(11) NOT NULL DEFAULT '-1',
  `subscription_duration_type` tinyint(4) NOT NULL DEFAULT 0,
  `subscription_unit` varchar(20) DEFAULT NULL,
  `loginredirect_page` text DEFAULT NULL,
  `category_list` longtext DEFAULT NULL,
  `page_list` longtext DEFAULT NULL,
  `post_list` longtext DEFAULT NULL,
  `comment_list` longtext DEFAULT NULL,
  `attachment_list` longtext DEFAULT NULL,
  `custom_post_list` longtext DEFAULT NULL,
  `disable_bookmark_list` longtext DEFAULT NULL,
  `options` longtext DEFAULT NULL,
  `protect_older_posts` tinyint(1) NOT NULL DEFAULT 0,
  `campaign_name` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_swpm_members_meta_tbl`
--

CREATE TABLE `wpxp_swpm_members_meta_tbl` (
  `meta_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_swpm_members_tbl`
--

CREATE TABLE `wpxp_swpm_members_tbl` (
  `member_id` int(12) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `first_name` varchar(64) DEFAULT '',
  `last_name` varchar(64) DEFAULT '',
  `password` varchar(255) NOT NULL,
  `member_since` date NOT NULL DEFAULT '0000-00-00',
  `membership_level` smallint(6) NOT NULL,
  `more_membership_levels` varchar(100) DEFAULT NULL,
  `account_state` enum('active','inactive','activation_required','expired','pending','unsubscribed') DEFAULT 'pending',
  `last_accessed` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_accessed_from_ip` varchar(128) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(64) DEFAULT NULL,
  `address_street` varchar(255) DEFAULT NULL,
  `address_city` varchar(255) DEFAULT NULL,
  `address_state` varchar(255) DEFAULT NULL,
  `address_zipcode` varchar(255) DEFAULT NULL,
  `home_page` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `gender` enum('male','female','not specified') DEFAULT 'not specified',
  `referrer` varchar(255) DEFAULT NULL,
  `extra_info` text DEFAULT NULL,
  `reg_code` varchar(255) DEFAULT NULL,
  `subscription_starts` date DEFAULT NULL,
  `initial_membership_level` smallint(6) DEFAULT NULL,
  `txn_id` varchar(255) DEFAULT '',
  `subscr_id` varchar(255) DEFAULT '',
  `company_name` varchar(255) DEFAULT '',
  `notes` text DEFAULT NULL,
  `flags` int(11) DEFAULT 0,
  `profile_image` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_swpm_payments_tbl`
--

CREATE TABLE `wpxp_swpm_payments_tbl` (
  `id` int(12) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `first_name` varchar(64) DEFAULT '',
  `last_name` varchar(64) DEFAULT '',
  `member_id` varchar(16) DEFAULT '',
  `membership_level` varchar(64) DEFAULT '',
  `txn_date` date NOT NULL DEFAULT '0000-00-00',
  `txn_id` varchar(255) NOT NULL DEFAULT '',
  `subscr_id` varchar(255) NOT NULL DEFAULT '',
  `reference` varchar(255) NOT NULL DEFAULT '',
  `payment_amount` varchar(32) NOT NULL DEFAULT '',
  `gateway` varchar(32) DEFAULT '',
  `status` varchar(255) DEFAULT '',
  `ip_address` varchar(128) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_termmeta`
--

CREATE TABLE `wpxp_termmeta` (
  `meta_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_terms`
--

CREATE TABLE `wpxp_terms` (
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL DEFAULT '',
  `slug` varchar(200) NOT NULL DEFAULT '',
  `term_group` bigint(10) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_term_relationships`
--

CREATE TABLE `wpxp_term_relationships` (
  `object_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `term_taxonomy_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `term_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_term_taxonomy`
--

CREATE TABLE `wpxp_term_taxonomy` (
  `term_taxonomy_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `taxonomy` varchar(32) NOT NULL DEFAULT '',
  `description` longtext NOT NULL,
  `parent` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `count` bigint(20) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_trp_gettext_en_gb`
--

CREATE TABLE `wpxp_trp_gettext_en_gb` (
  `id` bigint(20) NOT NULL,
  `original` longtext NOT NULL,
  `translated` longtext DEFAULT NULL,
  `domain` longtext DEFAULT NULL,
  `status` int(20) DEFAULT NULL,
  `original_id` bigint(20) DEFAULT NULL,
  `plural_form` int(20) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_trp_gettext_original_meta`
--

CREATE TABLE `wpxp_trp_gettext_original_meta` (
  `meta_id` bigint(20) NOT NULL,
  `original_id` bigint(20) NOT NULL,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_trp_gettext_original_strings`
--

CREATE TABLE `wpxp_trp_gettext_original_strings` (
  `id` bigint(20) NOT NULL,
  `original` text NOT NULL,
  `domain` text NOT NULL,
  `context` text DEFAULT NULL,
  `original_plural` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_tutor_carts`
--

CREATE TABLE `wpxp_tutor_carts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `coupon_code` varchar(50) DEFAULT NULL,
  `created_at_gmt` datetime NOT NULL,
  `updated_at_gmt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_tutor_cart_items`
--

CREATE TABLE `wpxp_tutor_cart_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cart_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_tutor_coupons`
--

CREATE TABLE `wpxp_tutor_coupons` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `coupon_status` varchar(50) DEFAULT NULL,
  `coupon_type` varchar(100) DEFAULT 'code',
  `coupon_code` varchar(50) NOT NULL,
  `coupon_title` varchar(255) NOT NULL,
  `coupon_description` text DEFAULT NULL,
  `discount_type` enum('percentage','flat') NOT NULL,
  `discount_amount` decimal(13,2) NOT NULL,
  `applies_to` varchar(100) DEFAULT 'all_courses_and_bundles',
  `total_usage_limit` int(10) UNSIGNED DEFAULT NULL,
  `per_user_usage_limit` tinyint(4) UNSIGNED DEFAULT NULL,
  `purchase_requirement` varchar(50) DEFAULT 'no_minimum',
  `purchase_requirement_value` decimal(13,2) DEFAULT NULL,
  `start_date_gmt` datetime NOT NULL,
  `expire_date_gmt` datetime DEFAULT NULL,
  `created_at_gmt` datetime NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `updated_at_gmt` datetime DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_tutor_coupon_applications`
--

CREATE TABLE `wpxp_tutor_coupon_applications` (
  `coupon_code` varchar(50) NOT NULL,
  `reference_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_tutor_coupon_usages`
--

CREATE TABLE `wpxp_tutor_coupon_usages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `coupon_code` varchar(50) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_tutor_customers`
--

CREATE TABLE `wpxp_tutor_customers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `billing_first_name` varchar(255) NOT NULL,
  `billing_last_name` varchar(255) NOT NULL,
  `billing_email` varchar(255) NOT NULL,
  `billing_phone` varchar(20) NOT NULL,
  `billing_zip_code` varchar(20) NOT NULL,
  `billing_address` text NOT NULL,
  `billing_country` varchar(100) NOT NULL,
  `billing_state` varchar(100) NOT NULL,
  `billing_city` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_tutor_earnings`
--

CREATE TABLE `wpxp_tutor_earnings` (
  `earning_id` bigint(20) NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `course_id` bigint(20) DEFAULT NULL,
  `order_id` bigint(20) DEFAULT NULL,
  `order_status` varchar(50) DEFAULT NULL,
  `course_price_total` decimal(16,2) DEFAULT NULL,
  `course_price_grand_total` decimal(16,2) DEFAULT NULL,
  `instructor_amount` decimal(16,2) DEFAULT NULL,
  `instructor_rate` decimal(16,2) DEFAULT NULL,
  `admin_amount` decimal(16,2) DEFAULT NULL,
  `admin_rate` decimal(16,2) DEFAULT NULL,
  `commission_type` varchar(20) DEFAULT NULL,
  `deduct_fees_amount` decimal(16,2) DEFAULT NULL,
  `deduct_fees_name` varchar(250) DEFAULT NULL,
  `deduct_fees_type` varchar(20) DEFAULT NULL,
  `process_by` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_tutor_ordermeta`
--

CREATE TABLE `wpxp_tutor_ordermeta` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `meta_key` varchar(255) NOT NULL,
  `meta_value` longtext NOT NULL,
  `created_at_gmt` datetime NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `updated_at_gmt` datetime DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_tutor_orders`
--

CREATE TABLE `wpxp_tutor_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT 0,
  `transaction_id` varchar(255) DEFAULT NULL COMMENT 'Transaction id from payment gateway',
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `order_type` varchar(50) NOT NULL,
  `order_status` varchar(50) NOT NULL,
  `payment_status` varchar(50) NOT NULL,
  `subtotal_price` decimal(13,2) NOT NULL,
  `total_price` decimal(13,2) NOT NULL,
  `net_payment` decimal(13,2) NOT NULL,
  `coupon_code` varchar(255) DEFAULT NULL,
  `coupon_amount` decimal(13,2) DEFAULT NULL,
  `discount_type` enum('percentage','flat') DEFAULT NULL,
  `discount_amount` decimal(13,2) DEFAULT NULL,
  `discount_reason` text DEFAULT NULL,
  `tax_type` varchar(50) DEFAULT NULL,
  `tax_rate` decimal(13,2) DEFAULT NULL COMMENT 'Tax percentage',
  `tax_amount` decimal(13,2) DEFAULT NULL,
  `fees` decimal(13,2) DEFAULT NULL,
  `earnings` decimal(13,2) DEFAULT NULL,
  `refund_amount` decimal(13,2) DEFAULT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `payment_payloads` longtext DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at_gmt` datetime NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `updated_at_gmt` datetime DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_tutor_order_items`
--

CREATE TABLE `wpxp_tutor_order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `regular_price` decimal(13,2) NOT NULL,
  `sale_price` varchar(13) DEFAULT NULL,
  `discount_price` varchar(13) DEFAULT NULL,
  `coupon_code` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_tutor_quiz_attempts`
--

CREATE TABLE `wpxp_tutor_quiz_attempts` (
  `attempt_id` bigint(20) NOT NULL,
  `course_id` bigint(20) DEFAULT NULL,
  `quiz_id` bigint(20) DEFAULT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `total_questions` int(11) DEFAULT NULL,
  `total_answered_questions` int(11) DEFAULT NULL,
  `total_marks` decimal(9,2) DEFAULT NULL,
  `earned_marks` decimal(9,2) DEFAULT NULL,
  `attempt_info` text DEFAULT NULL,
  `attempt_status` varchar(50) DEFAULT NULL,
  `attempt_ip` varchar(250) DEFAULT NULL,
  `attempt_started_at` datetime DEFAULT NULL,
  `attempt_ended_at` datetime DEFAULT NULL,
  `is_manually_reviewed` int(1) DEFAULT NULL,
  `manually_reviewed_at` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_tutor_quiz_attempt_answers`
--

CREATE TABLE `wpxp_tutor_quiz_attempt_answers` (
  `attempt_answer_id` bigint(20) NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `quiz_id` bigint(20) DEFAULT NULL,
  `question_id` bigint(20) DEFAULT NULL,
  `quiz_attempt_id` bigint(20) DEFAULT NULL,
  `given_answer` longtext DEFAULT NULL,
  `question_mark` decimal(8,2) DEFAULT NULL,
  `achieved_mark` decimal(8,2) DEFAULT NULL,
  `minus_mark` decimal(8,2) DEFAULT NULL,
  `is_correct` tinyint(4) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_tutor_quiz_questions`
--

CREATE TABLE `wpxp_tutor_quiz_questions` (
  `question_id` bigint(20) NOT NULL,
  `quiz_id` bigint(20) DEFAULT NULL,
  `question_title` text DEFAULT NULL,
  `question_description` longtext DEFAULT NULL,
  `answer_explanation` longtext DEFAULT '',
  `question_type` varchar(50) DEFAULT NULL,
  `question_mark` decimal(9,2) DEFAULT NULL,
  `question_settings` longtext DEFAULT NULL,
  `question_order` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_tutor_quiz_question_answers`
--

CREATE TABLE `wpxp_tutor_quiz_question_answers` (
  `answer_id` bigint(20) NOT NULL,
  `belongs_question_id` bigint(20) DEFAULT NULL,
  `belongs_question_type` varchar(250) DEFAULT NULL,
  `answer_title` text DEFAULT NULL,
  `is_correct` tinyint(4) DEFAULT NULL,
  `image_id` bigint(20) DEFAULT NULL,
  `answer_two_gap_match` text DEFAULT NULL,
  `answer_view_format` varchar(250) DEFAULT NULL,
  `answer_settings` text DEFAULT NULL,
  `answer_order` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_tutor_withdraws`
--

CREATE TABLE `wpxp_tutor_withdraws` (
  `withdraw_id` bigint(20) NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `amount` decimal(16,2) DEFAULT NULL,
  `method_data` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_um_metadata`
--

CREATE TABLE `wpxp_um_metadata` (
  `umeta_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `um_key` varchar(255) DEFAULT NULL,
  `um_value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_usermeta`
--

CREATE TABLE `wpxp_usermeta` (
  `umeta_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_users`
--

CREATE TABLE `wpxp_users` (
  `ID` bigint(20) UNSIGNED NOT NULL,
  `user_login` varchar(60) NOT NULL DEFAULT '',
  `user_pass` varchar(255) NOT NULL DEFAULT '',
  `user_nicename` varchar(50) NOT NULL DEFAULT '',
  `user_email` varchar(100) NOT NULL DEFAULT '',
  `user_url` varchar(100) NOT NULL DEFAULT '',
  `user_registered` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_activation_key` varchar(255) NOT NULL DEFAULT '',
  `user_status` int(11) NOT NULL DEFAULT 0,
  `display_name` varchar(250) NOT NULL DEFAULT '',
  `centre_id` int(8) NOT NULL,
  `rescue_role` int(8) NOT NULL,
  `assigned_org` int(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_user_registration_sessions`
--

CREATE TABLE `wpxp_user_registration_sessions` (
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `session_key` char(32) NOT NULL,
  `session_value` longtext NOT NULL,
  `session_expiry` bigint(20) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_uwp_form_extras`
--

CREATE TABLE `wpxp_uwp_form_extras` (
  `id` int(11) NOT NULL,
  `form_type` varchar(255) NOT NULL,
  `field_type` varchar(255) NOT NULL COMMENT 'text,checkbox,radio,select,textarea',
  `site_htmlvar_name` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL,
  `is_default` enum('0','1') NOT NULL DEFAULT '0',
  `is_dummy` enum('0','1') NOT NULL DEFAULT '0',
  `expand_custom_value` int(11) DEFAULT NULL,
  `searching_range_mode` int(11) DEFAULT NULL,
  `expand_search` int(11) DEFAULT NULL,
  `front_search_title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `front_css_class` varchar(255) DEFAULT NULL,
  `first_search_value` int(11) DEFAULT NULL,
  `first_search_text` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `last_search_text` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `search_min_value` int(11) DEFAULT NULL,
  `search_max_value` int(11) DEFAULT NULL,
  `search_diff_value` int(11) DEFAULT NULL,
  `search_condition` varchar(100) DEFAULT NULL,
  `field_input_type` varchar(255) DEFAULT NULL,
  `field_data_type` varchar(255) DEFAULT NULL,
  `form_id` int(11) NOT NULL DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_uwp_form_fields`
--

CREATE TABLE `wpxp_uwp_form_fields` (
  `id` int(11) NOT NULL,
  `form_type` varchar(100) DEFAULT NULL,
  `data_type` varchar(100) DEFAULT NULL,
  `field_type` varchar(255) NOT NULL COMMENT 'text,checkbox,radio,select,textarea',
  `field_type_key` varchar(255) NOT NULL,
  `site_title` varchar(255) DEFAULT NULL,
  `form_label` varchar(255) DEFAULT NULL,
  `help_text` varchar(255) DEFAULT NULL,
  `htmlvar_name` varchar(255) DEFAULT NULL,
  `default_value` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL,
  `option_values` text DEFAULT NULL,
  `is_active` enum('0','1') NOT NULL DEFAULT '1',
  `placeholder_value` varchar(255) DEFAULT NULL,
  `for_admin_use` enum('0','1') NOT NULL DEFAULT '0',
  `is_default` enum('0','1') NOT NULL DEFAULT '0',
  `is_dummy` enum('0','1') NOT NULL DEFAULT '0',
  `is_public` enum('0','1','2') NOT NULL DEFAULT '0',
  `is_required` enum('0','1') NOT NULL DEFAULT '0',
  `is_register_field` enum('0','1') NOT NULL DEFAULT '0',
  `is_search_field` enum('0','1') NOT NULL DEFAULT '0',
  `is_register_only_field` enum('0','1') NOT NULL DEFAULT '0',
  `required_msg` varchar(255) DEFAULT NULL,
  `show_in` text DEFAULT NULL,
  `user_roles` text DEFAULT NULL,
  `extra_fields` text DEFAULT NULL,
  `field_icon` varchar(255) DEFAULT NULL,
  `css_class` varchar(255) DEFAULT NULL,
  `decimal_point` varchar(10) NOT NULL,
  `validation_pattern` varchar(255) NOT NULL,
  `validation_msg` text DEFAULT NULL,
  `form_id` int(11) NOT NULL DEFAULT 1,
  `user_sort` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_uwp_profile_tabs`
--

CREATE TABLE `wpxp_uwp_profile_tabs` (
  `id` int(11) NOT NULL,
  `form_type` varchar(100) DEFAULT NULL,
  `sort_order` int(11) NOT NULL,
  `tab_layout` varchar(100) NOT NULL,
  `tab_type` varchar(100) NOT NULL,
  `tab_level` int(11) NOT NULL,
  `tab_parent` int(11) NOT NULL,
  `tab_privacy` int(11) NOT NULL DEFAULT 0,
  `user_decided` int(11) NOT NULL DEFAULT 0,
  `tab_name` varchar(255) NOT NULL,
  `tab_icon` varchar(255) NOT NULL,
  `tab_key` varchar(255) NOT NULL,
  `tab_content` text DEFAULT NULL,
  `form_id` int(11) NOT NULL DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_uwp_user_sorting`
--

CREATE TABLE `wpxp_uwp_user_sorting` (
  `id` int(11) NOT NULL,
  `data_type` varchar(255) NOT NULL,
  `field_type` varchar(255) NOT NULL,
  `site_title` varchar(255) NOT NULL,
  `htmlvar_name` varchar(255) NOT NULL,
  `field_icon` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `tab_parent` varchar(100) NOT NULL DEFAULT '0',
  `tab_level` int(11) NOT NULL DEFAULT 0,
  `is_active` int(11) NOT NULL DEFAULT 0,
  `is_default` int(11) NOT NULL DEFAULT 0,
  `sort` varchar(5) DEFAULT 'asc'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wc_admin_notes`
--

CREATE TABLE `wpxp_wc_admin_notes` (
  `note_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(20) NOT NULL,
  `locale` varchar(20) NOT NULL,
  `title` longtext NOT NULL,
  `content` longtext NOT NULL,
  `content_data` longtext DEFAULT NULL,
  `status` varchar(200) NOT NULL,
  `source` varchar(200) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_reminder` datetime DEFAULT NULL,
  `is_snoozable` tinyint(1) NOT NULL DEFAULT 0,
  `layout` varchar(20) NOT NULL DEFAULT '',
  `image` varchar(200) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `icon` varchar(200) NOT NULL DEFAULT 'info'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wc_admin_note_actions`
--

CREATE TABLE `wpxp_wc_admin_note_actions` (
  `action_id` bigint(20) UNSIGNED NOT NULL,
  `note_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `query` longtext NOT NULL,
  `status` varchar(255) NOT NULL,
  `actioned_text` varchar(255) NOT NULL,
  `nonce_action` varchar(255) DEFAULT NULL,
  `nonce_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wc_category_lookup`
--

CREATE TABLE `wpxp_wc_category_lookup` (
  `category_tree_id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wc_customer_lookup`
--

CREATE TABLE `wpxp_wc_customer_lookup` (
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `username` varchar(60) NOT NULL DEFAULT '',
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `date_last_active` timestamp NULL DEFAULT NULL,
  `date_registered` timestamp NULL DEFAULT NULL,
  `country` char(2) NOT NULL DEFAULT '',
  `postcode` varchar(20) NOT NULL DEFAULT '',
  `city` varchar(100) NOT NULL DEFAULT '',
  `state` varchar(100) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wc_download_log`
--

CREATE TABLE `wpxp_wc_download_log` (
  `download_log_id` bigint(20) UNSIGNED NOT NULL,
  `timestamp` datetime NOT NULL,
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_ip_address` varchar(100) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wc_order_coupon_lookup`
--

CREATE TABLE `wpxp_wc_order_coupon_lookup` (
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `coupon_id` bigint(20) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `discount_amount` double NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wc_order_product_lookup`
--

CREATE TABLE `wpxp_wc_order_product_lookup` (
  `order_item_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `variation_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `product_qty` int(11) NOT NULL,
  `product_net_revenue` double NOT NULL DEFAULT 0,
  `product_gross_revenue` double NOT NULL DEFAULT 0,
  `coupon_amount` double NOT NULL DEFAULT 0,
  `tax_amount` double NOT NULL DEFAULT 0,
  `shipping_amount` double NOT NULL DEFAULT 0,
  `shipping_tax_amount` double NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wc_order_stats`
--

CREATE TABLE `wpxp_wc_order_stats` (
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_created_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_paid` datetime DEFAULT '0000-00-00 00:00:00',
  `date_completed` datetime DEFAULT '0000-00-00 00:00:00',
  `num_items_sold` int(11) NOT NULL DEFAULT 0,
  `total_sales` double NOT NULL DEFAULT 0,
  `tax_total` double NOT NULL DEFAULT 0,
  `shipping_total` double NOT NULL DEFAULT 0,
  `net_total` double NOT NULL DEFAULT 0,
  `returning_customer` tinyint(1) DEFAULT NULL,
  `status` varchar(200) NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wc_order_tax_lookup`
--

CREATE TABLE `wpxp_wc_order_tax_lookup` (
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `tax_rate_id` bigint(20) UNSIGNED NOT NULL,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `shipping_tax` double NOT NULL DEFAULT 0,
  `order_tax` double NOT NULL DEFAULT 0,
  `total_tax` double NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wc_product_attributes_lookup`
--

CREATE TABLE `wpxp_wc_product_attributes_lookup` (
  `product_id` bigint(20) NOT NULL,
  `product_or_parent_id` bigint(20) NOT NULL,
  `taxonomy` varchar(32) NOT NULL,
  `term_id` bigint(20) NOT NULL,
  `is_variation_attribute` tinyint(1) NOT NULL,
  `in_stock` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wc_product_download_directories`
--

CREATE TABLE `wpxp_wc_product_download_directories` (
  `url_id` bigint(20) UNSIGNED NOT NULL,
  `url` varchar(256) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wc_product_meta_lookup`
--

CREATE TABLE `wpxp_wc_product_meta_lookup` (
  `product_id` bigint(20) NOT NULL,
  `sku` varchar(100) DEFAULT '',
  `virtual` tinyint(1) DEFAULT 0,
  `downloadable` tinyint(1) DEFAULT 0,
  `min_price` decimal(19,4) DEFAULT NULL,
  `max_price` decimal(19,4) DEFAULT NULL,
  `onsale` tinyint(1) DEFAULT 0,
  `stock_quantity` double DEFAULT NULL,
  `stock_status` varchar(100) DEFAULT 'instock',
  `rating_count` bigint(20) DEFAULT 0,
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `total_sales` bigint(20) DEFAULT 0,
  `tax_status` varchar(100) DEFAULT 'taxable',
  `tax_class` varchar(100) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wc_rate_limits`
--

CREATE TABLE `wpxp_wc_rate_limits` (
  `rate_limit_id` bigint(20) UNSIGNED NOT NULL,
  `rate_limit_key` varchar(200) NOT NULL,
  `rate_limit_expiry` bigint(20) UNSIGNED NOT NULL,
  `rate_limit_remaining` smallint(10) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wc_reserved_stock`
--

CREATE TABLE `wpxp_wc_reserved_stock` (
  `order_id` bigint(20) NOT NULL,
  `product_id` bigint(20) NOT NULL,
  `stock_quantity` double NOT NULL DEFAULT 0,
  `timestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `expires` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wc_tax_rate_classes`
--

CREATE TABLE `wpxp_wc_tax_rate_classes` (
  `tax_rate_class_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL DEFAULT '',
  `slug` varchar(200) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wc_webhooks`
--

CREATE TABLE `wpxp_wc_webhooks` (
  `webhook_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(200) NOT NULL,
  `name` text NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `delivery_url` text NOT NULL,
  `secret` text NOT NULL,
  `topic` varchar(200) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_created_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `api_version` smallint(4) NOT NULL,
  `failure_count` smallint(10) NOT NULL DEFAULT 0,
  `pending_delivery` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_woocommerce_api_keys`
--

CREATE TABLE `wpxp_woocommerce_api_keys` (
  `key_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `description` varchar(200) DEFAULT NULL,
  `permissions` varchar(10) NOT NULL,
  `consumer_key` char(64) NOT NULL,
  `consumer_secret` char(43) NOT NULL,
  `nonces` longtext DEFAULT NULL,
  `truncated_key` char(7) NOT NULL,
  `last_access` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_woocommerce_attribute_taxonomies`
--

CREATE TABLE `wpxp_woocommerce_attribute_taxonomies` (
  `attribute_id` bigint(20) UNSIGNED NOT NULL,
  `attribute_name` varchar(200) NOT NULL,
  `attribute_label` varchar(200) DEFAULT NULL,
  `attribute_type` varchar(20) NOT NULL,
  `attribute_orderby` varchar(20) NOT NULL,
  `attribute_public` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_woocommerce_downloadable_product_permissions`
--

CREATE TABLE `wpxp_woocommerce_downloadable_product_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `download_id` varchar(36) NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `order_key` varchar(200) NOT NULL,
  `user_email` varchar(200) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `downloads_remaining` varchar(9) DEFAULT NULL,
  `access_granted` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `access_expires` datetime DEFAULT NULL,
  `download_count` bigint(20) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_woocommerce_log`
--

CREATE TABLE `wpxp_woocommerce_log` (
  `log_id` bigint(20) UNSIGNED NOT NULL,
  `timestamp` datetime NOT NULL,
  `level` smallint(4) NOT NULL,
  `source` varchar(200) NOT NULL,
  `message` longtext NOT NULL,
  `context` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_woocommerce_order_itemmeta`
--

CREATE TABLE `wpxp_woocommerce_order_itemmeta` (
  `meta_id` bigint(20) UNSIGNED NOT NULL,
  `order_item_id` bigint(20) UNSIGNED NOT NULL,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_woocommerce_order_items`
--

CREATE TABLE `wpxp_woocommerce_order_items` (
  `order_item_id` bigint(20) UNSIGNED NOT NULL,
  `order_item_name` text NOT NULL,
  `order_item_type` varchar(200) NOT NULL DEFAULT '',
  `order_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_woocommerce_payment_tokenmeta`
--

CREATE TABLE `wpxp_woocommerce_payment_tokenmeta` (
  `meta_id` bigint(20) UNSIGNED NOT NULL,
  `payment_token_id` bigint(20) UNSIGNED NOT NULL,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_woocommerce_payment_tokens`
--

CREATE TABLE `wpxp_woocommerce_payment_tokens` (
  `token_id` bigint(20) UNSIGNED NOT NULL,
  `gateway_id` varchar(200) NOT NULL,
  `token` text NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `type` varchar(200) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_woocommerce_sessions`
--

CREATE TABLE `wpxp_woocommerce_sessions` (
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `session_key` char(32) NOT NULL,
  `session_value` longtext NOT NULL,
  `session_expiry` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_woocommerce_shipping_zones`
--

CREATE TABLE `wpxp_woocommerce_shipping_zones` (
  `zone_id` bigint(20) UNSIGNED NOT NULL,
  `zone_name` varchar(200) NOT NULL,
  `zone_order` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_woocommerce_shipping_zone_locations`
--

CREATE TABLE `wpxp_woocommerce_shipping_zone_locations` (
  `location_id` bigint(20) UNSIGNED NOT NULL,
  `zone_id` bigint(20) UNSIGNED NOT NULL,
  `location_code` varchar(200) NOT NULL,
  `location_type` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_woocommerce_shipping_zone_methods`
--

CREATE TABLE `wpxp_woocommerce_shipping_zone_methods` (
  `zone_id` bigint(20) UNSIGNED NOT NULL,
  `instance_id` bigint(20) UNSIGNED NOT NULL,
  `method_id` varchar(200) NOT NULL,
  `method_order` bigint(20) UNSIGNED NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_woocommerce_tax_rates`
--

CREATE TABLE `wpxp_woocommerce_tax_rates` (
  `tax_rate_id` bigint(20) UNSIGNED NOT NULL,
  `tax_rate_country` varchar(2) NOT NULL DEFAULT '',
  `tax_rate_state` varchar(200) NOT NULL DEFAULT '',
  `tax_rate` varchar(8) NOT NULL DEFAULT '',
  `tax_rate_name` varchar(200) NOT NULL DEFAULT '',
  `tax_rate_priority` bigint(20) UNSIGNED NOT NULL,
  `tax_rate_compound` int(1) NOT NULL DEFAULT 0,
  `tax_rate_shipping` int(1) NOT NULL DEFAULT 1,
  `tax_rate_order` bigint(20) UNSIGNED NOT NULL,
  `tax_rate_class` varchar(200) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_woocommerce_tax_rate_locations`
--

CREATE TABLE `wpxp_woocommerce_tax_rate_locations` (
  `location_id` bigint(20) UNSIGNED NOT NULL,
  `location_code` varchar(200) NOT NULL,
  `tax_rate_id` bigint(20) UNSIGNED NOT NULL,
  `location_type` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpbdp_form_fields`
--

CREATE TABLE `wpxp_wpbdp_form_fields` (
  `id` bigint(20) NOT NULL,
  `label` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `field_type` varchar(100) NOT NULL,
  `association` varchar(100) NOT NULL,
  `validators` text DEFAULT NULL,
  `weight` int(5) NOT NULL DEFAULT 0,
  `display_flags` text DEFAULT NULL,
  `field_data` blob DEFAULT NULL,
  `shortname` varchar(255) NOT NULL DEFAULT '',
  `tag` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpbdp_listings`
--

CREATE TABLE `wpxp_wpbdp_listings` (
  `listing_id` bigint(20) NOT NULL,
  `fee_id` bigint(20) DEFAULT NULL,
  `fee_price` decimal(10,2) DEFAULT 0.00,
  `fee_days` smallint(5) UNSIGNED DEFAULT 0,
  `fee_images` smallint(5) UNSIGNED DEFAULT 0,
  `expiration_date` timestamp NULL DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `is_sticky` tinyint(1) NOT NULL DEFAULT 0,
  `subscription_id` varchar(255) DEFAULT '',
  `subscription_data` longblob DEFAULT NULL,
  `listing_status` varchar(255) NOT NULL DEFAULT 'unknown',
  `flags` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpbdp_logs`
--

CREATE TABLE `wpxp_wpbdp_logs` (
  `id` bigint(20) NOT NULL,
  `object_id` bigint(20) DEFAULT 0,
  `rel_object_id` bigint(20) DEFAULT 0,
  `object_type` varchar(20) DEFAULT '',
  `created_at` datetime NOT NULL,
  `log_type` varchar(255) DEFAULT '',
  `actor` varchar(255) DEFAULT '',
  `message` text DEFAULT '',
  `data` longblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpbdp_payments`
--

CREATE TABLE `wpxp_wpbdp_payments` (
  `id` bigint(20) NOT NULL,
  `listing_id` bigint(20) NOT NULL DEFAULT 0,
  `parent_id` bigint(20) NOT NULL DEFAULT 0,
  `payment_key` varchar(255) DEFAULT '',
  `payment_type` varchar(255) DEFAULT '',
  `payment_items` longblob DEFAULT NULL,
  `data` longblob DEFAULT NULL,
  `context` varchar(255) DEFAULT '',
  `payer_email` varchar(255) DEFAULT '',
  `payer_first_name` varchar(255) DEFAULT '',
  `payer_last_name` varchar(255) DEFAULT '',
  `payer_data` blob DEFAULT NULL,
  `gateway` varchar(255) DEFAULT NULL,
  `gateway_tx_id` varchar(255) DEFAULT '',
  `currency_code` varchar(3) NOT NULL DEFAULT 'USD',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(255) NOT NULL,
  `is_test` tinyint(1) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `processed_on` timestamp NULL DEFAULT NULL,
  `processed_by` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpbdp_plans`
--

CREATE TABLE `wpxp_wpbdp_plans` (
  `id` bigint(20) NOT NULL,
  `label` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `days` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `images` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `sticky` tinyint(1) NOT NULL DEFAULT 0,
  `recurring` tinyint(1) NOT NULL DEFAULT 0,
  `pricing_model` varchar(100) NOT NULL DEFAULT 'flat',
  `pricing_details` blob DEFAULT NULL,
  `supported_categories` text NOT NULL DEFAULT '',
  `weight` int(5) NOT NULL DEFAULT 0,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT '',
  `extra_data` longblob DEFAULT NULL,
  `tag` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpda_csv_uploads`
--

CREATE TABLE `wpxp_wpda_csv_uploads` (
  `csv_id` mediumint(9) NOT NULL,
  `csv_name` varchar(100) NOT NULL,
  `csv_real_file_name` varchar(4096) NOT NULL,
  `csv_orig_file_name` varchar(4096) NOT NULL,
  `csv_timestamp` datetime DEFAULT NULL,
  `csv_mapping` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpda_logging`
--

CREATE TABLE `wpxp_wpda_logging` (
  `log_time` datetime NOT NULL,
  `log_id` varchar(50) NOT NULL,
  `log_type` enum('FATAL','ERROR','WARN','INFO','DEBUG','TRACE') DEFAULT NULL,
  `log_msg` varchar(4096) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpda_media`
--

CREATE TABLE `wpxp_wpda_media` (
  `media_schema_name` varchar(64) NOT NULL DEFAULT '',
  `media_table_name` varchar(64) NOT NULL,
  `media_column_name` varchar(64) NOT NULL,
  `media_type` enum('Image','ImageURL','Attachment','Hyperlink','Audio','Video') DEFAULT NULL,
  `media_activated` enum('Yes','No') DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpda_menus`
--

CREATE TABLE `wpxp_wpda_menus` (
  `menu_id` mediumint(9) NOT NULL,
  `menu_schema_name` varchar(64) NOT NULL DEFAULT '',
  `menu_table_name` varchar(64) NOT NULL,
  `menu_name` varchar(100) NOT NULL,
  `menu_slug` varchar(100) NOT NULL,
  `menu_role` varchar(100) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpda_project`
--

CREATE TABLE `wpxp_wpda_project` (
  `project_id` mediumint(9) NOT NULL,
  `project_name` varchar(100) NOT NULL,
  `project_description` text DEFAULT NULL,
  `add_to_menu` enum('Yes','No') DEFAULT NULL,
  `menu_name` varchar(30) DEFAULT NULL,
  `project_sequence` smallint(6) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpda_project_page`
--

CREATE TABLE `wpxp_wpda_project_page` (
  `project_id` mediumint(9) NOT NULL,
  `page_id` mediumint(9) NOT NULL,
  `page_name` varchar(100) NOT NULL,
  `add_to_menu` enum('Yes','No') DEFAULT NULL,
  `page_type` enum('table','parent/child','static') NOT NULL,
  `page_schema_name` varchar(64) NOT NULL DEFAULT '',
  `page_table_name` varchar(64) DEFAULT NULL,
  `page_setname` varchar(100) DEFAULT 'default',
  `page_mode` enum('edit','view') NOT NULL,
  `page_allow_insert` enum('yes','no','only') NOT NULL,
  `page_allow_delete` enum('yes','no') NOT NULL,
  `page_allow_import` enum('yes','no') NOT NULL,
  `page_allow_bulk` enum('yes','no') NOT NULL,
  `page_allow_full_export` enum('yes','no') DEFAULT 'no',
  `page_content` bigint(20) UNSIGNED DEFAULT NULL,
  `page_title` varchar(100) DEFAULT NULL,
  `page_subtitle` varchar(100) DEFAULT NULL,
  `page_role` varchar(100) DEFAULT NULL,
  `page_where` varchar(4096) DEFAULT NULL,
  `page_orderby` varchar(4096) DEFAULT NULL,
  `page_sequence` smallint(6) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpda_project_table`
--

CREATE TABLE `wpxp_wpda_project_table` (
  `wpda_table_name` varchar(64) NOT NULL,
  `wpda_schema_name` varchar(64) NOT NULL DEFAULT '',
  `wpda_table_setname` varchar(100) NOT NULL DEFAULT 'default',
  `wpda_table_design` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpda_publisher`
--

CREATE TABLE `wpxp_wpda_publisher` (
  `pub_id` mediumint(9) NOT NULL,
  `pub_name` varchar(100) NOT NULL,
  `pub_data_source` enum('Table','Query','CPT') DEFAULT 'Table',
  `pub_schema_name` varchar(64) NOT NULL DEFAULT '',
  `pub_table_name` varchar(64) NOT NULL,
  `pub_column_names` varchar(4096) DEFAULT '*',
  `pub_cpt` varchar(20) DEFAULT NULL,
  `pub_cpt_fields` text DEFAULT NULL,
  `pub_cpt_query` text DEFAULT NULL,
  `pub_cpt_format` text DEFAULT NULL,
  `pub_format` text DEFAULT NULL,
  `pub_query` text DEFAULT NULL,
  `pub_sort_icons` enum('default','none') DEFAULT NULL,
  `pub_styles` set('default','stripe','hover','order-column','row-border','compact','cell-border') DEFAULT 'default',
  `pub_style_premium` enum('Yes','No') DEFAULT 'No',
  `pub_style_user` varchar(50) DEFAULT NULL,
  `pub_style_color` varchar(50) DEFAULT 'default',
  `pub_style_space` tinyint(2) UNSIGNED DEFAULT 10,
  `pub_style_corner` tinyint(2) UNSIGNED DEFAULT 0,
  `pub_style_modal_width` tinyint(2) UNSIGNED DEFAULT 80,
  `pub_responsive` enum('Yes','No') DEFAULT NULL,
  `pub_responsive_popup_title` varchar(50) DEFAULT NULL,
  `pub_responsive_cols` int(10) UNSIGNED DEFAULT 0,
  `pub_responsive_type` enum('Modal','Collapsed','Expanded') DEFAULT NULL,
  `pub_responsive_modal_hyperlinks` enum('If not listed','Never','Always') DEFAULT NULL,
  `pub_responsive_icon` enum('Yes','No') DEFAULT NULL,
  `pub_flat_scrollx` enum('Yes','No') DEFAULT NULL,
  `pub_show_advanced_settings` tinyint(1) DEFAULT NULL,
  `pub_default_where` varchar(2000) DEFAULT '',
  `pub_default_orderby` varchar(100) DEFAULT '',
  `pub_table_options_searching` char(3) DEFAULT 'on',
  `pub_table_options_ordering` char(3) DEFAULT 'on',
  `pub_table_options_paging` char(3) DEFAULT 'on',
  `pub_table_options_serverside` char(3) DEFAULT 'on',
  `pub_table_options_nl2br` char(3) DEFAULT NULL,
  `pub_table_options_advanced` text DEFAULT NULL,
  `pub_extentions` varchar(2000) DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpda_table_design`
--

CREATE TABLE `wpxp_wpda_table_design` (
  `wpda_table_name` varchar(64) NOT NULL,
  `wpda_schema_name` varchar(64) NOT NULL DEFAULT '',
  `wpda_table_design` text NOT NULL,
  `wpda_date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `wpda_last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpda_table_settings`
--

CREATE TABLE `wpxp_wpda_table_settings` (
  `wpda_schema_name` varchar(64) NOT NULL DEFAULT '',
  `wpda_table_name` varchar(64) NOT NULL,
  `wpda_table_settings` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpfm_backup`
--

CREATE TABLE `wpxp_wpfm_backup` (
  `id` int(11) NOT NULL,
  `backup_name` text DEFAULT NULL,
  `backup_date` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpforms_tasks_meta`
--

CREATE TABLE `wpxp_wpforms_tasks_meta` (
  `id` bigint(20) NOT NULL,
  `action` varchar(255) NOT NULL,
  `data` longtext NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpie_template`
--

CREATE TABLE `wpxp_wpie_template` (
  `id` int(11) NOT NULL,
  `status` varchar(25) DEFAULT NULL,
  `opration` varchar(100) NOT NULL,
  `username` varchar(60) NOT NULL,
  `unique_id` varchar(100) NOT NULL,
  `opration_type` varchar(100) NOT NULL,
  `options` longtext DEFAULT NULL,
  `process_log` varchar(255) DEFAULT NULL,
  `process_lock` int(3) DEFAULT NULL,
  `create_date` datetime NOT NULL,
  `last_update_date` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpum_fieldmeta`
--

CREATE TABLE `wpxp_wpum_fieldmeta` (
  `meta_id` bigint(20) UNSIGNED NOT NULL,
  `wpum_field_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpum_fields`
--

CREATE TABLE `wpxp_wpum_fields` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `group_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `field_order` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `type` varchar(20) NOT NULL DEFAULT 'text',
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpum_fieldsgroups`
--

CREATE TABLE `wpxp_wpum_fieldsgroups` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `group_order` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(190) NOT NULL DEFAULT '',
  `description` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpum_registration_formmeta`
--

CREATE TABLE `wpxp_wpum_registration_formmeta` (
  `meta_id` bigint(20) UNSIGNED NOT NULL,
  `wpum_registration_form_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpum_registration_forms`
--

CREATE TABLE `wpxp_wpum_registration_forms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpum_search_fields`
--

CREATE TABLE `wpxp_wpum_search_fields` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `meta_key` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpum_stripe_invoices`
--

CREATE TABLE `wpxp_wpum_stripe_invoices` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` varchar(255) NOT NULL,
  `total` decimal(8,2) NOT NULL,
  `currency` varchar(20) NOT NULL,
  `gateway_mode` varchar(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_wpum_stripe_subscriptions`
--

CREATE TABLE `wpxp_wpum_stripe_subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` varchar(255) NOT NULL,
  `plan_id` varchar(255) NOT NULL,
  `subscription_id` varchar(255) NOT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `gateway_mode` varchar(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_yoast_indexable`
--

CREATE TABLE `wpxp_yoast_indexable` (
  `id` int(11) UNSIGNED NOT NULL,
  `permalink` longtext DEFAULT NULL,
  `permalink_hash` varchar(40) DEFAULT NULL,
  `object_id` bigint(20) DEFAULT NULL,
  `object_type` varchar(32) NOT NULL,
  `object_sub_type` varchar(32) DEFAULT NULL,
  `author_id` bigint(20) DEFAULT NULL,
  `post_parent` bigint(20) DEFAULT NULL,
  `title` text DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `breadcrumb_title` text DEFAULT NULL,
  `post_status` varchar(20) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT NULL,
  `is_protected` tinyint(1) DEFAULT 0,
  `has_public_posts` tinyint(1) DEFAULT NULL,
  `number_of_pages` int(11) UNSIGNED DEFAULT NULL,
  `canonical` longtext DEFAULT NULL,
  `primary_focus_keyword` varchar(191) DEFAULT NULL,
  `primary_focus_keyword_score` int(3) DEFAULT NULL,
  `readability_score` int(3) DEFAULT NULL,
  `is_cornerstone` tinyint(1) DEFAULT 0,
  `is_robots_noindex` tinyint(1) DEFAULT 0,
  `is_robots_nofollow` tinyint(1) DEFAULT 0,
  `is_robots_noarchive` tinyint(1) DEFAULT 0,
  `is_robots_noimageindex` tinyint(1) DEFAULT 0,
  `is_robots_nosnippet` tinyint(1) DEFAULT 0,
  `twitter_title` text DEFAULT NULL,
  `twitter_image` longtext DEFAULT NULL,
  `twitter_description` longtext DEFAULT NULL,
  `twitter_image_id` varchar(191) DEFAULT NULL,
  `twitter_image_source` text DEFAULT NULL,
  `open_graph_title` text DEFAULT NULL,
  `open_graph_description` longtext DEFAULT NULL,
  `open_graph_image` longtext DEFAULT NULL,
  `open_graph_image_id` varchar(191) DEFAULT NULL,
  `open_graph_image_source` text DEFAULT NULL,
  `open_graph_image_meta` mediumtext DEFAULT NULL,
  `link_count` int(11) DEFAULT NULL,
  `incoming_link_count` int(11) DEFAULT NULL,
  `prominent_words_version` int(11) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `blog_id` bigint(20) NOT NULL DEFAULT 1,
  `language` varchar(32) DEFAULT NULL,
  `region` varchar(32) DEFAULT NULL,
  `schema_page_type` varchar(64) DEFAULT NULL,
  `schema_article_type` varchar(64) DEFAULT NULL,
  `has_ancestors` tinyint(1) DEFAULT 0,
  `estimated_reading_time_minutes` int(11) DEFAULT NULL,
  `version` int(11) DEFAULT 1,
  `object_last_modified` datetime DEFAULT NULL,
  `object_published_at` datetime DEFAULT NULL,
  `inclusive_language_score` int(3) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_yoast_indexable_hierarchy`
--

CREATE TABLE `wpxp_yoast_indexable_hierarchy` (
  `indexable_id` int(11) UNSIGNED NOT NULL,
  `ancestor_id` int(11) UNSIGNED NOT NULL,
  `depth` int(11) UNSIGNED DEFAULT NULL,
  `blog_id` bigint(20) NOT NULL DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_yoast_migrations`
--

CREATE TABLE `wpxp_yoast_migrations` (
  `id` int(11) UNSIGNED NOT NULL,
  `version` varchar(191) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_yoast_primary_term`
--

CREATE TABLE `wpxp_yoast_primary_term` (
  `id` int(11) UNSIGNED NOT NULL,
  `post_id` bigint(20) DEFAULT NULL,
  `term_id` bigint(20) DEFAULT NULL,
  `taxonomy` varchar(32) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `blog_id` bigint(20) NOT NULL DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wpxp_yoast_seo_links`
--

CREATE TABLE `wpxp_yoast_seo_links` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `post_id` bigint(20) UNSIGNED DEFAULT NULL,
  `target_post_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` varchar(8) DEFAULT NULL,
  `indexable_id` int(11) UNSIGNED DEFAULT NULL,
  `target_indexable_id` int(11) UNSIGNED DEFAULT NULL,
  `height` int(11) UNSIGNED DEFAULT NULL,
  `width` int(11) UNSIGNED DEFAULT NULL,
  `size` int(11) UNSIGNED DEFAULT NULL,
  `language` varchar(32) DEFAULT NULL,
  `region` varchar(32) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `rescue_admissions`
--
ALTER TABLE `rescue_admissions`
  ADD PRIMARY KEY (`admission_id`);

--
-- Indexes for table `rescue_alerts`
--
ALTER TABLE `rescue_alerts`
  ADD PRIMARY KEY (`alert_id`);

--
-- Indexes for table `rescue_animal_species`
--
ALTER TABLE `rescue_animal_species`
  ADD PRIMARY KEY (`species_id`);

--
-- Indexes for table `rescue_animal_types`
--
ALTER TABLE `rescue_animal_types`
  ADD PRIMARY KEY (`type_id`);

--
-- Indexes for table `rescue_areas`
--
ALTER TABLE `rescue_areas`
  ADD PRIMARY KEY (`area_id`);

--
-- Indexes for table `rescue_centres`
--
ALTER TABLE `rescue_centres`
  ADD PRIMARY KEY (`rescue_id`);

--
-- Indexes for table `rescue_connections`
--
ALTER TABLE `rescue_connections`
  ADD PRIMARY KEY (`connection_id`);

--
-- Indexes for table `rescue_dose_size`
--
ALTER TABLE `rescue_dose_size`
  ADD PRIMARY KEY (`dose_id`);

--
-- Indexes for table `rescue_finders`
--
ALTER TABLE `rescue_finders`
  ADD PRIMARY KEY (`finder_id`);

--
-- Indexes for table `rescue_frequencies`
--
ALTER TABLE `rescue_frequencies`
  ADD PRIMARY KEY (`frequency_id`);

--
-- Indexes for table `rescue_frequency_times`
--
ALTER TABLE `rescue_frequency_times`
  ADD PRIMARY KEY (`frequency_time_id`);

--
-- Indexes for table `rescue_incidents`
--
ALTER TABLE `rescue_incidents`
  ADD PRIMARY KEY (`incident_id`);

--
-- Indexes for table `rescue_incident_related`
--
ALTER TABLE `rescue_incident_related`
  ADD PRIMARY KEY (`inc_rel_id`);

--
-- Indexes for table `rescue_injury_record`
--
ALTER TABLE `rescue_injury_record`
  ADD PRIMARY KEY (`injury_id`);

--
-- Indexes for table `rescue_labs`
--
ALTER TABLE `rescue_labs`
  ADD PRIMARY KEY (`lab_id`);

--
-- Indexes for table `rescue_labs_tests`
--
ALTER TABLE `rescue_labs_tests`
  ADD PRIMARY KEY (`l_test_id`);

--
-- Indexes for table `rescue_locations`
--
ALTER TABLE `rescue_locations`
  ADD PRIMARY KEY (`location_id`);

--
-- Indexes for table `rescue_measurements`
--
ALTER TABLE `rescue_measurements`
  ADD PRIMARY KEY (`weight_id`);

--
-- Indexes for table `rescue_medications`
--
ALTER TABLE `rescue_medications`
  ADD PRIMARY KEY (`medication_id`);

--
-- Indexes for table `rescue_medications_given`
--
ALTER TABLE `rescue_medications_given`
  ADD PRIMARY KEY (`med_adm_id`);

--
-- Indexes for table `rescue_medication_trans`
--
ALTER TABLE `rescue_medication_trans`
  ADD PRIMARY KEY (`med_trans_id`);

--
-- Indexes for table `rescue_messages`
--
ALTER TABLE `rescue_messages`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `rescue_month_data`
--
ALTER TABLE `rescue_month_data`
  ADD PRIMARY KEY (`month_id`);

--
-- Indexes for table `rescue_networks`
--
ALTER TABLE `rescue_networks`
  ADD PRIMARY KEY (`network_id`);

--
-- Indexes for table `rescue_net_chat`
--
ALTER TABLE `rescue_net_chat`
  ADD PRIMARY KEY (`chat_id`);

--
-- Indexes for table `rescue_notes_patients`
--
ALTER TABLE `rescue_notes_patients`
  ADD PRIMARY KEY (`note_id`);

--
-- Indexes for table `rescue_orgs`
--
ALTER TABLE `rescue_orgs`
  ADD PRIMARY KEY (`org_id`);

--
-- Indexes for table `rescue_partner_log`
--
ALTER TABLE `rescue_partner_log`
  ADD PRIMARY KEY (`p_log_id`);

--
-- Indexes for table `rescue_partner_types`
--
ALTER TABLE `rescue_partner_types`
  ADD PRIMARY KEY (`p_type_id`);

--
-- Indexes for table `rescue_patients`
--
ALTER TABLE `rescue_patients`
  ADD PRIMARY KEY (`patient_id`);

--
-- Indexes for table `rescue_prescriptions`
--
ALTER TABLE `rescue_prescriptions`
  ADD PRIMARY KEY (`prescription_id`);

--
-- Indexes for table `rescue_presenting_complaints`
--
ALTER TABLE `rescue_presenting_complaints`
  ADD PRIMARY KEY (`pc_id`);

--
-- Indexes for table `rescue_query`
--
ALTER TABLE `rescue_query`
  ADD PRIMARY KEY (`q_id`);

--
-- Indexes for table `rescue_roles`
--
ALTER TABLE `rescue_roles`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `rescue_sample_types`
--
ALTER TABLE `rescue_sample_types`
  ADD PRIMARY KEY (`s_type_id`);

--
-- Indexes for table `rescue_severity_score`
--
ALTER TABLE `rescue_severity_score`
  ADD PRIMARY KEY (`ss_id`);

--
-- Indexes for table `rescue_stock_medication`
--
ALTER TABLE `rescue_stock_medication`
  ADD PRIMARY KEY (`medication_profile_id`);

--
-- Indexes for table `rescue_treatments`
--
ALTER TABLE `rescue_treatments`
  ADD PRIMARY KEY (`treatment_given_id`);

--
-- Indexes for table `rescue_triages`
--
ALTER TABLE `rescue_triages`
  ADD PRIMARY KEY (`triage_id`);

--
-- Indexes for table `rescue_weights`
--
ALTER TABLE `rescue_weights`
  ADD PRIMARY KEY (`weight_id`);

--
-- Indexes for table `wpxp_actionscheduler_actions`
--
ALTER TABLE `wpxp_actionscheduler_actions`
  ADD PRIMARY KEY (`action_id`),
  ADD KEY `hook` (`hook`),
  ADD KEY `status` (`status`),
  ADD KEY `scheduled_date_gmt` (`scheduled_date_gmt`),
  ADD KEY `args` (`args`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `last_attempt_gmt` (`last_attempt_gmt`),
  ADD KEY `claim_id_status_scheduled_date_gmt` (`claim_id`,`status`,`scheduled_date_gmt`);

--
-- Indexes for table `wpxp_actionscheduler_claims`
--
ALTER TABLE `wpxp_actionscheduler_claims`
  ADD PRIMARY KEY (`claim_id`),
  ADD KEY `date_created_gmt` (`date_created_gmt`);

--
-- Indexes for table `wpxp_actionscheduler_groups`
--
ALTER TABLE `wpxp_actionscheduler_groups`
  ADD PRIMARY KEY (`group_id`),
  ADD KEY `slug` (`slug`(191));

--
-- Indexes for table `wpxp_actionscheduler_logs`
--
ALTER TABLE `wpxp_actionscheduler_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `action_id` (`action_id`),
  ADD KEY `log_date_gmt` (`log_date_gmt`);

--
-- Indexes for table `wpxp_commentmeta`
--
ALTER TABLE `wpxp_commentmeta`
  ADD PRIMARY KEY (`meta_id`),
  ADD KEY `comment_id` (`comment_id`),
  ADD KEY `meta_key` (`meta_key`(191));

--
-- Indexes for table `wpxp_comments`
--
ALTER TABLE `wpxp_comments`
  ADD PRIMARY KEY (`comment_ID`),
  ADD KEY `comment_post_ID` (`comment_post_ID`),
  ADD KEY `comment_approved_date_gmt` (`comment_approved`,`comment_date_gmt`),
  ADD KEY `comment_date_gmt` (`comment_date_gmt`),
  ADD KEY `comment_parent` (`comment_parent`),
  ADD KEY `comment_author_email` (`comment_author_email`(10)),
  ADD KEY `woo_idx_comment_type` (`comment_type`);

--
-- Indexes for table `wpxp_connections`
--
ALTER TABLE `wpxp_connections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `slug` (`slug`(191));
ALTER TABLE `wpxp_connections` ADD FULLTEXT KEY `search` (`family_name`,`first_name`,`middle_name`,`last_name`,`title`,`organization`,`department`,`contact_first_name`,`contact_last_name`,`bio`,`notes`);

--
-- Indexes for table `wpxp_connections_address`
--
ALTER TABLE `wpxp_connections_address`
  ADD PRIMARY KEY (`id`,`entry_id`);
ALTER TABLE `wpxp_connections_address` ADD FULLTEXT KEY `search` (`line_1`,`line_2`,`line_3`,`city`,`state`,`zipcode`,`country`);

--
-- Indexes for table `wpxp_connections_date`
--
ALTER TABLE `wpxp_connections_date`
  ADD PRIMARY KEY (`id`,`entry_id`);

--
-- Indexes for table `wpxp_connections_email`
--
ALTER TABLE `wpxp_connections_email`
  ADD PRIMARY KEY (`id`,`entry_id`);

--
-- Indexes for table `wpxp_connections_link`
--
ALTER TABLE `wpxp_connections_link`
  ADD PRIMARY KEY (`id`,`entry_id`);

--
-- Indexes for table `wpxp_connections_messenger`
--
ALTER TABLE `wpxp_connections_messenger`
  ADD PRIMARY KEY (`id`,`entry_id`);

--
-- Indexes for table `wpxp_connections_meta`
--
ALTER TABLE `wpxp_connections_meta`
  ADD PRIMARY KEY (`meta_id`),
  ADD KEY `entry_id` (`entry_id`),
  ADD KEY `meta_key` (`meta_key`(191));

--
-- Indexes for table `wpxp_connections_phone`
--
ALTER TABLE `wpxp_connections_phone`
  ADD PRIMARY KEY (`id`,`entry_id`);
ALTER TABLE `wpxp_connections_phone` ADD FULLTEXT KEY `search` (`number`);

--
-- Indexes for table `wpxp_connections_social`
--
ALTER TABLE `wpxp_connections_social`
  ADD PRIMARY KEY (`id`,`entry_id`);

--
-- Indexes for table `wpxp_connections_terms`
--
ALTER TABLE `wpxp_connections_terms`
  ADD PRIMARY KEY (`term_id`),
  ADD KEY `slug` (`slug`(191)),
  ADD KEY `name` (`name`(191));

--
-- Indexes for table `wpxp_connections_term_meta`
--
ALTER TABLE `wpxp_connections_term_meta`
  ADD PRIMARY KEY (`meta_id`),
  ADD KEY `term_id` (`term_id`),
  ADD KEY `meta_key` (`meta_key`(191));

--
-- Indexes for table `wpxp_connections_term_relationships`
--
ALTER TABLE `wpxp_connections_term_relationships`
  ADD PRIMARY KEY (`entry_id`,`term_taxonomy_id`),
  ADD KEY `term_taxonomy_id` (`term_taxonomy_id`);

--
-- Indexes for table `wpxp_connections_term_taxonomy`
--
ALTER TABLE `wpxp_connections_term_taxonomy`
  ADD PRIMARY KEY (`term_taxonomy_id`),
  ADD UNIQUE KEY `term_id_taxonomy` (`term_id`,`taxonomy`),
  ADD KEY `taxonomy` (`taxonomy`);

--
-- Indexes for table `wpxp_e_events`
--
ALTER TABLE `wpxp_e_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_at_index` (`created_at`);

--
-- Indexes for table `wpxp_e_notes`
--
ALTER TABLE `wpxp_e_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `route_url_index` (`route_url`(191)),
  ADD KEY `post_id_index` (`post_id`),
  ADD KEY `element_id_index` (`element_id`),
  ADD KEY `parent_id_index` (`parent_id`),
  ADD KEY `author_id_index` (`author_id`),
  ADD KEY `status_index` (`status`),
  ADD KEY `is_resolved_index` (`is_resolved`),
  ADD KEY `is_public_index` (`is_public`),
  ADD KEY `created_at_index` (`created_at`),
  ADD KEY `updated_at_index` (`updated_at`),
  ADD KEY `last_activity_at_index` (`last_activity_at`);

--
-- Indexes for table `wpxp_e_notes_users_relations`
--
ALTER TABLE `wpxp_e_notes_users_relations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type_index` (`type`),
  ADD KEY `note_id_index` (`note_id`),
  ADD KEY `user_id_index` (`user_id`);

--
-- Indexes for table `wpxp_e_submissions`
--
ALTER TABLE `wpxp_e_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hash_id_unique_index` (`hash_id`),
  ADD KEY `main_meta_id_index` (`main_meta_id`),
  ADD KEY `hash_id_index` (`hash_id`),
  ADD KEY `type_index` (`type`),
  ADD KEY `post_id_index` (`post_id`),
  ADD KEY `element_id_index` (`element_id`),
  ADD KEY `campaign_id_index` (`campaign_id`),
  ADD KEY `user_id_index` (`user_id`),
  ADD KEY `user_ip_index` (`user_ip`),
  ADD KEY `status_index` (`status`),
  ADD KEY `is_read_index` (`is_read`),
  ADD KEY `created_at_gmt_index` (`created_at_gmt`),
  ADD KEY `updated_at_gmt_index` (`updated_at_gmt`),
  ADD KEY `created_at_index` (`created_at`),
  ADD KEY `updated_at_index` (`updated_at`),
  ADD KEY `referer_index` (`referer`(191)),
  ADD KEY `referer_title_index` (`referer_title`(191));

--
-- Indexes for table `wpxp_e_submissions_actions_log`
--
ALTER TABLE `wpxp_e_submissions_actions_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `submission_id_index` (`submission_id`),
  ADD KEY `action_name_index` (`action_name`),
  ADD KEY `status_index` (`status`),
  ADD KEY `created_at_gmt_index` (`created_at_gmt`),
  ADD KEY `updated_at_gmt_index` (`updated_at_gmt`),
  ADD KEY `created_at_index` (`created_at`),
  ADD KEY `updated_at_index` (`updated_at`);

--
-- Indexes for table `wpxp_e_submissions_values`
--
ALTER TABLE `wpxp_e_submissions_values`
  ADD PRIMARY KEY (`id`),
  ADD KEY `submission_id_index` (`submission_id`),
  ADD KEY `key_index` (`key`);

--
-- Indexes for table `wpxp_links`
--
ALTER TABLE `wpxp_links`
  ADD PRIMARY KEY (`link_id`),
  ADD KEY `link_visible` (`link_visible`);

--
-- Indexes for table `wpxp_loginizer_logs`
--
ALTER TABLE `wpxp_loginizer_logs`
  ADD UNIQUE KEY `ip` (`ip`);

--
-- Indexes for table `wpxp_login_log`
--
ALTER TABLE `wpxp_login_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_moodle_enrollment`
--
ALTER TABLE `wpxp_moodle_enrollment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_options`
--
ALTER TABLE `wpxp_options`
  ADD PRIMARY KEY (`option_id`),
  ADD UNIQUE KEY `option_name` (`option_name`),
  ADD KEY `autoload` (`autoload`);

--
-- Indexes for table `wpxp_pmpro_discount_codes`
--
ALTER TABLE `wpxp_pmpro_discount_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `starts` (`starts`),
  ADD KEY `expires` (`expires`);

--
-- Indexes for table `wpxp_pmpro_discount_codes_levels`
--
ALTER TABLE `wpxp_pmpro_discount_codes_levels`
  ADD PRIMARY KEY (`code_id`,`level_id`),
  ADD KEY `initial_payment` (`initial_payment`);

--
-- Indexes for table `wpxp_pmpro_discount_codes_uses`
--
ALTER TABLE `wpxp_pmpro_discount_codes_uses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `timestamp` (`timestamp`);

--
-- Indexes for table `wpxp_pmpro_memberships_categories`
--
ALTER TABLE `wpxp_pmpro_memberships_categories`
  ADD PRIMARY KEY (`membership_id`,`category_id`),
  ADD UNIQUE KEY `category_membership` (`category_id`,`membership_id`);

--
-- Indexes for table `wpxp_pmpro_memberships_pages`
--
ALTER TABLE `wpxp_pmpro_memberships_pages`
  ADD PRIMARY KEY (`page_id`,`membership_id`),
  ADD UNIQUE KEY `membership_page` (`membership_id`,`page_id`);

--
-- Indexes for table `wpxp_pmpro_memberships_users`
--
ALTER TABLE `wpxp_pmpro_memberships_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `membership_id` (`membership_id`),
  ADD KEY `modified` (`modified`),
  ADD KEY `code_id` (`code_id`),
  ADD KEY `enddate` (`enddate`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `wpxp_pmpro_membership_levelmeta`
--
ALTER TABLE `wpxp_pmpro_membership_levelmeta`
  ADD PRIMARY KEY (`meta_id`),
  ADD KEY `pmpro_membership_level_id` (`pmpro_membership_level_id`),
  ADD KEY `meta_key` (`meta_key`);

--
-- Indexes for table `wpxp_pmpro_membership_levels`
--
ALTER TABLE `wpxp_pmpro_membership_levels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `allow_signups` (`allow_signups`),
  ADD KEY `initial_payment` (`initial_payment`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `wpxp_pmpro_membership_ordermeta`
--
ALTER TABLE `wpxp_pmpro_membership_ordermeta`
  ADD PRIMARY KEY (`meta_id`),
  ADD KEY `pmpro_membership_order_id` (`pmpro_membership_order_id`),
  ADD KEY `meta_key` (`meta_key`);

--
-- Indexes for table `wpxp_pmpro_membership_orders`
--
ALTER TABLE `wpxp_pmpro_membership_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `membership_id` (`membership_id`),
  ADD KEY `status` (`status`),
  ADD KEY `timestamp` (`timestamp`),
  ADD KEY `gateway` (`gateway`),
  ADD KEY `gateway_environment` (`gateway_environment`),
  ADD KEY `payment_transaction_id` (`payment_transaction_id`),
  ADD KEY `subscription_transaction_id` (`subscription_transaction_id`),
  ADD KEY `affiliate_id` (`affiliate_id`),
  ADD KEY `affiliate_subid` (`affiliate_subid`),
  ADD KEY `checkout_id` (`checkout_id`);

--
-- Indexes for table `wpxp_pmxi_files`
--
ALTER TABLE `wpxp_pmxi_files`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_pmxi_hash`
--
ALTER TABLE `wpxp_pmxi_hash`
  ADD PRIMARY KEY (`hash`);

--
-- Indexes for table `wpxp_pmxi_history`
--
ALTER TABLE `wpxp_pmxi_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_pmxi_images`
--
ALTER TABLE `wpxp_pmxi_images`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_pmxi_imports`
--
ALTER TABLE `wpxp_pmxi_imports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_pmxi_posts`
--
ALTER TABLE `wpxp_pmxi_posts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_pmxi_templates`
--
ALTER TABLE `wpxp_pmxi_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_podsrel`
--
ALTER TABLE `wpxp_podsrel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `field_item_idx` (`field_id`,`item_id`),
  ADD KEY `rel_field_rel_item_idx` (`related_field_id`,`related_item_id`),
  ADD KEY `field_rel_item_idx` (`field_id`,`related_item_id`),
  ADD KEY `rel_field_item_idx` (`related_field_id`,`item_id`);

--
-- Indexes for table `wpxp_postmeta`
--
ALTER TABLE `wpxp_postmeta`
  ADD PRIMARY KEY (`meta_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `meta_key` (`meta_key`(191));

--
-- Indexes for table `wpxp_posts`
--
ALTER TABLE `wpxp_posts`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `post_name` (`post_name`(191)),
  ADD KEY `type_status_date` (`post_type`,`post_status`,`post_date`,`ID`),
  ADD KEY `post_parent` (`post_parent`),
  ADD KEY `post_author` (`post_author`);

--
-- Indexes for table `wpxp_promag_email_templates`
--
ALTER TABLE `wpxp_promag_email_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_promag_fields`
--
ALTER TABLE `wpxp_promag_fields`
  ADD PRIMARY KEY (`field_id`);

--
-- Indexes for table `wpxp_promag_friends`
--
ALTER TABLE `wpxp_promag_friends`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_promag_groups`
--
ALTER TABLE `wpxp_promag_groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_promag_group_requests`
--
ALTER TABLE `wpxp_promag_group_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_promag_group_update_request`
--
ALTER TABLE `wpxp_promag_group_update_request`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_promag_msg_conversation`
--
ALTER TABLE `wpxp_promag_msg_conversation`
  ADD PRIMARY KEY (`m_id`),
  ADD KEY `t_id` (`t_id`);

--
-- Indexes for table `wpxp_promag_msg_threads`
--
ALTER TABLE `wpxp_promag_msg_threads`
  ADD PRIMARY KEY (`t_id`);

--
-- Indexes for table `wpxp_promag_notification`
--
ALTER TABLE `wpxp_promag_notification`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_promag_paypal_log`
--
ALTER TABLE `wpxp_promag_paypal_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_promag_sections`
--
ALTER TABLE `wpxp_promag_sections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_rank_math_internal_links`
--
ALTER TABLE `wpxp_rank_math_internal_links`
  ADD PRIMARY KEY (`id`),
  ADD KEY `link_direction` (`post_id`,`type`);

--
-- Indexes for table `wpxp_rank_math_internal_meta`
--
ALTER TABLE `wpxp_rank_math_internal_meta`
  ADD PRIMARY KEY (`object_id`);

--
-- Indexes for table `wpxp_routiz_messages`
--
ALTER TABLE `wpxp_routiz_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_routiz_notifications`
--
ALTER TABLE `wpxp_routiz_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_routiz_user_packages`
--
ALTER TABLE `wpxp_routiz_user_packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_routiz_views`
--
ALTER TABLE `wpxp_routiz_views`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_routiz_visits`
--
ALTER TABLE `wpxp_routiz_visits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_routiz_wallet`
--
ALTER TABLE `wpxp_routiz_wallet`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `wpxp_routiz_wallet_payouts`
--
ALTER TABLE `wpxp_routiz_wallet_payouts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_routiz_wallet_transactions`
--
ALTER TABLE `wpxp_routiz_wallet_transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_swpm_membership_meta_tbl`
--
ALTER TABLE `wpxp_swpm_membership_meta_tbl`
  ADD PRIMARY KEY (`id`),
  ADD KEY `level_id` (`level_id`);

--
-- Indexes for table `wpxp_swpm_membership_tbl`
--
ALTER TABLE `wpxp_swpm_membership_tbl`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_swpm_members_meta_tbl`
--
ALTER TABLE `wpxp_swpm_members_meta_tbl`
  ADD PRIMARY KEY (`meta_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `wpxp_swpm_members_tbl`
--
ALTER TABLE `wpxp_swpm_members_tbl`
  ADD PRIMARY KEY (`member_id`);

--
-- Indexes for table `wpxp_swpm_payments_tbl`
--
ALTER TABLE `wpxp_swpm_payments_tbl`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_termmeta`
--
ALTER TABLE `wpxp_termmeta`
  ADD PRIMARY KEY (`meta_id`),
  ADD KEY `term_id` (`term_id`),
  ADD KEY `meta_key` (`meta_key`(191));

--
-- Indexes for table `wpxp_terms`
--
ALTER TABLE `wpxp_terms`
  ADD PRIMARY KEY (`term_id`),
  ADD KEY `slug` (`slug`(191)),
  ADD KEY `name` (`name`(191));

--
-- Indexes for table `wpxp_term_relationships`
--
ALTER TABLE `wpxp_term_relationships`
  ADD PRIMARY KEY (`object_id`,`term_taxonomy_id`),
  ADD KEY `term_taxonomy_id` (`term_taxonomy_id`);

--
-- Indexes for table `wpxp_term_taxonomy`
--
ALTER TABLE `wpxp_term_taxonomy`
  ADD PRIMARY KEY (`term_taxonomy_id`),
  ADD UNIQUE KEY `term_id_taxonomy` (`term_id`,`taxonomy`),
  ADD KEY `taxonomy` (`taxonomy`);

--
-- Indexes for table `wpxp_trp_gettext_en_gb`
--
ALTER TABLE `wpxp_trp_gettext_en_gb`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `index_name` (`original`(100));
ALTER TABLE `wpxp_trp_gettext_en_gb` ADD FULLTEXT KEY `original_fulltext` (`original`);

--
-- Indexes for table `wpxp_trp_gettext_original_meta`
--
ALTER TABLE `wpxp_trp_gettext_original_meta`
  ADD PRIMARY KEY (`meta_id`),
  ADD UNIQUE KEY `meta_id` (`meta_id`),
  ADD KEY `gettext_index_original_id` (`original_id`),
  ADD KEY `gettext_meta_key` (`meta_key`(250));

--
-- Indexes for table `wpxp_trp_gettext_original_strings`
--
ALTER TABLE `wpxp_trp_gettext_original_strings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gettext_index_original` (`original`(100));

--
-- Indexes for table `wpxp_tutor_carts`
--
ALTER TABLE `wpxp_tutor_carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `coupon_code` (`coupon_code`);

--
-- Indexes for table `wpxp_tutor_cart_items`
--
ALTER TABLE `wpxp_tutor_cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `wpxp_tutor_coupons`
--
ALTER TABLE `wpxp_tutor_coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `coupon_code` (`coupon_code`),
  ADD KEY `start_date_gmt` (`start_date_gmt`),
  ADD KEY `expire_date_gmt` (`expire_date_gmt`);

--
-- Indexes for table `wpxp_tutor_coupon_applications`
--
ALTER TABLE `wpxp_tutor_coupon_applications`
  ADD KEY `coupon_code` (`coupon_code`),
  ADD KEY `reference_id` (`reference_id`);

--
-- Indexes for table `wpxp_tutor_coupon_usages`
--
ALTER TABLE `wpxp_tutor_coupon_usages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coupon_code` (`coupon_code`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `wpxp_tutor_customers`
--
ALTER TABLE `wpxp_tutor_customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `billing_email` (`billing_email`);

--
-- Indexes for table `wpxp_tutor_earnings`
--
ALTER TABLE `wpxp_tutor_earnings`
  ADD PRIMARY KEY (`earning_id`);

--
-- Indexes for table `wpxp_tutor_ordermeta`
--
ALTER TABLE `wpxp_tutor_ordermeta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `meta_key` (`meta_key`);

--
-- Indexes for table `wpxp_tutor_orders`
--
ALTER TABLE `wpxp_tutor_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_type` (`order_type`),
  ADD KEY `payment_status` (`payment_status`),
  ADD KEY `order_status` (`order_status`),
  ADD KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `wpxp_tutor_order_items`
--
ALTER TABLE `wpxp_tutor_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `wpxp_tutor_quiz_attempts`
--
ALTER TABLE `wpxp_tutor_quiz_attempts`
  ADD PRIMARY KEY (`attempt_id`);

--
-- Indexes for table `wpxp_tutor_quiz_attempt_answers`
--
ALTER TABLE `wpxp_tutor_quiz_attempt_answers`
  ADD PRIMARY KEY (`attempt_answer_id`);

--
-- Indexes for table `wpxp_tutor_quiz_questions`
--
ALTER TABLE `wpxp_tutor_quiz_questions`
  ADD PRIMARY KEY (`question_id`);

--
-- Indexes for table `wpxp_tutor_quiz_question_answers`
--
ALTER TABLE `wpxp_tutor_quiz_question_answers`
  ADD PRIMARY KEY (`answer_id`);

--
-- Indexes for table `wpxp_tutor_withdraws`
--
ALTER TABLE `wpxp_tutor_withdraws`
  ADD PRIMARY KEY (`withdraw_id`);

--
-- Indexes for table `wpxp_um_metadata`
--
ALTER TABLE `wpxp_um_metadata`
  ADD PRIMARY KEY (`umeta_id`),
  ADD KEY `user_id_indx` (`user_id`),
  ADD KEY `meta_key_indx` (`um_key`),
  ADD KEY `meta_value_indx` (`um_value`(191));

--
-- Indexes for table `wpxp_usermeta`
--
ALTER TABLE `wpxp_usermeta`
  ADD PRIMARY KEY (`umeta_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `meta_key` (`meta_key`(191));

--
-- Indexes for table `wpxp_users`
--
ALTER TABLE `wpxp_users`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `user_login_key` (`user_login`),
  ADD KEY `user_nicename` (`user_nicename`),
  ADD KEY `user_email` (`user_email`);

--
-- Indexes for table `wpxp_user_registration_sessions`
--
ALTER TABLE `wpxp_user_registration_sessions`
  ADD PRIMARY KEY (`session_key`),
  ADD UNIQUE KEY `session_id` (`session_id`);

--
-- Indexes for table `wpxp_uwp_form_extras`
--
ALTER TABLE `wpxp_uwp_form_extras`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_uwp_form_fields`
--
ALTER TABLE `wpxp_uwp_form_fields`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_uwp_profile_tabs`
--
ALTER TABLE `wpxp_uwp_profile_tabs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_uwp_user_sorting`
--
ALTER TABLE `wpxp_uwp_user_sorting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_wc_admin_notes`
--
ALTER TABLE `wpxp_wc_admin_notes`
  ADD PRIMARY KEY (`note_id`);

--
-- Indexes for table `wpxp_wc_admin_note_actions`
--
ALTER TABLE `wpxp_wc_admin_note_actions`
  ADD PRIMARY KEY (`action_id`),
  ADD KEY `note_id` (`note_id`);

--
-- Indexes for table `wpxp_wc_category_lookup`
--
ALTER TABLE `wpxp_wc_category_lookup`
  ADD PRIMARY KEY (`category_tree_id`,`category_id`);

--
-- Indexes for table `wpxp_wc_customer_lookup`
--
ALTER TABLE `wpxp_wc_customer_lookup`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `wpxp_wc_download_log`
--
ALTER TABLE `wpxp_wc_download_log`
  ADD PRIMARY KEY (`download_log_id`),
  ADD KEY `permission_id` (`permission_id`),
  ADD KEY `timestamp` (`timestamp`);

--
-- Indexes for table `wpxp_wc_order_coupon_lookup`
--
ALTER TABLE `wpxp_wc_order_coupon_lookup`
  ADD PRIMARY KEY (`order_id`,`coupon_id`),
  ADD KEY `coupon_id` (`coupon_id`),
  ADD KEY `date_created` (`date_created`);

--
-- Indexes for table `wpxp_wc_order_product_lookup`
--
ALTER TABLE `wpxp_wc_order_product_lookup`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `date_created` (`date_created`);

--
-- Indexes for table `wpxp_wc_order_stats`
--
ALTER TABLE `wpxp_wc_order_stats`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `date_created` (`date_created`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `status` (`status`(191));

--
-- Indexes for table `wpxp_wc_order_tax_lookup`
--
ALTER TABLE `wpxp_wc_order_tax_lookup`
  ADD PRIMARY KEY (`order_id`,`tax_rate_id`),
  ADD KEY `tax_rate_id` (`tax_rate_id`),
  ADD KEY `date_created` (`date_created`);

--
-- Indexes for table `wpxp_wc_product_attributes_lookup`
--
ALTER TABLE `wpxp_wc_product_attributes_lookup`
  ADD PRIMARY KEY (`product_or_parent_id`,`term_id`,`product_id`,`taxonomy`),
  ADD KEY `is_variation_attribute_term_id` (`is_variation_attribute`,`term_id`);

--
-- Indexes for table `wpxp_wc_product_download_directories`
--
ALTER TABLE `wpxp_wc_product_download_directories`
  ADD PRIMARY KEY (`url_id`),
  ADD KEY `url` (`url`(191));

--
-- Indexes for table `wpxp_wc_product_meta_lookup`
--
ALTER TABLE `wpxp_wc_product_meta_lookup`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `virtual` (`virtual`),
  ADD KEY `downloadable` (`downloadable`),
  ADD KEY `stock_status` (`stock_status`),
  ADD KEY `stock_quantity` (`stock_quantity`),
  ADD KEY `onsale` (`onsale`),
  ADD KEY `min_max_price` (`min_price`,`max_price`);

--
-- Indexes for table `wpxp_wc_rate_limits`
--
ALTER TABLE `wpxp_wc_rate_limits`
  ADD PRIMARY KEY (`rate_limit_id`),
  ADD UNIQUE KEY `rate_limit_key` (`rate_limit_key`(191));

--
-- Indexes for table `wpxp_wc_reserved_stock`
--
ALTER TABLE `wpxp_wc_reserved_stock`
  ADD PRIMARY KEY (`order_id`,`product_id`);

--
-- Indexes for table `wpxp_wc_tax_rate_classes`
--
ALTER TABLE `wpxp_wc_tax_rate_classes`
  ADD PRIMARY KEY (`tax_rate_class_id`),
  ADD UNIQUE KEY `slug` (`slug`(191));

--
-- Indexes for table `wpxp_wc_webhooks`
--
ALTER TABLE `wpxp_wc_webhooks`
  ADD PRIMARY KEY (`webhook_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `wpxp_woocommerce_api_keys`
--
ALTER TABLE `wpxp_woocommerce_api_keys`
  ADD PRIMARY KEY (`key_id`),
  ADD KEY `consumer_key` (`consumer_key`),
  ADD KEY `consumer_secret` (`consumer_secret`);

--
-- Indexes for table `wpxp_woocommerce_attribute_taxonomies`
--
ALTER TABLE `wpxp_woocommerce_attribute_taxonomies`
  ADD PRIMARY KEY (`attribute_id`),
  ADD KEY `attribute_name` (`attribute_name`(20));

--
-- Indexes for table `wpxp_woocommerce_downloadable_product_permissions`
--
ALTER TABLE `wpxp_woocommerce_downloadable_product_permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD KEY `download_order_key_product` (`product_id`,`order_id`,`order_key`(16),`download_id`),
  ADD KEY `download_order_product` (`download_id`,`order_id`,`product_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_order_remaining_expires` (`user_id`,`order_id`,`downloads_remaining`,`access_expires`);

--
-- Indexes for table `wpxp_woocommerce_log`
--
ALTER TABLE `wpxp_woocommerce_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `level` (`level`);

--
-- Indexes for table `wpxp_woocommerce_order_itemmeta`
--
ALTER TABLE `wpxp_woocommerce_order_itemmeta`
  ADD PRIMARY KEY (`meta_id`),
  ADD KEY `order_item_id` (`order_item_id`),
  ADD KEY `meta_key` (`meta_key`(32));

--
-- Indexes for table `wpxp_woocommerce_order_items`
--
ALTER TABLE `wpxp_woocommerce_order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `wpxp_woocommerce_payment_tokenmeta`
--
ALTER TABLE `wpxp_woocommerce_payment_tokenmeta`
  ADD PRIMARY KEY (`meta_id`),
  ADD KEY `payment_token_id` (`payment_token_id`),
  ADD KEY `meta_key` (`meta_key`(32));

--
-- Indexes for table `wpxp_woocommerce_payment_tokens`
--
ALTER TABLE `wpxp_woocommerce_payment_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `wpxp_woocommerce_sessions`
--
ALTER TABLE `wpxp_woocommerce_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD UNIQUE KEY `session_key` (`session_key`);

--
-- Indexes for table `wpxp_woocommerce_shipping_zones`
--
ALTER TABLE `wpxp_woocommerce_shipping_zones`
  ADD PRIMARY KEY (`zone_id`);

--
-- Indexes for table `wpxp_woocommerce_shipping_zone_locations`
--
ALTER TABLE `wpxp_woocommerce_shipping_zone_locations`
  ADD PRIMARY KEY (`location_id`),
  ADD KEY `location_id` (`location_id`),
  ADD KEY `location_type_code` (`location_type`(10),`location_code`(20));

--
-- Indexes for table `wpxp_woocommerce_shipping_zone_methods`
--
ALTER TABLE `wpxp_woocommerce_shipping_zone_methods`
  ADD PRIMARY KEY (`instance_id`);

--
-- Indexes for table `wpxp_woocommerce_tax_rates`
--
ALTER TABLE `wpxp_woocommerce_tax_rates`
  ADD PRIMARY KEY (`tax_rate_id`),
  ADD KEY `tax_rate_country` (`tax_rate_country`),
  ADD KEY `tax_rate_state` (`tax_rate_state`(2)),
  ADD KEY `tax_rate_class` (`tax_rate_class`(10)),
  ADD KEY `tax_rate_priority` (`tax_rate_priority`);

--
-- Indexes for table `wpxp_woocommerce_tax_rate_locations`
--
ALTER TABLE `wpxp_woocommerce_tax_rate_locations`
  ADD PRIMARY KEY (`location_id`),
  ADD KEY `tax_rate_id` (`tax_rate_id`),
  ADD KEY `location_type_code` (`location_type`(10),`location_code`(20));

--
-- Indexes for table `wpxp_wpbdp_form_fields`
--
ALTER TABLE `wpxp_wpbdp_form_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `field_type` (`field_type`);

--
-- Indexes for table `wpxp_wpbdp_listings`
--
ALTER TABLE `wpxp_wpbdp_listings`
  ADD PRIMARY KEY (`listing_id`);

--
-- Indexes for table `wpxp_wpbdp_logs`
--
ALTER TABLE `wpxp_wpbdp_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_wpbdp_payments`
--
ALTER TABLE `wpxp_wpbdp_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `listing_id` (`listing_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `wpxp_wpbdp_plans`
--
ALTER TABLE `wpxp_wpbdp_plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_wpda_csv_uploads`
--
ALTER TABLE `wpxp_wpda_csv_uploads`
  ADD PRIMARY KEY (`csv_id`);

--
-- Indexes for table `wpxp_wpda_logging`
--
ALTER TABLE `wpxp_wpda_logging`
  ADD PRIMARY KEY (`log_time`,`log_id`);

--
-- Indexes for table `wpxp_wpda_media`
--
ALTER TABLE `wpxp_wpda_media`
  ADD PRIMARY KEY (`media_schema_name`,`media_table_name`,`media_column_name`);

--
-- Indexes for table `wpxp_wpda_menus`
--
ALTER TABLE `wpxp_wpda_menus`
  ADD PRIMARY KEY (`menu_id`);

--
-- Indexes for table `wpxp_wpda_project`
--
ALTER TABLE `wpxp_wpda_project`
  ADD PRIMARY KEY (`project_id`),
  ADD UNIQUE KEY `wpxp_wpdp_project_project_name` (`project_name`);

--
-- Indexes for table `wpxp_wpda_project_page`
--
ALTER TABLE `wpxp_wpda_project_page`
  ADD PRIMARY KEY (`page_id`),
  ADD UNIQUE KEY `project_id` (`project_id`,`page_name`,`page_role`);

--
-- Indexes for table `wpxp_wpda_project_table`
--
ALTER TABLE `wpxp_wpda_project_table`
  ADD PRIMARY KEY (`wpda_schema_name`,`wpda_table_name`,`wpda_table_setname`);

--
-- Indexes for table `wpxp_wpda_publisher`
--
ALTER TABLE `wpxp_wpda_publisher`
  ADD PRIMARY KEY (`pub_id`),
  ADD UNIQUE KEY `pub_name` (`pub_name`);

--
-- Indexes for table `wpxp_wpda_table_design`
--
ALTER TABLE `wpxp_wpda_table_design`
  ADD PRIMARY KEY (`wpda_schema_name`,`wpda_table_name`);

--
-- Indexes for table `wpxp_wpda_table_settings`
--
ALTER TABLE `wpxp_wpda_table_settings`
  ADD PRIMARY KEY (`wpda_schema_name`,`wpda_table_name`);

--
-- Indexes for table `wpxp_wpfm_backup`
--
ALTER TABLE `wpxp_wpfm_backup`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_wpforms_tasks_meta`
--
ALTER TABLE `wpxp_wpforms_tasks_meta`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_wpie_template`
--
ALTER TABLE `wpxp_wpie_template`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_wpum_fieldmeta`
--
ALTER TABLE `wpxp_wpum_fieldmeta`
  ADD PRIMARY KEY (`meta_id`),
  ADD KEY `wpum_field_id` (`wpum_field_id`),
  ADD KEY `meta_key` (`meta_key`(191));

--
-- Indexes for table `wpxp_wpum_fields`
--
ALTER TABLE `wpxp_wpum_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `field_order` (`field_order`);

--
-- Indexes for table `wpxp_wpum_fieldsgroups`
--
ALTER TABLE `wpxp_wpum_fieldsgroups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`),
  ADD KEY `group_order` (`group_order`);

--
-- Indexes for table `wpxp_wpum_registration_formmeta`
--
ALTER TABLE `wpxp_wpum_registration_formmeta`
  ADD PRIMARY KEY (`meta_id`),
  ADD KEY `wpum_registration_form_id` (`wpum_registration_form_id`),
  ADD KEY `meta_key` (`meta_key`(191));

--
-- Indexes for table `wpxp_wpum_registration_forms`
--
ALTER TABLE `wpxp_wpum_registration_forms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_wpum_search_fields`
--
ALTER TABLE `wpxp_wpum_search_fields`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_wpum_stripe_invoices`
--
ALTER TABLE `wpxp_wpum_stripe_invoices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_wpum_stripe_subscriptions`
--
ALTER TABLE `wpxp_wpum_stripe_subscriptions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wpxp_yoast_indexable`
--
ALTER TABLE `wpxp_yoast_indexable`
  ADD PRIMARY KEY (`id`),
  ADD KEY `object_type_and_sub_type` (`object_type`,`object_sub_type`),
  ADD KEY `object_id_and_type` (`object_id`,`object_type`),
  ADD KEY `permalink_hash_and_object_type` (`permalink_hash`,`object_type`),
  ADD KEY `subpages` (`post_parent`,`object_type`,`post_status`,`object_id`),
  ADD KEY `prominent_words` (`prominent_words_version`,`object_type`,`object_sub_type`,`post_status`),
  ADD KEY `published_sitemap_index` (`object_published_at`,`is_robots_noindex`,`object_type`,`object_sub_type`);

--
-- Indexes for table `wpxp_yoast_indexable_hierarchy`
--
ALTER TABLE `wpxp_yoast_indexable_hierarchy`
  ADD PRIMARY KEY (`indexable_id`,`ancestor_id`),
  ADD KEY `indexable_id` (`indexable_id`),
  ADD KEY `ancestor_id` (`ancestor_id`),
  ADD KEY `depth` (`depth`);

--
-- Indexes for table `wpxp_yoast_migrations`
--
ALTER TABLE `wpxp_yoast_migrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wpxp_yoast_migrations_version` (`version`);

--
-- Indexes for table `wpxp_yoast_primary_term`
--
ALTER TABLE `wpxp_yoast_primary_term`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_taxonomy` (`post_id`,`taxonomy`),
  ADD KEY `post_term` (`post_id`,`term_id`);

--
-- Indexes for table `wpxp_yoast_seo_links`
--
ALTER TABLE `wpxp_yoast_seo_links`
  ADD PRIMARY KEY (`id`),
  ADD KEY `link_direction` (`post_id`,`type`),
  ADD KEY `indexable_link_direction` (`indexable_id`,`type`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `rescue_admissions`
--
ALTER TABLE `rescue_admissions`
  MODIFY `admission_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_alerts`
--
ALTER TABLE `rescue_alerts`
  MODIFY `alert_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_animal_species`
--
ALTER TABLE `rescue_animal_species`
  MODIFY `species_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_animal_types`
--
ALTER TABLE `rescue_animal_types`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_areas`
--
ALTER TABLE `rescue_areas`
  MODIFY `area_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_centres`
--
ALTER TABLE `rescue_centres`
  MODIFY `rescue_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_connections`
--
ALTER TABLE `rescue_connections`
  MODIFY `connection_id` int(255) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_dose_size`
--
ALTER TABLE `rescue_dose_size`
  MODIFY `dose_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_finders`
--
ALTER TABLE `rescue_finders`
  MODIFY `finder_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_frequencies`
--
ALTER TABLE `rescue_frequencies`
  MODIFY `frequency_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_frequency_times`
--
ALTER TABLE `rescue_frequency_times`
  MODIFY `frequency_time_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_incidents`
--
ALTER TABLE `rescue_incidents`
  MODIFY `incident_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_incident_related`
--
ALTER TABLE `rescue_incident_related`
  MODIFY `inc_rel_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_injury_record`
--
ALTER TABLE `rescue_injury_record`
  MODIFY `injury_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_labs`
--
ALTER TABLE `rescue_labs`
  MODIFY `lab_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_labs_tests`
--
ALTER TABLE `rescue_labs_tests`
  MODIFY `l_test_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_locations`
--
ALTER TABLE `rescue_locations`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_measurements`
--
ALTER TABLE `rescue_measurements`
  MODIFY `weight_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_medications`
--
ALTER TABLE `rescue_medications`
  MODIFY `medication_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_medications_given`
--
ALTER TABLE `rescue_medications_given`
  MODIFY `med_adm_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_medication_trans`
--
ALTER TABLE `rescue_medication_trans`
  MODIFY `med_trans_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_messages`
--
ALTER TABLE `rescue_messages`
  MODIFY `message_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_month_data`
--
ALTER TABLE `rescue_month_data`
  MODIFY `month_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_networks`
--
ALTER TABLE `rescue_networks`
  MODIFY `network_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_net_chat`
--
ALTER TABLE `rescue_net_chat`
  MODIFY `chat_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_notes_patients`
--
ALTER TABLE `rescue_notes_patients`
  MODIFY `note_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_orgs`
--
ALTER TABLE `rescue_orgs`
  MODIFY `org_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_partner_log`
--
ALTER TABLE `rescue_partner_log`
  MODIFY `p_log_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_partner_types`
--
ALTER TABLE `rescue_partner_types`
  MODIFY `p_type_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_patients`
--
ALTER TABLE `rescue_patients`
  MODIFY `patient_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_prescriptions`
--
ALTER TABLE `rescue_prescriptions`
  MODIFY `prescription_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_presenting_complaints`
--
ALTER TABLE `rescue_presenting_complaints`
  MODIFY `pc_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_query`
--
ALTER TABLE `rescue_query`
  MODIFY `q_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_roles`
--
ALTER TABLE `rescue_roles`
  MODIFY `role_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_sample_types`
--
ALTER TABLE `rescue_sample_types`
  MODIFY `s_type_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_severity_score`
--
ALTER TABLE `rescue_severity_score`
  MODIFY `ss_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_stock_medication`
--
ALTER TABLE `rescue_stock_medication`
  MODIFY `medication_profile_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_treatments`
--
ALTER TABLE `rescue_treatments`
  MODIFY `treatment_given_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_triages`
--
ALTER TABLE `rescue_triages`
  MODIFY `triage_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_weights`
--
ALTER TABLE `rescue_weights`
  MODIFY `weight_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_actionscheduler_actions`
--
ALTER TABLE `wpxp_actionscheduler_actions`
  MODIFY `action_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_actionscheduler_claims`
--
ALTER TABLE `wpxp_actionscheduler_claims`
  MODIFY `claim_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_actionscheduler_groups`
--
ALTER TABLE `wpxp_actionscheduler_groups`
  MODIFY `group_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_actionscheduler_logs`
--
ALTER TABLE `wpxp_actionscheduler_logs`
  MODIFY `log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_commentmeta`
--
ALTER TABLE `wpxp_commentmeta`
  MODIFY `meta_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_comments`
--
ALTER TABLE `wpxp_comments`
  MODIFY `comment_ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_connections`
--
ALTER TABLE `wpxp_connections`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_connections_address`
--
ALTER TABLE `wpxp_connections_address`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_connections_date`
--
ALTER TABLE `wpxp_connections_date`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_connections_email`
--
ALTER TABLE `wpxp_connections_email`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_connections_link`
--
ALTER TABLE `wpxp_connections_link`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_connections_messenger`
--
ALTER TABLE `wpxp_connections_messenger`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_connections_meta`
--
ALTER TABLE `wpxp_connections_meta`
  MODIFY `meta_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_connections_phone`
--
ALTER TABLE `wpxp_connections_phone`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_connections_social`
--
ALTER TABLE `wpxp_connections_social`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_connections_terms`
--
ALTER TABLE `wpxp_connections_terms`
  MODIFY `term_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_connections_term_meta`
--
ALTER TABLE `wpxp_connections_term_meta`
  MODIFY `meta_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_connections_term_taxonomy`
--
ALTER TABLE `wpxp_connections_term_taxonomy`
  MODIFY `term_taxonomy_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_e_events`
--
ALTER TABLE `wpxp_e_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_e_notes`
--
ALTER TABLE `wpxp_e_notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_e_notes_users_relations`
--
ALTER TABLE `wpxp_e_notes_users_relations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_e_submissions`
--
ALTER TABLE `wpxp_e_submissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_e_submissions_actions_log`
--
ALTER TABLE `wpxp_e_submissions_actions_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_e_submissions_values`
--
ALTER TABLE `wpxp_e_submissions_values`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_links`
--
ALTER TABLE `wpxp_links`
  MODIFY `link_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_login_log`
--
ALTER TABLE `wpxp_login_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_moodle_enrollment`
--
ALTER TABLE `wpxp_moodle_enrollment`
  MODIFY `id` mediumint(9) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_options`
--
ALTER TABLE `wpxp_options`
  MODIFY `option_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_pmpro_discount_codes`
--
ALTER TABLE `wpxp_pmpro_discount_codes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_pmpro_discount_codes_uses`
--
ALTER TABLE `wpxp_pmpro_discount_codes_uses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_pmpro_memberships_users`
--
ALTER TABLE `wpxp_pmpro_memberships_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_pmpro_membership_levelmeta`
--
ALTER TABLE `wpxp_pmpro_membership_levelmeta`
  MODIFY `meta_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_pmpro_membership_levels`
--
ALTER TABLE `wpxp_pmpro_membership_levels`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_pmpro_membership_ordermeta`
--
ALTER TABLE `wpxp_pmpro_membership_ordermeta`
  MODIFY `meta_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_pmpro_membership_orders`
--
ALTER TABLE `wpxp_pmpro_membership_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_pmxi_files`
--
ALTER TABLE `wpxp_pmxi_files`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_pmxi_history`
--
ALTER TABLE `wpxp_pmxi_history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_pmxi_images`
--
ALTER TABLE `wpxp_pmxi_images`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_pmxi_imports`
--
ALTER TABLE `wpxp_pmxi_imports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_pmxi_posts`
--
ALTER TABLE `wpxp_pmxi_posts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_pmxi_templates`
--
ALTER TABLE `wpxp_pmxi_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_podsrel`
--
ALTER TABLE `wpxp_podsrel`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_postmeta`
--
ALTER TABLE `wpxp_postmeta`
  MODIFY `meta_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_posts`
--
ALTER TABLE `wpxp_posts`
  MODIFY `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_promag_email_templates`
--
ALTER TABLE `wpxp_promag_email_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_promag_fields`
--
ALTER TABLE `wpxp_promag_fields`
  MODIFY `field_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_promag_friends`
--
ALTER TABLE `wpxp_promag_friends`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_promag_groups`
--
ALTER TABLE `wpxp_promag_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_promag_group_requests`
--
ALTER TABLE `wpxp_promag_group_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_promag_group_update_request`
--
ALTER TABLE `wpxp_promag_group_update_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_promag_msg_conversation`
--
ALTER TABLE `wpxp_promag_msg_conversation`
  MODIFY `m_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_promag_msg_threads`
--
ALTER TABLE `wpxp_promag_msg_threads`
  MODIFY `t_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_promag_notification`
--
ALTER TABLE `wpxp_promag_notification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_promag_paypal_log`
--
ALTER TABLE `wpxp_promag_paypal_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_promag_sections`
--
ALTER TABLE `wpxp_promag_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_rank_math_internal_links`
--
ALTER TABLE `wpxp_rank_math_internal_links`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_routiz_messages`
--
ALTER TABLE `wpxp_routiz_messages`
  MODIFY `id` mediumint(9) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_routiz_notifications`
--
ALTER TABLE `wpxp_routiz_notifications`
  MODIFY `id` mediumint(9) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_routiz_user_packages`
--
ALTER TABLE `wpxp_routiz_user_packages`
  MODIFY `id` mediumint(9) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_routiz_views`
--
ALTER TABLE `wpxp_routiz_views`
  MODIFY `id` mediumint(9) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_routiz_visits`
--
ALTER TABLE `wpxp_routiz_visits`
  MODIFY `id` mediumint(9) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_routiz_wallet_payouts`
--
ALTER TABLE `wpxp_routiz_wallet_payouts`
  MODIFY `id` mediumint(9) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_routiz_wallet_transactions`
--
ALTER TABLE `wpxp_routiz_wallet_transactions`
  MODIFY `id` mediumint(9) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_swpm_membership_meta_tbl`
--
ALTER TABLE `wpxp_swpm_membership_meta_tbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_swpm_membership_tbl`
--
ALTER TABLE `wpxp_swpm_membership_tbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_swpm_members_meta_tbl`
--
ALTER TABLE `wpxp_swpm_members_meta_tbl`
  MODIFY `meta_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_swpm_members_tbl`
--
ALTER TABLE `wpxp_swpm_members_tbl`
  MODIFY `member_id` int(12) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_swpm_payments_tbl`
--
ALTER TABLE `wpxp_swpm_payments_tbl`
  MODIFY `id` int(12) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_termmeta`
--
ALTER TABLE `wpxp_termmeta`
  MODIFY `meta_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_terms`
--
ALTER TABLE `wpxp_terms`
  MODIFY `term_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_term_taxonomy`
--
ALTER TABLE `wpxp_term_taxonomy`
  MODIFY `term_taxonomy_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_trp_gettext_en_gb`
--
ALTER TABLE `wpxp_trp_gettext_en_gb`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_trp_gettext_original_meta`
--
ALTER TABLE `wpxp_trp_gettext_original_meta`
  MODIFY `meta_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_trp_gettext_original_strings`
--
ALTER TABLE `wpxp_trp_gettext_original_strings`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_tutor_carts`
--
ALTER TABLE `wpxp_tutor_carts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_tutor_cart_items`
--
ALTER TABLE `wpxp_tutor_cart_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_tutor_coupons`
--
ALTER TABLE `wpxp_tutor_coupons`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_tutor_coupon_usages`
--
ALTER TABLE `wpxp_tutor_coupon_usages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_tutor_customers`
--
ALTER TABLE `wpxp_tutor_customers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_tutor_earnings`
--
ALTER TABLE `wpxp_tutor_earnings`
  MODIFY `earning_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_tutor_ordermeta`
--
ALTER TABLE `wpxp_tutor_ordermeta`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_tutor_orders`
--
ALTER TABLE `wpxp_tutor_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_tutor_order_items`
--
ALTER TABLE `wpxp_tutor_order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_tutor_quiz_attempts`
--
ALTER TABLE `wpxp_tutor_quiz_attempts`
  MODIFY `attempt_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_tutor_quiz_attempt_answers`
--
ALTER TABLE `wpxp_tutor_quiz_attempt_answers`
  MODIFY `attempt_answer_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_tutor_quiz_questions`
--
ALTER TABLE `wpxp_tutor_quiz_questions`
  MODIFY `question_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_tutor_quiz_question_answers`
--
ALTER TABLE `wpxp_tutor_quiz_question_answers`
  MODIFY `answer_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_tutor_withdraws`
--
ALTER TABLE `wpxp_tutor_withdraws`
  MODIFY `withdraw_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_um_metadata`
--
ALTER TABLE `wpxp_um_metadata`
  MODIFY `umeta_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_usermeta`
--
ALTER TABLE `wpxp_usermeta`
  MODIFY `umeta_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_users`
--
ALTER TABLE `wpxp_users`
  MODIFY `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_user_registration_sessions`
--
ALTER TABLE `wpxp_user_registration_sessions`
  MODIFY `session_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_uwp_form_extras`
--
ALTER TABLE `wpxp_uwp_form_extras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_uwp_form_fields`
--
ALTER TABLE `wpxp_uwp_form_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_uwp_profile_tabs`
--
ALTER TABLE `wpxp_uwp_profile_tabs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_uwp_user_sorting`
--
ALTER TABLE `wpxp_uwp_user_sorting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wc_admin_notes`
--
ALTER TABLE `wpxp_wc_admin_notes`
  MODIFY `note_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wc_admin_note_actions`
--
ALTER TABLE `wpxp_wc_admin_note_actions`
  MODIFY `action_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wc_customer_lookup`
--
ALTER TABLE `wpxp_wc_customer_lookup`
  MODIFY `customer_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wc_download_log`
--
ALTER TABLE `wpxp_wc_download_log`
  MODIFY `download_log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wc_product_download_directories`
--
ALTER TABLE `wpxp_wc_product_download_directories`
  MODIFY `url_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wc_rate_limits`
--
ALTER TABLE `wpxp_wc_rate_limits`
  MODIFY `rate_limit_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wc_tax_rate_classes`
--
ALTER TABLE `wpxp_wc_tax_rate_classes`
  MODIFY `tax_rate_class_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wc_webhooks`
--
ALTER TABLE `wpxp_wc_webhooks`
  MODIFY `webhook_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_woocommerce_api_keys`
--
ALTER TABLE `wpxp_woocommerce_api_keys`
  MODIFY `key_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_woocommerce_attribute_taxonomies`
--
ALTER TABLE `wpxp_woocommerce_attribute_taxonomies`
  MODIFY `attribute_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_woocommerce_downloadable_product_permissions`
--
ALTER TABLE `wpxp_woocommerce_downloadable_product_permissions`
  MODIFY `permission_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_woocommerce_log`
--
ALTER TABLE `wpxp_woocommerce_log`
  MODIFY `log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_woocommerce_order_itemmeta`
--
ALTER TABLE `wpxp_woocommerce_order_itemmeta`
  MODIFY `meta_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_woocommerce_order_items`
--
ALTER TABLE `wpxp_woocommerce_order_items`
  MODIFY `order_item_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_woocommerce_payment_tokenmeta`
--
ALTER TABLE `wpxp_woocommerce_payment_tokenmeta`
  MODIFY `meta_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_woocommerce_payment_tokens`
--
ALTER TABLE `wpxp_woocommerce_payment_tokens`
  MODIFY `token_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_woocommerce_sessions`
--
ALTER TABLE `wpxp_woocommerce_sessions`
  MODIFY `session_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_woocommerce_shipping_zones`
--
ALTER TABLE `wpxp_woocommerce_shipping_zones`
  MODIFY `zone_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_woocommerce_shipping_zone_locations`
--
ALTER TABLE `wpxp_woocommerce_shipping_zone_locations`
  MODIFY `location_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_woocommerce_shipping_zone_methods`
--
ALTER TABLE `wpxp_woocommerce_shipping_zone_methods`
  MODIFY `instance_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_woocommerce_tax_rates`
--
ALTER TABLE `wpxp_woocommerce_tax_rates`
  MODIFY `tax_rate_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_woocommerce_tax_rate_locations`
--
ALTER TABLE `wpxp_woocommerce_tax_rate_locations`
  MODIFY `location_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wpbdp_form_fields`
--
ALTER TABLE `wpxp_wpbdp_form_fields`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wpbdp_logs`
--
ALTER TABLE `wpxp_wpbdp_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wpbdp_payments`
--
ALTER TABLE `wpxp_wpbdp_payments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wpbdp_plans`
--
ALTER TABLE `wpxp_wpbdp_plans`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wpda_csv_uploads`
--
ALTER TABLE `wpxp_wpda_csv_uploads`
  MODIFY `csv_id` mediumint(9) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wpda_menus`
--
ALTER TABLE `wpxp_wpda_menus`
  MODIFY `menu_id` mediumint(9) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wpda_project`
--
ALTER TABLE `wpxp_wpda_project`
  MODIFY `project_id` mediumint(9) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wpda_project_page`
--
ALTER TABLE `wpxp_wpda_project_page`
  MODIFY `page_id` mediumint(9) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wpda_publisher`
--
ALTER TABLE `wpxp_wpda_publisher`
  MODIFY `pub_id` mediumint(9) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wpfm_backup`
--
ALTER TABLE `wpxp_wpfm_backup`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wpforms_tasks_meta`
--
ALTER TABLE `wpxp_wpforms_tasks_meta`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wpie_template`
--
ALTER TABLE `wpxp_wpie_template`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wpum_fieldmeta`
--
ALTER TABLE `wpxp_wpum_fieldmeta`
  MODIFY `meta_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wpum_fields`
--
ALTER TABLE `wpxp_wpum_fields`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wpum_fieldsgroups`
--
ALTER TABLE `wpxp_wpum_fieldsgroups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wpum_registration_formmeta`
--
ALTER TABLE `wpxp_wpum_registration_formmeta`
  MODIFY `meta_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wpum_registration_forms`
--
ALTER TABLE `wpxp_wpum_registration_forms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wpum_search_fields`
--
ALTER TABLE `wpxp_wpum_search_fields`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wpum_stripe_invoices`
--
ALTER TABLE `wpxp_wpum_stripe_invoices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_wpum_stripe_subscriptions`
--
ALTER TABLE `wpxp_wpum_stripe_subscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_yoast_indexable`
--
ALTER TABLE `wpxp_yoast_indexable`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_yoast_migrations`
--
ALTER TABLE `wpxp_yoast_migrations`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_yoast_primary_term`
--
ALTER TABLE `wpxp_yoast_primary_term`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wpxp_yoast_seo_links`
--
ALTER TABLE `wpxp_yoast_seo_links`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `wpxp_tutor_carts`
--
ALTER TABLE `wpxp_tutor_carts`
  ADD CONSTRAINT `fk_tutor_cart_user_id` FOREIGN KEY (`user_id`) REFERENCES `wpxp_users` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `wpxp_tutor_cart_items`
--
ALTER TABLE `wpxp_tutor_cart_items`
  ADD CONSTRAINT `fk_tutor_cart_item_cart_id` FOREIGN KEY (`cart_id`) REFERENCES `wpxp_tutor_carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tutor_cart_item_course_id` FOREIGN KEY (`course_id`) REFERENCES `wpxp_posts` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `wpxp_tutor_coupon_applications`
--
ALTER TABLE `wpxp_tutor_coupon_applications`
  ADD CONSTRAINT `fk_tutor_coupon_application_coupon_code` FOREIGN KEY (`coupon_code`) REFERENCES `wpxp_tutor_coupons` (`coupon_code`) ON DELETE CASCADE;

--
-- Constraints for table `wpxp_tutor_coupon_usages`
--
ALTER TABLE `wpxp_tutor_coupon_usages`
  ADD CONSTRAINT `fk_tutor_coupon_usage_coupon_code` FOREIGN KEY (`coupon_code`) REFERENCES `wpxp_tutor_coupons` (`coupon_code`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tutor_coupon_usage_user_id` FOREIGN KEY (`user_id`) REFERENCES `wpxp_users` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `wpxp_tutor_ordermeta`
--
ALTER TABLE `wpxp_tutor_ordermeta`
  ADD CONSTRAINT `fk_tutor_ordermeta_order_id` FOREIGN KEY (`order_id`) REFERENCES `wpxp_tutor_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wpxp_tutor_order_items`
--
ALTER TABLE `wpxp_tutor_order_items`
  ADD CONSTRAINT `fk_tutor_order_item_order_id` FOREIGN KEY (`order_id`) REFERENCES `wpxp_tutor_orders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
