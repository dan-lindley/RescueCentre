<?php
// ------------------------------------------------------------
// permissions.php
// Core backend permission engine for Rescue Centre Platform
// ------------------------------------------------------------

// Make sure DB and session globals are available
if (!isset($pdo)) {
   require_once __DIR__ . '/../dashmain.php';
}
require_once __DIR__ . '/audit.php';   

function permissions_sql_quote_enum_value(string $value): string
{
    return "'" . str_replace("'", "''", $value) . "'";
}

function permissions_ensure_type_value(string $type): void
{
    global $pdo;

    static $checked = [];
    $type = trim($type);
    if ($type === '' || isset($checked[$type])) {
        return;
    }

    $checked[$type] = true;

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM rescue_permissions LIKE 'type'");
        $column = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if (!$column || empty($column['Type'])) {
            return;
        }

        $columnType = (string)$column['Type'];
        if (!preg_match('/^enum\((.*)\)$/i', $columnType, $matches)) {
            return;
        }

        preg_match_all("/'((?:[^']|'')*)'/", $matches[1], $valueMatches);
        $values = array_map(static function (string $value): string {
            return str_replace("''", "'", $value);
        }, $valueMatches[1] ?? []);

        if (in_array($type, $values, true)) {
            return;
        }

        $values[] = $type;
        $enumSql = implode(',', array_map('permissions_sql_quote_enum_value', $values));
        $nullSql = ((string)($column['Null'] ?? '') === 'YES') ? 'NULL' : 'NOT NULL';
        $default = $column['Default'] ?? null;
        $defaultSql = $default !== null ? ' DEFAULT ' . permissions_sql_quote_enum_value((string)$default) : '';

        $pdo->exec("ALTER TABLE rescue_permissions MODIFY COLUMN `type` ENUM($enumSql) $nullSql$defaultSql");
    } catch (Throwable $e) {
        // If the schema cannot be upgraded here, the later insert will surface the DB error.
    }
}

/* ============================================================
    registerPermission($key, $description, $type)
    ------------------------------------------------------------
    • Called at the top of any page or action usage
    • If permission doesn't exist in DB → auto-create it
    • Always returns permission_id
============================================================ */
function registerPermission($key, $description = "", $type = "action") {
    global $pdo;

    permissions_ensure_type_value((string)$type);

    // 1. Check if permission exists
    $stmt = $pdo->prepare("
        SELECT permission_id 
        FROM rescue_permissions 
        WHERE permission_key = :key 
        LIMIT 1
    ");
    $stmt->execute([':key' => $key]);
    $pid = $stmt->fetchColumn();

    if ($pid) {
        return $pid; // Already exists
    }

    // 2. Create new permission entry
    $insert = $pdo->prepare("
        INSERT INTO rescue_permissions (permission_key, description, type, created_at)
        VALUES (:key, :description, :type, NOW())
    ");
    $insert->execute([
        ':key'         => $key,
        ':description' => $description,
        ':type'        => $type
    ]);

    $pid = $pdo->lastInsertId();

    // 3. Assign DENY defaults for all roles (system-wide default = centre_id 0)
    $roles = $pdo->query("SELECT role_id FROM rescue_roles")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($roles as $rid) {
        $pdo->prepare("
            INSERT INTO rescue_role_permissions (centre_id, role_id, permission_id, allow)
            VALUES (0, :rid, :pid, 0)
        ")->execute([
            ':rid' => $rid,
            ':pid' => $pid
        ]);
    }

    return $pid;
}


/* ============================================================
    can($permission_key)
    ------------------------------------------------------------
    • Checks user override first → if present RETURN
    • Then centre-level role override
    • Then system-level role default
    • If nothing found → deny
============================================================ */
function can($permission_key) {
    global $pdo;
    
    // These MUST already be defined in your session globals
    $uid = (int)($GLOBALS['user_id'] ?? 0);
    $rid = (int)($GLOBALS['rescue_role'] ?? 0);
    $cid = (int)($GLOBALS['centre_id'] ?? 0);

    if (!$uid || !$rid) {
        return false; // Not logged in or invalid state
    }

    // Rescue role 1 is the centre owner/administrator created during install.
    // It must never be locked out of management, staff, or permission screens.
    if ($rid === 1) {
        return true;
    }

    // 1. Resolve permission_id
    $stmt = $pdo->prepare("
        SELECT permission_id 
        FROM rescue_permissions 
        WHERE permission_key = :key LIMIT 1
    ");
    $stmt->execute([':key' => $permission_key]);
    $pid = $stmt->fetchColumn();

    if (!$pid) {
        return false; // Permission hasn't been registered yet
    }

    // 2. USER OVERRIDE
    $stmt = $pdo->prepare("
        SELECT allow 
        FROM rescue_user_permissions
        WHERE user_id = :uid AND permission_id = :pid
        LIMIT 1
    ");
    $stmt->execute([':uid' => $uid, ':pid' => $pid]);
    $u = $stmt->fetchColumn();

    if ($u !== false) {
        return (bool)$u; // Override wins
    }

    // 3. CENTRE-LEVEL ROLE PERMISSION
    $stmt = $pdo->prepare("
        SELECT allow
        FROM rescue_role_permissions
        WHERE role_id = :rid
        AND centre_id = :cid
        AND permission_id = :pid
        LIMIT 1
    ");
    $stmt->execute([':rid' => $rid, ':cid' => $cid, ':pid' => $pid]);
    $r = $stmt->fetchColumn();

    if ($r !== false) {
        return (bool)$r;
    }

    // 4. SYSTEM-DIRECT ROLE DEFAULTS (centre_id = 0)
    $stmt = $pdo->prepare("
        SELECT allow
        FROM rescue_role_permissions
        WHERE role_id = :rid
        AND centre_id = 0
        AND permission_id = :pid
        LIMIT 1
    ");
    $stmt->execute([':rid' => $rid, ':pid' => $pid]);
    $sys = $stmt->fetchColumn();

    if ($sys !== false) {
        return (bool)$sys;
    }

    // 5. SAFETY FALLBACK — deny
    return false;
}


/* ============================================================
    requirePermission($permission_key)
    ------------------------------------------------------------
    • Enforces permission
    • If not allowed → 403 + kill execution
============================================================ */
function requirePermission($key)
{
    if (can($key)) {
        return;
    }

    global $pdo;

    // 1. Audit log
    audit_write(
        $pdo,
        'access_denied',
        'permissions',
        null,
        [
            'permission_required' => $key,
            'attempted_url'       => $_SERVER['REQUEST_URI'] ?? ''
        ]
    );

    // 2. Prepare 403
    http_response_code(403);

    // 3. Pass data into template
    $GLOBALS['blocked_permission'] = $key;
    $GLOBALS['blocked_url']        = $_SERVER['REQUEST_URI'] ?? '';

    // 4. Load clean template (NO header/footer includes!)
    include __DIR__ . '/../views/errors/403.php';

    exit;
}
