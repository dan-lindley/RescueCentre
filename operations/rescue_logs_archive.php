<?php
/**
 * rescue_logs_archive.php
 *
 * Maintenance for rescue_logs:
 *  - mode=maintain: create missing partitions + archive + verify (NO DROP)
 *  - mode=drop: drop up to N VERIFIED partitions (default 1) (NO ARCHIVE)
 *
 * Returns: ['status' => 'success|partial_success|failed', 'actions'=>[], 'errors'=>[]]
 */

function rescueLogsMaintenance(PDO $pdo, array $cfg): array
{
    $mode = $cfg['mode'] ?? 'maintain';

    $tableName    = 'rescue_logs';
    $archiveTable = 'rescue_archive';
    $partitionCol = 'created_at';

    $hotMonths   = (int)$cfg['hot_months'];
    $aheadMonths = (int)$cfg['ahead_months'];
    $timezone    = $cfg['timezone'] ?? 'UTC';

    $archiveBase = rtrim((string)($cfg['archive_base_dir'] ?? ''), '/');

    $maxArchive = (int)($cfg['max_archive_months_per_run'] ?? 1);
    if ($maxArchive < 1) $maxArchive = 1;

    $maxDrop = (int)($cfg['max_drop_partitions_per_run'] ?? 1);
    if ($maxDrop < 1) $maxDrop = 1;

    $result = [
        'status'  => 'success',
        'actions' => [],
        'errors'  => [],
    ];

    $now = new DateTime('first day of this month', new DateTimeZone($timezone));
    $hotCutoff = (clone $now)->modify("-{$hotMonths} months")->format('Y-m-d');

    // Resolve DB name for information_schema checks
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    if (!$dbName) {
        return [
            'status' => 'failed',
            'actions' => [],
            'errors' => ['No database selected (DATABASE() returned NULL).'],
        ];
    }

    if ($mode === 'maintain') {
        // -------------------------
        // A) Create missing partitions for current..+aheadMonths
        // -------------------------
        for ($i = 0; $i <= $aheadMonths; $i++) {
            $start = (clone $now)->modify("+{$i} months");
            $end   = (clone $start)->modify("+1 month");

            $partition = 'p' . $start->format('Ym');
            $startYmd  = $start->format('Y-m-d');
            $endYmd    = $end->format('Y-m-d');

            // If ledger already has it, skip
            $stmt = $pdo->prepare("
                SELECT 1 FROM {$archiveTable}
                WHERE table_name = ? AND period_start = ?
                LIMIT 1
            ");
            $stmt->execute([$tableName, $startYmd]);
            if ($stmt->fetchColumn()) {
                continue;
            }

            // If DB already has partition, just insert ledger row
            if (partitionExists($pdo, $dbName, $tableName, $partition)) {
                upsertArchiveRow($pdo, $archiveTable, $tableName, $partition, $startYmd, $endYmd, 'CREATED', null);
                $result['actions'][] = "Ledger added for existing partition {$partition}.";
                continue;
            }

            // Create partition by reorganizing pmax
            try {
                $pdo->exec("
                    ALTER TABLE {$tableName}
                    REORGANIZE PARTITION pmax INTO (
                        PARTITION {$partition} VALUES LESS THAN ('{$endYmd}'),
                        PARTITION pmax VALUES LESS THAN (MAXVALUE)
                    )
                ");

                upsertArchiveRow($pdo, $archiveTable, $tableName, $partition, $startYmd, $endYmd, 'CREATED', null);
                $result['actions'][] = "Created partition {$partition}.";

            } catch (Throwable $e) {
                $result['status'] = 'failed';
                $result['errors'][] = "Failed creating {$partition}: " . $e->getMessage();
            }
        }

        // -------------------------
        // B) Archive + verify old partitions (NO DROP)
        // -------------------------
        if ($archiveBase === '') {
            $result['status'] = ($result['status'] === 'failed') ? 'failed' : 'partial_success';
            $result['errors'][] = "archive_base_dir not set.";
            return $result;
        }

        // Find up to maxArchive candidates older than cutoff and not VERIFIED/DROPPED
        $stmt = $pdo->prepare("
            SELECT id, partition_name, period_start, period_end, state
            FROM {$archiveTable}
            WHERE table_name = ?
              AND period_start < ?
              AND state IN ('CREATED','FAILED','EXPORTED')
            ORDER BY period_start ASC
            LIMIT {$maxArchive}
        ");
        $stmt->execute([$tableName, $hotCutoff]);

        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($candidates)) {
            $result['actions'][] = "No partitions eligible for archive/verify.";
            return $result;
        }

        $columns = getTableColumns($pdo, $dbName, $tableName);
        if (empty($columns)) {
            $result['status'] = 'failed';
            $result['errors'][] = "Could not determine columns for {$tableName}.";
            return $result;
        }

        foreach ($candidates as $row) {
            $id = (int)$row['id'];
            $partition = $row['partition_name'];
            $startYmd = $row['period_start'];
            $endYmd = $row['period_end'];

            $dt = new DateTime($startYmd, new DateTimeZone($timezone));
            $year  = $dt->format('Y');
            $month = $dt->format('m');

            $dir  = "{$archiveBase}/year={$year}/month={$month}";
            $file = "{$dir}/{$tableName}_{$year}_{$month}.csv.gz";

            try {
                ensureDir($dir);

                $dbCount = countRowsInRange($pdo, $tableName, $partitionCol, $startYmd, $endYmd);
                $minMax  = minMaxInRange($pdo, $tableName, $partitionCol, $startYmd, $endYmd);

                $fileCount = exportToGzCsv(
                    $pdo, $tableName, $columns, $partitionCol,
                    $startYmd, $endYmd, $file
                );

                // verify archive exists
                if (!file_exists($file) || filesize($file) < 10) {
                    throw new RuntimeException("Archive file missing/empty after export: {$file}");
                }

                // verify counts match (required)
                if ($dbCount !== $fileCount) {
                    throw new RuntimeException("Row count mismatch (db={$dbCount}, file={$fileCount})");
                }

                // mark VERIFIED + metadata
                $stmtU = $pdo->prepare("
                    UPDATE {$archiveTable}
                    SET state='VERIFIED',
                        state_changed_at=NOW(),
                        export_path=?,
                        row_count_db=?,
                        row_count_file=?,
                        min_created_at=?,
                        max_created_at=?,
                        error_message=NULL
                    WHERE id=?
                ");
                $stmtU->execute([
                    $file,
                    $dbCount,
                    $fileCount,
                    $minMax['min_created_at'],
                    $minMax['max_created_at'],
                    $id
                ]);

                $result['actions'][] = "Archived+verified {$partition} to {$file} (rows={$dbCount}).";

            } catch (Throwable $e) {
                $result['status'] = ($result['status'] === 'failed') ? 'failed' : 'partial_success';

                $stmtF = $pdo->prepare("
                    UPDATE {$archiveTable}
                    SET state='FAILED',
                        state_changed_at=NOW(),
                        error_message=?
                    WHERE id=?
                ");
                $stmtF->execute([substr($e->getMessage(), 0, 65000), $id]);

                $result['errors'][] = "Archive failed for {$partition}: " . $e->getMessage();
            }
        }

        return $result;
    }

    if ($mode === 'drop') {
        // -------------------------
        // DROP ONLY: drop up to maxDrop VERIFIED partitions older than cutoff
        // -------------------------
        $stmt = $pdo->prepare("
            SELECT id, partition_name
            FROM {$archiveTable}
            WHERE table_name = ?
              AND period_start < ?
              AND state = 'VERIFIED'
            ORDER BY period_start ASC
            LIMIT {$maxDrop}
        ");
        $stmt->execute([$tableName, $hotCutoff]);

        $toDrop = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($toDrop)) {
            $result['actions'][] = "No VERIFIED partitions eligible to drop.";
            return $result;
        }

        foreach ($toDrop as $row) {
            $id = (int)$row['id'];
            $partition = $row['partition_name'];

            try {
                if (partitionExists($pdo, $dbName, $tableName, $partition)) {
                    $pdo->exec("ALTER TABLE {$tableName} DROP PARTITION {$partition}");
                }

                $stmtU = $pdo->prepare("
                    UPDATE {$archiveTable}
                    SET state='DROPPED',
                        state_changed_at=NOW(),
                        error_message=NULL
                    WHERE id=?
                ");
                $stmtU->execute([$id]);

                $result['actions'][] = "Dropped partition {$partition}.";

            } catch (Throwable $e) {
                $result['status'] = ($result['status'] === 'failed') ? 'failed' : 'partial_success';

                $stmtF = $pdo->prepare("
                    UPDATE {$archiveTable}
                    SET state='FAILED',
                        state_changed_at=NOW(),
                        error_message=?
                    WHERE id=?
                ");
                $stmtF->execute([substr($e->getMessage(), 0, 65000), $id]);

                $result['errors'][] = "Drop failed for {$partition}: " . $e->getMessage();
            }
        }

        return $result;
    }

    return [
        'status' => 'failed',
        'actions' => [],
        'errors' => ["Unknown mode: {$mode}"],
    ];
}

/* ----------------- Helpers (internal) ----------------- */

function partitionExists(PDO $pdo, string $dbName, string $tableName, string $partitionName): bool
{
    $stmt = $pdo->prepare("
        SELECT 1
        FROM information_schema.PARTITIONS
        WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND PARTITION_NAME=?
        LIMIT 1
    ");
    $stmt->execute([$dbName, $tableName, $partitionName]);
    return (bool)$stmt->fetchColumn();
}

function upsertArchiveRow(PDO $pdo, string $archiveTable, string $tableName, string $partitionName, string $startYmd, string $endYmd, string $state, ?string $err): void
{
    // Assumes UNIQUE(table_name, period_start) exists (as per your design)
    $sql = "
        INSERT INTO {$archiveTable}
          (table_name, partition_name, period_start, period_end, state, state_changed_at, error_message)
        VALUES
          (?, ?, ?, ?, ?, NOW(), ?)
        ON DUPLICATE KEY UPDATE
          partition_name = VALUES(partition_name),
          period_end = VALUES(period_end),
          state = VALUES(state),
          state_changed_at = NOW(),
          error_message = VALUES(error_message)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tableName, $partitionName, $startYmd, $endYmd, $state, $err]);
}

function ensureDir(string $dir): void
{
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("Failed to create directory: {$dir}");
        }
    }
}

function getTableColumns(PDO $pdo, string $dbName, string $tableName): array
{
    $stmt = $pdo->prepare("
        SELECT COLUMN_NAME
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=? AND TABLE_NAME=?
        ORDER BY ORDINAL_POSITION
    ");
    $stmt->execute([$dbName, $tableName]);
    $cols = [];
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cols[] = $r['COLUMN_NAME'];
    }
    return $cols;
}

function countRowsInRange(PDO $pdo, string $tableName, string $col, string $startYmd, string $endYmd): int
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM {$tableName}
        WHERE {$col} >= ? AND {$col} < ?
    ");
    $stmt->execute([$startYmd . " 00:00:00", $endYmd . " 00:00:00"]);
    return (int)$stmt->fetchColumn();
}

function minMaxInRange(PDO $pdo, string $tableName, string $col, string $startYmd, string $endYmd): array
{
    $stmt = $pdo->prepare("
        SELECT MIN({$col}) AS min_created_at, MAX({$col}) AS max_created_at
        FROM {$tableName}
        WHERE {$col} >= ? AND {$col} < ?
    ");
    $stmt->execute([$startYmd . " 00:00:00", $endYmd . " 00:00:00"]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    return [
        'min_created_at' => $r['min_created_at'] ?? null,
        'max_created_at' => $r['max_created_at'] ?? null,
    ];
}

function exportToGzCsv(
    PDO $pdo,
    string $tableName,
    array $columns,
    string $dateCol,
    string $startYmd,
    string $endYmd,
    string $gzFile
): int {
    $colList = implode(',', array_map(function ($c) { return "`{$c}`"; }, $columns));

    $stmt = $pdo->prepare("
        SELECT {$colList}
        FROM {$tableName}
        WHERE {$dateCol} >= ? AND {$dateCol} < ?
        ORDER BY {$dateCol} ASC
    ");
    $stmt->execute([$startYmd . " 00:00:00", $endYmd . " 00:00:00"]);

    $gz = gzopen($gzFile, 'wb9');
    if ($gz === false) {
        throw new RuntimeException("Cannot open gzip file for writing: {$gzFile}");
    }

    try {
        gzwrite($gz, csvLine($columns));

        $count = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $line = [];
            foreach ($columns as $c) {
                $line[] = $row[$c];
            }
            gzwrite($gz, csvLine($line));
            $count++;
        }
        return $count;
    } finally {
        gzclose($gz);
    }
}

function csvLine(array $fields): string
{
    $fp = fopen('php://temp', 'r+');
    if ($fp === false) return "";
    fputcsv($fp, $fields);
    rewind($fp);
    $csv = stream_get_contents($fp);
    fclose($fp);
    return $csv === false ? "" : $csv;
}
