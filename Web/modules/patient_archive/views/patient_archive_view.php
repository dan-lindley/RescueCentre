<?php
// views/patient_archive_view.php
// Modernised Patient Archive: x-form toolbar + server-side search/pagination + CSV exports
// - No legacy modals
// - No DataTables dependency
// - Keeps language labels + Care Plan button
// - Adds coloured pills, vertical table separators, and button-style pagination

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// centre_id must already be available from the template
$centre_id_int = isset($centre_id) ? (int)$centre_id : 0;
if ($centre_id_int <= 0) {
    echo "Error: centre_id missing.";
    exit;
}

/* -----------------------------
   Helpers
----------------------------- */
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function fmt_dt($dt) {
    if (!$dt) return '';
    try {
        $d = new DateTime($dt);
        return $d->format('d-m-Y <\b\r> H:i');
    } catch (Exception $e) {
        return h($dt);
    }
}

function build_url(array $overrides = []) {
    $base = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null) unset($base[$k]);
        else $base[$k] = $v;
    }
    return '?' . http_build_query($base);
}

function wra_info($wra) {
    $wra = (int)$wra;
    // Legacy logic retained, but return a styling level
    if ($wra > 90) return ['level' => 'na', 'value' => 'N/A'];
    if ($wra >= 6) return ['level' => 'bad', 'value' => (string)$wra];
    if ($wra >= 3) return ['level' => 'warn', 'value' => (string)$wra];
    return ['level' => 'ok', 'value' => (string)$wra];
}

function days_level($days) {
    $days = (int)$days;
    if ($days > 120) return 'dark';
    if ($days > 90)  return 'bad';
    if ($days > 60)  return 'warn';
    if ($days > 31)  return 'mid';
    return 'low';
}

function archive_disposition_chip_class($disposition) {
    $disp = strtolower(trim((string)$disposition));

    if ($disp === 'released') {
        return 'good';
    }

    if ($disp === 'held in captivity') {
        return 'warn';
    }

    if (
        str_contains($disp, 'died') ||
        str_contains($disp, 'dead') ||
        str_contains($disp, 'doa') ||
        str_contains($disp, 'euthan')
    ) {
        return 'bad';
    }

    return 'warn';
}

/* -----------------------------
   Inputs (search / pagination)
----------------------------- */
$q           = trim((string)($_GET['q'] ?? ''));
$disposition = trim((string)($_GET['disposition'] ?? ''));
$page        = (int)($_GET['page'] ?? 1);
$allowed_per_page = [10, 20, 25, 50, 100, 9999];
$saved_per_page = (int)($_SESSION['my_patients_per_page'] ?? 0);

if ((int)($_SESSION['account_id'] ?? 0) > 0) {
    try {
        $pageSizeStmt = $pdo->prepare('SELECT my_patients_per_page FROM accounts WHERE id = ? LIMIT 1');
        $pageSizeStmt->execute([(int)$_SESSION['account_id']]);
        $saved_per_page = (int)$pageSizeStmt->fetchColumn();
    } catch (Throwable $e) {
        $saved_per_page = 25;
    }
}

$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : $saved_per_page;

if ($page < 1) $page = 1;

if (!in_array($per_page, $allowed_per_page, true)) $per_page = 25;
$_SESSION['my_patients_per_page'] = $per_page;

if (isset($_GET['per_page']) && (int)($_SESSION['account_id'] ?? 0) > 0) {
    try {
        $savePageSizeStmt = $pdo->prepare('UPDATE accounts SET my_patients_per_page = ? WHERE id = ?');
        $savePageSizeStmt->execute([$per_page, (int)$_SESSION['account_id']]);
    } catch (Throwable $e) {
        // Preference saving should never block the archive view.
    }
}

$offset = ($page - 1) * $per_page;

/* -----------------------------
   Disposition filter options
----------------------------- */
$dispositionStmt = $pdo->prepare("
    SELECT DISTINCT TRIM(a.disposition) AS disposition
    FROM rescue_admissions a
    INNER JOIN rescue_patients p ON a.patient_id = p.patient_id
    WHERE p.centre_id = :centre_id
      AND a.disposition IS NOT NULL
      AND TRIM(a.disposition) <> ''
    ORDER BY disposition ASC
");
$dispositionStmt->execute([':centre_id' => $centre_id_int]);
$dispositionOptions = $dispositionStmt->fetchAll(PDO::FETCH_COLUMN);

/* -----------------------------
   WHERE / params
----------------------------- */
$where  = " WHERE p.centre_id = :centre_id ";
$params = [':centre_id' => $centre_id_int];

if ($q !== '') {
    $where .= " AND (
        p.patient_id LIKE :q_exact
        OR p.name LIKE :q_like
        OR p.animal_species LIKE :q_like
        OR p.animal_type LIKE :q_like
        OR a.presenting_complaint LIKE :q_like
        OR a.disposition LIKE :q_like
    ) ";
    $params[':q_exact'] = $q . '%';
    $params[':q_like']  = '%' . $q . '%';
}

if ($disposition === '__none__') {
    $where .= " AND (a.disposition IS NULL OR TRIM(a.disposition) = '') ";
} elseif ($disposition === '__all_died__') {
    $where .= " AND (
        LOWER(TRIM(a.disposition)) LIKE '%died%'
        OR LOWER(TRIM(a.disposition)) LIKE '%dead%'
        OR LOWER(TRIM(a.disposition)) LIKE '%doa%'
        OR LOWER(TRIM(a.disposition)) LIKE '%euthan%'
    ) ";
} elseif ($disposition !== '') {
    $where .= " AND TRIM(a.disposition) = :disposition ";
    $params[':disposition'] = $disposition;
}

/* -----------------------------
   Total count
----------------------------- */
$sql_count = "
    SELECT COUNT(*) AS total_rows
    FROM rescue_admissions a
    INNER JOIN rescue_patients p ON a.patient_id = p.patient_id
    $where
";
$stmt = $pdo->prepare($sql_count);
$stmt->execute($params);
$total_rows = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total_rows'] ?? 0);

$total_pages = max(1, (int)ceil($total_rows / $per_page));
if ($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $per_page;
}

/* -----------------------------
   Fetch page
----------------------------- */
$sql = "
    SELECT
        a.admission_id,
        a.patient_id,
        a.admission_date,
        a.presenting_complaint,
        a.disposition,
        a.disposition_date,
        (a.bc_score + a.age_score + a.severity_score) AS wra,
        DATEDIFF(COALESCE(a.disposition_date, NOW()), a.admission_date) AS daysincare,

        p.name,
        p.sex,
        p.animal_species,
        p.animal_type
    FROM rescue_admissions a
    INNER JOIN rescue_patients p ON a.patient_id = p.patient_id
    $where
    ORDER BY a.admission_date DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);

$stmt->bindValue(':centre_id', $centre_id_int, PDO::PARAM_INT);
if ($q !== '') {
    $stmt->bindValue(':q_exact', $params[':q_exact'], PDO::PARAM_STR);
    $stmt->bindValue(':q_like',  $params[':q_like'],  PDO::PARAM_STR);
}
if ($disposition !== '' && !in_array($disposition, ['__none__', '__all_died__'], true)) {
    $stmt->bindValue(':disposition', $disposition, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* -----------------------------
   Pagination window + showing range
----------------------------- */
$win = 2;
$start_page = max(1, $page - $win);
$end_page   = min($total_pages, $page + $win);

$from_n = ($total_rows === 0) ? 0 : ($offset + 1);
$to_n   = min($total_rows, $offset + $per_page);

?>
<div class="content-block">

    <style>
        .archive-toolbar{
            margin-bottom: 12px;
        }
        .archive-title{ margin:0; line-height:1.15; }
        .archive-sub{ margin-top:6px; opacity:0.85; font-size:0.95em; }

        .mini-muted{ opacity:0.8; font-size:0.9em; }

        .archive-chips { margin:6px 6px 0 0; }
        .archive-filter-grid {
            grid-template-columns: minmax(280px, 2fr) minmax(180px, 1fr) minmax(100px, .55fr) auto;
        }
        .archive-clear-field { align-items:flex-start; }
        .archive-clear-btn {
            min-width:0;
            min-height:38px;
            padding:6px 10px;
            font-size:0.85rem;
        }
        @media (max-width: 900px) {
            .archive-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>

    <!-- Header + Exports -->
    <div class="rc-panel rc-split-head archive-toolbar">
        <div>
            <h3 class="archive-title">
                <?php echo h($lang['ARC_YOU_CARED_FOR'] ?? 'You cared for'); ?>
                <?php echo (int)$total_rows; ?>
                <?php echo h($lang['PAT_IN_RESCUE'] ?? 'patients in rescue'); ?>
            </h3>
            <div class="archive-sub">
                <?php echo h($lang['PAT_ARCHIVE_SUBTITLE'] ?? 'Archived admissions for patients previously in your care.'); ?>
            </div>
            <?php if ($q !== ''): ?>
                <div class="mini-muted" style="margin-top:6px;">
                    <?php echo h($lang['SEARCH'] ?? 'Search'); ?>: <b><?php echo h($q); ?></b>
                </div>
            <?php endif; ?>
            <?php if ($disposition !== ''): ?>
                <div class="mini-muted" style="margin-top:6px;">
                    <?php echo h($lang['DISPOSITION'] ?? 'Disposition'); ?>:
                    <b><?php
                        echo h(match ($disposition) {
                            '__none__' => $lang['NOT_COMPLETED'] ?? 'Not completed',
                            '__all_died__' => $lang['ALL_DIED'] ?? 'All died',
                            default => $disposition,
                        });
                    ?></b>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Controls (no Apply button) -->
    <form method="get" class="xform" style="margin-bottom:12px;">
        <div class="xform-grid archive-filter-grid" style="align-items:end;">
            <input type="hidden" name="module" value="patient_archive">
            <input type="hidden" name="view" value="archive">

            <div class="xform-field">
                <label class="xform-label"><?php echo h($lang['SEARCH_ARCHIVE'] ?? ($lang['SEARCH'] ?? 'Search')); ?></label>
                <input
                    type="text"
                    name="q"
                    class="xform-input"
                    value="<?php echo h($q); ?>"
                    placeholder="<?php echo h($lang['SEARCH_ARCHIVE_PLACEHOLDER'] ?? 'CRN, name, species, complaint, disposition...'); ?>"
                >
            </div>

            <div class="xform-field">
                <label class="xform-label"><?php echo h($lang['DISPOSITION'] ?? 'Disposition'); ?></label>
                <select name="disposition" class="xform-input" onchange="this.form.submit()">
                    <option value=""><?php echo h($lang['ALL_DISPOSITIONS'] ?? 'All dispositions'); ?></option>
                    <option value="__none__" <?php echo ($disposition === '__none__') ? 'selected' : ''; ?>>
                        <?php echo h($lang['NOT_COMPLETED'] ?? 'Not completed'); ?>
                    </option>
                    <option value="__all_died__" <?php echo ($disposition === '__all_died__') ? 'selected' : ''; ?>>
                        <?php echo h($lang['ALL_DIED'] ?? 'All died'); ?>
                    </option>
                    <?php foreach ($dispositionOptions as $dispositionOption): ?>
                        <option value="<?php echo h($dispositionOption); ?>" <?php echo ($disposition === $dispositionOption) ? 'selected' : ''; ?>>
                            <?php echo h($dispositionOption); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="xform-field">
                <label class="xform-label"><?php echo h($lang['SHOW'] ?? 'Show'); ?></label>
                <select name="per_page" class="xform-input" onchange="this.form.submit()">
                    <?php foreach ($allowed_per_page as $pp): ?>
                        <option value="<?php echo (int)$pp; ?>" <?php echo ($per_page === $pp) ? 'selected' : ''; ?>>
                            <?php echo $pp === 9999 ? h($lang['TABLE_ALL'] ?? 'All') : (int)$pp; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($q !== '' || $disposition !== ''): ?>
                <div class="xform-field archive-clear-field">
                    <button
                        type="button"
                        class="rc-pager-btn archive-clear-btn"
                        onclick="window.location.href=<?php echo h(json_encode(build_url(['q' => null, 'disposition' => null, 'page' => 1]))); ?>"
                    >
                        <?php echo h($lang['CLEAR'] ?? 'Clear'); ?>
                    </button>
                </div>
            <?php endif; ?>

            <input type="hidden" name="page" value="1">
        </div>
    </form>

    <!-- Table -->
    <div class="table-responsive">
        <table class="display compact row-hover rc-table" width="100%" cellspacing="0">
            <thead class="thead-dark">
                <tr>
                    <th><?php echo h($lang['DATE_OF'] ?? 'Date of'); ?><br><?php echo h($lang['ADMISSION'] ?? 'Admission'); ?></th>
                    <th class="align-middle"><?php echo h($lang['PATIENT'] ?? 'Patient'); ?></th>
                    <th class="align-middle" width="240"><?php echo h($lang['PRESENTING_COMPLAINT'] ?? 'Presenting complaint'); ?></th>
                    <th class="align-middle"><?php echo h($lang['WRA'] ?? 'WRA'); ?> <?php echo h($lang['PAT_SCORE'] ?? 'score'); ?></th>
                    <th class="align-middle"><?php echo h($lang['DISPOSITION'] ?? 'Disposition'); ?></th>
                    <th class="align-middle"><?php echo h($lang['DATE_OF'] ?? 'Date of'); ?><br><?php echo h($lang['DISPOSITION'] ?? 'Disposition'); ?></th>
                    <th class="align-middle"><?php echo h($lang['PAT_DAYS_IN_CARE'] ?? 'Days in care'); ?></th>
                    <th class="align-middle"></th>
                </tr>
            </thead>

            <tbody>
            <?php if (!$rows): ?>
                <tr>
                    <td colspan="8" style="padding:14px;">
                        <?php echo h($lang['NO_RESULTS'] ?? 'No results.'); ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <?php
                        $patient_id = (int)($row['patient_id'] ?? 0);

                        $adm_format = fmt_dt($row['admission_date'] ?? null);
                        $dis_format = fmt_dt($row['disposition_date'] ?? null);

                        $w = wra_info($row['wra'] ?? 0);
                        $wra_level = $w['level'];
                        $wra_val   = $w['value'];

                        $days = (int)($row['daysincare'] ?? 0);
                        $days_level_class = days_level($days);

                        $sex = trim((string)($row['sex'] ?? ''));
                        $species = trim((string)($row['animal_species'] ?? ''));
                        $type = trim((string)($row['animal_type'] ?? ''));
                        $disp = trim((string)($row['disposition'] ?? ''));
                    ?>
                    <tr>
                        <td><?php echo $adm_format; ?></td>

                        <td class="align-middle">
                            <div style="font-weight:800;">
                                <?php echo h($lang['CRN'] ?? 'CRN'); ?>: <?php echo (int)$patient_id; ?> —
                                <?php echo h($row['name'] ?? ''); ?>
                            </div>
                            <div>
                                <?php if ($sex !== ''): ?>
                                    <span class="rc-chip blue archive-chips"><?php echo h($sex); ?></span>
                                <?php endif; ?>
                                <?php if ($species !== ''): ?>
                                    <span class="rc-chip good archive-chips"><?php echo h($species); ?></span>
                                <?php endif; ?>
                                <?php if ($type !== ''): ?>
                                    <span class="rc-chip purple archive-chips"><?php echo h($type); ?></span>
                                <?php endif; ?>
                            </div>
                        </td>

                        <td class="align-middle">
                            <?php echo h($row['presenting_complaint'] ?? ''); ?>
                        </td>

                        <td class="align-middle" style="text-align:center;">
                            <span class="rc-badge <?php echo h($wra_level); ?>">
                                <?php echo h($wra_val); ?>
                            </span>
                        </td>

                        <td class="align-middle">
                            <?php if ($disp !== ''): ?>
                                <span class="rc-chip <?php echo h(archive_disposition_chip_class($disp)); ?>"><?php echo h($disp); ?></span>
                            <?php else: ?>
                                <span class="mini-muted"><?php echo h($lang['NOT_COMPLETED'] ?? 'Not completed'); ?></span>
                            <?php endif; ?>
                        </td>

                        <td class="align-middle"><?php echo $dis_format; ?></td>

                        <td class="align-middle" style="text-align:center;">
                            <span class="rc-badge <?php echo h($days_level_class); ?>">
                                <?php echo (int)$days; ?>
                            </span>
                        </td>

                        <td class="align-middle">
                            <div class="btn-group">
                                <a
                                    href="viewpatient.php?patient_id=<?php echo (int)$patient_id; ?>"
                                    class="btn green"
                                    title="<?php echo h($lang['TIP_VIEW_CARE_PLAN'] ?? 'View Care Plan'); ?>"
                                >
                                    <?php echo h($lang['BTN_CARE_PLAN'] ?? 'Care Plan'); ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Footer / pagination -->
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-top:12px;">
        <div class="muted">
            <?php
                echo h($lang['SHOWING'] ?? 'Showing') . " " . (int)$from_n . "-" . (int)$to_n . " " .
                     h($lang['OF'] ?? 'of') . " " . (int)$total_rows;
            ?>
        </div>

        <div class="rc-pager">
            <a class="rc-pager-btn <?php echo ($page <= 1) ? 'disabled' : ''; ?>"
               href="<?php echo h(build_url(['page' => max(1, $page - 1)])); ?>">
                <?php echo h($lang['PREV'] ?? 'Prev'); ?>
            </a>

            <?php if ($start_page > 1): ?>
                <a class="rc-pager-btn" href="<?php echo h(build_url(['page' => 1])); ?>">1</a>
                <?php if ($start_page > 2): ?><span style="padding:0 6px;">…</span><?php endif; ?>
            <?php endif; ?>

            <?php for ($p = $start_page; $p <= $end_page; $p++): ?>
                <a class="rc-pager-btn <?php echo ($p === $page) ? 'active' : ''; ?>"
                   href="<?php echo h(build_url(['page' => $p])); ?>">
                    <?php echo (int)$p; ?>
                </a>
            <?php endfor; ?>

            <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?><span style="padding:0 6px;">…</span><?php endif; ?>
                <a class="rc-pager-btn" href="<?php echo h(build_url(['page' => $total_pages])); ?>">
                    <?php echo (int)$total_pages; ?>
                </a>
            <?php endif; ?>

            <a class="rc-pager-btn <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>"
               href="<?php echo h(build_url(['page' => min($total_pages, $page + 1)])); ?>">
                <?php echo h($lang['NEXT'] ?? 'Next'); ?>
            </a>
        </div>
    </div>

</div>
