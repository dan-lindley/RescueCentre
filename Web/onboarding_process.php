<?php
// onboarding_process.php (PASTE-OVER, MariaDB-safe, no PK name assumptions)
// Fixes:
// - CSRF validation
// - Idempotent onboarding (self-heals onboarded=1)
// - Prevents duplicate centre/vet/org creation
// - Does NOT assume rescue_* tables have PK column named "id"
// - MariaDB-safe: LIMIT 1 FOR UPDATE clause order

include 'main.php';
check_loggedin($pdo);

function redirect_error(string $msg) {
    header('Location: onboarding.php?error=' . urlencode($msg));
    exit;
}

function col_exists(PDO $pdo, string $table, string $col): bool {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$col]);
    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

function table_pk(PDO $pdo, string $table): string {
    $stmt = $pdo->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || empty($row['Column_name'])) {
        throw new Exception("Cannot determine PRIMARY KEY for table: $table");
    }
    return $row['Column_name'];
}


function centreIsValid($centreId): bool {
    // If your rule is strictly "> 1", change this line to: return (int)$centreId > 1;
    return is_numeric($centreId) && (int)$centreId > 0;
}

// ✅ Require account_id
$account_id = (int)($_SESSION['account_id'] ?? 0);
if ($account_id <= 0) {
    redirect_error('Your session expired. Please log in again.');
}

// ✅ CSRF validation (auth script sets csrf_token; onboarding.php includes it)
$post_token = (string)($_POST['csrf_token'] ?? '');
$sess_token = (string)($_SESSION['csrf_token'] ?? '');
if ($sess_token === '' || $post_token === '' || !hash_equals($sess_token, $post_token)) {
    redirect_error('Your session expired. Please try again.');
}

// Role + form inputs
$role     = $_SESSION['account_role'] ?? 'Member';
$name     = trim($_POST['name'] ?? '');
$tel      = trim($_POST['tel'] ?? '');
$org_type = trim($_POST['org_type'] ?? '');
$address  = trim($_POST['address'] ?? '');

if ($name === '') {
    redirect_error('Please enter a name.');
}
if ($role === 'NGO' && $org_type === '') {
    redirect_error('Please select an organisation type.');
}

try {
    $pdo->beginTransaction();

    // ✅ Lock the account row (MariaDB syntax: LIMIT ... FOR UPDATE)
    $stmt = $pdo->prepare("
        SELECT id, email, role, centre_id, vet_id, ngo_id, onboarded
        FROM accounts
        WHERE id = ?
        LIMIT 1 FOR UPDATE
    ");
    $stmt->execute([$account_id]);
    $acct = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$acct) {
        throw new Exception('Account not found');
    }

    // If DB role differs, trust DB to avoid wrong path inserts
    if (!empty($acct['role']) && $acct['role'] !== $role) {
        $role = (string)$acct['role'];
        $_SESSION['account_role'] = $role;
    }

    // ✅ If already onboarded in DB, just sync session and exit (idempotent)
    if (isset($acct['onboarded']) && (int)$acct['onboarded'] === 1) {
        $_SESSION['onboarded'] = 1;
        $_SESSION['centre_id'] = $acct['centre_id'] ?? null;
        $_SESSION['vet_id']    = $acct['vet_id'] ?? null;
        $_SESSION['ngo_id']    = $acct['ngo_id'] ?? null;

        $pdo->commit();
        header('Location: home.php');
        exit;
    }

    $account_email = (string)($acct['email'] ?? '');

    // ----------------------------
    // MEMBER
    // ----------------------------
    if ($role === 'Member') {

$existing_centre_id = isset($acct['centre_id']) ? (int)$acct['centre_id'] : 0;

// ✅ SAFEGUARD #1:
// Only trust centre_id if that centre is actually owned by this account.
if (centreIsValid($existing_centre_id)) {
    $centresPk = table_pk($pdo, 'rescue_centres');

    $stmt = $pdo->prepare("
        SELECT `$centresPk`
        FROM rescue_centres
        WHERE `$centresPk` = ? AND owner_id = ?
        LIMIT 1
    ");
    $stmt->execute([$existing_centre_id, $account_id]);
    $ownedCentreId = (int)($stmt->fetchColumn() ?: 0);

    if ($ownedCentreId > 0) {
        $stmt = $pdo->prepare("UPDATE accounts SET centre_id = ?, onboarded = 1 WHERE id = ?");
        $stmt->execute([$ownedCentreId, $account_id]);

        $_SESSION['centre_id'] = $ownedCentreId;
        $_SESSION['onboarded'] = 1;

        $pdo->commit();
        header('Location: home.php');
        exit;
    }

    // Account has a centre_id, but it does not belong to this account.
    // Clear it and continue with normal lookup/create flow.
    $stmt = $pdo->prepare("UPDATE accounts SET centre_id = NULL WHERE id = ?");
    $stmt->execute([$account_id]);

    $acct['centre_id'] = null;
    $existing_centre_id = 0;
}

        // ✅ SAFEGUARD #2:
        // If a centre already exists for this owner_id, reuse it (no duplicate centres).
        $centresPk = table_pk($pdo, 'rescue_centres');
        $stmt = $pdo->prepare("SELECT `$centresPk` FROM rescue_centres WHERE owner_id = ? ORDER BY `$centresPk` ASC LIMIT 1");
        $stmt->execute([$account_id]);
        $existingCentreRowId = (int)($stmt->fetchColumn() ?: 0);

        if ($existingCentreRowId > 0) {
            $stmt = $pdo->prepare("UPDATE accounts SET centre_id = ?, onboarded = 1 WHERE id = ?");
            $stmt->execute([$existingCentreRowId, $account_id]);

            $_SESSION['centre_id'] = $existingCentreRowId;
            $_SESSION['onboarded'] = 1;

            $pdo->commit();
            header('Location: home.php');
            exit;
        }

        // Create rescue centre (your original column set)
        // If your rescue_centres schema differs, we can switch this to a dynamic insert too.
        $sql = 'INSERT INTO rescue_centres
            (rescue_name, owner_id, centre_type, email, office_tel, mobile, `24_hour`, address_line_one, address_line_two, city, postcode, coordinates, accepting_admissions, closed_message, species_accepted, opening_hours, ngo_parameter, centre_lat, centre_long)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $name,
            $account_id,
            'Rescue',
            $account_email ?: '',
            '', // office_tel
            '', // mobile
            '', // 24_hour
            '', // address_line_one
            '', // address_line_two
            '', // city
            '', // postcode
            '', // coordinates
            'Yes', // accepting_admissions
            '', // closed_message
            '', // species_accepted
            '', // opening_hours
            '0', // ngo_parameter
            '',  // centre_lat
            ''   // centre_long
        ]);

        // Use the PK we discovered (not assuming it's "id")
        $centre_id = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare("UPDATE accounts SET centre_id = ?, onboarded = 1 WHERE id = ?");
        $stmt->execute([$centre_id, $account_id]);

        $_SESSION['centre_id'] = $centre_id;
        $_SESSION['onboarded'] = 1;

    // ----------------------------
    // VET
    // ----------------------------
    } elseif ($role === 'Vet') {

        $existing_vet_id = isset($acct['vet_id']) ? (int)$acct['vet_id'] : 0;

        // ✅ If already linked, just mark onboarded and exit
        if ($existing_vet_id > 0) {
            $stmt = $pdo->prepare("UPDATE accounts SET onboarded = 1, vet_ok = 1 WHERE id = ?");
            $stmt->execute([$account_id]);

            $_SESSION['vet_id'] = $existing_vet_id;
            $_SESSION['vet_ok'] = 1;
            $_SESSION['onboarded'] = 1;

            $pdo->commit();
            header('Location: home.php');
            exit;
        }

        // Optional safeguard: reuse vet created_by_account_id if present
        $reuseVetId = 0;
        $vetsPk = table_pk($pdo, 'rescue_vets');

        if (col_exists($pdo, 'rescue_vets', 'created_by_account_id')) {
            $stmt = $pdo->prepare("SELECT `$vetsPk` FROM rescue_vets WHERE created_by_account_id = ? LIMIT 1");
            $stmt->execute([$account_id]);
            $reuseVetId = (int)($stmt->fetchColumn() ?: 0);
        }

        if ($reuseVetId > 0) {
            $stmt = $pdo->prepare("UPDATE accounts SET vet_id = ?, vet_ok = 1, onboarded = 1 WHERE id = ?");
            $stmt->execute([$reuseVetId, $account_id]);

            $_SESSION['vet_id'] = $reuseVetId;
            $_SESSION['vet_ok'] = 1;
            $_SESSION['onboarded'] = 1;

            $pdo->commit();
            header('Location: home.php');
            exit;
        }

        // Build insert for vets (schema-aware)
        $table = 'rescue_vets';
        $cols = [];
        $vals = [];

        $cols[] = 'practice_name'; $vals[] = $name;

        if (col_exists($pdo, $table, 'practice_tel')) {
            $cols[] = 'practice_tel'; $vals[] = ($tel !== '' ? $tel : null);
        }
        if (col_exists($pdo, $table, 'created_by_account_id')) {
            $cols[] = 'created_by_account_id'; $vals[] = $account_id;
        }
        if (col_exists($pdo, $table, 'status')) {
            $cols[] = 'status'; $vals[] = 'Active';
        }
        if (col_exists($pdo, $table, 'admin_notes')) {
            $cols[] = 'admin_notes'; $vals[] = null;
        }

        $placeholders = implode(',', array_fill(0, count($cols), '?'));
        $sql = 'INSERT INTO rescue_vets (' . implode(',', $cols) . ') VALUES (' . $placeholders . ')';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($vals);

        $vet_id = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare("UPDATE accounts SET vet_id = ?, vet_ok = 1, onboarded = 1 WHERE id = ?");
        $stmt->execute([$vet_id, $account_id]);

        $_SESSION['vet_id'] = $vet_id;
        $_SESSION['vet_ok'] = 1;
        $_SESSION['onboarded'] = 1;

    // ----------------------------
    // NGO
    // ----------------------------
    } elseif ($role === 'NGO') {

        $existing_ngo_id = isset($acct['ngo_id']) ? (int)$acct['ngo_id'] : 0;

        // ✅ If already linked, just mark onboarded and exit
        if ($existing_ngo_id > 0) {
            $stmt = $pdo->prepare("UPDATE accounts SET onboarded = 1, ngo_ok = 1 WHERE id = ?");
            $stmt->execute([$account_id]);

            $_SESSION['ngo_id'] = $existing_ngo_id;
            $_SESSION['ngo_ok'] = 1;
            $_SESSION['onboarded'] = 1;

            $pdo->commit();
            header('Location: home.php');
            exit;
        }

        // Optional safeguard: reuse org created_by_account_id if present
        $reuseNgoId = 0;
        $orgsPk = table_pk($pdo, 'rescue_orgs');

        if (col_exists($pdo, 'rescue_orgs', 'created_by_account_id')) {
            $stmt = $pdo->prepare("SELECT `$orgsPk` FROM rescue_orgs WHERE created_by_account_id = ? LIMIT 1");
            $stmt->execute([$account_id]);
            $reuseNgoId = (int)($stmt->fetchColumn() ?: 0);
        }

        if ($reuseNgoId > 0) {
            $stmt = $pdo->prepare("UPDATE accounts SET ngo_id = ?, ngo_ok = 1, onboarded = 1 WHERE id = ?");
            $stmt->execute([$reuseNgoId, $account_id]);

            $_SESSION['ngo_id'] = $reuseNgoId;
            $_SESSION['ngo_ok'] = 1;
            $_SESSION['onboarded'] = 1;

            $pdo->commit();
            header('Location: home.php');
            exit;
        }

        // Build insert for orgs (schema-aware)
        $table = 'rescue_orgs';
        $cols = [];
        $vals = [];

        $cols[] = 'org_name'; $vals[] = $name;

        if (col_exists($pdo, $table, 'org_type')) {
            $cols[] = 'org_type'; $vals[] = $org_type;
        }
        if (col_exists($pdo, $table, 'org_address')) {
            $cols[] = 'org_address'; $vals[] = ($address !== '' ? $address : null);
        }
        if (col_exists($pdo, $table, 'org_valid_until')) {
            $cols[] = 'org_valid_until'; $vals[] = null;
        }
        if (col_exists($pdo, $table, 'created_by_account_id')) {
            $cols[] = 'created_by_account_id'; $vals[] = $account_id;
        }
        if (col_exists($pdo, $table, 'status')) {
            $cols[] = 'status'; $vals[] = 'Active';
        }
        if (col_exists($pdo, $table, 'admin_notes')) {
            $cols[] = 'admin_notes'; $vals[] = null;
        }

        $placeholders = implode(',', array_fill(0, count($cols), '?'));
        $sql = 'INSERT INTO rescue_orgs (' . implode(',', $cols) . ') VALUES (' . $placeholders . ')';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($vals);

        $ngo_id = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare("UPDATE accounts SET ngo_id = ?, ngo_ok = 1, onboarded = 1 WHERE id = ?");
        $stmt->execute([$ngo_id, $account_id]);

        $_SESSION['ngo_id'] = $ngo_id;
        $_SESSION['ngo_ok'] = 1;
        $_SESSION['onboarded'] = 1;

    } else {
        redirect_error('Unknown account role. Please contact support.');
    }

    $pdo->commit();
    header('Location: home.php');
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // Keep user-friendly message; log $e->getMessage() server-side if you have logging
    redirect_error('Something went wrong saving your details.');
}

