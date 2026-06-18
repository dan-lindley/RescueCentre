<?php
// modules/incidents/views/incident.php
if (!defined('APP_LOADED')) exit;

require_once __DIR__ . '/../controllers/incidents_lib.php';
$incident_lang = incidents_module_language();
$incidentAction = 'modules/incidents/controllers/incidents_handler.php';

$centre_id_int = isset($centre_id) ? (int)$centre_id : (int)($_SESSION['centre_id'] ?? 0);
$incident_id = (int)($_GET['incident_id'] ?? 0);

function incident_detail_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function incident_detail_datetime_value($value): string
{
    if (empty($value)) {
        return '';
    }
    $time = strtotime((string)$value);
    return $time ? date('Y-m-d\TH:i', $time) : '';
}

$incident = null;
$admissions = [];
$linked_patients = [];

if ($centre_id_int <= 0) {
    echo '<div class="alert-box alert-red">' . incident_detail_h(incidents_text('CENTRE_CONTEXT_MISSING', 'Centre context missing.')) . '</div>';
    return;
}

if ($incident_id <= 0) {
    echo '<div class="alert-box alert-red">' . incident_detail_h(incidents_text('INC_MISSING', 'Incident missing.')) . '</div>';
    return;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            incident_id,
            incident_date,
            incident_location_line_1,
            incident_location_line_2,
            incident_location_city,
            incident_location_postcode,
            incident_centre_ref,
            incident_total_casualties,
            incident_doa,
            incident_mass_cas,
            centre_id,
            user_id
        FROM rescue_incidents
        WHERE incident_id = :incident_id
          AND centre_id = :centre_id
        LIMIT 1
    ");
    $stmt->execute([
        ':incident_id' => $incident_id,
        ':centre_id' => $centre_id_int,
    ]);
    $incident = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$incident) {
        echo '<div class="alert-box alert-red">' . incident_detail_h(incidents_text('INC_NOT_FOUND', 'Incident not found.')) . '</div>';
        return;
    }

    $stmt = $pdo->prepare("
        SELECT
            a.admission_id,
            a.patient_id,
            a.admission_date,
            a.presenting_complaint,
            p.name,
            p.animal_type,
            p.animal_species,
            p.sex
        FROM rescue_admissions a
        INNER JOIN rescue_patients p
            ON p.patient_id = a.patient_id
        WHERE a.centre_id = ?
          AND p.centre_id = ?
        ORDER BY a.admission_date DESC, a.admission_id DESC
        LIMIT 500
    ");
    $stmt->execute([$centre_id_int, $centre_id_int]);
    $admissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT
            rel.inc_rel_id,
            rel.incident_id,
            rel.admission_id,
            rel.finder_id,
            a.patient_id,
            a.admission_date,
            a.presenting_complaint,
            a.age_on_admission,
            p.name,
            p.sex,
            p.animal_type,
            p.animal_species
        FROM rescue_incident_related rel
        INNER JOIN rescue_admissions a
            ON a.admission_id = rel.admission_id
        INNER JOIN rescue_patients p
            ON p.patient_id = a.patient_id
        WHERE rel.centre_id = ?
          AND a.centre_id = ?
          AND p.centre_id = ?
          AND rel.is_deleted = 0
          AND rel.incident_id = ?
        ORDER BY p.name ASC, a.admission_id ASC
    ");
    $stmt->execute([$centre_id_int, $centre_id_int, $centre_id_int, $incident_id]);
    $linked_patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    echo '<div class="alert-box alert-red">' . incident_detail_h(incidents_text('INC_DETAIL_LOAD_FAILED', 'Incident could not be loaded:')) . ' ' . incident_detail_h($e->getMessage()) . '</div>';
    return;
}
?>

<?php if (!empty($_GET['msg'])): ?>
    <div class="alert-box alert-green mar-bot-2"><?= incident_detail_h((string)$_GET['msg']) ?></div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
    <div class="alert-box alert-red mar-bot-2"><?= incident_detail_h((string)$_GET['error']) ?></div>
<?php endif; ?>

<div class="content-title">
    <div class="title">
        <div class="txt">
            <h2 class="pagehead"><?= incident_detail_h(incidents_text('INCIDENT', 'Incident')) ?></h2>
            <p><?= incident_detail_h(incidents_text('INC_DETAIL_SUBTITLE', 'View and edit incident details.')) ?></p>
        </div>
    </div>
    <div class="btns">
        <a href="module.php?module=incidents&amp;view=incidents" class="btn grey"><?= incident_detail_h(incidents_text('BACK', 'Back') . ' ' . incidents_text('TO', 'to') . ' ' . incidents_text('LM_INCIDENTS', 'Incidents')) ?></a>
    </div>
</div>

<style>
    .incident-form-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        align-items: end;
    }
    .incident-form-grid .span-2 { grid-column: span 2; }
    .incident-label {
        display: block;
        font-size: 0.78rem;
        font-weight: 600;
        margin-bottom: 5px;
        color: #52606d;
    }
    .incident-input {
        width: 100%;
        min-height: 36px;
        border: 1px solid #d9e2ec;
        border-radius: 6px;
        padding: 7px 9px;
        background: #fff;
        color: #1f2933;
    }
    .incident-summary {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }
    .incident-pill {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        border-radius: 999px;
        background: #f0f4f8;
        color: #334e68;
        padding: 4px 10px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    .incident-link-form {
        display: grid;
        grid-template-columns: minmax(220px, 1fr) auto;
        gap: 8px;
        margin-bottom: 12px;
        align-items: center;
    }
    .incident-patient-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
    }
    .incident-patient-table th,
    .incident-patient-table td {
        border-top: 1px solid #edf1f5;
        padding: 8px 6px;
        text-align: left;
        vertical-align: middle;
        font-size: 0.88rem;
    }
    .incident-patient-table th {
        color: #52606d;
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0;
    }
    .incident-inline-form { display: inline; }
    @media (max-width: 900px) {
        .incident-form-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 620px) {
        .incident-form-grid,
        .incident-link-form {
            grid-template-columns: 1fr;
        }
        .incident-form-grid .span-2 {
            grid-column: span 1;
        }
        .incident-patient-table {
            display: block;
            overflow-x: auto;
        }
    }
</style>

<div class="content-block">
    <h3><?= incident_detail_h(incidents_text('INCIDENT', 'Incident')) ?> #<?= (int)$incident['incident_id'] ?></h3>

    <div class="incident-summary">
        <span class="incident-pill"><?= (int)$incident['incident_total_casualties'] ?> <?= incident_detail_h(incidents_text('INC_CASUALTIES', 'casualties')) ?></span>
        <span class="incident-pill"><?= (int)$incident['incident_doa'] ?> <?= incident_detail_h(incidents_text('INC_DOA', 'DOA')) ?></span>
        <span class="incident-pill"><?= incident_detail_h(((int)$incident['incident_mass_cas'] === 1) ? incidents_text('INC_MASS_CASUALTY', 'Mass casualty') : incidents_text('STANDARD', 'Standard')) ?></span>
        <span class="incident-pill"><?= count($linked_patients) ?> <?= incident_detail_h(incidents_text('INC_LINKED_PATIENTS', 'Linked Patients')) ?></span>
    </div>

    <form method="post" action="<?= incident_detail_h($incidentAction) ?>">
        <input type="hidden" name="incident_action" value="update">
        <input type="hidden" name="incident_id" value="<?= (int)$incident['incident_id'] ?>">
        <input type="hidden" name="return_to" value="detail">

        <div class="incident-form-grid">
            <div>
                <label class="incident-label" for="incident_date"><?= incident_detail_h(incidents_text('INCIDENT', 'Incident') . ' ' . incidents_text('DATE', 'Date') . '/' . incidents_text('TIME', 'Time')) ?></label>
                <input class="incident-input" type="datetime-local" id="incident_date" name="incident_date" required value="<?= incident_detail_h(incident_detail_datetime_value($incident['incident_date'])) ?>">
            </div>
            <div>
                <label class="incident-label" for="incident_centre_ref"><?= incident_detail_h(incidents_text('CENTRE', 'Centre') . ' ' . incidents_text('REFERENCE', 'Reference')) ?></label>
                <input class="incident-input" type="text" id="incident_centre_ref" name="incident_centre_ref" value="<?= incident_detail_h($incident['incident_centre_ref']) ?>">
            </div>
            <div>
                <label class="incident-label" for="incident_total_casualties"><?= incident_detail_h(incidents_text('TOTAL', 'Total') . ' ' . incidents_text('INC_CASUALTIES', 'casualties')) ?></label>
                <input class="incident-input" type="number" min="0" step="1" id="incident_total_casualties" name="incident_total_casualties" value="<?= (int)$incident['incident_total_casualties'] ?>">
            </div>
            <div>
                <label class="incident-label" for="incident_doa"><?= incident_detail_h(incidents_text('INC_DEAD_ON_ARRIVAL', 'Dead on arrival')) ?></label>
                <input class="incident-input" type="number" min="0" step="1" id="incident_doa" name="incident_doa" value="<?= (int)$incident['incident_doa'] ?>">
            </div>

            <div class="span-2">
                <label class="incident-label" for="incident_location_line_1"><?= incident_detail_h(incidents_text('LOCATION', 'Location') . ' 1') ?></label>
                <input class="incident-input" type="text" id="incident_location_line_1" name="incident_location_line_1" value="<?= incident_detail_h($incident['incident_location_line_1']) ?>">
            </div>
            <div class="span-2">
                <label class="incident-label" for="incident_location_line_2"><?= incident_detail_h(incidents_text('LOCATION', 'Location') . ' 2') ?></label>
                <input class="incident-input" type="text" id="incident_location_line_2" name="incident_location_line_2" value="<?= incident_detail_h($incident['incident_location_line_2']) ?>">
            </div>

            <div>
                <label class="incident-label" for="incident_location_city"><?= incident_detail_h(incidents_text('CITY', 'City')) ?></label>
                <input class="incident-input" type="text" id="incident_location_city" name="incident_location_city" value="<?= incident_detail_h($incident['incident_location_city']) ?>">
            </div>
            <div>
                <label class="incident-label" for="incident_location_postcode"><?= incident_detail_h(incidents_text('POSTCODE', 'Postcode')) ?></label>
                <input class="incident-input" type="text" id="incident_location_postcode" name="incident_location_postcode" value="<?= incident_detail_h($incident['incident_location_postcode']) ?>">
            </div>
            <div>
                <label class="incident-label" for="incident_mass_cas"><?= incident_detail_h(incidents_text('INC_MASS_CASUALTY', 'Mass casualty')) ?></label>
                <select class="incident-input" id="incident_mass_cas" name="incident_mass_cas">
                    <option value="0"<?= ((int)$incident['incident_mass_cas'] === 0) ? ' selected' : '' ?>><?= incident_detail_h(incidents_text('NO', 'No')) ?></option>
                    <option value="1"<?= ((int)$incident['incident_mass_cas'] === 1) ? ' selected' : '' ?>><?= incident_detail_h(incidents_text('YES', 'Yes')) ?></option>
                </select>
            </div>
            <div>
                <button type="submit" class="btn green" style="width:100%;"><?= incident_detail_h(incidents_text('SAVE', 'Save') . ' ' . incidents_text('INCIDENT', 'Incident')) ?></button>
            </div>
        </div>
    </form>
</div>

<div class="content-block">
    <h3><?= incident_detail_h(incidents_text('INC_LINKED_PATIENTS', 'Linked Patients')) ?></h3>

    <form class="incident-link-form" method="post" action="<?= incident_detail_h($incidentAction) ?>">
        <input type="hidden" name="incident_action" value="link">
        <input type="hidden" name="incident_id" value="<?= (int)$incident['incident_id'] ?>">
        <input type="hidden" name="return_to" value="detail">
        <select class="incident-input" name="admission_id" required>
            <option value=""><?= incident_detail_h(incidents_text('INC_LINK_ADMISSION_PLACEHOLDER', 'Link an admission/patient...')) ?></option>
            <?php foreach ($admissions as $admission): ?>
                <?php
                    $label = trim((string)($admission['name'] ?? ''));
                    if ($label === '') {
                        $label = incidents_text('PATIENT', 'Patient') . ' #' . (int)$admission['patient_id'];
                    }
                    $species = trim((string)(($admission['animal_species'] ?? '') ?: ($admission['animal_type'] ?? '')));
                    $date = !empty($admission['admission_date']) ? date('d M Y', strtotime((string)$admission['admission_date'])) : '';
                ?>
                <option value="<?= (int)$admission['admission_id'] ?>">
                    <?= incident_detail_h('#' . (int)$admission['patient_id'] . ' - ' . $label . ($species ? ' - ' . $species : '') . ($date ? ' - ' . $date : '')) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn blue"><?= incident_detail_h(incidents_text('LINK', 'Link') . ' ' . incidents_text('PATIENT', 'Patient')) ?></button>
    </form>

    <?php if (!$linked_patients): ?>
        <div class="alert-box alert-grey" style="margin-bottom:0;"><?= incident_detail_h(incidents_text('INC_NO_PATIENTS_LINKED', 'No patients linked to this incident.')) ?></div>
    <?php else: ?>
        <table class="incident-patient-table">
            <thead>
                <tr>
                    <th><?= incident_detail_h(incidents_text('PATIENT', 'Patient')) ?></th>
                    <th><?= incident_detail_h(incidents_text('SPECIES', 'Species')) ?></th>
                    <th><?= incident_detail_h(incidents_text('SEX', 'Sex')) ?></th>
                    <th><?= incident_detail_h(incidents_text('AGE', 'Age')) ?></th>
                    <th><?= incident_detail_h(incidents_text('COMPLAINT', 'Complaint')) ?></th>
                    <th><?= incident_detail_h(incidents_text('ADMISSION', 'Admission')) ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($linked_patients as $patient): ?>
                    <tr>
                        <td>
                            <a href="viewpatient.php?patient_id=<?= (int)$patient['patient_id'] ?>">
                                <?= incident_detail_h(trim((string)$patient['name']) !== '' ? $patient['name'] : incidents_text('PATIENT', 'Patient') . ' #' . (int)$patient['patient_id']) ?>
                            </a>
                            <br><small>#<?= (int)$patient['patient_id'] ?> / <?= incident_detail_h(incidents_text('ADMISSION', 'Admission')) ?> #<?= (int)$patient['admission_id'] ?></small>
                        </td>
                        <td><?= incident_detail_h(($patient['animal_species'] ?? '') ?: ($patient['animal_type'] ?? '')) ?></td>
                        <td><?= incident_detail_h($patient['sex']) ?></td>
                        <td><?= incident_detail_h($patient['age_on_admission']) ?></td>
                        <td><?= incident_detail_h($patient['presenting_complaint']) ?></td>
                        <td><?= !empty($patient['admission_date']) ? incident_detail_h(date('d M Y', strtotime((string)$patient['admission_date']))) : '' ?></td>
                        <td style="text-align:right;">
                            <form class="incident-inline-form" method="post" action="<?= incident_detail_h($incidentAction) ?>">
                                <input type="hidden" name="incident_action" value="unlink">
                                <input type="hidden" name="incident_id" value="<?= (int)$incident['incident_id'] ?>">
                                <input type="hidden" name="inc_rel_id" value="<?= (int)$patient['inc_rel_id'] ?>">
                                <input type="hidden" name="return_to" value="detail">
                                <button type="submit" class="btn red small"><?= incident_detail_h(incidents_text('UNLINK', 'Unlink')) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
