<?php
// views/cohorts.php

require_once __DIR__ . '/../controllers/cohorts_lib.php';

$cohortAction = 'modules/cohorts/controllers/cohorts_handler.php';

$centre_id_int = (int)($centre_id ?? $_SESSION['centre_id'] ?? 0);

$cohortStmt = $pdo->prepare("
    SELECT c.*,
           COALESCE(m.active_members, 0) AS active_members
    FROM rescue_cohorts c
    LEFT JOIN (
        SELECT cohort_id, COUNT(*) AS active_members
        FROM rescue_cohort_members
        WHERE left_at IS NULL
        GROUP BY cohort_id
    ) m ON m.cohort_id = c.cohort_id
    WHERE c.centre_id = :centre_id
    ORDER BY FIELD(c.status, 'active', 'ended'), c.created_at DESC
");
$cohortStmt->execute([':centre_id' => $centre_id_int]);
$cohorts = $cohortStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$cohortIds = array_map(static fn($row) => (int)$row['cohort_id'], $cohorts);
$membersByCohort = [];
$activeMemberCountsByCohort = [];
$notesByCohort = [];
$noteCountsByCohort = [];
$notePagesByCohort = [];
$notesPerPage = 10;

if (!empty($cohortIds)) {
    $placeholders = implode(',', array_fill(0, count($cohortIds), '?'));
    $memberStmt = $pdo->prepare("
        SELECT
            cm.cohort_id,
            cm.patient_id,
            cm.joined_at,
            p.name,
            p.sex,
            p.animal_species,
            a.admission_id,
            a.current_location
        FROM rescue_cohort_members cm
        INNER JOIN rescue_patients p
            ON p.patient_id = cm.patient_id
           AND p.centre_id = ?
        LEFT JOIN rescue_admissions a
            ON a.admission_id = (
                SELECT MAX(a2.admission_id)
                FROM rescue_admissions a2
                WHERE a2.patient_id = cm.patient_id
            )
        WHERE cm.cohort_id IN ($placeholders)
          AND cm.left_at IS NULL
        ORDER BY p.animal_species ASC, p.patient_id ASC
    ");
    $memberStmt->execute(array_merge([$centre_id_int], $cohortIds));
    foreach ($memberStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $member) {
        $memberCohortId = (int)$member['cohort_id'];
        $membersByCohort[$memberCohortId][] = $member;
        $activeMemberCountsByCohort[$memberCohortId] = ($activeMemberCountsByCohort[$memberCohortId] ?? 0) + 1;
    }

    $noteCountStmt = $pdo->prepare("
        SELECT cohort_id, COUNT(*) AS total_notes
        FROM rescue_cohort_care_notes
        WHERE cohort_id IN ($placeholders)
        GROUP BY cohort_id
    ");
    $noteCountStmt->execute($cohortIds);
    foreach ($noteCountStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $countRow) {
        $noteCountsByCohort[(int)$countRow['cohort_id']] = (int)$countRow['total_notes'];
    }

    $notesStmt = $pdo->prepare("
        SELECT
            n.cohort_id,
            n.cohort_note_id,
            n.note_text,
            n.created_by,
            n.created_at,
            a.username AS created_by_name
        FROM rescue_cohort_care_notes n
        LEFT JOIN accounts a
            ON a.id = n.created_by
        WHERE n.cohort_id = :cohort_id
        ORDER BY n.created_at DESC, n.cohort_note_id DESC
        LIMIT :limit OFFSET :offset
    ");
    foreach ($cohortIds as $notesCohortId) {
        $pageKey = 'notes_page_' . $notesCohortId;
        $totalNotes = $noteCountsByCohort[$notesCohortId] ?? 0;
        $totalPages = max(1, (int)ceil($totalNotes / $notesPerPage));
        $currentPage = max(1, min($totalPages, (int)($_GET[$pageKey] ?? 1)));
        $notePagesByCohort[$notesCohortId] = [
            'current' => $currentPage,
            'total' => $totalPages,
            'total_notes' => $totalNotes,
            'page_key' => $pageKey,
        ];

        $notesStmt->bindValue(':cohort_id', $notesCohortId, PDO::PARAM_INT);
        $notesStmt->bindValue(':limit', $notesPerPage, PDO::PARAM_INT);
        $notesStmt->bindValue(':offset', ($currentPage - 1) * $notesPerPage, PDO::PARAM_INT);
        $notesStmt->execute();
        $notesByCohort[$notesCohortId] = $notesStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

$dietStmt = $pdo->prepare("
    SELECT cdi.centre_diet_item_id, di.name, di.type, di.default_unit
    FROM rescue_centre_diet_items cdi
    JOIN rescue_diet_items di ON di.diet_item_id = cdi.diet_item_id
    WHERE cdi.centre_id = ?
      AND cdi.is_enabled = 1
    ORDER BY di.name ASC
");
$dietStmt->execute([$centre_id_int]);
$dietItems = $dietStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$locationStmt = $pdo->prepare("
    SELECT location_id, location_name, location_area
    FROM rescue_locations
    WHERE centre_id = ?
      AND (deleted = 0 OR deleted IS NULL)
    ORDER BY location_area ASC, location_name ASC
");
$locationStmt->execute([$centre_id_int]);
$cohortLocations = $locationStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$adoptionsModuleActive = function_exists('modules_is_active')
    ? modules_is_active($pdo, 'adoptions', $centre_id_int)
    : false;
$dispStmt = $pdo->prepare("
    SELECT disposition
    FROM rescue_dispositions
    WHERE (:adoptions_active = 1 OR disposition NOT IN ('For Adoption', 'Adopted'))
    ORDER BY disposition ASC
");
$dispStmt->execute([':adoptions_active' => $adoptionsModuleActive ? 1 : 0]);
$cohortDispositions = $dispStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$feedChartsByCohort = [];
if (!empty($cohortIds)) {
    $feedChartLabels = [];
    $feedChartDays = [];
    $feedChartIndex = [];
    $chartStart = new DateTime('today -29 days');
    $chartEnd = new DateTime('today');
    $cursor = clone $chartStart;
    while ($cursor <= $chartEnd) {
        $dayKey = $cursor->format('Y-m-d');
        $feedChartDays[] = $dayKey;
        $feedChartLabels[] = $cursor->format('j M');
        $feedChartIndex[$dayKey] = count($feedChartDays) - 1;
        $cursor->modify('+1 day');
    }

    foreach ($cohortIds as $cohortIdForChart) {
        $feedChartsByCohort[$cohortIdForChart] = [
            'labels' => $feedChartLabels,
            'offered' => array_fill(0, count($feedChartDays), 0),
            'remaining' => array_fill(0, count($feedChartDays), 0),
            'consumed' => array_fill(0, count($feedChartDays), 0),
            'per_patient' => array_fill(0, count($feedChartDays), 0),
            'unit' => '',
        ];
    }

    $feedPlaceholders = implode(',', array_fill(0, count($cohortIds), '?'));
    $feedChartStmt = $pdo->prepare("
        SELECT
            cohort_id,
            DATE(fed_at) AS feed_day,
            SUM(COALESCE(amount_in, 0)) AS offered_total,
            SUM(COALESCE(amount_out, 0)) AS remaining_total,
            SUM(GREATEST(COALESCE(amount_in, 0) - COALESCE(amount_out, 0), 0)) AS consumed_total,
            MAX(amount_unit) AS amount_unit
        FROM rescue_cohort_feeding_logs
        WHERE cohort_id IN ($feedPlaceholders)
          AND DATE(fed_at) BETWEEN ? AND ?
        GROUP BY cohort_id, DATE(fed_at)
        ORDER BY feed_day ASC
    ");
    $feedChartStmt->execute(array_merge($cohortIds, [$chartStart->format('Y-m-d'), $chartEnd->format('Y-m-d')]));

    foreach ($feedChartStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $feedRow) {
        $chartCohortId = (int)$feedRow['cohort_id'];
        $feedDay = (string)$feedRow['feed_day'];
        if (!isset($feedChartsByCohort[$chartCohortId], $feedChartIndex[$feedDay])) {
            continue;
        }

        $idx = $feedChartIndex[$feedDay];
        $feedChartsByCohort[$chartCohortId]['offered'][$idx] = round((float)$feedRow['offered_total'], 2);
        $feedChartsByCohort[$chartCohortId]['remaining'][$idx] = round((float)$feedRow['remaining_total'], 2);
        $feedChartsByCohort[$chartCohortId]['consumed'][$idx] = round((float)$feedRow['consumed_total'], 2);
        $memberCount = max(1, (int)($activeMemberCountsByCohort[$chartCohortId] ?? 0));
        $feedChartsByCohort[$chartCohortId]['per_patient'][$idx] = round((float)$feedRow['consumed_total'] / $memberCount, 2);
        if (!$feedChartsByCohort[$chartCohortId]['unit'] && !empty($feedRow['amount_unit'])) {
            $feedChartsByCohort[$chartCohortId]['unit'] = (string)$feedRow['amount_unit'];
        }
    }
}
?>

<div class="content-title">
    <div class="title">
        <div class="icon">
            <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M96 128C96 92.7 124.7 64 160 64L480 64C515.3 64 544 92.7 544 128L544 512C544 547.3 515.3 576 480 576L160 576C124.7 576 96 547.3 96 512L96 128zM176 160L176 240L288 240L288 160L176 160zM352 160L352 240L464 240L464 160L352 160zM176 304L176 384L288 384L288 304L176 304zM352 304L352 384L464 384L464 304L352 304zM176 448L176 512L288 512L288 448L176 448zM352 448L352 512L464 512L464 448L352 448z"/></svg>
        </div>
        <div class="txt">
            <h2><?= $lang['LM_COHORTS'] ?? 'Cohorts' ?></h2>
            <p><?= $lang['COHORTS_SUBTITLE'] ?? 'Manage grouped patients, care notes, feeding, moves and dispositions.' ?></p>
        </div>
    </div>
</div>

<style>
    .cohort-title { margin: 0 0 4px 0; font-size: 1.45rem; line-height:1.15; }
    .cohort-patients {
        margin-top:12px;
        padding-top:12px;
        border-top:1px solid #eef2f7;
    }
    .cohort-patients .btn { white-space:nowrap; }
    .cohort-notes-list { margin-bottom:12px; }
    .cohort-note-meta {
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        color:#6b7280;
        font-size:.8rem;
        margin-bottom:4px;
    }
    .cohort-note-text {
        color:#111827;
        line-height:1.35;
    }
    .cohort-note-pagination {
        display:flex;
        align-items:center;
        justify-content:flex-end;
        gap:8px;
        flex-wrap:wrap;
        margin:-4px 0 12px;
        color:#6b7280;
        font-size:.85rem;
    }
    .cohort-feed-grid {
        display: grid;
        grid-template-columns: 1fr 1.6fr .8fr .8fr .8fr 1.2fr;
        gap: 10px;
        align-items: end;
    }
    .cohort-feed-grid .span-2 { grid-column: span 2; }
    .cohort-feed-grid .span-3 { grid-column: span 3; }
    .cohort-feed-grid .span-6 { grid-column: span 6; }
    .cohort-intake-chart {
        margin: 0;
        padding: 10px 12px;
    }
    .cohort-intake-head { margin-bottom:8px; }
    .cohort-intake-bars {
        display:grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        align-items:center;
    }
    .cohort-intake-key {
        display:flex;
        gap:12px;
        flex-wrap:wrap;
        font-size:.8rem;
        color:#4b5563;
    }
    .cohort-intake-key span::before {
        content:'';
        display:inline-block;
        width:10px;
        height:10px;
        border-radius:2px;
        margin-right:5px;
        vertical-align:-1px;
        background:#22c55e;
    }
    .cohort-intake-key span:last-child::before { background:#f59e0b; }
    .cohort-feed-history {
        margin: 0 0 12px 0;
        padding: 10px 12px;
    }
    .cohort-feed-history-head { margin-bottom:8px; }
    .cohort-feed-history-canvas {
        height: 180px;
        min-height: 180px;
    }
    .cohort-feed-history-canvas canvas {
        display:block;
        width:100% !important;
        height:100% !important;
    }
    .cohort-feed-history-empty {
        font-size:.85rem;
        color:#6b7280;
        padding:8px 0 2px;
    }
    @media (max-width: 800px) {
        .cohort-feed-grid { grid-template-columns:1fr; }
        .cohort-feed-grid .span-2,
        .cohort-feed-grid .span-3,
        .cohort-feed-grid .span-6 { grid-column: span 1; }
        .cohort-intake-bars { grid-template-columns:1fr; }
    }
</style>

<?php if (!empty($_GET['msg'])): ?>
    <div class="alert-box alert-green">
        <?= cohorts_h($_GET['msg']) ?>
    </div>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
    <div class="alert-box alert-red">
        <?= cohorts_h($_GET['error']) ?>
    </div>
<?php endif; ?>

<?php if (empty($cohorts)): ?>
    <div class="alert-box alert-grey">
        No cohorts have been created yet. Go to <a href="patients.php">My Patients</a>, select a location tab, then use <strong>Make cohort</strong>.
    </div>
<?php else: ?>
    <div class="rc-stack">
        <?php foreach ($cohorts as $cohort): ?>
            <?php
                $cohortId = (int)$cohort['cohort_id'];
                $members = $membersByCohort[$cohortId] ?? [];
                $cohortNotes = $notesByCohort[$cohortId] ?? [];
                $notePage = $notePagesByCohort[$cohortId] ?? [
                    'current' => 1,
                    'total' => 1,
                    'total_notes' => 0,
                    'page_key' => 'notes_page_' . $cohortId,
                ];
                $notePanelOpen = isset($_GET[$notePage['page_key']]);
                $isActive = (string)$cohort['status'] === 'active';
                $locationLabel = (string)($cohort['location_label'] ?? '');
                if ($locationLabel === '') {
                    $locationLabel = !empty($cohort['location_key']) ? (string)$cohort['location_key'] : 'Location not set';
                }
                $feedChart = $feedChartsByCohort[$cohortId] ?? [
                    'labels' => [],
                    'offered' => [],
                    'remaining' => [],
                    'consumed' => [],
                    'per_patient' => [],
                    'unit' => '',
                ];
                $hasFeedChartData = (array_sum($feedChart['offered']) + array_sum($feedChart['remaining']) + array_sum($feedChart['consumed']) + array_sum($feedChart['per_patient'])) > 0;
            ?>
            <div class="rc-card" id="cohort-<?= $cohortId ?>" data-cohort-row>
                <div class="rc-row-head">
                    <div>
                        <h3 class="cohort-title"><?= cohorts_h($cohort['cohort_name']) ?></h3>
                        <div class="rc-inline-list rc-muted">
                            <span><?= (int)$cohort['active_members'] ?> active member<?= (int)$cohort['active_members'] === 1 ? '' : 's' ?></span>
                        </div>
                        <?php if (!empty($cohort['notes'])): ?>
                            <div style="margin-top:8px; color:#374151;"><?= nl2br(cohorts_h($cohort['notes'])) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="rc-actions-stack">
                        <div class="rc-actions">
                            <span class="rc-status <?= $isActive ? 'active' : 'inactive' ?>"><?= cohorts_h($cohort['status']) ?></span>
                            <span class="rc-chip blue">Current location: <strong><?= cohorts_h($locationLabel) ?></strong></span>
                        </div>

                        <div class="rc-actions">
                            <button type="button" class="btn blue" data-cohort-toggle data-target="cohort-note-<?= $cohortId ?>">Care notes</button>
                            <?php if ($isActive): ?>
                                <button type="button" class="btn green" data-cohort-toggle data-target="cohort-feed-<?= $cohortId ?>">Feeding</button>
                                <button type="button" class="btn orange" data-cohort-toggle data-target="cohort-move-<?= $cohortId ?>">Move</button>
                                <button type="button" class="btn red" data-cohort-toggle data-target="cohort-discharge-<?= $cohortId ?>">Discharge</button>
                                <button type="button" class="btn grey" data-cohort-toggle data-target="cohort-add-<?= $cohortId ?>">Add CRN</button>
                                <form method="post" action="<?= cohorts_h($cohortAction) ?>" style="margin:0;" onsubmit="return confirm('End this cohort? This does not move or discharge any patients.');">
                                    <input type="hidden" name="action" value="end">
                                    <input type="hidden" name="centre_id" value="<?= $centre_id_int ?>">
                                    <input type="hidden" name="cohort_id" value="<?= $cohortId ?>">
                                    <button type="submit" class="btn purple">End Cohort</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="rc-item-grid cohort-patients">
                    <?php if (empty($members)): ?>
                        <div class="alert-box alert-grey" style="margin:0; width:100%;">No active members.</div>
                    <?php else: ?>
                        <?php foreach ($members as $member): ?>
                            <?php
                                $sexInitial = strtoupper(substr(trim((string)($member['sex'] ?? '')), 0, 1));
                                $sexLabel = $sexInitial !== '' ? ' (' . $sexInitial . ')' : '';
                            ?>
                            <div class="rc-item">
                                <div class="rc-item-main">
                                    <strong>CRN <?= (int)$member['patient_id'] ?> - <?= cohorts_h($member['name'] ?? '') ?><?= cohorts_h($sexLabel) ?></strong>
                                    <small><?= cohorts_h($member['animal_species'] ?? '') ?></small>
                                </div>
                                <a class="btn green" href="viewpatient.php?patient_id=<?= (int)$member['patient_id'] ?>">Care plan</a>
                                <?php if ($isActive): ?>
                                    <form method="post" action="<?= cohorts_h($cohortAction) ?>" style="margin:0;">
                                        <input type="hidden" name="action" value="remove_member">
                                        <input type="hidden" name="centre_id" value="<?= $centre_id_int ?>">
                                        <input type="hidden" name="cohort_id" value="<?= $cohortId ?>">
                                        <input type="hidden" name="patient_id" value="<?= (int)$member['patient_id'] ?>">
                                        <button type="submit" class="btn orange">Unlink</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div id="cohort-note-<?= $cohortId ?>" class="rc-toggle-panel<?= $notePanelOpen ? ' is-open' : '' ?>" data-cohort-panel>
                    <div class="rc-scroll-list cohort-notes-list">
                        <?php if (empty($cohortNotes)): ?>
                            <div class="alert-box alert-grey" style="margin:0;">No cohort care notes have been logged yet.</div>
                        <?php else: ?>
                            <?php foreach ($cohortNotes as $note): ?>
                                <div class="rc-card rc-card-muted">
                                    <div class="cohort-note-meta">
                                        <span><?= cohorts_h($note['created_at'] ?? '') ?></span>
                                        <span><?= cohorts_h($note['created_by_name'] ?: ('User #' . (int)$note['created_by'])) ?></span>
                                    </div>
                                    <div class="cohort-note-text"><?= nl2br(cohorts_h($note['note_text'] ?? '')) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ((int)$notePage['total'] > 1): ?>
                        <?php
                            $baseParams = $_GET;
                            $basePath = strtok($_SERVER['REQUEST_URI'], '?');
                            $prevPage = max(1, (int)$notePage['current'] - 1);
                            $nextPage = min((int)$notePage['total'], (int)$notePage['current'] + 1);

                            $prevParams = $baseParams;
                            $prevParams[$notePage['page_key']] = $prevPage;
                            $prevUrl = $basePath . '?' . http_build_query($prevParams) . '#cohort-' . $cohortId;

                            $nextParams = $baseParams;
                            $nextParams[$notePage['page_key']] = $nextPage;
                            $nextUrl = $basePath . '?' . http_build_query($nextParams) . '#cohort-' . $cohortId;
                        ?>
                        <div class="cohort-note-pagination">
                            <?php if ((int)$notePage['current'] > 1): ?>
                                <a class="btn grey" href="<?= cohorts_h($prevUrl) ?>">Newest</a>
                            <?php endif; ?>
                            <span>Page <?= (int)$notePage['current'] ?> of <?= (int)$notePage['total'] ?>, <?= (int)$notePage['total_notes'] ?> notes</span>
                            <?php if ((int)$notePage['current'] < (int)$notePage['total']): ?>
                                <a class="btn grey" href="<?= cohorts_h($nextUrl) ?>">Older</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($isActive): ?>
                        <form method="post" action="<?= cohorts_h($cohortAction) ?>" class="xform">
                            <input type="hidden" name="action" value="note">
                            <input type="hidden" name="centre_id" value="<?= $centre_id_int ?>">
                            <input type="hidden" name="cohort_id" value="<?= $cohortId ?>">
                            <label class="xform-label">Care note for all active members</label>
                            <textarea name="note_text" class="xform-input" rows="4" required></textarea>
                            <div style="margin-top:10px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                                <label><input type="checkbox" name="public" value="1"> Public?</label>
                                <button type="submit" class="btn blue">Save cohort note</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <?php if ($isActive): ?>
                    <div id="cohort-feed-<?= $cohortId ?>" class="rc-toggle-panel" data-cohort-panel>
                        <div class="alert-box alert-grey cohort-feed-history">
                            <div class="rc-split-head cohort-feed-history-head">
                                <div>
                                    <strong>Feed intake history</strong>
                                    <div style="font-size:.85rem; opacity:.85;">Last 30 days from cohort feeding logs</div>
                                </div>
                                <?php if (!empty($feedChart['unit'])): ?>
                                    <div style="font-size:.85rem; color:#4b5563;">Unit: <?= cohorts_h($feedChart['unit']) ?></div>
                                <?php endif; ?>
                            </div>

                            <?php if ($hasFeedChartData): ?>
                                <div class="cohort-feed-history-canvas">
                                    <canvas class="cohort-feed-history-chart"
                                            data-labels="<?= cohorts_h(json_encode($feedChart['labels'])) ?>"
                                            data-offered="<?= cohorts_h(json_encode($feedChart['offered'])) ?>"
                                            data-remaining="<?= cohorts_h(json_encode($feedChart['remaining'])) ?>"
                                            data-consumed="<?= cohorts_h(json_encode($feedChart['consumed'])) ?>"
                                            data-per-patient="<?= cohorts_h(json_encode($feedChart['per_patient'])) ?>"
                                            data-unit="<?= cohorts_h($feedChart['unit']) ?>"></canvas>
                                </div>
                            <?php else: ?>
                                <div class="cohort-feed-history-empty">No cohort feeding records have been logged for this cohort yet.</div>
                            <?php endif; ?>
                        </div>

                        <form method="post" action="<?= cohorts_h($cohortAction) ?>" class="xform cohort-feed-form">
                            <input type="hidden" name="action" value="feed">
                            <input type="hidden" name="centre_id" value="<?= $centre_id_int ?>">
                            <input type="hidden" name="cohort_id" value="<?= $cohortId ?>">
                            <input type="hidden" name="feed_type" class="cohort-feed-type">
                            <input type="hidden" name="remaining_percent" class="cohort-remaining-percent" value="">

                            <div class="cohort-feed-grid">
                                <div class="xform-field">
                                    <label class="xform-label">Date / time</label>
                                    <input type="datetime-local" name="fed_at" class="xform-input" value="<?= date('Y-m-d\TH:i') ?>">
                                </div>

                                <div class="xform-field">
                                    <label class="xform-label">Diet item</label>
                                    <select name="centre_diet_item_id" class="xform-input cohort-diet-select" required>
                                        <option value="">Select diet</option>
                                        <?php foreach ($dietItems as $item): ?>
                                            <option value="<?= (int)$item['centre_diet_item_id'] ?>"
                                                    data-type="<?= cohorts_h($item['type']) ?>"
                                                    data-unit="<?= cohorts_h($item['default_unit']) ?>">
                                                <?= cohorts_h($item['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="xform-field">
                                    <label class="xform-label">Feed type</label>
                                    <input type="text" class="xform-input cohort-feed-type-display" readonly placeholder="Select diet item">
                                </div>

                                <div class="xform-field">
                                    <label class="xform-label">Amount offered <span class="cohort-unit-offered" style="opacity:0.8;"></span></label>
                                    <input type="number" step="0.01" min="0" name="offered_value" class="xform-input cohort-offered-value" required>
                                </div>

                                <div class="xform-field">
                                    <label style="display:block;">&nbsp;</label>
                                    <label style="margin:0;">
                                        <input type="checkbox" name="is_estimated" value="1" class="cohort-is-estimated">
                                        Estimated (slider)
                                    </label>
                                </div>

                                <div class="xform-field">
                                    <div class="cohort-remaining-value-wrap">
                                        <label class="xform-label">Remaining <span class="cohort-unit-remaining" style="opacity:0.8;"></span></label>
                                        <input type="number" step="0.01" min="0" name="remaining_value" class="xform-input cohort-remaining-value" value="">
                                    </div>

                                    <div class="cohort-remaining-slider-wrap" style="display:none; margin-top:6px;">
                                        <label class="xform-label">Remaining (estimated)</label>
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <span style="font-size:0.75rem; opacity:0.85; white-space:nowrap;">Empty</span>
                                            <input type="range" class="cohort-remaining-slider" min="0" max="100" step="5" value="0" style="width:100%;">
                                            <span style="font-size:0.75rem; opacity:0.85; white-space:nowrap;">Full</span>
                                        </div>
                                        <div style="margin-top:6px; font-size:0.75rem; opacity:0.9;">
                                            Remaining: <strong><span class="cohort-remaining-slider-text">0</span>%</strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="xform-field span-6">
                                    <div class="alert-box alert-grey cohort-intake-chart">
                                        <div class="rc-split-head cohort-intake-head">
                                            <div>
                                                <strong>Feed intake tracker</strong>
                                                <div style="font-size:.85rem; opacity:.85;">
                                                    Consumed: <strong><span class="cohort-consumed-preview">-</span></strong>
                                                    <span class="cohort-intake-percent"></span>
                                                </div>
                                            </div>
                                            <div class="cohort-intake-key">
                                                <span>Consumed</span>
                                                <span>Remaining</span>
                                            </div>
                                        </div>
                                        <div class="cohort-intake-bars">
                                            <div class="rc-progress">
                                                <span class="rc-progress-fill green cohort-intake-consumed"></span>
                                                <span class="rc-progress-fill amber cohort-intake-remaining"></span>
                                            </div>
                                            <div style="font-size:.85rem; color:#4b5563;">
                                                Offered: <strong><span class="cohort-offered-preview">-</span></strong>
                                                &nbsp; Remaining: <strong><span class="cohort-remaining-preview">-</span></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="xform-field span-3">
                                    <label class="xform-label">Notes (optional)</label>
                                    <input type="text" name="notes" class="xform-input" placeholder="Optional notes (e.g. tolerated well, slow feed, etc.)">
                                </div>
                                <div class="xform-field">
                                    <button type="submit" class="btn green" style="width:100%;">Save feed</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div id="cohort-move-<?= $cohortId ?>" class="rc-toggle-panel" data-cohort-panel>
                        <form method="post" action="<?= cohorts_h($cohortAction) ?>" class="xform" onsubmit="return confirm('Move all active cohort members to this location?');">
                            <input type="hidden" name="action" value="move">
                            <input type="hidden" name="centre_id" value="<?= $centre_id_int ?>">
                            <input type="hidden" name="cohort_id" value="<?= $cohortId ?>">

                            <div class="xform-grid">
                                <div class="xform-field">
                                    <label class="xform-label">New rescue location</label>
                                    <select name="new_location_id" class="xform-input" required>
                                        <option value="">Select location</option>
                                        <?php foreach ($cohortLocations as $loc): ?>
                                            <?php
                                                $locLabel = trim((string)($loc['location_area'] ?? ''));
                                                if ($locLabel !== '') {
                                                    $locLabel .= ' - ';
                                                }
                                                $locLabel .= (string)$loc['location_name'];
                                            ?>
                                            <option value="<?= (int)$loc['location_id'] ?>"><?= cohorts_h($locLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="xform-field" style="align-self:end;">
                                    <button type="submit" class="btn orange">Move cohort</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div id="cohort-discharge-<?= $cohortId ?>" class="rc-toggle-panel" data-cohort-panel>
                        <form method="post" action="<?= cohorts_h($cohortAction) ?>" class="xform" onsubmit="return confirm('Apply this disposition to all active cohort members?');">
                            <input type="hidden" name="action" value="discharge">
                            <input type="hidden" name="centre_id" value="<?= $centre_id_int ?>">
                            <input type="hidden" name="cohort_id" value="<?= $cohortId ?>">

                            <div class="xform-grid">
                                <div class="xform-field">
                                    <label class="xform-label">Disposition</label>
                                    <select name="disposition" class="xform-input" required>
                                        <option value="">Select disposition</option>
                                        <?php foreach ($cohortDispositions as $d): ?>
                                            <option value="<?= cohorts_h($d['disposition']) ?>"><?= cohorts_h($d['disposition']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="xform-field">
                                    <label class="xform-label">Disposition Date &amp; Time</label>
                                    <input type="datetime-local" name="disposition_date" class="xform-input" value="<?= date('Y-m-d\TH:i') ?>" required>
                                </div>

                                <div class="xform-field">
                                    <label class="xform-label">Euthanasia Method</label>
                                    <select name="euthanasia_method" class="xform-input">
                                        <option value="Not Applicable" selected>Not applicable</option>
                                        <option value="Pharmacological - Vet">Pharmacological - Vet</option>
                                        <option value="Pharmacological - Centre">Pharmacological - Centre</option>
                                        <option value="Manual">Manual</option>
                                        <option value="Captive Bolt">Captive Bolt</option>
                                        <option value="Shot">Shot</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>

                                <div class="xform-field" style="grid-column: span 3;">
                                    <label class="xform-label">Comments</label>
                                    <textarea name="disposition_comment" class="xform-input" rows="3"></textarea>
                                </div>

                                <div class="xform-field" style="align-self:end;">
                                    <button type="submit" class="btn red">Apply disposition</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div id="cohort-add-<?= $cohortId ?>" class="rc-toggle-panel" data-cohort-panel>
                        <form method="post" action="<?= cohorts_h($cohortAction) ?>" class="xform">
                            <input type="hidden" name="action" value="add_member">
                            <input type="hidden" name="centre_id" value="<?= $centre_id_int ?>">
                            <input type="hidden" name="cohort_id" value="<?= $cohortId ?>">
                            <div style="display:flex; gap:8px; align-items:end; flex-wrap:wrap;">
                                <div>
                                    <label class="xform-label">Add patient by CRN</label>
                                    <input type="number" name="patient_id" class="xform-input" min="1" required>
                                </div>
                                <button type="submit" class="btn green">Add member</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-cohort-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            const target = document.getElementById(button.dataset.target);
            if (!target) return;

            const row = button.closest('[data-cohort-row]');
            if (row) {
                row.querySelectorAll('[data-cohort-panel]').forEach(function (panel) {
                    if (panel !== target) panel.classList.remove('is-open');
                });
            }

            target.classList.toggle('is-open');
            if (target.classList.contains('is-open')) {
                initCohortFeedCharts(target);
            }
        });
    });

    function unitDisplay(unit) {
        if (!unit) return '';
        if (unit === 'unit') return 'units';
        return unit;
    }

    function num(value) {
        const parsed = parseFloat(value);
        return isNaN(parsed) ? 0 : parsed;
    }

    function fmt(value) {
        const fixed = value.toFixed(2);
        return fixed.replace(/\.?0+$/, '');
    }

    function clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
    }

    function parseChartArray(value) {
        try {
            const parsed = JSON.parse(value || '[]');
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) {
            return [];
        }
    }

    function initCohortFeedCharts(scope) {
        const chartScope = scope || document;
        if (typeof Chart === 'undefined') {
            window.setTimeout(function () {
                initCohortFeedCharts(chartScope);
            }, 120);
            return;
        }

        chartScope.querySelectorAll('.cohort-feed-history-chart').forEach(function (canvas) {
            if (canvas.dataset.chartReady === '1') return;
            if (!canvas.offsetParent) return;

            const labels = parseChartArray(canvas.dataset.labels);
            const offered = parseChartArray(canvas.dataset.offered).map(Number);
            const remaining = parseChartArray(canvas.dataset.remaining).map(Number);
            const consumed = parseChartArray(canvas.dataset.consumed).map(Number);
            const perPatient = parseChartArray(canvas.dataset.perPatient).map(Number);
            const unit = canvas.dataset.unit || '';

            const datasets = [
                {
                    label: 'Consumed',
                    data: consumed,
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34, 197, 94, 0.12)',
                    tension: 0.3,
                    fill: false
                },
                {
                    label: 'Avg per patient',
                    data: perPatient,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.12)',
                    borderDash: [2, 3],
                    tension: 0.3,
                    fill: false
                },
                {
                    label: 'Remaining',
                    data: remaining,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.12)',
                    tension: 0.3,
                    fill: false
                },
                {
                    label: 'Offered',
                    data: offered,
                    borderColor: '#64748b',
                    backgroundColor: 'rgba(100, 116, 139, 0.10)',
                    borderDash: [5, 4],
                    tension: 0.3,
                    fill: false
                }
            ].filter(function (dataset) {
                return dataset.data.some(function (value) {
                    return Number(value) > 0;
                });
            });

            if (!datasets.length) return;

            canvas.dataset.chartReady = '1';
            new Chart(canvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const value = context.parsed.y || 0;
                                    return context.dataset.label + ': ' + fmt(value) + (unit ? unitDisplay(unit) : '');
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 8
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return fmt(Number(value));
                                }
                            }
                        }
                    }
                }
            });
        });
    }

    document.querySelectorAll('.cohort-feed-form').forEach(function (form) {
        const dietSelect = form.querySelector('.cohort-diet-select');
        const typeHidden = form.querySelector('.cohort-feed-type');
        const typeDisplay = form.querySelector('.cohort-feed-type-display');
        const offeredInput = form.querySelector('.cohort-offered-value');
        const estimatedInput = form.querySelector('.cohort-is-estimated');
        const remainingValueWrap = form.querySelector('.cohort-remaining-value-wrap');
        const remainingValueInput = form.querySelector('.cohort-remaining-value');
        const remainingSliderWrap = form.querySelector('.cohort-remaining-slider-wrap');
        const remainingSlider = form.querySelector('.cohort-remaining-slider');
        const remainingSliderText = form.querySelector('.cohort-remaining-slider-text');
        const remainingPercent = form.querySelector('.cohort-remaining-percent');
        const unitOffered = form.querySelector('.cohort-unit-offered');
        const unitRemaining = form.querySelector('.cohort-unit-remaining');
        const consumedPreview = form.querySelector('.cohort-consumed-preview');
        const offeredPreview = form.querySelector('.cohort-offered-preview');
        const remainingPreview = form.querySelector('.cohort-remaining-preview');
        const intakePercent = form.querySelector('.cohort-intake-percent');
        const consumedBar = form.querySelector('.cohort-intake-consumed');
        const remainingBar = form.querySelector('.cohort-intake-remaining');

        let currentType = '';
        let currentUnit = '';

        function setUnits(unit) {
            currentUnit = unit || '';
            const suffix = unitDisplay(currentUnit);
            unitOffered.textContent = suffix ? ' (' + suffix + ')' : '';
            unitRemaining.textContent = suffix ? ' (' + suffix + ')' : '';
        }

        function switchEstimatedUI(isEstimated) {
            if (isEstimated) {
                remainingValueWrap.style.display = 'none';
                remainingSliderWrap.style.display = 'block';
                remainingValueInput.value = '';
                remainingPercent.value = remainingSlider.value;
                remainingSliderText.textContent = remainingSlider.value;
            } else {
                remainingValueWrap.style.display = 'block';
                remainingSliderWrap.style.display = 'none';
                remainingPercent.value = '';
            }
            updateConsumedPreview();
        }

        function setType(type) {
            currentType = type || '';
            typeHidden.value = currentType;
            typeDisplay.value = currentType ? currentType.charAt(0).toUpperCase() + currentType.slice(1) : '';
            typeDisplay.placeholder = currentType ? '' : 'Select diet item';

            if (currentType === 'solid') {
                estimatedInput.disabled = false;
                estimatedInput.parentElement.style.opacity = 1;
            } else {
                estimatedInput.checked = false;
                estimatedInput.disabled = true;
                estimatedInput.parentElement.style.opacity = 0.6;
                switchEstimatedUI(false);
            }
        }

        function updateConsumedPreview() {
            const offered = num(offeredInput.value);
            if (!dietSelect.value || offered <= 0) {
                consumedPreview.textContent = '-';
                offeredPreview.textContent = '-';
                remainingPreview.textContent = '-';
                intakePercent.textContent = '';
                consumedBar.style.width = '0%';
                remainingBar.style.width = '0%';
                return;
            }

            let remaining = 0;
            if (estimatedInput.checked && currentType === 'solid') {
                const pct = clamp(num(remainingSlider.value), 0, 100);
                remaining = offered * (pct / 100);
                remainingPercent.value = pct;
            } else {
                remaining = clamp(num(remainingValueInput.value), 0, offered);
            }

            const consumed = Math.max(0, offered - remaining);
            const consumedPercent = offered > 0 ? clamp((consumed / offered) * 100, 0, 100) : 0;
            const remainingPercentValue = clamp(100 - consumedPercent, 0, 100);
            const unit = unitDisplay(currentUnit);

            consumedPreview.textContent = fmt(consumed) + (unit ? unit : '');
            offeredPreview.textContent = fmt(offered) + (unit ? unit : '');
            remainingPreview.textContent = fmt(remaining) + (unit ? unit : '');
            intakePercent.textContent = '(' + fmt(consumedPercent) + '%)';
            consumedBar.style.width = consumedPercent + '%';
            remainingBar.style.width = remainingPercentValue + '%';
        }

        function updateFromDiet() {
            const option = dietSelect.options[dietSelect.selectedIndex];
            if (!option || !option.dataset.type) {
                setType('');
                setUnits('');
                estimatedInput.checked = false;
                estimatedInput.disabled = true;
                switchEstimatedUI(false);
                updateConsumedPreview();
                return;
            }

            setType(option.dataset.type);
            setUnits(option.dataset.unit);
            switchEstimatedUI(option.dataset.type === 'solid' && estimatedInput.checked);
            updateConsumedPreview();
        }

        dietSelect.addEventListener('change', updateFromDiet);
        offeredInput.addEventListener('input', updateConsumedPreview);
        remainingValueInput.addEventListener('input', updateConsumedPreview);
        estimatedInput.addEventListener('change', function () {
            if (currentType !== 'solid') {
                estimatedInput.checked = false;
            }
            switchEstimatedUI(estimatedInput.checked && currentType === 'solid');
        });
        remainingSlider.addEventListener('input', function () {
            remainingSliderText.textContent = remainingSlider.value;
            remainingPercent.value = remainingSlider.value;
            updateConsumedPreview();
        });

        updateFromDiet();
    });

    initCohortFeedCharts(document);
});
</script>
