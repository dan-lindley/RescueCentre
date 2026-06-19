<?php
// modules/adoptions/controllers/adoptions_lib.php
if (!defined('APP_LOADED')) exit;

function adoptions_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function adoptions_centre_id(): int
{
    if (isset($GLOBALS['centre_id'])) {
        return (int)$GLOBALS['centre_id'];
    }
    if (isset($_SESSION['centre_id'])) {
        return (int)$_SESSION['centre_id'];
    }
    if (isset($_SESSION['rescue_id'])) {
        return (int)$_SESSION['rescue_id'];
    }
    return 0;
}

function adoptions_soft_delete_filter(PDO $pdo): string
{
    try {
        $stmt = $pdo->prepare("
            SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME IN ('rescue_admissions', 'rescue_patients')
              AND COLUMN_NAME = 'is_deleted'
        ");
        $stmt->execute();

        $tables = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $tables[(string)$row['TABLE_NAME']] = true;
        }

        $filter = '';
        if (!empty($tables['rescue_admissions'])) {
            $filter .= "\n        AND COALESCE(a.is_deleted, 0) = 0";
        }
        if (!empty($tables['rescue_patients'])) {
            $filter .= "\n        AND COALESCE(p.is_deleted, 0) = 0";
        }

        return $filter;
    } catch (Throwable $e) {
        return '';
    }
}

function adoptions_fetch_patients(PDO $pdo, string $disposition, int $centreId): array
{
    if ($centreId <= 0) {
        return [];
    }

    $softDeleteFilter = adoptions_soft_delete_filter($pdo);
    $stmt = $pdo->prepare("
        SELECT
            a.admission_id,
            a.patient_id,
            a.admission_date,
            a.disposition,
            a.disposition_date,
            a.presenting_complaint,
            a.current_location,
            a.current_location_id,
            p.name,
            p.sex,
            p.animal_species,
            p.animal_type,
            rl.location_name,
            DATEDIFF(COALESCE(a.disposition_date, NOW()), a.admission_date) AS daysincare
        FROM rescue_admissions a
        INNER JOIN rescue_patients p
            ON p.patient_id = a.patient_id
        LEFT JOIN rescue_locations rl
            ON rl.location_id = NULLIF(a.current_location_id, 0)
           AND rl.centre_id = :location_centre_id
           AND (rl.deleted = 0 OR rl.deleted IS NULL)
        WHERE p.centre_id = :centre_id
          AND a.disposition = :disposition
          {$softDeleteFilter}
        ORDER BY COALESCE(a.disposition_date, a.admission_date) DESC, a.admission_id DESC
    ");
    $stmt->execute([
        ':location_centre_id' => $centreId,
        ':centre_id' => $centreId,
        ':disposition' => $disposition,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function adoptions_count_by_disposition(PDO $pdo, int $centreId): array
{
    if ($centreId <= 0) {
        return ['For Adoption' => 0, 'Adopted' => 0];
    }

    $softDeleteFilter = adoptions_soft_delete_filter($pdo);
    $stmt = $pdo->prepare("
        SELECT a.disposition, COUNT(*) AS total
        FROM rescue_admissions a
        INNER JOIN rescue_patients p
            ON p.patient_id = a.patient_id
        WHERE p.centre_id = :centre_id
          AND a.disposition IN ('For Adoption', 'Adopted')
          {$softDeleteFilter}
        GROUP BY a.disposition
    ");
    $stmt->execute([':centre_id' => $centreId]);

    $counts = ['For Adoption' => 0, 'Adopted' => 0];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $counts[(string)$row['disposition']] = (int)$row['total'];
    }

    return $counts;
}

function adoptions_render_patient_table(array $patients, string $emptyMessage): void
{
    if (!$patients) {
        echo '<div class="alert-box alert-grey">' . adoptions_h($emptyMessage) . '</div>';
        return;
    }
    ?>
    <div class="rc-table-scroll">
        <table class="rc-table row-hover">
            <thead>
                <tr>
                    <th>CRN</th>
                    <th>Patient</th>
                    <th>Admission</th>
                    <th>Disposition</th>
                    <th>Location</th>
                    <th>Complaint</th>
                    <th class="rc-table-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($patients as $patient): ?>
                    <?php
                    $location = trim((string)($patient['location_name'] ?? ''));
                    if ($location === '') {
                        $location = trim((string)($patient['current_location'] ?? ''));
                    }
                    if ($location === '') {
                        $location = 'Unassigned';
                    }
                    ?>
                    <tr>
                        <td><strong>CRN-<?= (int)$patient['patient_id'] ?></strong></td>
                        <td>
                            <strong><?= adoptions_h($patient['name'] ?: 'Unnamed') ?></strong><br>
                            <span class="rc-muted">
                                <?= adoptions_h($patient['animal_species'] ?? '') ?>
                                <?php if (!empty($patient['animal_type'])): ?>
                                    (<?= adoptions_h($patient['animal_type']) ?>)
                                <?php endif; ?>
                            </span>
                        </td>
                        <td>
                            <?= adoptions_h($patient['admission_date'] ? date('d-m-Y', strtotime((string)$patient['admission_date'])) : '-') ?><br>
                            <span class="rc-muted"><?= (int)($patient['daysincare'] ?? 0) ?> days in care</span>
                        </td>
                        <td>
                            <strong><?= adoptions_h($patient['disposition']) ?></strong>
                            <?php if (!empty($patient['disposition_date'])): ?>
                                <br><span class="rc-muted"><?= adoptions_h(date('d-m-Y', strtotime((string)$patient['disposition_date']))) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= adoptions_h($location) ?></td>
                        <td><?= adoptions_h($patient['presenting_complaint'] ?? '-') ?></td>
                        <td class="rc-table-actions">
                            <a href="viewpatient.php?patient_id=<?= (int)$patient['patient_id'] ?>" class="btn blue">View</a>
                            <a href="editpatient.php?patient_id=<?= (int)$patient['patient_id'] ?>&pid=<?= (int)$patient['patient_id'] ?>" class="btn grey">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
