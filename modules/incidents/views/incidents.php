<?php
// modules/incidents/views/incidents.php
if (!defined('APP_LOADED')) exit;

require_once __DIR__ . '/../controllers/incidents_lib.php';
$incident_lang = incidents_module_language();
$incidentAction = 'modules/incidents/controllers/incidents_handler.php';

$centre_id_int = isset($centre_id) ? (int)$centre_id : (int)($_SESSION['centre_id'] ?? 0);

function incident_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function incident_location_text(array $incident): string
{
    $parts = array_filter([
        $incident['incident_location_line_1'] ?? '',
        $incident['incident_location_line_2'] ?? '',
        $incident['incident_location_city'] ?? '',
        $incident['incident_location_postcode'] ?? '',
    ], static fn($part) => trim((string)$part) !== '');

    return $parts ? implode(', ', $parts) : incidents_text('NO_LOCATION_RECORDED', 'No location recorded');
}

$incidents = [];
$admissions = [];
$linked_by_incident = [];
$total_casualties = 0;
$total_doa = 0;
$linked_patient_count = 0;

if ($centre_id_int <= 0) {
    echo '<div class="alert-box alert-red">' . incident_h(incidents_text('CENTRE_CONTEXT_MISSING', 'Centre context missing.')) . '</div>';
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
        WHERE centre_id = :centre_id
        ORDER BY incident_date DESC, incident_id DESC
    ");
    $stmt->execute([':centre_id' => $centre_id_int]);
    $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        WHERE a.centre_id = :centre_id
          AND p.centre_id = :centre_id
        ORDER BY a.admission_date DESC, a.admission_id DESC
        LIMIT 500
    ");
    $stmt->execute([':centre_id' => $centre_id_int]);
    $admissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($incidents) {
        $incident_ids = array_map(static fn($row) => (int)$row['incident_id'], $incidents);
        $placeholders = implode(',', array_fill(0, count($incident_ids), '?'));

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
              AND rel.incident_id IN ($placeholders)
            ORDER BY rel.incident_id DESC, p.name ASC, a.admission_id ASC
        ");
        $stmt->execute(array_merge([$centre_id_int, $centre_id_int, $centre_id_int], $incident_ids));

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linked) {
            $linked_by_incident[(int)$linked['incident_id']][] = $linked;
            $linked_patient_count++;
        }
    }

    foreach ($incidents as $incident) {
        $total_casualties += (int)$incident['incident_total_casualties'];
        $total_doa += (int)$incident['incident_doa'];
    }
} catch (Throwable $e) {
    echo '<div class="alert-box alert-red">' . incident_h(incidents_text('INC_LOAD_FAILED', 'Incidents could not be loaded:')) . ' ' . incident_h($e->getMessage()) . '</div>';
}
?>

<?php if (!empty($_GET['msg'])): ?>
    <div class="alert-box alert-green mar-bot-2"><?= incident_h((string)$_GET['msg']) ?></div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
    <div class="alert-box alert-red mar-bot-2"><?= incident_h((string)$_GET['error']) ?></div>
<?php endif; ?>

<div class="content-title">
    <div class="title">
        <div class="icon">
            <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" aria-hidden="true"><path d="M320 32C355.3 32 384 60.7 384 96L384 128L480 128C515.3 128 544 156.7 544 192L544 512C544 547.3 515.3 576 480 576L160 576C124.7 576 96 547.3 96 512L96 192C96 156.7 124.7 128 160 128L256 128L256 96C256 60.7 284.7 32 320 32zM160 176C151.2 176 144 183.2 144 192L144 512C144 520.8 151.2 528 160 528L480 528C488.8 528 496 520.8 496 512L496 192C496 183.2 488.8 176 480 176L160 176zM288 128L352 128L352 96C352 78.3 337.7 64 320 64C302.3 64 288 78.3 288 96L288 128zM224 288L416 288C429.3 288 440 298.7 440 312C440 325.3 429.3 336 416 336L224 336C210.7 336 200 325.3 200 312C200 298.7 210.7 288 224 288zM224 384L416 384C429.3 384 440 394.7 440 408C440 421.3 429.3 432 416 432L224 432C210.7 432 200 421.3 200 408C200 394.7 210.7 384 224 384z"/></svg>
        </div>
        <div class="txt">
            <h2 class="pagehead"><?= incident_h(incidents_text('LM_INCIDENTS', 'Incidents')) ?></h2>
            <p><?= incident_h(incidents_text('INC_SUBTITLE', 'Create incidents and manage linked patients.')) ?></p>
        </div>
    </div>
</div>

<div class="rc-stat-grid incident-stat-grid">
    <div class="rc-stat incident-stat incident-stat-blue">
        <strong><?= count($incidents) ?></strong>
        <span><?= incident_h(incidents_text('INC_TOTAL_INCIDENTS', 'Total incidents')) ?></span>
    </div>
    <div class="rc-stat incident-stat incident-stat-orange">
        <strong><?= $total_casualties ?></strong>
        <span><?= incident_h(incidents_text('TOTAL', 'Total') . ' ' . incidents_text('INC_CASUALTIES', 'casualties')) ?></span>
    </div>
    <div class="rc-stat incident-stat incident-stat-red">
        <strong><?= $total_doa ?></strong>
        <span><?= incident_h(incidents_text('INC_DEAD_ON_ARRIVAL', 'Dead on arrival')) ?></span>
    </div>
    <div class="rc-stat incident-stat incident-stat-purple">
        <strong><?= $linked_patient_count ?></strong>
        <span><?= incident_h(incidents_text('INC_LINKED_PATIENTS', 'Linked patients')) ?></span>
    </div>
</div>

<div class="content-block">
    <h3><?= incident_h(incidents_text('CREATE', 'Create') . ' ' . incidents_text('NEW', 'New') . ' ' . incidents_text('INCIDENT', 'Incident')) ?></h3>
    <form method="post" action="<?= incident_h($incidentAction) ?>">
        <input type="hidden" name="incident_action" value="create">

        <div class="rc-form-grid">
            <div class="rc-form-field">
                <label class="rc-form-label" for="incident_date"><?= incident_h(incidents_text('INCIDENT', 'Incident') . ' ' . incidents_text('DATE', 'Date') . '/' . incidents_text('TIME', 'Time')) ?></label>
                <input class="rc-input" type="datetime-local" id="incident_date" name="incident_date" required value="<?= incident_h(date('Y-m-d\TH:i')) ?>">
            </div>
            <div class="rc-form-field">
                <label class="rc-form-label" for="incident_centre_ref"><?= incident_h(incidents_text('CENTRE', 'Centre') . ' ' . incidents_text('REFERENCE', 'Reference')) ?></label>
                <input class="rc-input" type="text" id="incident_centre_ref" name="incident_centre_ref">
            </div>
            <div class="rc-form-field">
                <label class="rc-form-label" for="incident_total_casualties"><?= incident_h(incidents_text('TOTAL', 'Total') . ' ' . incidents_text('INC_CASUALTIES', 'casualties')) ?></label>
                <input class="rc-input" type="number" min="0" step="1" id="incident_total_casualties" name="incident_total_casualties" value="0">
            </div>
            <div class="rc-form-field">
                <label class="rc-form-label" for="incident_doa"><?= incident_h(incidents_text('INC_DEAD_ON_ARRIVAL', 'Dead on arrival')) ?></label>
                <input class="rc-input" type="number" min="0" step="1" id="incident_doa" name="incident_doa" value="0">
            </div>

            <div class="rc-form-field span-2">
                <label class="rc-form-label" for="incident_location_line_1"><?= incident_h(incidents_text('LOCATION', 'Location') . ' 1') ?></label>
                <input class="rc-input" type="text" id="incident_location_line_1" name="incident_location_line_1">
            </div>
            <div class="rc-form-field span-2">
                <label class="rc-form-label" for="incident_location_line_2"><?= incident_h(incidents_text('LOCATION', 'Location') . ' 2') ?></label>
                <input class="rc-input" type="text" id="incident_location_line_2" name="incident_location_line_2">
            </div>

            <div class="rc-form-field">
                <label class="rc-form-label" for="incident_location_city"><?= incident_h(incidents_text('CITY', 'City')) ?></label>
                <input class="rc-input" type="text" id="incident_location_city" name="incident_location_city">
            </div>
            <div class="rc-form-field">
                <label class="rc-form-label" for="incident_location_postcode"><?= incident_h(incidents_text('POSTCODE', 'Postcode')) ?></label>
                <input class="rc-input" type="text" id="incident_location_postcode" name="incident_location_postcode">
            </div>
            <div class="rc-form-field">
                <label class="rc-form-label" for="incident_mass_cas"><?= incident_h(incidents_text('INC_MASS_CASUALTY', 'Mass casualty')) ?></label>
                <select class="rc-input" id="incident_mass_cas" name="incident_mass_cas">
                    <option value="0"><?= incident_h(incidents_text('NO', 'No')) ?></option>
                    <option value="1"><?= incident_h(incidents_text('YES', 'Yes')) ?></option>
                </select>
            </div>
            <div class="rc-form-field">
                <button type="submit" class="btn green"><?= incident_h(incidents_text('CREATE', 'Create') . ' ' . incidents_text('INCIDENT', 'Incident')) ?></button>
            </div>
        </div>
    </form>
</div>

<div class="content-block">
    <h3><?= incident_h(incidents_text('LM_INCIDENTS', 'Incidents')) ?></h3>

    <?php if (!$incidents): ?>
        <div class="alert-box alert-grey"><?= incident_h(incidents_text('INC_NO_INCIDENTS', 'No incidents have been created yet.')) ?></div>
    <?php else: ?>
        <div class="rc-list">
            <?php foreach ($incidents as $incident): ?>
                <?php
                    $incident_id = (int)$incident['incident_id'];
                    $linked_patients = $linked_by_incident[$incident_id] ?? [];
                    $linked_count = count($linked_patients);
                ?>
                <details class="rc-panel incident-card" id="incident-<?= $incident_id ?>"<?= (int)($_GET['incident_id'] ?? 0) === $incident_id ? ' open' : '' ?>>
                    <summary class="incident-summary-row">
                        <div class="incident-summary-main">
                            <span class="incident-chevron" aria-hidden="true"></span>
                            <div>
                                <h4><?= incident_h(incidents_text('INCIDENT', 'Incident')) ?> #<?= $incident_id ?></h4>
                                <div class="rc-muted incident-summary-meta">
                                    <span><?= incident_h(date('d M Y H:i', strtotime((string)$incident['incident_date']))) ?></span>
                                    <span><?= incident_h(incident_location_text($incident)) ?></span>
                                    <?php if (!empty($incident['incident_centre_ref'])): ?>
                                        <span><?= incident_h(incidents_text('REFERENCE', 'Reference')) ?>: <?= incident_h($incident['incident_centre_ref']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="rc-inline-list">
                            <span class="rc-chip blue"><?= (int)$incident['incident_total_casualties'] ?> <?= incident_h(incidents_text('INC_CASUALTIES', 'casualties')) ?></span>
                            <span class="rc-chip warn"><?= (int)$incident['incident_doa'] ?> <?= incident_h(incidents_text('INC_DOA', 'DOA')) ?></span>
                            <span class="rc-chip <?= ((int)$incident['incident_mass_cas'] === 1) ? 'purple' : '' ?>"><?= incident_h(((int)$incident['incident_mass_cas'] === 1) ? incidents_text('INC_MASS_CASUALTY', 'Mass casualty') : incidents_text('STANDARD', 'Standard')) ?></span>
                            <span class="rc-chip"><?= $linked_count ?> <?= incident_h(incidents_text('LINKED', 'Linked')) ?></span>
                        </div>
                    </summary>

                    <div class="incident-card-body rc-stack">
                        <form class="incident-link-form" method="post" action="<?= incident_h($incidentAction) ?>">
                            <input type="hidden" name="incident_action" value="link">
                            <input type="hidden" name="incident_id" value="<?= $incident_id ?>">
                            <select class="rc-input" name="admission_id" required>
                                <option value=""><?= incident_h(incidents_text('INC_LINK_ADMISSION_PLACEHOLDER', 'Link an admission/patient...')) ?></option>
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
                                        <?= incident_h('#' . (int)$admission['patient_id'] . ' - ' . $label . ($species ? ' - ' . $species : '') . ($date ? ' - ' . $date : '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn green"><?= incident_h(incidents_text('LINK', 'Link') . ' ' . incidents_text('PATIENT', 'Patient')) ?></button>
                        </form>

                        <?php if (!$linked_patients): ?>
                            <div class="alert-box alert-grey"><?= incident_h(incidents_text('INC_NO_PATIENTS_LINKED', 'No patients linked to this incident.')) ?></div>
                        <?php else: ?>
                            <div class="rc-table-scroll">
                            <table class="rc-table">
                            <thead>
                                <tr>
                                    <th><?= incident_h(incidents_text('PATIENT', 'Patient')) ?></th>
                                    <th><?= incident_h(incidents_text('SPECIES', 'Species')) ?></th>
                                    <th><?= incident_h(incidents_text('SEX', 'Sex')) ?></th>
                                    <th><?= incident_h(incidents_text('AGE', 'Age')) ?></th>
                                    <th><?= incident_h(incidents_text('COMPLAINT', 'Complaint')) ?></th>
                                    <th><?= incident_h(incidents_text('ADMISSION', 'Admission')) ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($linked_patients as $patient): ?>
                                    <tr>
                                        <td>
                                            <a href="viewpatient.php?patient_id=<?= (int)$patient['patient_id'] ?>">
                                                <?= incident_h(trim((string)$patient['name']) !== '' ? $patient['name'] : incidents_text('PATIENT', 'Patient') . ' #' . (int)$patient['patient_id']) ?>
                                            </a>
                                            <br><small>#<?= (int)$patient['patient_id'] ?> / <?= incident_h(incidents_text('ADMISSION', 'Admission')) ?> #<?= (int)$patient['admission_id'] ?></small>
                                        </td>
                                        <td><?= incident_h(($patient['animal_species'] ?? '') ?: ($patient['animal_type'] ?? '')) ?></td>
                                        <td><?= incident_h($patient['sex']) ?></td>
                                        <td><?= incident_h($patient['age_on_admission']) ?></td>
                                        <td><?= incident_h($patient['presenting_complaint']) ?></td>
                                        <td><?= !empty($patient['admission_date']) ? incident_h(date('d M Y', strtotime((string)$patient['admission_date']))) : '' ?></td>
                                        <td class="rc-table-actions">
                                            <form method="post" action="<?= incident_h($incidentAction) ?>">
                                                <input type="hidden" name="incident_action" value="unlink">
                                                <input type="hidden" name="incident_id" value="<?= $incident_id ?>">
                                                <input type="hidden" name="inc_rel_id" value="<?= (int)$patient['inc_rel_id'] ?>">
                                                <button type="submit" class="btn red small"><?= incident_h(incidents_text('UNLINK', 'Unlink')) ?></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            </table>
                            </div>
                        <?php endif; ?>

                        <div class="rc-actions">
                            <a class="btn blue small" href="module.php?module=incidents&amp;view=incident&amp;incident_id=<?= $incident_id ?>"><?= incident_h(incidents_text('EDIT', 'Edit') . ' ' . incidents_text('INCIDENT', 'Incident')) ?></a>
                        </div>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($_GET['incident_id'])): ?>
    <script>
        (function () {
            const target = document.getElementById("incident-<?= (int)$_GET['incident_id'] ?>");
            if (target) {
                target.scrollIntoView({ behavior: "smooth", block: "start" });
            }
        })();
    </script>
<?php endif; ?>
