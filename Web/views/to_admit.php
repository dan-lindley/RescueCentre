<?php
// views/to_admit.php
// ----------------------------------------------
// SHOW ALL PATIENTS WAITING TO BE ADMITTED
// + RE-ADMIT LOOKUP (microchip/ring) ACROSS DATABASE
// ----------------------------------------------

require_once __DIR__ . '/../config.php';

// ----------------------------------------------
// Build PDO
// ----------------------------------------------
try {
    $pdo = new PDO(
        "mysql:host=" . db_host . ";dbname=" . db_name . ";charset=" . db_charset,
        db_user,
        db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]
    );
} catch (Exception $e) {
    die("Database connection failed.");
}

// ----------------------------------------------
// Determine centre_id
// ----------------------------------------------
$user_id   = $_SESSION['account_id'] ?? null;
$centre_id = $_SESSION['centre_id'] ?? null;

if (!$centre_id && $user_id) {
    $stmt = $pdo->prepare("SELECT centre_id FROM accounts WHERE id = :uid LIMIT 1");
    $stmt->execute([':uid' => $user_id]);
    $centre_id = $stmt->fetchColumn();
    if ($centre_id) $_SESSION['centre_id'] = $centre_id;
}

if (!$centre_id) {
    die('<div class="alert-box alert-red">Cannot load: no centre_id in session.</div>');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function toAdmitColumnExists(PDO $pdo, string $table, string $column): bool {
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table
              AND COLUMN_NAME = :column
        ");
        $stmt->execute([
            ':table' => $table,
            ':column' => $column,
        ]);
        $cache[$key] = ((int)$stmt->fetchColumn() > 0);
    } catch (Throwable $e) {
        $cache[$key] = false;
    }

    return $cache[$key];
}
// TEMP DEBUG — remove after fixing
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

function dbg_dump($label, $val) {
    echo '<pre class="rc-panel">';
    echo htmlspecialchars($label . ":\n" . print_r($val, true));
    echo '</pre>';
}

/* ============================================================
   RE-ADMIT LOOKUP (ADDED)
   - Search by microchip OR ring number across the database
   - Show last admitted centre (most recent admission)
   - Provide Admit button that goes to controllers/admission/readmit.php
   ============================================================ */

$lookup_q_raw   = trim((string)($_GET['lookup_q'] ?? ''));
$lookup_q_norm  = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $lookup_q_raw)); // remove spaces/dashes etc
$lookup_results = [];

if ($lookup_q_norm !== '' && strlen($lookup_q_norm) >= 4) {

    $sql_lookup = "
        SELECT
            p.patient_id,
            p.name,
            p.animal_species,
            p.ring_number,
            p.microchip_number,

            a.admission_id   AS last_admission_id,
            a.admission_date AS last_admission_date,
            COALESCE(a.centre_id, p.centre_id)   AS last_centre_id,
            c.rescue_name                        AS last_centre_name


           
        FROM rescue_patients p
        LEFT JOIN rescue_admissions a
            ON a.admission_id = (
                SELECT a2.admission_id
                FROM rescue_admissions a2
                WHERE a2.patient_id = p.patient_id
                ORDER BY a2.admission_date DESC, a2.admission_id DESC
                LIMIT 1
            )
        LEFT JOIN rescue_centres c
        ON c.rescue_id = COALESCE(a.centre_id, p.centre_id)

        WHERE
            UPPER(REPLACE(REPLACE(IFNULL(p.microchip_number,''), ' ', ''), '-', '')) = :q1
            OR
            UPPER(REPLACE(REPLACE(IFNULL(p.ring_number,''), ' ', ''), '-', '')) = :q2
        LIMIT 50
    ";

    $stmt = $pdo->prepare($sql_lookup);
    $stmt->execute([
        ':q1' => $lookup_q_norm,
        ':q2' => $lookup_q_norm]);
    $lookup_results = $stmt->fetchAll();

    // Add match type for display (microchip vs ring)
    foreach ($lookup_results as &$r) {
        $mc = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)($r['microchip_number'] ?? '')));
        $rn = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)($r['ring_number'] ?? '')));
        $r['match_type'] = ($mc === $lookup_q_norm) ? 'Microchip' : 'Ring';
    }
    unset($r);
}

/* ============================================================
   END RE-ADMIT LOOKUP (ADDED)
   ============================================================ */

// ----------------------------------------------
// FETCH ALL "TO ADMIT" PATIENTS
// ----------------------------------------------
$patientDeletedFilter = toAdmitColumnExists($pdo, 'rescue_patients', 'is_deleted')
    ? " AND COALESCE(p.is_deleted, 0) = 0"
    : "";
$admissionDeletedJoinFilter = toAdmitColumnExists($pdo, 'rescue_admissions', 'is_deleted')
    ? " AND COALESCE(a.is_deleted, 0) = 0"
    : "";

$sql = "
    SELECT 
        p.patient_id,
        p.name,
        p.animal_species,
        p.date_added,
        a.admission_id,
        a.age_on_admission,
        a.current_location,
        a.collection_location,
        a.presenting_complaint,
        a.ss_text,
        a.bcs_text,
        a.w_temp
    FROM rescue_patients p
    LEFT JOIN rescue_admissions a 
        ON p.patient_id = a.patient_id
        $admissionDeletedJoinFilter
    WHERE p.centre_id = :cid
      AND p.state = 'To Admit'
      $patientDeletedFilter
    ORDER BY p.date_added DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':cid' => $centre_id]);
$patients = $stmt->fetchAll();

// ----------------------------------------------
// Helper: Return readable completion summary
// ----------------------------------------------
function sectionCompletionSummary($row) {
    global $lang;
    $summary = [];

    // SECTION 1 – From rescue_patients (simplified)
    $s1 = 0;
    if (!empty($row['animal_species'])) $s1++;
    if (!empty($row['name']) && $row['name'] !== "Not completed") $s1++;
    $summary[] = "S1: " . ($s1 >= 2 ? ($lang['COMPLETE'] ?? 'Complete') : ($lang['INCOMPLETE'] ?? 'Incomplete'));

    // SECTION 2 – Basic admission
    $s2_needed = ['age_on_admission', 'current_location'];
    $s2_done = 0;
    foreach ($s2_needed as $f) {
        if (!empty($row[$f])) $s2_done++;
    }
    $summary[] = "S2: " . ($s2_done >= 2 ? ($lang['COMPLETE'] ?? 'Complete') : ($lang['INCOMPLETE'] ?? 'Incomplete'));

    // SECTION 3 – Finder + collection
    $s3_needed = ['collection_location'];
    $s3_done = 0;
    foreach ($s3_needed as $f) {
        if (!empty($row[$f])) $s3_done++;
    }
    $summary[] = "S3: " . ($s3_done >= 1 ? ($lang['PARTIAL'] ?? 'Partial') : ($lang['NOT_STARTED'] ?? 'Not started'));

    // SECTION 5 – Triage
    $s5_needed = ['ss_text', 'bcs_text', 'presenting_complaint'];
    $s5_done = 0;
    foreach ($s5_needed as $f) {
        if (!empty($row[$f])) $s5_done++;
    }
    $summary[] = "S5: " . ($s5_done >= 3 ? ($lang['COMPLETE'] ?? 'Complete') : ($lang['INCOMPLETE'] ?? 'Incomplete'));

    return implode(" • ", $summary);
}

?>

    <div class="content-block">

        <!-- RE-ADMIT SEARCH (ADDED) -->
        <div class="rc-alert blue">
            <h3><?= htmlspecialchars($lang['ADM_READMIT_EXISTING'] ?? 'Re-admit existing patient') ?></h3>
            <form method="get" action="admission.php" class="xform">
                <input type="hidden" name="sid" value="<?= (int)($_GET['sid'] ?? 1) ?>">
                <?php if (!empty($_GET['pid'])): ?><input type="hidden" name="pid" value="<?= (int)$_GET['pid'] ?>"><?php endif; ?>
                <?php if (!empty($_GET['aid'])): ?><input type="hidden" name="aid" value="<?= (int)$_GET['aid'] ?>"><?php endif; ?>

                <div class="xform-grid">
                    <div class="xform-field span-2">
                        <input type="text" name="lookup_q" class="xform-input"
                               value="<?= htmlspecialchars($lookup_q_raw) ?>"
                               placeholder="<?= htmlspecialchars($lang['ADM_LOOKUP_PLACEHOLDER'] ?? 'Enter microchip or ring number to search database wide...') ?>">
                    </div>
                    <div class="xform-field">
                        <button type="submit" class="btn primary"><?= htmlspecialchars($lang['SEARCH'] ?? 'Search') ?></button>
                    </div>
                </div>
            </form>

            <?php if ($lookup_q_raw !== '' && strlen($lookup_q_norm) < 4): ?>
                <div class="rc-note"><?= htmlspecialchars($lang['ADM_MIN_SEARCH_CHARACTERS'] ?? 'Enter at least 4 characters.') ?></div>
            <?php endif; ?>

            <?php if (!empty($lookup_results)): ?>
                <div class="rc-table-scroll">
                <table class="rc-table">
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars($lang['PATIENT'] ?? 'Patient') ?></th>
                            <th><?= htmlspecialchars($lang['SPECIES'] ?? 'Species') ?></th>
                            <th><?= htmlspecialchars($lang['MATCHED'] ?? 'Matched') ?></th>
                            <th><?= htmlspecialchars(($lang['LAST'] ?? 'Last') . ' ' . strtolower($lang['ADMITTED'] ?? 'admitted') . ' ' . strtolower($lang['CENTRE'] ?? 'centre')) ?></th>
                            <th><?= htmlspecialchars(($lang['LAST'] ?? 'Last') . ' ' . strtolower($lang['ADMISSION'] ?? 'admission') . ' ' . strtolower($lang['DATE'] ?? 'date')) ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lookup_results as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['name'] ?? '') ?></strong></td>
                                <td><?= htmlspecialchars($r['animal_species'] ?? '') ?></td>
                                <td class="rc-muted">
                                    <?= htmlspecialchars(($r['match_type'] ?? '') === 'Microchip' ? ($lang['MICROCHIP'] ?? 'Microchip') : ($lang['RING'] ?? 'Ring')) ?>:
                                    <?= htmlspecialchars(($r['match_type'] ?? '') === 'Microchip' ? ($r['microchip_number'] ?? '') : ($r['ring_number'] ?? '')) ?>
                                </td>
                                <td><?= htmlspecialchars($r['last_centre_name'] ?: (($lang['CENTRE'] ?? 'Centre') . ' #' . ($r['last_centre_id'] ?? ''))) ?></td>

                                <td class="rc-muted">
                                    <?= !empty($r['last_admission_date']) ? htmlspecialchars(date("d M Y H:i", strtotime($r['last_admission_date']))) : '—' ?>
                                </td>
                                <td class="rc-table-actions">
                                    <a class="btn green"
                                       href="controllers/admissions/readmit.php?pid=<?= (int)$r['patient_id'] ?>">
                                        <?= htmlspecialchars($lang['ADM_SKIP_TO_SECTION_2'] ?? 'Admit (skip to S2)') ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php elseif ($lookup_q_raw !== '' && strlen($lookup_q_norm) >= 4): ?>
                <div class="rc-note"><?= htmlspecialchars(($lang['NO_RESULTS'] ?? 'No results') . '.') ?></div>
            <?php endif; ?>
        </div>
        <!-- END RE-ADMIT SEARCH (ADDED) -->

        <div class="title">
        <div class="txt">
            <h3><?= htmlspecialchars($lang['ADMIT'] ?? 'Admit') ?></h3>
            <p><?= htmlspecialchars($lang['ADM_TO_ADMIT_HELP'] ?? 'These patients have been registered but still require the admission to complete. They will appear on My Patients once the admission has been finalised.') ?></p>
        </div>
    </div>

        <?php if (empty($patients)): ?>
            <p class="alert-box alert-grey">
                <?= htmlspecialchars($lang['ADM_NONE_AWAITING'] ?? 'No patients are currently awaiting admission.') ?>
            </p>
        <?php else: ?>

        <div class="rc-table-scroll">
        <table class="rc-table">
            <thead>
                <tr>
                    <th><?= htmlspecialchars($lang['PATIENT'] ?? 'Patient') ?></th>
                    <th><?= htmlspecialchars($lang['SPECIES'] ?? 'Species') ?></th>
                    <th><?= htmlspecialchars(($lang['DATE'] ?? 'Date') . ' ' . ($lang['ADDED'] ?? 'Added')) ?></th>
                    <th><?= htmlspecialchars(($lang['ADMISSION'] ?? 'Admission') . ' ' . ($lang['PROGRESS'] ?? 'Progress')) ?></th>
                    <th></th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($patients as $p): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                        <td><?= htmlspecialchars($p['animal_species']) ?></td>
                        <td><?= htmlspecialchars(date("d M Y H:i", strtotime($p['date_added']))) ?></td>

                        <td class="rc-muted">
                            <?= sectionCompletionSummary($p) ?>
                        </td>

                        <td class="rc-table-actions">
                            <a class="btn green"
                               href="../admission.php?sid=1&pid=<?= (int)$p['patient_id'] ?><?= !empty($p['admission_id']) ? '&aid=' . (int)$p['admission_id'] : '' ?>">
                                <?= htmlspecialchars($lang['CONTINUE'] ?? 'Continue') ?>
                            </a>
                            <form method="post"
                                  action="controllers/admissions/discard_partial.php"
                                  onsubmit="return confirm(<?= htmlspecialchars(json_encode($lang['ADM_CONFIRM_DISCARD'] ?? 'Discard this partial admission? It will be removed from the To Admit queue.'), ENT_QUOTES, 'UTF-8') ?>);"
                                  style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="patient_id" value="<?= (int)$p['patient_id'] ?>">
                                <input type="hidden" name="admission_id" value="<?= (int)($p['admission_id'] ?? 0) ?>">
                                <button type="submit" class="btn red"><?= htmlspecialchars($lang['DISCARD'] ?? 'Discard') ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <?php endif; ?>
    </div>
