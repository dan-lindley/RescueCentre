<?php
/**
 * archive_health.php
 *
 * READ-ONLY archive health check.
 * No side effects. No maintenance.
 */

function archiveHealth(PDO $pdo, array $cfg): array
{
    $tableName   = $cfg['table_name'];
    $hotMonths   = (int)$cfg['hot_months'];
    $aheadMonths = (int)$cfg['ahead_months'];
    $timezone    = $cfg['timezone'] ?? 'UTC';

    $now = new DateTime('first day of this month', new DateTimeZone($timezone));

    // ---- determine required periods ----
    $required = [];

    for ($i = $hotMonths; $i >= 0; $i--) {
        $required[] = (clone $now)->modify("-{$i} months")->format('Y-m-d');
    }
    for ($i = 1; $i <= $aheadMonths; $i++) {
        $required[] = (clone $now)->modify("+{$i} months")->format('Y-m-d');
    }

    // ---- fetch archive metadata ----
    $stmt = $pdo->prepare("
        SELECT period_start, partition_name, state
        FROM rescue_archive
        WHERE table_name = ?
    ");
    $stmt->execute([$tableName]);

    $found = [];
    $expired = [];

    $hotCutoff = (clone $now)->modify("-{$hotMonths} months")->format('Y-m-d');

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $found[$row['period_start']] = $row['partition_name'];

        if ($row['period_start'] < $hotCutoff && $row['state'] !== 'DROPPED') {
            $expired[] = $row['partition_name'];
        }
    }

    // ---- detect missing required partitions ----
    $missing = [];
    foreach ($required as $ps) {
        if (!isset($found[$ps])) {
            $missing[] = 'p' . str_replace('-', '', substr($ps, 0, 7));
        }
    }

    // ---- final decision ----
    $healthy = empty($missing) && empty($expired);

    return [
        'health'  => $healthy ? 'healthy' : 'unhealthy',
        'message' => $healthy
            ? 'Archive system healthy – no maintenance required'
            : 'Archive system NOT healthy – maintenance required',

        'details' => [
            'missing_partitions' => $missing,
            'expired_partitions' => $expired,
            'hot_cutoff' => $hotCutoff,
            'required_periods' => $required,
        ],
    ];
}
