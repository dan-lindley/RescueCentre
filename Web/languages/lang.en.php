<?php
/* 
------------------
Language: English
------------------
==============================================================================
LANGUAGE FILE GUIDELINES
==============================================================================

This file follows a "canonical first" approach.

Before adding a new language key, check whether an equivalent key already
exists elsewhere in this file.

RULES
------------------------------------------------------------------------------

1. REUSE GLOBAL KEYS WHERE POSSIBLE

   Use existing global labels for common concepts such as:

       SAVE
       DELETE
       EDIT
       ADD
       SEARCH
       CLEAR
       NAME
       DESCRIPTION
       DATE
       TIME
       STATUS
       LOCATION
       NOTES
       COMMENTS
       USER

   Do NOT create module-specific versions of these unless the wording
   genuinely differs because of context.

   Avoid:
       NET_ACCEPT
       LOC_SAVE
       SETTINGS_EDIT

   Prefer:
       ACCEPT
       SAVE
       EDIT


2. CREATE MODULE KEYS ONLY WHEN NECESSARY

   Module prefixes (ADM_, NET_, LOC_, DATA_, SETTINGS_, etc.) should only
   be used when the text is specific to that workflow.

   Appropriate examples:

       ADM_PATIENT_ADMITTED
       NET_JOIN_REQUEST_APPROVED
       DATA_RECORD_RECOVERED

   These represent business events and should remain explicit.


3. HELP TEXT SHOULD REMAIN CONTEXTUAL

   Guidance, placeholders and explanatory text should stay module-specific.

   Examples:

       SETTINGS_AUTH_APP_HELP
       ADM_WEATHER_SOURCE_HELP
       RX_REPEAT_HELP

   These should not be generalised.


4. VALIDATION AND ERROR MESSAGES SHOULD REMAIN EXPLICIT

   Keep validation messages close to the workflow they belong to.

   Examples:

       ADM_FINDER_REQUIRED
       NET_NAME_REQUIRED
       SETTINGS_API_NAME_REQUIRED

   Although similar, these provide important contextual information.


5. SUCCESS MESSAGES

   Prefer generic success messages for standard CRUD operations.

   Examples:

       MSG_SAVED
       MSG_UPDATED
       MSG_DELETED
       MSG_CREATED

   Create workflow-specific messages only when the action changes system
   state in a meaningful way.

   Keep specific:

       ADM_PATIENT_ADMITTED
       NET_LEFT_NETWORK
       DATA_RECORD_PERMANENTLY_DELETED

   Avoid creating separate keys for trivial variations such as:

       CARE_NOTE_SAVED
       BIO_SAVED
       OBSERVATION_SAVED


6. THINK IN CONCEPTS, NOT SCREENS

   Language keys represent concepts used throughout the application,
   not individual pages.

   Ask:

       "Would another part of the system use exactly this wording?"

   If yes, reuse the existing key.

   If no, create a new contextual key.


7. TRANSLATION CHECK

   Before introducing a new key, consider:

       - Does this key already exist?
       - Is this a workflow-specific sentence?
       - Would another language require different wording here?
       - Would reusing an existing key reduce clarity?

   If unsure, favour clarity over excessive deduplication.


8. CONSISTENCY OVER MINIMISING FILE SIZE

   The goal is not to have the smallest language file.

   The goal is to maintain a clear, predictable and translator-friendly
   structure that scales as new languages and modules are introduced.

==============================================================================
*/


$lang = array();

//GLOBAL USED CROSS SITE
$lang['PATIENT'] = 'Patient';
$lang['PRESENTING_COMPLAINT'] = 'Presenting Complaint';
$lang['ADMISSION'] = 'Admission';
$lang['DATE_OF'] = 'Date of';
$lang['DAYS'] = 'days';
$lang['TO'] = 'to';
$lang['NAME'] = 'Name';
$lang['SEX'] = 'Sex';
$lang['RINGED'] = 'Ringed';
$lang['RING'] = 'Ring';
$lang['MICROCHIP'] = 'Microchip';
$lang['SPECIES'] = 'Species';
$lang['IDENTIFIER'] = 'Identifier';
$lang['NUMBER_ABBR'] = 'No';
$lang['NUMBER'] = 'Number';
$lang['DISPOSITION'] = 'Disposition';
$lang['WRA'] = 'WRA';
$lang['WILDLIFE_RAPID_ASSESSMENT'] = 'Wildlife Rapid Assessment';
$lang['LOCATION'] = 'Location';
$lang['INCIDENT'] = 'Incident';
$lang['AGE'] = 'Age';
$lang['YES'] = 'Yes';
$lang['NO'] = 'No';
$lang['SELECT_PATIENT'] = 'Select Patient';
$lang['MALE'] = 'Male';
$lang['FEMALE'] = 'Female';
$lang['FEMALE_PREG'] = 'Pregnant Female';
$lang['FEMALE_LACT'] = 'Lactating Female';
$lang['UNDETERMINED'] = 'Undetermined';
$lang['ANIMAL_ORDER'] = 'Animal Class';
$lang['ANIMAL_TYPE'] = 'Animal Genus';
$lang['ANIMAL_SPECIES'] = 'Animal Species';
$lang['SELECT_A'] = 'Select a';
$lang['BIRTH'] = 'birth';
$lang['THIS_YEAR'] = 'this year';
$lang['YEAR'] = 'year';
$lang['IN'] = 'in';

$lang['ADD_STOCK'] = 'Add Stock';

$lang['FORM'] = 'Form';
$lang['CONCENTRATION'] = 'Concentration';
$lang['PACK_SIZE'] = 'Pack Size';
$lang['USE_WITHIN'] = 'Use Within';
$lang['REORDER'] = 'Reorder';
$lang['MG_ML'] = 'mg/ml';
$lang['DAYS_ABBR'] = 'd';

$lang['STOCK_FORM'] = 'Stock Form';
$lang['SELECT_FORM'] = 'Select form…';
$lang['CONCENTRATION_DOSE'] = 'Concentration Dose';
$lang['DOSE_TYPE'] = 'Dose Type';
$lang['CONCENTRATION_VOLUME'] = 'Concentration Volume';
$lang['VOLUME_TYPE'] = 'Volume Type';
$lang['REORDER_LEVEL'] = 'Reorder Level';
$lang['USE_WITHIN_DAYS'] = 'Use Within (days)';
$lang['MG_ML_AUTO'] = 'mg/ml (auto)';

$lang['SAVE']   = 'Save';
$lang['DELETE'] = 'Delete';
$lang['SEARCH'] = 'Search';
$lang['CLEAR']  = 'Clear';
$lang['SELECT'] = 'Select';
$lang['ADD'] = 'Add';
$lang['NEW'] = 'New';
$lang['EDIT'] = 'Edit';
$lang['GROUP'] = 'Group';
$lang['CANCEL'] = 'Cancel';
$lang['CONTINUE'] = 'Continue';
$lang['PAGE'] = 'Page';
$lang['SECTION'] = 'Section';
$lang['ANIMAL'] = 'Animal';
$lang['DETAILS'] = 'Details';
$lang['INFORMATION'] = 'Information';
$lang['COLLECTION'] = 'Collection';
$lang['FINDER'] = 'Finder';
$lang['TELEPHONE'] = 'Telephone';
$lang['BIOMETRICS'] = 'Biometrics';
$lang['TRIAGE'] = 'Triage';
$lang['ASSESSMENT'] = 'Assessment';
$lang['SEVERITY'] = 'Severity';
$lang['CONDITION'] = 'Condition';
$lang['WEATHER'] = 'Weather';
$lang['DATA'] = 'Data';
$lang['DECLARATION'] = 'Declaration';
$lang['SIGNATURE'] = 'Signature';
$lang['DISCHARGE'] = 'Discharge';
$lang['COMMENTS'] = 'Comments';
$lang['ACTIONS'] = 'Actions';
$lang['USER'] = 'User';
$lang['PASSWORD'] = 'Password';
$lang['MODULE'] = 'Module';
$lang['SYSTEM'] = 'System';
$lang['ROWS'] = 'rows';
$lang['EXPORT'] = 'Export';
$lang['DESCRIPTION'] = 'Description';
$lang['OPEN'] = 'Open';
$lang['MANAGE'] = 'Manage';
$lang['WEIGHT'] = 'Weight';
$lang['MEASUREMENT'] = 'Measurement';
$lang['CENTRE'] = 'Centre';
$lang['ADDRESS'] = 'Address';
$lang['HOUSE'] = 'House';
$lang['OPTIONAL'] = 'Optional';
$lang['DATE'] = 'Date';
$lang['TIME'] = 'Time';
$lang['SCORE'] = 'Score';
$lang['BODY'] = 'Body';
$lang['HISTORY'] = 'History';
$lang['TEMPERATURE'] = 'Temperature';
$lang['WIND'] = 'Wind';
$lang['SPEED'] = 'Speed';
$lang['HUMIDITY'] = 'Humidity';
$lang['NOTES'] = 'Notes';
$lang['FETCH'] = 'Fetch';
$lang['MARK'] = 'Mark';
$lang['CREATE'] = 'Create';
$lang['ADMIT'] = 'Admit';
$lang['TOTAL'] = 'Total';
$lang['MONTH'] = 'Month';
$lang['CAUSE'] = 'Cause';
$lang['MAP'] = 'Map';
$lang['DAY'] = 'Day';
$lang['HOUR'] = 'Hour';
$lang['WEEK'] = 'Week';
$lang['RELEASES'] = 'Releases';
$lang['DEATHS'] = 'Deaths';
$lang['CLINICAL'] = 'Clinical';
$lang['EFFICIENCY'] = 'Efficiency';
$lang['STATISTICS'] = 'Statistics';
$lang['MORE'] = 'More';
$lang['USE'] = 'Use';
$lang['EXTERNAL'] = 'External';
$lang['EUTHANASIA'] = 'Euthanasia';
$lang['METHOD'] = 'Method';
$lang['LAST'] = 'Last';
$lang['PREVIOUS'] = 'Previous';
$lang['ADMITTED'] = 'Admitted';
$lang['ALL_TIME'] = 'All Time';
$lang['MOST_COMMON'] = 'Most Common';
$lang['MIX'] = 'Mix';
$lang['CARE'] = 'Care';
$lang['LAYERS'] = 'Layers';
$lang['VIEW'] = 'View';
$lang['RECORD'] = 'Record';
$lang['INSIGHTS'] = 'Insights';
$lang['SEASONAL'] = 'Seasonal';
$lang['OUTCOMES'] = 'Outcomes';
$lang['TOP'] = 'Top';
$lang['STAY'] = 'Stay';
$lang['LINK'] = 'Link';
$lang['UNLINK'] = 'Unlink';
$lang['LINKED'] = 'Linked';
$lang['CITY'] = 'City';
$lang['POSTCODE'] = 'Postcode';
$lang['REFERENCE'] = 'Reference';
$lang['STANDARD'] = 'Standard';
$lang['BACK'] = 'Back';
$lang['NO_LOCATION_RECORDED'] = 'No location recorded';

// FORMS
$lang['CARE_NOTE'] = 'Care Note';
$lang['AUTHOR'] = 'Author';
$lang['PUBLIC'] = 'Public';
$lang['OBSERVATION'] = 'Observation';
$lang['PRESCRIPTION'] = 'Prescription';
$lang['TREATMENT'] = 'Treatment';
$lang['FEEDING'] = 'Feeding';
$lang['LABS'] = 'Labs';
$lang['LAB_TEST'] = 'Lab Test';
$lang['SAMPLE_TYPE'] = 'Sample Type';
$lang['RESULT'] = 'Result';
$lang['REPORTED_BY'] = 'Reported By';
$lang['VOLUME'] = 'Volume';
$lang['DURATION'] = 'Duration';
$lang['PACK'] = 'Pack';
$lang['REFUSED'] = 'Refused';
$lang['SKIPPED'] = 'Skipped';
$lang['CONSUMED'] = 'Consumed';
$lang['EMPTY'] = 'Empty';
$lang['FULL'] = 'Full';
$lang['POSITIVE'] = 'Positive';
$lang['SIMPLE'] = 'Simple';
$lang['FOR'] = 'for';
$lang['DONE_BY'] = 'Done By';
$lang['DATE_STARTED'] = 'Date Started';
$lang['GIVEN_ON'] = 'Given On';
$lang['VOLUME_USED'] = 'Volume Used';
$lang['CURRENT_AGE'] = 'Current Age';
$lang['CURRENT_SEVERITY'] = 'Current Severity';
$lang['DIET_ITEM'] = 'Diet item';
$lang['FEED_TYPE'] = 'Feed type';
$lang['AMOUNT_OFFERED'] = 'Amount offered';
$lang['ESTIMATED'] = 'Estimated';
$lang['NEW_LOCATION'] = 'New location';
$lang['MOVE_PATIENT'] = 'Move Patient';
$lang['ATTACH'] = 'Attach';
$lang['IMAGE'] = 'Image';
$lang['LAST_RECORDED_WEIGHT'] = 'Last recorded weight';
$lang['GRAMS'] = 'Grams';
$lang['KILOGRAMS'] = 'Kilograms';
$lang['POUNDS'] = 'Pounds';
$lang['MILLIMETERS'] = 'Millimeters';
$lang['CENTIMETERS'] = 'Centimeters';
$lang['METERS'] = 'Meters';
$lang['INCHES'] = 'Inches';
$lang['FEET'] = 'Feet';
$lang['SUBCUTANEOUS_INJECTION'] = 'Subcutaneous Injection';
$lang['INTRAVENOUS_INJECTION'] = 'Intravenous Injection';
$lang['ORAL'] = 'Oral';
$lang['TOPICAL'] = 'Topical';
$lang['RX_REPEAT_HELP'] = 'For weekly or fortnightly prescriptions, add a prescription for each administration day.';
$lang['PATIENT_CONTEXT_MISSING'] = 'Patient context missing.';
$lang['NO_ACTIVE_LOCATIONS'] = 'No active locations are available for this centre.';
$lang['CN_IMAGE_HELP'] = 'Choose a file to preview it in the gallery. It uploads only when you save the care note.';
$lang['CN_NO_IMAGES'] = 'No existing images yet. Upload one to attach it.';
$lang['CN_REMOVE_UPLOAD'] = 'Remove this upload';
$lang['LAB_SEARCH_PLACEHOLDER'] = 'Type to search tests...';
$lang['LAB_SEARCH_HELP'] = 'Start typing, then select a matching test.';
$lang['FEED_NOTES_PLACEHOLDER'] = 'Optional notes, for example tolerance or feeding speed.';
$lang['MED_SELECT_PACK'] = 'Select pack...';
$lang['MED_STOCK_SHORTFALL'] = 'Not enough remaining in this pack';
$lang['MED_STOCK_SUBMIT_SHORTFALL'] = 'Submitting will finish this pack and require another.';
$lang['MED_CALCULATED_VOLUME'] = 'Calculated volume';
$lang['MED_BASED_ON'] = 'based on';
$lang['DOSE_BY_WEIGHT'] = 'Dose is by weight (per kg)';
$lang['TRT_HEATING_PAD'] = 'Heating Pad';
$lang['TRT_FOOD'] = 'Food';
$lang['TRT_WATER'] = 'Water';
$lang['TRT_IV'] = 'IV';
$lang['TRT_SUBCUTANEOUS_FLUIDS'] = 'Subcutaneous Fluids';
$lang['TRT_PAIN_RELIEF'] = 'Pain Relief';
$lang['TRT_PARASITE_REMOVAL'] = 'Parasite Removal';
$lang['TRT_TICK_REMOVAL'] = 'Tick Removal';
$lang['TRT_BATH'] = 'Bath';
$lang['TRT_INCUBATOR'] = 'Incubator';
$lang['TRT_MAGGOT_REMOVAL'] = 'Maggot Removal';
$lang['TRT_FLYSTRIKE_REMOVAL'] = 'Flystrike (eggs) Removal';
$lang['TRT_TOPICAL'] = 'Topical Treatment';
$lang['TRT_OVER_COUNTER_MEDICATION'] = 'Over-counter Medication';
$lang['TRT_NATURAL_REMEDY'] = 'Natural Remedy';
$lang['TRT_OTHER_NOTES'] = 'Other (use notes to describe)';

// LOCATIONS
$lang['LOC_LOCATION_MANAGEMENT'] = 'Location Management';
$lang['LOC_LOCATION_MANAGEMENT_SUBTITLE'] = 'View, edit and create locations and areas within your rescue';
$lang['LOC_OCCUPANCY'] = 'Occupancy';
$lang['LOC_OCCUPANCY_SUBTITLE'] = 'View the occupancy status in your rescue';
$lang['LOC_ADD_ZONES_AREAS_LOCATIONS'] = 'Add Zones, Areas & Locations';
$lang['LOC_MANAGE_LOCATIONS'] = 'Manage Locations';
$lang['LOC_UPDATE_PATIENTS_BATCH'] = 'Update Patients (batch)';
$lang['LOC_ZONES_AREAS_LOCATIONS'] = 'Zones, Areas & Locations';
$lang['LOC_ZONES_AREAS_LOCATIONS_SUBTITLE'] = 'Zones -> Areas -> Locations (inline edit + delete)';
$lang['LOC_ZONE'] = 'Zone';
$lang['LOC_ZONES'] = 'Zones';
$lang['LOC_AREA'] = 'Area';
$lang['LOC_AREAS'] = 'Areas';
$lang['LOC_LOCATION_TYPE'] = 'Location type';
$lang['LOC_ENABLED'] = 'Enabled';
$lang['LOC_INACTIVE'] = 'inactive';
$lang['LOC_NO_ZONES'] = 'No zones are set up yet. Start by creating your first zone.';
$lang['LOC_ADD_ZONE'] = 'Add Zone';
$lang['LOC_ADD_ANOTHER_ZONE'] = 'Add Another Zone';
$lang['LOC_ZONE_NAME'] = 'Zone name';
$lang['LOC_YOUR_RESCUE_NAME'] = 'Your rescue name';
$lang['LOC_SAVE_ZONE'] = 'Save Zone';
$lang['LOC_EDIT_ZONE_NAME'] = 'Edit zone name';
$lang['LOC_EDIT_AREA_NAME'] = 'Edit area name';
$lang['LOC_ZONE_ID'] = 'Zone ID';
$lang['LOC_AREA_ID'] = 'Area ID';
$lang['LOC_DELETE_ZONE_CONFIRM'] = 'Delete this zone? This will only work if it has no areas.';
$lang['LOC_DELETE_AREA_CONFIRM'] = 'Delete this area? This will only work if it has no locations.';
$lang['LOC_DELETE_LOCATION_CONFIRM'] = 'Delete this location? This is a soft delete.';
$lang['LOC_NO_AREAS_ZONE'] = 'No areas in this zone yet.';
$lang['LOC_NO_LOCATIONS_YET'] = 'No locations yet.';
$lang['LOC_ADD_AREA_ZONE'] = 'Add area to this zone';
$lang['LOC_ADD_AREA'] = 'Add Area';
$lang['LOC_LOCATION_NAME'] = 'Location name';
$lang['LOC_ADD_LOCATION_NAME'] = 'Add location name';
$lang['LOC_ADD_NEW_LOCATION'] = 'Add new location...';
$lang['LOC_MAX'] = 'Max';
$lang['LOC_MAX_OCCUPANCY'] = 'Max occupancy';
$lang['LOC_ADD_MAX_OCCUPANCY'] = 'Add max occupancy';
$lang['LOC_CAPACITY_SUMMARY'] = 'Centre Capacity Summary';
$lang['LOC_CENTRE_TOTAL'] = 'Centre Total';
$lang['LOC_CAPACITY'] = 'Capacity';
$lang['LOC_OCCUPANCY_VALUE'] = 'Occupancy';
$lang['LOC_UTILISATION'] = 'Utilisation';
$lang['LOC_NO_AREA_ASSIGNED'] = 'No Area Assigned';
$lang['LOC_NO_LOCATIONS_AREA'] = 'No locations in this area.';
$lang['LOC_LOCATIONS_MANAGER'] = 'Locations Manager';
$lang['LOC_LOCATIONS_MANAGER_SUBTITLE'] = 'Deleted locations bin, link repair, and bulk normalisation';
$lang['LOC_CENTRE_SUMMARY'] = 'Centre summary';
$lang['LOC_ACTIVE_LOCATIONS'] = 'Locations (active)';
$lang['LOC_DELETED_LOCATIONS'] = 'Locations (deleted)';
$lang['LOC_NEEDS_ATTENTION'] = 'Needs attention';
$lang['LOC_LEGACY_TEXT_ONLY'] = 'Legacy text-only';
$lang['LOC_BROKEN_AREA_ID'] = 'Broken area_id';
$lang['LOC_AREAS_MISSING_ZONE'] = 'Areas missing zone';
$lang['LOC_DUPLICATE_AREA_NAMES'] = 'Duplicate area names';
$lang['LOC_DELETED_LOCATIONS_BIN'] = 'Deleted Locations (Bin)';
$lang['LOC_DELETED_LOCATIONS_HELP'] = 'Restore items, or attempt a hard delete.';
$lang['LOC_LINK_STATE'] = 'Link state';
$lang['LOC_RESTORE'] = 'Restore';
$lang['LOC_HARD_DELETE'] = 'Hard Delete';
$lang['LOC_HARD_DELETE_CONFIRM'] = 'Hard delete this location permanently? This cannot be undone.';
$lang['LOC_NO_DELETED_LOCATIONS'] = 'No deleted locations.';
$lang['LOC_REPAIR_LEGACY'] = 'Repair: Legacy Text -> Area ID';
$lang['LOC_REPAIR_LEGACY_HELP'] = 'These locations have no area_id, but do have legacy location_area text. Link them to the correct area.';
$lang['LOC_LEGACY_AREA_TEXT'] = 'Legacy area text';
$lang['LOC_SUGGESTED_MATCH'] = 'Suggested match';
$lang['LOC_ASSIGN_AREA'] = 'Assign area';
$lang['LOC_SELECT_AREA'] = 'Select area...';
$lang['LOC_NO_LEGACY_LOCATIONS'] = 'No legacy text-only locations found.';
$lang['LOC_REPAIR_BROKEN_AREA'] = 'Repair: Broken Area Links';
$lang['LOC_REPAIR_BROKEN_AREA_HELP'] = 'These locations have an area_id that does not resolve to an area in this centre.';
$lang['LOC_CURRENT_AREA_ID'] = 'Current area_id';
$lang['LOC_REASSIGN_AREA'] = 'Reassign area';
$lang['LOC_FIX'] = 'Fix';
$lang['LOC_NO_BROKEN_AREA_LINKS'] = 'No broken area_id links found.';
$lang['LOC_REPAIR_AREAS_MISSING_ZONE'] = 'Repair: Areas Missing Zone';
$lang['LOC_REPAIR_AREAS_MISSING_ZONE_HELP'] = 'These areas have no zone_id. Assign them to a zone.';
$lang['LOC_ASSIGN_ZONE'] = 'Assign zone';
$lang['LOC_SELECT_ZONE'] = 'Select zone...';
$lang['LOC_ASSIGN'] = 'Assign';
$lang['LOC_NO_AREAS_MISSING_ZONE'] = 'No areas missing zone.';
$lang['LOC_BULK_TOOLS'] = 'Bulk Tools';
$lang['LOC_BULK_TOOLS_HELP'] = 'These tools are powerful. Use with care.';
$lang['LOC_BACKFILL_AREA_ID'] = 'Backfill area_id (unique matches only)';
$lang['LOC_BACKFILL_CONFIRM'] = 'Backfill area_id for locations where a UNIQUE legacy text match exists?';
$lang['LOC_SYNC_LOCATION_AREA'] = 'Sync location_area from area_id';
$lang['LOC_SYNC_CONFIRM'] = 'Sync location_area text from area_id for all linked locations?';
$lang['LOC_BULK_NOTE'] = 'Note: Duplicate area names prevent safe auto-linking. The backfill tool will skip ambiguous matches.';
$lang['LOC_CENTRE_ID_MISSING'] = 'Centre ID missing.';
$lang['LOC_BATCH_ASSIGN_TITLE'] = 'Batch assign Location IDs';
$lang['LOC_BATCH_ASSIGN_HELP'] = 'Tick rows, choose a location, update.';
$lang['LOC_UPDATED_SKIPPED'] = 'Updated: %d. Skipped: %d.';
$lang['LOC_SEARCH_BATCH_PLACEHOLDER'] = 'Search CRN, name, species, location...';
$lang['LOC_SHOW_ALL_ADMITTED'] = 'Show all admitted patients';
$lang['LOC_SELECT_ALL_VISIBLE'] = 'Select all (visible)';
$lang['LOC_SELECT_NONE_VISIBLE'] = 'Select none (visible)';
$lang['LOC_APPLY'] = 'Apply';
$lang['LOC_ASSIGN_LOCATION'] = 'Assign location';
$lang['LOC_UNNAMED'] = 'Unnamed';
$lang['LOC_UNKNOWN_SPECIES'] = 'Unknown species';
$lang['LOC_BLANK'] = '(blank)';
$lang['LOC_NO_MATCH'] = 'no match';
$lang['LOC_ID_SET'] = 'ID set';
$lang['LOC_TEXT_MATCH'] = 'text match';
$lang['LOC_COLLECTED_FROM'] = 'Collected from';
$lang['LOC_PRESENTING'] = 'Presenting';
$lang['LOC_RECORDED'] = 'Recorded';
$lang['LOC_SELECT_DASH'] = '- Select -';
$lang['LOC_NO_ADMITTED_PATIENTS'] = 'No admitted patients found.';
$lang['LOC_UPDATE_SELECTED'] = 'Update selected';
$lang['LOC_CENTRE_CONTEXT_MISSING'] = 'Centre context not available.';

// DATA
$lang['DATA_PERMISSION_ACCESS'] = 'Access to Data Management';
$lang['DATA_MANAGEMENT_SUBTITLE'] = 'Manage audit logs and fix data issues';
$lang['DATA_LOGS'] = 'Data Logs';
$lang['DATA_REVIEW_QUEUE'] = 'Review Queue';
$lang['DATA_DELETE_RECOVERY'] = 'Delete / Recovery';
$lang['DATA_MFA_VERIFIED'] = 'Verification complete. Please run the selected action again.';
$lang['DATA_TOOL_UNAVAILABLE'] = 'This data tool is not available yet.';
$lang['DATA_AUDIT_LOGS'] = 'Audit Logs';
$lang['DATA_AUDIT_LOGS_SUBTITLE'] = 'Centre activity and patient timeline';
$lang['DATA_DATABASE_ERROR'] = 'Database error';
$lang['DATA_ENDPOINT'] = 'Endpoint';
$lang['DATA_NO_LOG_ENTRIES'] = 'No log entries found.';
$lang['DATA_PAYLOAD'] = 'Payload';
$lang['DATA_CONTEXT_MISSING'] = 'Missing context.';
$lang['DATA_CLICK_PIN_HELP'] = 'Click a pin to load its details below';
$lang['DATA_NO_PIN_SELECTED'] = 'No pin selected';
$lang['DATA_SELECTED_RECORD'] = 'Selected record';
$lang['DATA_SELECT_PIN_BEGIN'] = 'Select a pin to begin';
$lang['DATA_STORED_COLLECTION_LOCATION'] = 'Stored collection location (original)';
$lang['DATA_STORED_COLLECTION_LOCATION_HELP'] = 'This is the original stored value used for geocoding.';
$lang['DATA_SEARCH_CORRECTED_ADDRESS'] = 'Search / corrected address';
$lang['DATA_CORRECTED_ADDRESS_PLACEHOLDER'] = 'Start typing a corrected address...';
$lang['DATA_CORRECTED_ADDRESS_HELP'] = 'Use autocomplete to refine or correct the original location.';
$lang['DATA_CLICK_MAP_COORDS'] = 'Click map to set coordinates';
$lang['DATA_SAVE_COORDS'] = 'Save coords';
$lang['DATA_RERUN_LOOKUP'] = 'Re-run lookup';
$lang['DATA_LOCATION_FIX_TIP'] = 'Tip: After selecting a pin, you can adjust lat/long manually or enable click map to set coordinates.';
$lang['DATA_REVIEW_QUEUE_HELP'] = 'Admissions marked with the Review disposition. These are hidden from My Patients and await manager review.';
$lang['DATA_SOFT_DELETE_MIGRATION_MISSING'] = 'The is_deleted migration has not been applied to patients/admissions yet.';
$lang['DATA_NO_REVIEW_RECORDS'] = 'No records are currently awaiting review.';
$lang['DATA_MARKED_REVIEW'] = 'Marked Review';
$lang['DATA_SOFT_DELETE'] = 'Soft Delete';
$lang['DATA_SOFT_DELETE_CONFIRM'] = 'Soft delete this reviewed record?';
$lang['DATA_DELETE_RECOVERY_HELP'] = 'Soft-deleted records across the centre. Recover restores the row; hard delete permanently removes it after verification.';
$lang['DATA_NO_DELETED_RECORDS'] = 'No soft-deleted records were found.';
$lang['DATA_DELETED_ROW'] = 'Deleted Row';
$lang['DATA_ROW_DATA'] = 'Row Data';
$lang['DATA_DELETED_FLAG'] = 'Deleted Flag';
$lang['DATA_RECOVER'] = 'Recover';
$lang['DATA_RECOVER_CONFIRM'] = 'Recover this deleted row?';
$lang['DATA_HARD_DELETE_CONFIRM'] = 'Permanently delete this row? This cannot be undone from the app.';
$lang['DATA_PARTNER_LOGS'] = 'Partner Logs';
$lang['DATA_QUICK_TASKS'] = 'Quick Tasks';
$lang['DATA_DUTIES'] = 'Duties';
$lang['DATA_STAFF'] = 'Staff';
$lang['DATA_TABLE_NOT_AVAILABLE'] = 'This table is not available for delete/recovery actions.';
$lang['DATA_DELETE_METADATA_FAILED'] = 'Could not resolve delete/recovery metadata.';
$lang['DATA_RECORD_SCOPE_FAILED'] = 'This record cannot be safely scoped to your centre.';
$lang['DATA_DELETED_RECORD_NOT_FOUND'] = 'Deleted record could not be found for this centre.';
$lang['DATA_SECURITY_TOKEN_FAILED'] = 'Security token failed. Please refresh and try again.';
$lang['DATA_REVIEW_PERMISSION_DENIED'] = 'Only Admin, Owner or Manager users can perform review actions.';
$lang['DATA_INVALID_CENTRE'] = 'Invalid centre.';
$lang['DATA_INVALID_DELETED_RECORD'] = 'Invalid deleted record.';
$lang['DATA_RECORD_RECOVERED'] = 'Record recovered.';
$lang['DATA_PASSWORD_FAILED'] = 'Password verification failed.';
$lang['DATA_RECORD_PERMANENTLY_DELETED'] = 'Record permanently deleted.';
$lang['DATA_INVALID_REVIEW_RECORD'] = 'Invalid review record.';
$lang['DATA_REVIEW_RECORD_NOT_FOUND'] = 'Review record could not be found for this centre.';
$lang['DATA_ONLY_REVIEW_SOFT_DELETE'] = 'Only admissions marked Review can be soft deleted from this queue.';
$lang['DATA_RECORD_SOFT_DELETED'] = 'Record soft deleted.';
$lang['DATA_UNKNOWN_REVIEW_ACTION'] = 'Unknown review action.';

// NETWORKS
$lang['NET_PERMISSION_ACCESS'] = 'Access to Networks Page';
$lang['NET_PAGE_SUBTITLE'] = 'Create and manage professional collaborative networks';
$lang['NET_MY_NETWORKS'] = 'My Networks';
$lang['NET_REQUESTS'] = 'Network Requests';
$lang['NET_FIND_NETWORK'] = 'Find a Network';
$lang['NET_CREATE_NETWORK'] = 'Create Network';
$lang['NET_APP_NOT_LOADED'] = 'APP_LOADED not defined.';
$lang['NET_TABLES_HELP'] = 'If this mentions missing tables or columns, check rescue_groups and rescue_group_members.';
$lang['NET_MY_NETWORKS_LOAD_ERROR'] = 'My Networks: load error';
$lang['NET_MY_NETWORKS_HELP'] = 'Networks your centre belongs to, and any pending invitations or requests.';
$lang['NET_PENDING'] = 'Pending';
$lang['NET_PENDING_COUNT'] = 'You have %d pending network item%s.';
$lang['NET_PENDING_HELP'] = 'Use Network Requests to accept or decline invites.';
$lang['NET_UNNAMED'] = 'Unnamed Network';
$lang['NET_NONE_TO_SHOW'] = 'No networks to show';
$lang['NET_NONE_ACTIVE_HELP'] = 'Your centre is not currently a member of any active networks.';
$lang['NET_ACTIVE_NETWORKS'] = 'Active Networks';
$lang['NET_ACTIVE_COUNT'] = 'You are an active member of %d network%s.';
$lang['NET_ROLE'] = 'Role';
$lang['NET_JOINED'] = 'Joined';
$lang['NET_OPEN_NETWORK'] = 'Open Network';
$lang['NET_REQUESTS_LOAD_ERROR'] = 'Network Requests: load error';
$lang['NET_REQUESTS_HELP'] = 'Invitations to join networks, your join requests, and approvals if you are a network admin.';
$lang['NET_INVITATIONS'] = 'Invitations';
$lang['NET_INVITATIONS_HELP'] = 'Networks that have invited your centre.';
$lang['NET_NO_INVITATIONS'] = 'No network invitations right now.';
$lang['NET_INVITED'] = 'Invited';
$lang['NET_ACCEPT'] = 'Accept';
$lang['NET_DECLINE'] = 'Decline';
$lang['NET_ACCEPT_INVITE_CONFIRM'] = 'Accept invitation to join this network?';
$lang['NET_DECLINE_INVITE_CONFIRM'] = 'Decline this network invitation?';
$lang['NET_MY_JOIN_REQUESTS'] = 'My Join Requests';
$lang['NET_MY_JOIN_REQUESTS_HELP'] = 'Networks your centre has requested to join.';
$lang['NET_NO_PENDING_JOIN_REQUESTS'] = 'No pending join requests.';
$lang['NET_REQUESTED'] = 'Requested';
$lang['NET_CANCEL_REQUEST'] = 'Cancel Request';
$lang['NET_CANCEL_JOIN_CONFIRM'] = 'Cancel this join request?';
$lang['NET_APPROVALS'] = 'Approvals';
$lang['NET_APPROVALS_HELP'] = 'Join requests waiting for approval in networks where your centre is an admin.';
$lang['NET_NO_APPROVALS'] = 'No join requests awaiting your approval.';
$lang['NET_UNKNOWN_CENTRE'] = 'Unknown Centre';
$lang['NET_REQUESTED_TO_JOIN'] = 'Requested to join';
$lang['NET_APPROVE'] = 'Approve';
$lang['NET_APPROVE_JOIN_CONFIRM'] = 'Approve this join request?';
$lang['NET_DECLINE_JOIN_CONFIRM'] = 'Decline this join request?';
$lang['NET_FIND_LOAD_ERROR'] = 'Find a Network: load error';
$lang['NET_FIND_HELP'] = 'Browse professional collaborative networks and request to join.';
$lang['NET_START_NEW'] = 'Start a new network';
$lang['NET_NAME'] = 'Network name';
$lang['NET_NAME_PLACEHOLDER'] = 'e.g. North West Wildlife Network';
$lang['NET_DESCRIPTION_PLACEHOLDER'] = 'Short description shown to other centres';
$lang['NET_JOIN_MODE'] = 'Join mode';
$lang['NET_REQUEST_TO_JOIN'] = 'Request to join';
$lang['NET_INVITE_ONLY'] = 'Invite only';
$lang['NET_JOIN_MODE_HELP'] = 'Request to join networks can be found by other centres. Invite only networks stay hidden unless you invite a centre.';
$lang['NET_CREATE_CONFIRM'] = 'Create this network?';
$lang['NET_SEARCH_PLACEHOLDER'] = 'Search networks by name...';
$lang['NET_NONE_FOUND'] = 'No networks found';
$lang['NET_YOU_ARE_MEMBER'] = 'You are a member';
$lang['NET_JOIN_PENDING'] = 'Join request pending';
$lang['NET_INVITE_WAITING'] = 'Invite waiting';
$lang['NET_REQUEST_DECLINED_PREVIOUSLY'] = 'Request declined previously';
$lang['NET_REMOVED_FROM_NETWORK'] = 'Removed from network';
$lang['NET_YOU_LEFT'] = 'You left this network';
$lang['NET_NOT_JOINED'] = 'Not joined';
$lang['NET_CANCEL_YOUR_JOIN_CONFIRM'] = 'Cancel your join request?';
$lang['NET_REQUEST_JOIN_CONFIRM'] = 'Request to join this network?';
$lang['NET_JOIN_NETWORK'] = 'Join Network';
$lang['NET_NETWORK'] = 'Network';
$lang['NET_MISSING_ID'] = 'Missing network id.';
$lang['NET_NOT_FOUND'] = 'Network not found.';
$lang['NET_ACCESS_DENIED'] = 'Access denied';
$lang['NET_NOT_ACTIVE_MEMBER'] = 'You are not an active member of this network.';
$lang['NET_LOAD_ERROR'] = 'Network: load error';
$lang['NET_YOUR_ROLE'] = 'Your role';
$lang['NET_BACK_MY_NETWORKS'] = 'Back to My Networks';
$lang['NET_LEAVE_NETWORK'] = 'Leave Network';
$lang['NET_LEAVE_CONFIRM'] = 'Leave this network?';
$lang['NET_ADMIN'] = 'Admin';
$lang['NET_ADMIN_HELP'] = 'Manage join requests and invite centres.';
$lang['NET_PENDING_JOIN_REQUESTS'] = 'Pending join requests';
$lang['NET_INVITE_CENTRE'] = 'Invite a centre';
$lang['NET_SEARCH_CENTRES_PLACEHOLDER'] = 'Search centres by name...';
$lang['NET_NO_CENTRES_TO_INVITE'] = 'No centres found to invite';
$lang['NET_INVITE'] = 'Invite';
$lang['NET_MEMBERS'] = 'Members';
$lang['NET_MEMBERS_HELP'] = 'Active centres in this network.';
$lang['NET_NO_MEMBERS'] = 'No members found.';
$lang['NET_YOU'] = 'You';
$lang['NET_MAKE_ADMIN'] = 'Make Admin';
$lang['NET_REMOVE_ADMIN'] = 'Remove Admin';
$lang['NET_REMOVE_CENTRE_CONFIRM'] = 'Remove this centre from the network?';
$lang['NET_SESSION_CONTEXT_MISSING'] = 'Session context missing (centre/user).';
$lang['NET_NAME_REQUIRED'] = 'Network name is required.';
$lang['NET_NAME_TOO_LONG'] = 'Network name is too long.';
$lang['NET_INVALID_JOIN_MODE'] = 'Invalid join mode.';
$lang['NET_CREATED'] = 'Network created.';
$lang['NET_CREATE_FAILED'] = 'Failed to create network: ';
$lang['NET_JOIN_REQUESTS_NOT_ACCEPTED'] = 'This network does not accept join requests.';
$lang['NET_ALREADY_MEMBER'] = 'You are already a member of this network.';
$lang['NET_JOIN_ALREADY_PENDING'] = 'Your join request is already pending.';
$lang['NET_INVITED_ACCEPT_REQUESTS'] = 'You have been invited. Accept the invite in Network Requests.';
$lang['NET_REMOVED_FROM_NETWORK_MSG'] = 'You have been removed from this network.';
$lang['NET_JOIN_REQUEST_SENT'] = 'Join request sent.';
$lang['NET_JOIN_REQUEST_STATUS_ERROR'] = 'Unable to request to join (current status: %s).';
$lang['NET_MISSING_REQUEST_ID'] = 'Missing request id.';
$lang['NET_MEMBERSHIP_NOT_FOUND'] = 'Membership record not found.';
$lang['NET_REQUEST_ACCESS_DENIED'] = 'You do not have access to this request.';
$lang['NET_ONLY_PENDING_CANCEL'] = 'Only pending join requests can be cancelled.';
$lang['NET_JOIN_REQUEST_CANCELLED'] = 'Join request cancelled.';
$lang['NET_MISSING_INVITE_ID'] = 'Missing invite id.';
$lang['NET_INVITE_NOT_FOUND'] = 'Invite record not found.';
$lang['NET_INVITE_ACCESS_DENIED'] = 'You do not have access to this invite.';
$lang['NET_ONLY_INVITES_ACCEPTED'] = 'Only invites can be accepted.';
$lang['NET_ONLY_INVITES_DECLINED'] = 'Only invites can be declined.';
$lang['NET_JOINED_NETWORK'] = 'You have joined the network.';
$lang['NET_INVITATION_DECLINED'] = 'Invitation declined.';
$lang['NET_REQUEST_NOT_FOUND'] = 'Request record not found.';
$lang['NET_ONLY_PENDING_APPROVED'] = 'Only pending requests can be approved.';
$lang['NET_ONLY_PENDING_DECLINED'] = 'Only pending requests can be declined.';
$lang['NET_MUST_ADMIN_APPROVE'] = 'You must be a network admin to approve requests.';
$lang['NET_MUST_ADMIN_DECLINE'] = 'You must be a network admin to decline requests.';
$lang['NET_JOIN_REQUEST_APPROVED'] = 'Join request approved.';
$lang['NET_JOIN_REQUEST_DECLINED'] = 'Join request declined.';
$lang['NET_MISSING_NETWORK_CENTRE_ID'] = 'Missing network/centre id.';
$lang['NET_MUST_ADMIN_INVITE'] = 'You must be a network admin to invite centres.';
$lang['NET_CANNOT_INVITE_OWN_CENTRE'] = 'You cannot invite your own centre.';
$lang['NET_CENTRE_ALREADY_IN_PROGRESS'] = 'Centre is already in progress for this network.';
$lang['NET_INVITATION_SENT'] = 'Invitation sent.';
$lang['NET_MISSING_MEMBER_ID'] = 'Missing member id.';
$lang['NET_MUST_ADMIN'] = 'You must be a network admin.';
$lang['NET_MEMBER_NOT_FOUND'] = 'Member record not found.';
$lang['NET_USE_LEAVE_OWN_CENTRE'] = 'Use Leave Network to remove your own centre.';
$lang['NET_MEMBER_REMOVED'] = 'Member removed.';
$lang['NET_INVALID_ROLE'] = 'Invalid role.';
$lang['NET_ONLY_ACTIVE_MEMBERS_UPDATED'] = 'Only active members can be updated.';
$lang['NET_MEMBER_ROLE_UPDATED'] = 'Member role updated.';
$lang['NET_ONLY_ADMIN_PROMOTE_FIRST'] = 'You are the only admin. Promote another admin before leaving.';
$lang['NET_LEFT_NETWORK'] = 'You have left the network.';
$lang['NET_MISSING_PATIENT_SHARE_ID'] = 'Missing patient share id.';
$lang['NET_PATIENT_SHARE_NOT_FOUND'] = 'Patient share not found.';
$lang['NET_PATIENT_NOT_SHARED'] = 'This patient is not currently shared.';
$lang['NET_UNSHARE_PERMISSION_DENIED'] = 'You do not have permission to unshare this patient.';
$lang['NET_PATIENT_SHARE_REMOVED'] = 'Patient share removed.';
$lang['NET_UNHANDLED_ACTION'] = 'Unhandled action.';

// SETTINGS
$lang['SETTINGS_CENTRE_SETTINGS'] = 'Centre Settings';
$lang['SETTINGS_EDIT_CENTRE_SETTINGS'] = 'Edit your centre settings';
$lang['SETTINGS_CENTRE_PROFILE'] = 'Centre Profile';
$lang['SETTINGS_PROFILE_PAGE'] = 'Profile Page';
$lang['SETTINGS_CONFIGURATION'] = 'Configuration';
$lang['SETTINGS_API_ACCESS'] = 'API Access';
$lang['SETTINGS_MANAGE_API_KEYS'] = 'Manage Centre API Keys';
$lang['SETTINGS_BULLETINS'] = 'Bulletins';
$lang['SETTINGS_CENTRE_DETAILS_TITLE'] = 'Your Centre Details';
$lang['SETTINGS_EDIT_CENTRE_DETAILS'] = 'Edit your centre details';
$lang['SETTINGS_NO_CENTRE_SELECTED'] = 'No centre selected.';
$lang['SETTINGS_CENTRE_NOT_FOUND'] = 'Centre not found.';
$lang['SETTINGS_CENTRE_NUMBER'] = 'Centre Number';
$lang['SETTINGS_CENTRE_DETAILS'] = 'Centre Details';
$lang['SETTINGS_CENTRE_NAME'] = 'Centre Name';
$lang['SETTINGS_EMAIL'] = 'Email';
$lang['SETTINGS_OFFICE_TEL'] = 'Office Tel';
$lang['SETTINGS_MOBILE'] = 'Mobile';
$lang['SETTINGS_24H_TEL'] = '24 Hour Telephone';
$lang['SETTINGS_ADDRESS_SEARCH'] = 'Address Search';
$lang['SETTINGS_ADDRESS_LINE_1'] = 'Address Line 1';
$lang['SETTINGS_ADDRESS_LINE_2'] = 'Address Line 2';
$lang['SETTINGS_COUNTY'] = 'County';
$lang['SETTINGS_COUNTRY_CODE'] = 'Country Code';
$lang['SETTINGS_ADDRESS_SEARCH_PLACEHOLDER'] = 'Start typing an address or postcode...';
$lang['SETTINGS_SPECIES_ACCEPTED'] = 'Species Accepted';
$lang['SETTINGS_SPECIES_PLACEHOLDER'] = 'Type species or orders...';
$lang['SETTINGS_OPENING_HOURS'] = 'Opening Hours';
$lang['SETTINGS_ACCEPTING_ADMISSIONS'] = 'Accepting Admissions';
$lang['SETTINGS_CLOSED_MESSAGE'] = 'Closed Message';
$lang['SETTINGS_SAVE_CHANGES'] = 'Save Changes';
$lang['SETTINGS_CHANGE_COVER_IMAGE'] = 'Change Cover Image';
$lang['SETTINGS_REPOSITION'] = 'Reposition';
$lang['SETTINGS_CHANGE_PROFILE_PHOTO'] = 'Change Profile Photo';
$lang['SETTINGS_CENTRE_BRANDING'] = 'Centre Branding';
$lang['SETTINGS_CENTRE_LOGO'] = 'Centre Logo';
$lang['SETTINGS_CHANGE_LOGO'] = 'Change Logo';
$lang['SETTINGS_CORPORATE_COLOUR'] = 'Corporate Colour';
$lang['SETTINGS_HEX_VALUE'] = 'Hex value';
$lang['SETTINGS_PICKER'] = 'Picker';
$lang['SETTINGS_SAVE_COLOUR'] = 'Save Colour';
$lang['SETTINGS_ABOUT_CENTRE'] = 'About This Centre';
$lang['SETTINGS_CENTRE_BIO'] = 'Centre Bio';
$lang['SETTINGS_SAVE_BIO'] = 'Save Bio';
$lang['SETTINGS_UPLOAD_FAILED'] = 'Upload failed.';
$lang['SETTINGS_POSITION_SAVE_FAILED'] = 'Could not save position.';
$lang['SETTINGS_CENTRE_CONFIG'] = 'Centre Configuration';
$lang['SETTINGS_MANAGE_CENTRE_CONFIG'] = 'Manage Centre Configuration';
$lang['SETTINGS_CONFIG_SUBTITLE'] = 'Operational, workflow and security settings for this centre.';
$lang['SETTINGS_CONFIG_NOT_FOUND'] = 'Centre configuration not found.';
$lang['SETTINGS_INVALID_REQUEST'] = 'Invalid request.';
$lang['SETTINGS_UPDATED'] = 'Settings updated.';
$lang['SETTINGS_REDIRECT_VERIFY'] = 'Redirecting to verification...';
$lang['SETTINGS_HANDOVER_DECLARATION_TEXT'] = 'Handover Declaration Text';
$lang['SETTINGS_HANDOVER_PLACEHOLDER'] = 'Enter the declaration text to display during handover...';
$lang['SETTINGS_HANDOVER_HELP'] = 'This text will be shown during handover workflows for this centre.';
$lang['SETTINGS_CENTRE_TYPE'] = 'Centre Type';
$lang['SETTINGS_WILDLIFE_RESCUE'] = 'Wildlife Rescue';
$lang['SETTINGS_ANIMAL_SANCTUARY'] = 'Animal Sanctuary';
$lang['SETTINGS_REHOMING_CENTRE'] = 'Rehoming Centre';
$lang['SETTINGS_CENTRE_TYPE_HELP'] = 'This setting controls centre classification and can be used to show or hide workflow features elsewhere in the system.';
$lang['SETTINGS_MFA'] = 'Multifactor Authentication';
$lang['SETTINGS_REQUIRE_MFA'] = 'Require MFA for critical actions';
$lang['SETTINGS_ENABLE_AUTH_APP'] = 'Enable Authenticator App (Recommended)';
$lang['SETTINGS_AUTH_APP_HELP'] = 'This option is only available when MFA is enabled. If disabled, MFA will use email based one time codes only.';
$lang['SETTINGS_SPECIES_PREFILL'] = 'Species Prefill';
$lang['SETTINGS_SINGLE_SPECIES'] = 'Single species centre (prefill species on admission)';
$lang['SETTINGS_SINGLE_SPECIES_HELP'] = 'When enabled, the admission species field will be pre-populated. Staff can still type over it.';
$lang['SETTINGS_DEFAULT_SPECIES'] = 'Default species';
$lang['SETTINGS_CONVENIENCE_DEFAULT'] = 'This is a convenience default only.';
$lang['SETTINGS_DOWNLOADS'] = 'Downloads';
$lang['SETTINGS_DOWNLOAD_WORDPRESS_PLUGINS'] = 'Download the latest WordPress plugins';
$lang['SETTINGS_DOWNLOAD_WORDPRESS_PLUGIN'] = 'Download WordPress Plugin';
$lang['SETTINGS_WORDPRESS_PLUGIN_HELP'] = 'Rescue Centre Patients plugin (.zip) for installation via WordPress Plugins -> Add New -> Upload Plugin';
$lang['SETTINGS_API_SUBTITLE'] = 'API keys created here are restricted to this centre only and can be used for patient lookup access.';
$lang['SETTINGS_API_CONTEXT_NOT_FOUND'] = 'Centre context not found.';
$lang['SETTINGS_API_NAME_REQUIRED'] = 'Please enter a name for the API key.';
$lang['SETTINGS_API_CREATED'] = 'API key created. Copy it now - it will only be shown once.';
$lang['SETTINGS_API_CREATE_FAILED'] = 'Unable to create API key.';
$lang['SETTINGS_API_INVALID_SELECTED'] = 'Invalid API key selected.';
$lang['SETTINGS_API_NOT_FOUND'] = 'API key not found.';
$lang['SETTINGS_API_CANNOT_REVOKE'] = 'You cannot revoke that API key.';
$lang['SETTINGS_API_ALREADY_REVOKED'] = 'That API key is already revoked.';
$lang['SETTINGS_API_REVOKED'] = 'API key revoked.';
$lang['SETTINGS_API_COPY_NOW'] = 'Copy this API key now. It will only be shown once.';
$lang['SETTINGS_ENDPOINT'] = 'Endpoint';
$lang['SETTINGS_ENDPOINT_URL'] = 'Endpoint URL';
$lang['SETTINGS_API_KEY'] = 'API Key';
$lang['SETTINGS_COPY_API_KEY'] = 'Copy API Key';
$lang['SETTINGS_API_KEY_COPIED'] = 'API key copied';
$lang['SETTINGS_COPY_ENDPOINT_URL'] = 'Copy endpoint URL';
$lang['SETTINGS_ENDPOINT_COPIED'] = 'Endpoint copied';
$lang['SETTINGS_GENERATE'] = 'Generate';
$lang['SETTINGS_GENERATED_KEYS'] = 'Generated Keys';
$lang['SETTINGS_NO_API_KEYS'] = 'No API keys have been created for this centre yet.';
$lang['SETTINGS_PREFIX'] = 'Prefix';
$lang['SETTINGS_LAST_USED'] = 'Last Used';
$lang['SETTINGS_REVOKE'] = 'Revoke';
$lang['SETTINGS_REVOKED'] = 'Revoked';
$lang['SETTINGS_REVOKE_CONFIRM'] = 'Revoke this API key? This cannot be undone.';
$lang['SETTINGS_EXAMPLE_MAIN_WEBSITE'] = 'e.g. Main Website';
$lang['SETTINGS_VALID_HEX_COLOUR'] = 'Enter a valid six-digit hex colour.';
$lang['SETTINGS_COLOUR_UPDATED'] = 'Corporate colour updated.';
$lang['SETTINGS_TAB_UNAVAILABLE'] = 'This settings tab is unavailable.';
$lang['SETTINGS_CSRF_MISSING'] = 'CSRF missing.';
$lang['SETTINGS_CSRF_INVALID'] = 'CSRF invalid.';
$lang['SETTINGS_NOT_AUTHENTICATED'] = 'Not authenticated.';
$lang['SETTINGS_NO_CENTRE_BOUND'] = 'No centre bound to this account.';
$lang['SETTINGS_BAD_IMAGE_TYPE'] = 'Bad image type.';
$lang['SETTINGS_UPLOAD_ERROR'] = 'Upload error.';
$lang['SETTINGS_INVALID_EXTENSION'] = 'Invalid extension.';
$lang['SETTINGS_SERVER_REFUSED_MOVE'] = 'Server refused move.';

$lang['PAG_PREV_TEXT'] = 'Prev';
$lang['PAG_NEXT_TEXT'] = 'Next';

// ERROR MESSAGES AND STATUSES
$lang['STATUS'] = 'Status';
$lang['CAPTIVE'] = 'Captive';
$lang['RELEASED'] = 'Released';
$lang['DECEASED'] = 'Deceased';
$lang['CLOSED'] = 'Closed';
$lang['COMPLETE'] = 'Complete';
$lang['INCOMPLETE'] = 'Incomplete';
$lang['PARTIAL'] = 'Partial';
$lang['NOT_STARTED'] = 'Not started';
$lang['NOT_COMPLETED'] = 'Not completed';
$lang['CURRENT'] = 'Current';
$lang['ACTIVE'] = 'Active';
$lang['NORMAL'] = 'Normal';
$lang['PEAK'] = 'Peak';
$lang['HIGH'] = 'High';
$lang['LOW'] = 'Low';
$lang['TYPICAL'] = 'Typical';
$lang['OCCUPIED'] = 'occupied';
$lang['EUTHANISED'] = 'Euthanised';
$lang['NOT_APPLICABLE'] = 'Not Applicable';
$lang['LOADED'] = 'Loaded';
$lang['SAVED'] = 'Saved';
$lang['CHANGES'] = 'Changes';
$lang['UNAVAILABLE'] = 'Unavailable';
$lang['SAVING'] = 'Saving';
$lang['FAILED'] = 'Failed';
$lang['PROGRESS'] = 'Progress';
$lang['ADDED'] = 'Added';
$lang['CREATED'] = 'Created';
$lang['UPDATED'] = 'Updated';
$lang['UNLINKED'] = 'Unlinked';
$lang['DATABASE_CONNECTION_MISSING'] = 'Database connection missing ($pdo not set).';
$lang['CENTRE_CONTEXT_MISSING'] = 'Centre context missing (centre_id not set).';
$lang['NO_RESULTS'] = 'No results';
$lang['SEARCH_ERROR'] = 'Search error';
$lang['ERROR'] = 'Error';
$lang['NETWORK_ERROR'] = 'Network error';
$lang['FORM_NOT_FOUND'] = 'Form not found';
$lang['MED_PROFILES_FAILED_TO_LOAD'] = 'Medication Profiles failed to load:';
$lang['DIET_MSG_ADDED'] = 'Item added to your centre list.';
$lang['DIET_MSG_UPDATED'] = 'Centre diet item updated.';
$lang['DIET_MSG_DELETED'] = 'Centre diet item removed.';
$lang['DIET_MSG_ERROR'] = 'Something went wrong. Please try again.';
$lang['DIET_AUDIT_UPDATED'] = 'Diet item updated';
$lang['DIET_AUDIT_DELETED'] = 'Diet item deleted';
$lang['DIET_AUDIT_ADDED'] = 'Diet item added to centre';
$lang['DIET_ITEM_ADDED_BADGE'] = 'Item added';
$lang['COMPLETE_REQUIRED_FIRST'] = 'Please complete required fields first.';
$lang['ADM_STATUS_FROM_DISPOSITION'] = 'Set automatically from disposition.';
$lang['ADM_FINDER_REQUIRED'] = 'Name and Telephone required.';
$lang['ADM_FINDER_ADDED'] = 'Finder added successfully.';
$lang['ADM_GEOLOCATION_UNSUPPORTED'] = 'Geolocation not supported.';
$lang['ADM_LOCATION_UNAVAILABLE'] = 'Unable to get location:';
$lang['FETCHING'] = 'Fetching...';
$lang['ADM_WEATHER_FETCH_DISABLED'] = 'Weather fetch is disabled (no permission to fetch and/or edit weather fields).';
$lang['ADM_WEATHER_DATE_UNAVAILABLE'] = 'Weather data not available for this date.';
$lang['ADM_WEATHER_HOUR_UNAVAILABLE'] = 'No weather data available for that hour.';
$lang['ADM_WEATHER_FETCH_ERROR'] = 'Error fetching weather.';
$lang['ADM_SAVE_COLLECTION_FIRST'] = 'Please set and save the collection location in Section 3 first.';
$lang['ADM_SAVE_ADMISSION_DATE_FIRST'] = 'Please set and save the admission date/time in Section 2 first.';
$lang['SIGNATURE_HELPER_UNAVAILABLE'] = 'The section save helper is not available.';
$lang['SIGNATURE_COMPLETE_LOCKED'] = 'Declaration completed with an electronic signature. Changes are not allowed.';
$lang['SIGNATURE_REFUSED_LOCKED'] = 'No signature was provided / signature refused. This has been recorded and cannot be changed.';
$lang['ADM_DECLARATION_EXISTS'] = 'A declaration record already exists for this admission';
$lang['ADM_DECLARATION_LOCKED'] = 'The declaration can only be completed once and cannot be edited.';
$lang['ADM_DECLARATION_NO_PERMISSION'] = 'You can view this declaration, but you do not have permission to complete it.';
$lang['ADM_DISPOSITION_NO_PERMISSION'] = 'You can view disposition details, but you do not have permission to update them.';
$lang['ADM_PATIENT_ADMITTED'] = 'Patient has been admitted.';
$lang['ADM_UNABLE_TO_ADMIT'] = 'Unable to admit patient.';
$lang['ADM_SECTION_NOT_IMPLEMENTED'] = 'Section %s not yet implemented.';
$lang['ADM_CREATING_SEPARATE'] = 'Creating a new separate admission...';
$lang['ADM_NETWORK_SAVE_ERROR'] = 'Network/JS error while saving.';
$lang['ADM_ERROR_SAVING_SECTION'] = 'Error saving this section.';
$lang['ADM_INVALID_SERVER_RESPONSE'] = 'The server returned an invalid response.';
$lang['ADM_SAVE_FAILED_STATUS'] = 'Save failed with status %s.';
$lang['ADM_SAVE_FAILED'] = 'Save failed.';
$lang['ADM_NETWORK_SAVE_FAILED'] = 'Network error while saving.';
$lang['ADM_NONE_AWAITING'] = 'No patients are currently awaiting admission.';
$lang['DASH_NO_STAY_DATA'] = 'No completed stay data available.';
$lang['MEDS_NONE_TODAY'] = 'There are no medications scheduled for today.';
$lang['MED_STOCK_NONE'] = 'No medication in stock.';
$lang['MED_STOCK_CURRENT_OPEN_PACK'] = 'Current Open Pack:';
$lang['MED_STOCK_NO_OPEN_PACKS'] = 'No open packs.';
$lang['MED_PROFILES_CURRENT'] = 'Current Profiles';
$lang['MED_PROFILES_NONE'] = 'No medication profiles set up yet.';
$lang['DIET_CENTRE_EMPTY'] = 'You haven’t added any diet items to your centre yet. Use the master list below to add your first items.';
$lang['DIET_NONE_FOUND'] = 'No diet items found';
$lang['NO_PATIENTS_FOUND'] = 'No patients found.';
$lang['TASK_COULD_NOT_BE_COMPLETED'] = 'Task could not be completed.';
$lang['NETWORK_ERROR_COMPLETING_TASK'] = 'Network error completing task.';

//ANIMAL CLASSES
$lang['AC_AMPHIBIAN'] = 'Amphibian';
$lang['AC_BIRD'] = 'Bird';
$lang['AC_FISH'] = 'Fish';
$lang['AC_MAMMAL'] = 'Mammal';
$lang['AC_REPTILE'] = 'Reptile';
$lang['AC_UNKNOWN'] = 'Unknown';

//ANIMAL TYPES
$lang['AT_Birds of Prey'] = 'Birds of Prey';

//DASHBOARD
$lang['DASH_ANIMALS_IN_CARE'] = 'Animals in your care';
$lang['DASH_ANIMALS_RELEASED'] = 'Released animals';
$lang['DASH_ANIMALS_THAT_DIED'] = 'Animals that have died';
$lang['DASH_ADMISSIONS_DAY_WEEK_TITLE'] = 'Admissions by Day of Week (Last 5 Years)';
$lang['DASH_ADMISSIONS_TIME_TITLE'] = 'Admissions by Time of Day (Last 5 Years)';
$lang['DASH_ADMISSIONS_DAY_TIME_TITLE'] = 'Admissions by Day and Time (Last 5 Years)';
$lang['ADMISSIONS'] = 'Admissions';
$lang['LOWER'] = 'Lower';
$lang['HIGHER'] = 'Higher';
$lang['DASH_ALL_TIME_SUMMARY'] = 'All-time summary';
$lang['DASH_SINCE_RECORDS_BEGAN'] = 'Since records began';
$lang['DASH_CAPACITY_UTILISATION'] = 'Capacity utilisation';
$lang['DASH_COMFORTABLE_CAPACITY'] = 'Comfortable capacity';
$lang['DASH_HIGH_OCCUPANCY'] = 'High occupancy';
$lang['DASH_MODERATE_OCCUPANCY'] = 'Moderate occupancy';
$lang['DASH_CHANGE_ON'] = 'Change on';
$lang['DASH_VS_LAST_YEAR_YTD'] = 'vs last year YTD';
$lang['DASH_THREE_YEAR_TREND'] = '3-year trend';
$lang['DASH_SEASONAL_PRESSURE'] = 'Seasonal pressure';
$lang['DASH_LAST_7_VS_NORM'] = 'Last 7 days vs seasonal norm';
$lang['DIFFERENCE'] = 'Difference';
$lang['DASH_BASED_ON_PREVIOUS_YEARS'] = 'based on the same 7-day window across the previous %s years';
$lang['DASH_SEASON_CLOSE'] = 'Close to seasonal average';
$lang['DASH_SEASON_WELL_ABOVE'] = 'Well above seasonal average';
$lang['DASH_SEASON_ABOVE'] = 'Above seasonal average';
$lang['DASH_SEASON_BELOW'] = 'Below seasonal average';
$lang['DASH_SPECIES_INSIGHTS_SUB'] = 'Long-term species patterns, outcomes, and rehabilitation load.';
$lang['DASH_AVG_STAY_OVERALL'] = 'Avg Stay Overall';
$lang['DASH_DAYS_ADMISSION_TO_DISPOSITION'] = 'days from admission to disposition';
$lang['DASH_TOP_5_OVERALL_AVG'] = 'Top 5 species compared with overall average of';
$lang['AVERAGE'] = 'Average';
$lang['CASES'] = 'Cases';
$lang['OTHER'] = 'Other';
$lang['TEMPERATURE_ABBR'] = 'Temp';
$lang['RAIN'] = 'Rain';
$lang['COMPLAINT'] = 'Complaint';

//MAP
$lang['MAP_CONTROLS'] = 'Controls';
$lang['MAP_ALL_SPECIES'] = 'All Species';
$lang['MAP_ALL_YEARS'] = 'All Years';
$lang['MAP_RESET'] = 'Reset Filters';
$lang['MAP_TEMPERATURE'] = 'Temperature';
$lang['MAP_WIND'] = 'Wind';
$lang['MAP_RAINFALL'] = 'Rainfall';
$lang['MAP_LEGEND'] = 'Map Key';
$lang['MAP_PIN_COLOURS'] = 'Pin Colours (Year)';
$lang['MAP_TWO_YEARS_AGO'] = 'Two Years Ago';
$lang['MAP_LAST_YEAR'] = 'Last Year';

//MY PATIENTS
$lang['PAT_YOU_HAVE'] = 'You have';
$lang['PAT_IN_RESCUE'] = 'patients in your rescue';
$lang['PAT_DAYS_IN_CARE'] = 'Days in care';
$lang['PAT_LOCATION'] = 'Patient Location';
$lang['PAT_SCORE'] = 'Score';
$lang['PAT_KEY_TO_DAYS'] = 'Key to days in care';
$lang['PAT_MORE_THAN'] = 'More than';
$lang['PAT_LESS_THAN'] = 'Less than';
$lang['ADD_PATIENTS'] = 'Add Patients';
$lang['RESIDENTS_SUBTITLE'] = 'Manage your long term residents.';
$lang['WRA_SCORE_EXPLAINED_BTN'] = 'WRA Score Explained';
$lang['WRA_SCORE_EXPLAINED_TITLE'] = 'Wildlife Rapid Assessment Score Explained';
$lang['TABLE_ALL'] = 'All';

//PATIENT ARCHIVE
$lang['ARC_YOU_CARED_FOR'] = 'You have cared for';

// MEDICATION
$lang['MEDS_ROUND_SUBTITLE'] = 'These Patients Require Medication Today';
$lang['MEDS_PRINT_AREA_ROUNDS'] = 'Print Area Rounds';
$lang['MEDS_PRINT_TIME_ROUNDS'] = 'Print Time Rounds';
$lang['MEDS_AREA_LABEL'] = 'Area:';
$lang['MEDS_UNSCHEDULED'] = 'Unscheduled';
$lang['MEDS_ROUND_MORNING'] = 'Morning Round';
$lang['MEDS_ROUND_LATE_MORNING'] = 'Late Morning Round';
$lang['MEDS_ROUND_LUNCHTIME'] = 'Lunchtime Round';
$lang['MEDS_ROUND_EARLY_AFTERNOON'] = 'Early Afternoon Round';
$lang['MEDS_ROUND_TEATIME'] = 'Teatime Round';
$lang['MEDS_ROUND_NIGHT'] = 'Night Time Round';
$lang['MEDS_COL_TIME'] = 'Time';
$lang['MEDS_COL_ROUTE'] = 'Route';
$lang['MEDS_COL_DOSE'] = 'Dose';
$lang['MEDS_COL_FREQUENCY'] = 'Frequency';
$lang['MEDS_BY'] = 'by';
$lang['MEDS_BTN_ADMINISTER'] = 'Administer';

$lang['MED_STOCK_TITLE'] = 'Medication Stock Management';
$lang['MED_STOCK_HEADING'] = 'Medication - Stock Management';
$lang['MED_STOCK_SUBTITLE'] = "Manage your centre's stock";
$lang['MED_STOCK_TAB_LIST'] = 'Medication in Stock';
$lang['MED_STOCK_TAB_PROFILES'] = 'Medication Profiles';
$lang['MED_STOCK_TAB_DIET'] = 'Diet Items';

$lang['MED_STOCK_PACKS'] = 'packs';
$lang['MED_STOCK_BATCH'] = 'Batch';
$lang['MED_STOCK_EXP'] = 'EXP';
$lang['MED_STOCK_REMAINING'] = 'remaining';
$lang['MED_STOCK_OPEN'] = 'OPEN';

$lang['MED_PROFILES_INTRO'] = 'Set up the medicines your centre routinely keeps so stock can be added and managed consistently.';
$lang['MED_PROFILES_DESC_1'] = 'Medication profiles define the medicines your centre keeps, and how they are supplied (strength, reference volume, pack size and form).';
$lang['MED_PROFILES_DESC_2'] = 'Once a profile is set up, you can add stock and administer medication without re-entering the same details each time.';
$lang['MED_PROFILES_KICKER'] = 'Create profile once → add stock → administer safely.';
$lang['MED_PROFILES_ADD_HELP'] = 'Start typing to select a medication from the master list, then set the centre-specific profile values.';
$lang['MED_PROFILES_START_TYPING'] = 'Start typing medication…';
$lang['MED_PROFILES_MASTER_LIST_HELP'] = 'Select from the master list (common name / medication name).';

$lang['PACK_SIZE_HINT'] = 'e.g. bottle size / tablets per pack';
$lang['PACK_SIZE_EXAMPLES'] = 'Examples: 100ml bottle, 28 tablets, 50g tube.';
$lang['ADD_MEDICATION_PROFILE'] = 'Add Medication Profile';

//DIET (Diet items)
$lang['DIET_CENTRE_TITLE'] = 'Centre Diet Items';
$lang['DIET_CENTRE_SUBTITLE'] = 'Below is the list of foodstuffs you regularly use in your centre. Toggle items on/off, adjust “use within” defaults, or remove items. Add new items from the master list underneath.';
$lang['DIET_TH_ITEM'] = 'Item';
$lang['DIET_TH_TYPE'] = 'Type';
$lang['DIET_TH_UNIT'] = 'Unit';
$lang['DIET_TH_ENABLED'] = 'Enabled';
$lang['DIET_TH_CATEGORY'] = 'Category';
$lang['DIET_TH_ACTION'] = 'Action';
$lang['DIET_CATEGORY_LABEL'] = 'Category:';
$lang['DIET_CONFIRM_REMOVE'] = 'Are you sure you want to remove this item from your centre?';
$lang['DIET_MASTER_TITLE'] = 'Master Diet Library';
$lang['DIET_SHOWING'] = 'Showing';
$lang['DIET_ITEMS'] = 'items';
$lang['DIET_MATCHING'] = 'matching';
$lang['DIET_SEARCH_PLACEHOLDER'] = 'Search diet items by name...';
$lang['DIET_ADD_TO_RESCUE'] = '+ Add to rescue';
$lang['DIET_FOR_THAT_SEARCH'] = 'for that search.';

// Stock add
$lang['MED_STOCK_ADD_TITLE'] = 'Add Medication to Stock';
$lang['MED_PROFILE'] = 'Medication Profile';
$lang['SELECT_MEDICATION'] = 'Select medication...';
$lang['MED_STOCK_PACKS_RECEIVED'] = 'Number of Packs Received';
$lang['MED_STOCK_BATCH_NUMBER'] = 'Batch Number';
$lang['MED_STOCK_EXPIRY_DATE'] = 'Expiry Date';
$lang['NOTES_OPTIONAL'] = 'Notes (optional)';

// Pack type words (used in dropdown labels)
$lang['PACKTYPE_BOTTLE'] = 'bottle';
$lang['PACKTYPE_TUBE'] = 'tube';
$lang['PACKTYPE_PACK'] = 'pack';

//NEW ADMISSION FORM

// ADMISSION SECTIONS
$lang['ADM_SECTION_1_INTRO'] = 'Enter the basic details for this patient.';
$lang['ADM_APPROX_DOB'] = 'Approx DOB';
$lang['ADM_SPECIES_SEARCH_PLACEHOLDER'] = 'Start typing common or scientific name...';
$lang['ADM_SPECIES_PREFILLED'] = 'Prefilled from centre default - you can change this.';
$lang['ADM_EXTERNAL_SPECIES_PLACEHOLDER'] = 'Type a common name... e.g. zebra';
$lang['ADM_SELECT_SPECIES_LIST'] = 'Select a species from the list.';
$lang['ADM_CLICK_SPECIES_LIST'] = 'Click a species from the list.';
$lang['ADM_HELD_IN_CAPTIVITY'] = 'Held in captivity';
$lang['ADM_TRANSFERRED_OUT'] = 'Transferred out';
$lang['ADM_DIED_EUTHANISED'] = 'Died - Euthanised';
$lang['ADM_DIED_AFTER_48_HOURS'] = 'Died - after 48 hours';
$lang['ADM_DIED_WITHIN_48_HOURS'] = 'Died - within 48 hours';
$lang['ADM_DIED_ON_ADMISSION'] = 'Died - on admission';
$lang['ADM_FOR_ADOPTION'] = 'For Adoption';
$lang['ADM_ADOPTED'] = 'Adopted';
$lang['ADDRESS_PLACEHOLDER'] = 'Start typing an address...';
$lang['LATITUDE'] = 'Latitude';
$lang['LONGITUDE'] = 'Longitude';
$lang['ADM_FINDER_SEARCH_PLACEHOLDER'] = 'Type to search finder...';
$lang['SMS_CONSENT'] = 'SMS Consent';
$lang['PASSPHRASE'] = 'Passphrase';
$lang['PASSPHRASE_HELP'] = 'Used for finder verification on the public page.';
$lang['DEHYDRATED'] = 'Dehydrated?';
$lang['STARVED'] = 'Starved?';
$lang['WEIGHT_UNIT'] = 'Weight unit';
$lang['MEASUREMENT_UNIT'] = 'Measurement unit';
$lang['MAMMALS'] = 'Mammals';
$lang['BIRDS'] = 'Birds';
$lang['NEWBORN'] = 'Newborn';
$lang['DEPENDENT_JUVENILE'] = 'Dependent Juvenile';
$lang['INDEPENDENT_JUVENILE'] = 'Independent Juvenile';
$lang['ADULT'] = 'Adult';
$lang['HATCHLING'] = 'Hatchling';
$lang['FLEDGLING'] = 'Fledgling';
$lang['APPARENTLY_HEALTHY'] = 'Apparently Healthy';
$lang['MILDLY_UNWELL'] = 'Mildly unwell';
$lang['OBVIOUS_INJURIES'] = 'Obvious Injuries';
$lang['SEVERE_INJURIES'] = 'Severe Injuries';
$lang['NEAR_DEATH'] = 'Near Death';
$lang['ADM_BCS_1_LABEL'] = '1 - Emaciated/Skeletal';
$lang['ADM_BCS_2_LABEL'] = '2 - Underweight';
$lang['ADM_BCS_3_LABEL'] = '3 - Slightly Underweight';
$lang['ADM_BCS_4_LABEL'] = '4 - Healthy';
$lang['ADM_BCS_5_LABEL'] = '5 - Overweight';
$lang['ON_EXAMINATION'] = 'On Examination';
$lang['ADM_WEATHER_NOTES_PLACEHOLDER'] = 'Describe weather or abnormalities';
$lang['ADM_WEATHER_SOURCE_HELP'] = 'Uses the collection location (Section 3) and admission date/time (Section 2).';
$lang['ADM_WEATHER_APPROX'] = 'Approx: %s°C, %s%% humidity, %s mph wind.';
$lang['ADM_DEFAULT_DECLARATION_1'] = 'By handing over this animal to the rescue centre, the finder confirms that they transfer ongoing responsibility for the care and welfare of the animal to the rescue.';
$lang['ADM_DEFAULT_DECLARATION_2'] = 'The finder understands that their personal details (where provided and consented) may be stored and used for the purposes of providing updates on the animal’s progress and for audit/legal purposes in line with GDPR and the centre’s privacy policy.';
$lang['ADM_SIGN_BELOW'] = 'Ask the finder to sign in the box below.';
$lang['SIGNATURE_INSTRUCTIONS'] = 'Use mouse or touch to sign. If no signature is provided, tick the box below.';
$lang['ADM_NO_SIGNATURE_PROMPT'] = 'Tick this box if a signature was not obtained:';
$lang['ADM_RECORDED_ON'] = 'recorded on';
$lang['SIGNATURE_COMPLETION_RULE'] = 'This section is only considered complete if either a signature is captured or this box is checked to record that a signature was refused, not possible or not collected.';
$lang['ADM_PHARMACOLOGICAL_VET'] = 'Pharmacological - Vet';
$lang['ADM_PHARMACOLOGICAL_CENTRE'] = 'Pharmacological - Centre';
$lang['MANUAL'] = 'Manual';
$lang['ADM_CAPTIVE_BOLT'] = 'Captive Bolt';
$lang['SHOT'] = 'Shot';
$lang['ADM_CONFIRM_ADMIT'] = 'Are you sure you want to admit this patient?';
$lang['ADM_DUPLICATE_PROMPT'] = "A partial admission already exists for this patient.\n\n%s\n\nOK = continue the existing partial admission.\nCancel = create a new separate admission.";
$lang['ADM_READMIT_EXISTING'] = 'Re-admit existing patient';
$lang['ADM_LOOKUP_PLACEHOLDER'] = 'Enter microchip or ring number to search database wide...';
$lang['ADM_MIN_SEARCH_CHARACTERS'] = 'Enter at least 4 characters.';
$lang['MATCHED'] = 'Matched';
$lang['ADM_SKIP_TO_SECTION_2'] = 'Admit (skip to S2)';
$lang['ADM_TO_ADMIT_HELP'] = 'These patients have been registered but still require the admission to complete. They will appear on My Patients once the admission has been finalised.';
$lang['DISCARD'] = 'Discard';
$lang['ADM_CONFIRM_DISCARD'] = 'Discard this partial admission? It will be removed from the To Admit queue.';

//LEFT MENU
$lang['LM_DASHBOARD'] = 'Dashboard';
$lang['LM_MESSAGES'] = 'Messages';
$lang['LM_MY_PATIENTS'] = 'My Patients';
$lang['LM_PATIENTS'] = 'Patients';
$lang['LM_PATIENT_ARCHIVE'] = 'Patient Archive';
$lang['LM_INCIDENTS'] = 'Incidents';
$lang['LM_FORMS'] = 'Forms';
$lang['LM_NEW_ADMISSION'] = 'New Admission';
$lang['LM_MEDICATION'] = 'Medication';
$lang['LM_MEDICATION_ROUND'] = 'Medication Round';
$lang['LM_STOCK_MANAGEMENT'] = 'Stock Management';
$lang['LM_QUERY_BUILDER'] = 'Query Builder';
$lang['LM_CENTRE_MANAGEMENT'] = 'Centre Management';
$lang['LM_STOCK_MEDICATION'] = 'Stock Medication';
$lang['LM_MANAGE_USERS'] = 'Manage Users';
$lang['LM_RESOURCES'] = 'Resources';
$lang['LM_KNOWLEDGEBASE'] = 'Knowledgebase';
$lang['LM_ORG_DASH'] = 'Organisation Dashboard';
$lang['LM_MANAGE_TASKS'] = 'Manage Tasks';
$lang['LM_TASKS'] = 'Tasks';
$lang['LM_SUPPORT'] = 'Support';
$lang['LM_RESIDENTS'] = 'Residents';
$lang['LM_ARCHIVE'] = 'Archive';
$lang['LM_INDIVIDUAL_PATIENT_VIEW'] = 'Individual Patient View';
$lang['LM_MANAGEMENT'] = 'Management';
$lang['LM_CENTRE_SETTINGS'] = 'Centre Settings';
$lang['LM_LOCATIONS'] = 'Locations';
$lang['LM_USERS'] = 'Accounts and Permissions';
$lang['LM_MANAGE_DATA'] = 'Manage Data';
$lang['LM_REPORTS'] = 'Reports';
$lang['LM_COMMUNITY'] = 'Community';
$lang['LM_FRIENDS'] = 'Friends';
$lang['LM_MESSAGE_BOARD'] = 'Message Board';
$lang['LM_NETWORK_VIEW'] = 'Network View';

// TOP Menu
$lang['CURRENT_LANGUAGE'] = 'EN';
$lang['LANG_ICON'] = 'en.png';
$lang['MENU_SETTINGS'] = 'Settings';
$lang['MENU_CONNECTIONS'] = 'Connections';
$lang['MENU_NETWORKS'] = 'Networks';
$lang['MENU_EDIT_ACCOUNT'] = 'Edit my Account';
$lang['MENU_LOGOUT'] = 'Logout';


// DAYS OF THE WEEK
$lang['DAY_MON'] = 'Mon';
$lang['DAY_TUE'] = 'Tue';
$lang['DAY_WED'] = 'Wed';
$lang['DAY_THU'] = 'Thu';
$lang['DAY_FRI'] = 'Fri';
$lang['DAY_SAT'] = 'Sat';
$lang['DAY_SUN'] = 'Sun';

// MONTHS - ABBREVIATED
$lang['MONTH_SHORT_JAN'] = 'Jan';
$lang['MONTH_SHORT_FEB'] = 'Feb';
$lang['MONTH_SHORT_MAR'] = 'Mar';
$lang['MONTH_SHORT_APR'] = 'Apr';
$lang['MONTH_SHORT_MAY'] = 'May';
$lang['MONTH_SHORT_JUN'] = 'Jun';
$lang['MONTH_SHORT_JUL'] = 'Jul';
$lang['MONTH_SHORT_AUG'] = 'Aug';
$lang['MONTH_SHORT_SEP'] = 'Sept';
$lang['MONTH_SHORT_OCT'] = 'Oct';
$lang['MONTH_SHORT_NOV'] = 'Nov';
$lang['MONTH_SHORT_DEC'] = 'Dec';

// MONTHS - FULL
$lang['MONTH_JAN'] = 'January';
$lang['MONTH_FEB'] = 'February';
$lang['MONTH_MAR'] = 'March';
$lang['MONTH_APR'] = 'April';
$lang['MONTH_MAY'] = 'May';
$lang['MONTH_JUN'] = 'June';
$lang['MONTH_JUL'] = 'July';
$lang['MONTH_AUG'] = 'August';
$lang['MONTH_SEP'] = 'September';
$lang['MONTH_OCT'] = 'October';
$lang['MONTH_NOV'] = 'November';
$lang['MONTH_DEC'] = 'December';

/* -------------------------------------------------------
   CANONICAL KEYS FOR TABLE/UI BITS (no more self-?? lines)
   ------------------------------------------------------- */
$lang['TABLE_SHOW'] = 'Show';
$lang['TABLE_ENTRIES'] = 'entries';
$lang['TABLE_SEARCH_PLACEHOLDER_PATIENTS'] = 'Search by CRN, name or species';

$lang['PAT_TAB_ALL'] = $lang['TABLE_ALL'];   // reuse
$lang['PAT_UNASSIGNED'] = 'Unassigned';
$lang['PAT_WRA_NA'] = 'N/A';
$lang['PAT_CRN'] = 'CRN';

$lang['PAT_TABLE_CRN_PATIENT'] = 'CRN / Patient';
$lang['PAT_TABLE_ADMISSION_DATE'] = 'Admission<br>Date';
$lang['PAT_TABLE_DAYS_IN_CARE'] = 'Days in<br>Care';
$lang['PAT_TABLE_PRESENTING_COMPLAINT'] = 'Presenting<br>Complaint';
$lang['PAT_TABLE_WRA_ADMISSION'] = 'WRA Score<br>(admission)';
$lang['PAT_TABLE_WRA_CURRENT'] = 'WRA Score<br>(current)';

$lang['BTN_CARE_PLAN'] = 'Care Plan';
$lang['TIP_VIEW_CARE_PLAN'] = 'View Care Plan';

$lang['TIP_ADD_A_CARE_NOTE'] = 'Add a Care Note';
$lang['TIP_ADD_AN_OBSERVATION'] = 'Add an Observation';
$lang['TIP_ADD_A_PRESCRIPTION'] = 'Add a Prescription';
$lang['TIP_ADMINISTER_A_MEDICATION'] = 'Administer a Medication';
$lang['TIP_ADD_A_TREATMENT'] = 'Add a Treatment';
$lang['TIP_ADD_A_FEEDING'] = 'Add a Feeding';
$lang['TIP_ADD_LAB_RESULTS'] = 'Add Lab Results';
$lang['TIP_ADD_A_WEIGHT'] = 'Add a Weight';
$lang['TIP_ADD_A_MEASUREMENT'] = 'Add a Measurement';
$lang['TIP_ADD_A_PARTNER_LOG'] = 'Add a Partner Log';
$lang['TIP_ADD_A_QUICK_TASK'] = 'Add a Quick Task';

$lang['TIP_DISCHARGE_THIS_PATIENT'] = 'Discharge this Patient';

$lang['TASK_UNKNOWN_USER'] = 'Unknown user';
$lang['TASK_TOOLTIP_COMPLETED'] = 'Patient had {task} - Completed by {by} on {dt}';
$lang['TASK_TOOLTIP_REQUIRES'] = 'Requires {task} (mark to complete)';

$lang['PAG_PREV'] = '«';
$lang['PAG_NEXT'] = '»';


/* -------------------------------------------------------
   COMPAT / ALIASES (keeps old/new view code working)
   ------------------------------------------------------- */
// my_patients wrappers (title/subtitle)
$lang['MY_PATIENTS_TITLE'] = $lang['LM_MY_PATIENTS']; // reuse left-menu text
$lang['MY_PATIENTS_SUB_TITLE'] = 'Manage your in-patients';

// stock headings accidentally created with different casing
$lang['MED_STOCK_Packs'] = $lang['MED_STOCK_PACKS'];        // if any legacy view used this

?>
