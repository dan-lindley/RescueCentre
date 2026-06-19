<?php
// /controllers/diet_controller.php
// Handles centre diet items (linking + enable toggle + use-within + hard delete)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../dashmain.php';      // provides $pdo + session context in your app
require_once __DIR__ . '/../getcentreinfo.php'; // provides $centre_id (current centre context)
include_once __DIR__ . '/../operations/audit.php';
audit_auto($pdo);


// -----------------------------------------------------------------------------
// Helpers
// -----------------------------------------------------------------------------
function clean_int($v, $default = 0): int {
    if (!isset($v)) return (int)$default;
    if (is_array($v)) return (int)$default;
    return (int)filter_var($v, FILTER_SANITIZE_NUMBER_INT);
}

function clean_str($v, $default = ''): string {
    if (!isset($v)) return (string)$default;
    if (is_array($v)) return (string)$default;
    return trim((string)$v);
}

function redirect_back(string $msg = 'updated'): void {
    // Always return user to the Diet tab
    header('Location: /medicationstock.php?sub=diet&msg=' . urlencode($msg));
    exit;
}

function require_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /medicationstock.php?sub=diet&msg=error');
        exit;
    }
}

// Default use-within rules locked in earlier
function default_use_within_days(string $category): int {
    return ($category === 'liquid') ? 730 : 365;
}

// -----------------------------------------------------------------------------
// Begin
// -----------------------------------------------------------------------------
require_post();

$action = clean_str($_POST['action'] ?? '');

if (!$action) {
    redirect_back('error');
}

// Centre scope: trust the wrapper context, not arbitrary POST
$current_centre_id = clean_int($GLOBALS['centre_id'] ?? $centre_id ?? 0);
if (!$current_centre_id) {
    redirect_back('error');
}

// Optional posted centre_id (we validate it matches current centre)
$posted_centre_id = clean_int($_POST['centre_id'] ?? 0);
if ($posted_centre_id && $posted_centre_id !== $current_centre_id) {
    redirect_back('error');
}

try {

    // -------------------------------------------------------------------------
    // ADD: link a diet item to this centre
    // action=add_to_centre
    // POST: diet_item_id, (optional) use_within_days
    // -------------------------------------------------------------------------
    if ($action === 'add_to_centre') {

        $diet_item_id = clean_int($_POST['diet_item_id'] ?? 0);
        if (!$diet_item_id) {
            redirect_back('error');
        }

        // Check the library item exists + fetch its category for defaulting
        $diStmt = $pdo->prepare("SELECT diet_item_id, category FROM rescue_diet_items WHERE diet_item_id = ? LIMIT 1");
        $diStmt->execute([$diet_item_id]);
        $di = $diStmt->fetch(PDO::FETCH_ASSOC);

        if (!$di) {
            redirect_back('error');
        }

        // If use_within_days passed, use it; else default by category
        $use_within_days = clean_int($_POST['use_within_days'] ?? 0);
        if ($use_within_days <= 0) {
            $use_within_days = default_use_within_days((string)$di['category']);
        }

        // Already linked?
        $existsStmt = $pdo->prepare("
            SELECT centre_diet_item_id
            FROM rescue_centre_diet_items
            WHERE centre_id = ? AND diet_item_id = ?
            LIMIT 1
        ");
        $existsStmt->execute([$current_centre_id, $diet_item_id]);
        $existingId = $existsStmt->fetchColumn();

        if ($existingId) {
            // If already exists, just enable it + ensure use_within_days set (non-destructive)
            $updStmt = $pdo->prepare("
                UPDATE rescue_centre_diet_items
                SET is_enabled = 1,
                    use_within_days = COALESCE(NULLIF(use_within_days, 0), ?)
                WHERE centre_diet_item_id = ? AND centre_id = ?
            ");
            $updStmt->execute([$use_within_days, $existingId, $current_centre_id]);

            redirect_back('added');
        }

        // Create link row
        $insStmt = $pdo->prepare("
            INSERT INTO rescue_centre_diet_items
                (centre_id, diet_item_id, use_within_days, is_enabled)
            VALUES
                (?, ?, ?, 1)
        ");
        $insStmt->execute([$current_centre_id, $diet_item_id, $use_within_days]);

        redirect_back('added');
    }

    // -------------------------------------------------------------------------
    // UPDATE: toggle enabled + update use_within_days
    // action=update_centre_diet_item
    // POST: centre_diet_item_id, use_within_days, is_enabled (checkbox)
    // -------------------------------------------------------------------------
    if ($action === 'update_centre_diet_item') {

        $centre_diet_item_id = clean_int($_POST['centre_diet_item_id'] ?? 0);
        if (!$centre_diet_item_id) {
            redirect_back('error');
        }

        // Checkbox: if not present, it is unchecked
        $is_enabled = isset($_POST['is_enabled']) ? 1 : 0;

        $use_within_days = clean_int($_POST['use_within_days'] ?? 0);
        if ($use_within_days < 0) $use_within_days = 0;

        // Scope check + update
        $updStmt = $pdo->prepare("
            UPDATE rescue_centre_diet_items
            SET is_enabled = ?,
                use_within_days = ?
            WHERE centre_diet_item_id = ?
              AND centre_id = ?
        ");
        $updStmt->execute([$is_enabled, $use_within_days, $centre_diet_item_id, $current_centre_id]);

        redirect_back('updated');
    }

    // -------------------------------------------------------------------------
    // DELETE: hard delete link
    // action=delete_centre_diet_item
    // POST: centre_diet_item_id
    // -------------------------------------------------------------------------
    if ($action === 'delete_centre_diet_item') {

        $centre_diet_item_id = clean_int($_POST['centre_diet_item_id'] ?? 0);
        if (!$centre_diet_item_id) {
            redirect_back('error');
        }

        $delStmt = $pdo->prepare("
            DELETE FROM rescue_centre_diet_items
            WHERE centre_diet_item_id = ?
              AND centre_id = ?
            LIMIT 1
        ");
        $delStmt->execute([$centre_diet_item_id, $current_centre_id]);

        redirect_back('deleted');
    }

    // Unknown action
    redirect_back('error');

} catch (Throwable $e) {
    // Optionally log $e somewhere (audit/logs). For now, fail safely.
    redirect_back('error');
}
