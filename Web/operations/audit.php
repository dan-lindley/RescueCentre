<?php
/**
 * Centralised audit logging for all form submissions and system events.
 * Writes to the rescue_logs table.
 */

/**
 * Write an audit log entry
 */
function audit_write($pdo, $action, $module = null, $old = null, $new = null)
{
    // Resolve user + centre context
    $user_id =
        $_SESSION['user_id']
        ?? $GLOBALS['user_id']
        ?? null;

    $centre_id =
        $_SESSION['centre_id']
        ?? $GLOBALS['centre_id']
        ?? null;

    // Request metadata
    $endpoint       = $_SERVER['REQUEST_URI']     ?? null;
    $request_method = $_SERVER['REQUEST_METHOD']  ?? null;
    $ip_address     = $_SERVER['REMOTE_ADDR']     ?? null;
    $user_agent     = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $sql = "
        INSERT INTO rescue_logs (
            user_id, centre_id, action, module, endpoint,
            request_method, ip_address, user_agent,
            old_data, new_data
        ) VALUES (
            :user_id, :centre_id, :action, :module, :endpoint,
            :request_method, :ip_address, :user_agent,
            :old_data, :new_data
        )
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':user_id'        => $user_id,
        ':centre_id'      => $centre_id,
        ':action'         => $action,
        ':module'         => $module,
        ':endpoint'       => $endpoint,
        ':request_method' => $request_method,
        ':ip_address'     => $ip_address,
        ':user_agent'     => $user_agent,
        ':old_data'       => $old ? json_encode($old, JSON_UNESCAPED_UNICODE) : null,
        ':new_data'       => $new ? json_encode($new, JSON_UNESCAPED_UNICODE) : null
    ]);
}

/**
 * Automatically logs all POST submissions handled by controllers.
 * Supports human-readable audit_action from forms.
 */
function audit_auto($pdo)
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return;
    }

    // Determine module
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $module = basename($script, '.php');

    // ✅ Resolve action FIRST
    $action = isset($_POST['audit_action']) && $_POST['audit_action'] !== ''
        ? trim($_POST['audit_action'])
        : ucwords(str_replace('_', ' ', $module));

    // ✅ Clean POST data BEFORE logging
    $post_clean = $_POST;
    unset(
        $post_clean['audit_action'],
        $post_clean['password'],
        $post_clean['confirm_password']
    );

    // Write log
    audit_write(
        $pdo,
        $action,
        $module,
        null,
        $post_clean
    );
}
