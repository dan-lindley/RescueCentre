<?php
// Load admin header
include_once __DIR__ . '/../../dashmain.php';
echo template_admin_header('403 — Access Denied');
?>
<?php
$blocked_permission = $GLOBALS['permission_required'] ?? 'unknown';
$blocked_url = $_SERVER['REQUEST_URI'] ?? '';
?>

<style>
    .error-403-wrapper {
        max-width: 600px;
        margin: 60px auto;
        padding: 40px 30px;
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.08);
        text-align: center;
        font-family: "Segoe UI", sans-serif;
        animation: fadeIn 0.4s ease-out;
    }
    .error-403-icon svg {
        margin-bottom: 15px;
    }
    .error-403-title {
        font-size: 28px;
        margin-bottom: 10px;
        font-weight: 700;
        color: #333;
    }
    .error-403-text {
        font-size: 16px;
        margin-bottom: 25px;
        color: #555;
        line-height: 1.4em;
    }
    .error-403-btn {
        display: inline-block;
        padding: 12px 24px;
        background: #3498db;
        color: #fff;
        text-decoration: none;
        border-radius: 6px;
        font-weight: 600;
        transition: 0.15s;
    }
    .error-403-btn:hover {
        background: #2d89c6;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="error-403-wrapper">
    <div class="error-403-icon">
        <svg width="70" height="70" viewBox="0 0 24 24" fill="none"
             xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="10" stroke="#E74C3C" stroke-width="2"/>
            <line x1="8" y1="8" x2="16" y2="16" stroke="#E74C3C" stroke-width="2"/>
            <line x1="16" y1="8" x2="8" y2="16" stroke="#E74C3C" stroke-width="2"/>
        </svg>
    </div>

    <div class="error-403-title">403 — Access Denied</div>

    <div class="error-403-text">
        You do not have permission to access this page.<br>
        If you believe this is a mistake, please contact your centre administrator.
    </div>
    <div style="font-size:14px; color:#777; margin-top:20px;">
    <strong>Permission Required:</strong> <?=htmlspecialchars($blocked_permission)?><br>
    <strong>Page:</strong> <?=htmlspecialchars($blocked_url)?>
</div>


   <BR><a href="/dashboard.php" class="error-403-btn">Back to Dashboard</a>
</div>

<?php
echo template_admin_footer();
?>
