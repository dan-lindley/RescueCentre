<?php
// operations/transfers_log.php

function transfers_auto(PDO $pdo): void
{
    $GLOBALS['transfers_defaults'] = [
        'centre_id' => $_SESSION['centre_id'] ?? null,
        'created_by_user_id' => $_SESSION['account_id'] ?? null,
    ];
}

function transfers_log(PDO $pdo, string $event_type, array $data = []): bool
{
    $defaults = $GLOBALS['transfers_defaults'] ?? [];

    $centre_id = $data['centre_id'] ?? $defaults['centre_id'] ?? null;
    $user_id   = $data['created_by_user_id'] ?? $defaults['created_by_user_id'] ?? null;

    if (
        !$centre_id ||
        !$user_id ||
        empty($data['patient_id']) ||
        empty($data['admission_id']) ||
        empty($event_type)
    ) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO rescue_transfers_log (
                centre_id,
                patient_id,
                admission_id,
                event_type,
                event_at,
                created_by_user_id,
                from_location_id,
                to_location_id,
                disposition_id,
                notes
            ) VALUES (
                :centre_id,
                :patient_id,
                :admission_id,
                :event_type,
                :event_at,
                :created_by_user_id,
                :from_location_id,
                :to_location_id,
                :disposition_id,
                :notes
            )
        ");

        return $stmt->execute([
            ':centre_id'           => (int)$centre_id,
            ':patient_id'          => (int)$data['patient_id'],
            ':admission_id'        => (int)$data['admission_id'],
            ':event_type'          => $event_type,
            ':event_at'            => $data['event_at'] ?? date('Y-m-d H:i:s'),
            ':created_by_user_id'  => (int)$user_id,
            ':from_location_id'    => $data['from_location_id'] ?? null,
            ':to_location_id'      => $data['to_location_id'] ?? null,
            ':disposition_id'      => $data['disposition_id'] ?? null,
            ':notes'               => $data['notes'] ?? null,
        ]);
    } catch (Throwable $e) {
        return false;
    }
}
