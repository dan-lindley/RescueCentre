<?php
if (!defined('APP_LOADED')) exit;

require_once __DIR__ . '/../core/mfa.php';

registerPermission('centre.config.manage', $lang['SETTINGS_MANAGE_CENTRE_CONFIG'], 'tab');
requirePermission('centre.config.manage');

$centre_id = (int)$GLOBALS['centre_id'];

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$msg = '';

// Load current settings
$stmt = $pdo->prepare("
    SELECT
        handover_declaration_text,
        centre_type,
        mfa_enabled,
        mfa_totp_enabled,
        single_species_prefill,
        single_species_default_species
    FROM rescue_centre_meta
    WHERE centre_id = ?
    LIMIT 1
");
$stmt->execute([$centre_id]);
$current = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current) {
    echo '<div class="rc-alert red">' . htmlspecialchars($lang['SETTINGS_CONFIG_NOT_FOUND']) . '</div>';
    return;
}

$handover_declaration_text = (string)($current['handover_declaration_text'] ?? '');
$centre_type               = (string)($current['centre_type'] ?? 'rescue');

$allowed_centre_types = ['rescue', 'sanctuary', 'rehoming'];
if (!in_array($centre_type, $allowed_centre_types, true)) {
    $centre_type = 'rescue';
}

$mfa_enabled      = (int)$current['mfa_enabled'];
$mfa_totp_enabled = (int)$current['mfa_totp_enabled'];

$single_species_prefill = (int)($current['single_species_prefill'] ?? 0);
$single_species_default_species = (string)($current['single_species_default_species'] ?? '');

// ------------------------------------------------------------
// SAVE: CENTRE DETAILS
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'save_centre_details'
) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
        $msg = '<div class="rc-alert red">' . htmlspecialchars($lang['SETTINGS_INVALID_REQUEST']) . '</div>';
    } else {
        $new_handover_declaration_text = trim((string)($_POST['handover_declaration_text'] ?? ''));
        $new_centre_type = (string)($_POST['centre_type'] ?? 'rescue');

        if (!in_array($new_centre_type, $allowed_centre_types, true)) {
            $new_centre_type = 'rescue';
        }

        // Optional length guard for textarea
        if (mb_strlen($new_handover_declaration_text) > 5000) {
            $new_handover_declaration_text = mb_substr($new_handover_declaration_text, 0, 5000);
        }

        $stmt = $pdo->prepare("
            UPDATE rescue_centre_meta
            SET handover_declaration_text = ?, centre_type = ?
            WHERE centre_id = ?
            LIMIT 1
        ");
        $stmt->execute([$new_handover_declaration_text, $new_centre_type, $centre_id]);

        $msg = '<div class="rc-alert green">' . htmlspecialchars($lang['SETTINGS_UPDATED']) . '</div>';

        // Update local vars for UI
        $handover_declaration_text = $new_handover_declaration_text;
        $centre_type               = $new_centre_type;
    }
}

// ------------------------------------------------------------
// SAVE: MFA SETTINGS
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'save_mfa_settings'
) {

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
        $msg = '<div class="rc-alert red">' . htmlspecialchars($lang['SETTINGS_INVALID_REQUEST']) . '</div>';
    } else {

        $new_mfa_enabled      = isset($_POST['mfa_enabled']) ? 1 : 0;
        $new_mfa_totp_enabled = isset($_POST['mfa_totp_enabled']) ? 1 : 0;

        // Gate: turning OFF MFA requires verification
        if ($mfa_enabled === 1 && $new_mfa_enabled === 0) {
            if (!rc_mfa_session_allows('centre.mfa.disable', $centre_id)) {

                $dest = '/mfa_verify.php?purpose=centre.mfa.disable'
                      . '&target=' . (int)$centre_id
                      . '&return=' . urlencode($_SERVER['REQUEST_URI']);

                if (!headers_sent()) {
                    header('Location: ' . $dest);
                    exit;
                }

                // Inside wrapper (headers already sent) -> client-side redirect
                echo '<div class="rc-alert blue">' . htmlspecialchars($lang['SETTINGS_REDIRECT_VERIFY']) . '</div>';
                echo '<script>window.location.href=' . json_encode($dest) . ';</script>';
                echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($dest, ENT_QUOTES, 'UTF-8') . '"></noscript>';
                exit;
            }
        }

        // Keep state sane: if MFA is off, force totp off too
        if ($new_mfa_enabled === 0) {
            $new_mfa_totp_enabled = 0;
        }

        $stmt = $pdo->prepare("
            UPDATE rescue_centre_meta
            SET mfa_enabled = ?, mfa_totp_enabled = ?
            WHERE centre_id = ?
            LIMIT 1
        ");
        $stmt->execute([$new_mfa_enabled, $new_mfa_totp_enabled, $centre_id]);

        $msg = '<div class="rc-alert green">' . htmlspecialchars($lang['SETTINGS_UPDATED']) . '</div>';

        // Update local vars for UI
        $mfa_enabled      = $new_mfa_enabled;
        $mfa_totp_enabled = $new_mfa_totp_enabled;
    }
}

// ------------------------------------------------------------
// SAVE: SINGLE SPECIES PREFILL
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'save_single_species'
) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
        $msg = '<div class="rc-alert red">' . htmlspecialchars($lang['SETTINGS_INVALID_REQUEST']) . '</div>';
    } else {

        $new_single_species_prefill = isset($_POST['single_species_prefill']) ? 1 : 0;
        $new_default_species = '';

        if ($new_single_species_prefill === 1) {
            $new_default_species = trim((string)($_POST['single_species_default_species'] ?? ''));

            if (mb_strlen($new_default_species) > 255) {
                $new_default_species = mb_substr($new_default_species, 0, 255);
            }
        }

        // Rule: When No => clear entry
        if ($new_single_species_prefill === 0) {
            $new_default_species = '';
        }

        $stmt = $pdo->prepare("
            UPDATE rescue_centre_meta
            SET single_species_prefill = ?, single_species_default_species = ?
            WHERE centre_id = ?
            LIMIT 1
        ");
        $stmt->execute([$new_single_species_prefill, $new_default_species, $centre_id]);

        $msg = '<div class="rc-alert green">' . htmlspecialchars($lang['SETTINGS_UPDATED']) . '</div>';

        // Update local vars for UI
        $single_species_prefill = $new_single_species_prefill;
        $single_species_default_species = $new_default_species;
    }
}
?>

<div class="content-title">
    <div class="title">
        <div class="txt">
            <h2><?= htmlspecialchars($lang['SETTINGS_CENTRE_CONFIG']) ?></h2>
            <p><?= htmlspecialchars($lang['SETTINGS_CONFIG_SUBTITLE']) ?></p>
        </div>
    </div>
</div>

<br><?= $msg ?>

<div class="card" style="margin-top:16px;">
    <div class="card-header">
        <strong><?= htmlspecialchars($lang['SETTINGS_CENTRE_DETAILS']) ?></strong><br><br>
    </div>
    <div class="card-body">

        <form method="post">
            <input type="hidden" name="action" value="save_centre_details">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
    <label for="handover_declaration_text" style="display:block; margin-bottom:8px;">
        <strong><?= htmlspecialchars($lang['SETTINGS_HANDOVER_DECLARATION_TEXT']) ?></strong>
    </label>

    <textarea
        name="handover_declaration_text"
        id="handover_declaration_text"
        class="form-control"
        placeholder="<?= htmlspecialchars($lang['SETTINGS_HANDOVER_PLACEHOLDER']) ?>"
        style="
            width:100%;
            min-height:160px;
            padding:12px;
            font-size:14px;
            line-height:1.5;
            resize:vertical;
        "
    ><?= htmlspecialchars($handover_declaration_text, ENT_QUOTES, 'UTF-8') ?></textarea>

    <div style="font-size: 0.9em; color: #666; margin-top:6px;">
        <?= htmlspecialchars($lang['SETTINGS_HANDOVER_HELP']) ?>
    </div>
</div>
            <br>

            <div class="form-group">
                <label><strong><?= htmlspecialchars($lang['SETTINGS_CENTRE_TYPE']) ?></strong></label>

                <div style="margin-top:10px; display:flex; flex-direction:column; gap:10px;">
                    <label style="display:flex; align-items:center; gap:10px; font-weight:normal;">
                        <input type="radio" name="centre_type" value="rescue" <?= $centre_type === 'rescue' ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($lang['SETTINGS_WILDLIFE_RESCUE']) ?></span>
                    </label>

                    <label style="display:flex; align-items:center; gap:10px; font-weight:normal;">
                        <input type="radio" name="centre_type" value="sanctuary" <?= $centre_type === 'sanctuary' ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($lang['SETTINGS_ANIMAL_SANCTUARY']) ?></span>
                    </label>

                    <label style="display:flex; align-items:center; gap:10px; font-weight:normal;">
                        <input type="radio" name="centre_type" value="rehoming" <?= $centre_type === 'rehoming' ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($lang['SETTINGS_REHOMING_CENTRE']) ?></span>
                    </label>
                </div>

                <div style="font-size: 0.9em; color: #666; margin-top:8px;">
                    <?= htmlspecialchars($lang['SETTINGS_CENTRE_TYPE_HELP']) ?>
                </div>
            </div>

            <br><button type="submit" class="btn btn-primary"><?= htmlspecialchars($lang['SAVE'] . ' ' . $lang['MENU_SETTINGS']) ?></button>
        </form>

    </div>
</div>

<br>

<div class="card" style="margin-top:16px;">
    <div class="card-header">
        <strong><?= htmlspecialchars($lang['SETTINGS_MFA']) ?></strong><br><br>
    </div>
    <div class="card-body">

        <form method="post">
            <input type="hidden" name="action" value="save_mfa_settings">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <div style="display:flex; align-items:center; gap:12px;">
                    <label class="switch">
                        <input type="checkbox" name="mfa_enabled" value="1" <?= $mfa_enabled ? 'checked' : '' ?>>
                        <span class="slider"></span>
                    </label>
                    <span><?= htmlspecialchars($lang['SETTINGS_REQUIRE_MFA']) ?></span>
                </div>
            </div>

            <br>

            <div class="form-group" style="margin-left:20px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <label class="switch">
                        <input type="checkbox" name="mfa_totp_enabled" value="1"
                               <?= $mfa_totp_enabled ? 'checked' : '' ?>
                               <?= $mfa_enabled ? '' : 'disabled' ?>>
                        <span class="slider"></span>
                    </label>
                    <span><?= htmlspecialchars($lang['SETTINGS_ENABLE_AUTH_APP']) ?></span>
                </div>
                <div style="font-size: 0.9em; color: #666; margin-top:5px;">
                    <?= htmlspecialchars($lang['SETTINGS_AUTH_APP_HELP']) ?>
                </div>
            </div>

            <br><button type="submit" class="btn btn-primary"><?= htmlspecialchars($lang['SAVE'] . ' ' . $lang['MENU_SETTINGS']) ?></button>
        </form>

    </div>
</div>

<br>

<div class="card" style="margin-top:16px;">
    <div class="card-header">
        <strong><?= htmlspecialchars($lang['SETTINGS_SPECIES_PREFILL']) ?></strong><br><br>
    </div>
    <div class="card-body">

        <form method="post" id="singleSpeciesForm">
            <input type="hidden" name="action" value="save_single_species">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <div style="display:flex; align-items:center; gap:12px;">
                    <label class="switch">
                        <input type="checkbox"
                               id="single_species_prefill"
                               name="single_species_prefill"
                               value="1"
                               <?= $single_species_prefill ? 'checked' : '' ?>>
                        <span class="slider"></span>
                    </label>
                    <span><?= htmlspecialchars($lang['SETTINGS_SINGLE_SPECIES']) ?></span>
                </div>
                <div style="font-size: 0.9em; color: #666; margin-top:5px;">
                    <?= htmlspecialchars($lang['SETTINGS_SINGLE_SPECIES_HELP']) ?>
                </div>
            </div>

            <div class="form-group" id="centre_species_wrap" style="margin-left:20px; position:relative; <?= $single_species_prefill ? '' : 'display:none;' ?>">
                <label for="centre_species_search"><strong><?= htmlspecialchars($lang['SETTINGS_DEFAULT_SPECIES']) ?></strong></label>

                <input type="text"
                       id="centre_species_search"
                       class="form-control"
                       autocomplete="off"
                       placeholder="<?= htmlspecialchars($lang['ADM_SPECIES_SEARCH_PLACEHOLDER']) ?>"
                       value="<?= htmlspecialchars($single_species_default_species, ENT_QUOTES, 'UTF-8') ?>">

                <div id="centre_species_results" class="autocomplete-results"></div>

                <input type="hidden"
                       name="single_species_default_species"
                       id="centre_species_value"
                       value="<?= htmlspecialchars($single_species_default_species, ENT_QUOTES, 'UTF-8') ?>">

                <div style="font-size: 0.9em; color: #666; margin-top:5px;">
                    <?= htmlspecialchars($lang['SETTINGS_CONVENIENCE_DEFAULT']) ?>
                </div>
            </div>

            <br><button type="submit" class="btn btn-primary"><?= htmlspecialchars($lang['SAVE'] . ' ' . $lang['MENU_SETTINGS']) ?></button>
        </form>

    </div>
</div>

<script>
(function () {
    const toggle      = document.getElementById('single_species_prefill');
    const wrap        = document.getElementById('centre_species_wrap');
    const input       = document.getElementById('centre_species_search');
    const resultsBox  = document.getElementById('centre_species_results');
    const hiddenValue = document.getElementById('centre_species_value');

    let timer = null;

    function clearResults() {
        if (resultsBox) resultsBox.innerHTML = '';
    }

    function syncWrap() {
        if (!toggle || !wrap) return;

        if (toggle.checked) {
            wrap.style.display = '';
            if (input) input.focus();
        } else {
            wrap.style.display = 'none';
            if (input) input.value = '';
            if (hiddenValue) hiddenValue.value = '';
            clearResults();
        }
    }

    function syncHiddenFromInput() {
        if (!input || !hiddenValue) return;
        hiddenValue.value = input.value || '';
    }

    function runSearch(q) {
        fetch('/controllers/search_species.php?q=' + encodeURIComponent(q))
            .then(res => res.json())
            .then(data => {
                clearResults();

                if (!Array.isArray(data) || data.length === 0) return;

                const ul = document.createElement('ul');
                data.forEach(item => {
                    const li = document.createElement('li');
                    li.textContent = item.species_display;
                    li.style.padding = "6px 10px";
                    li.style.cursor = "pointer";

                    li.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();

                        if (input) input.value = item.species_name;
                        if (hiddenValue) hiddenValue.value = item.species_name;

                        clearResults();
                    });

                    ul.appendChild(li);
                });

                resultsBox.appendChild(ul);
            })
            .catch(() => clearResults());
    }

    if (toggle) {
        toggle.addEventListener('change', syncWrap);
    }

    if (input) {
        input.addEventListener('input', function () {
            const q = (input.value || '').trim();
            syncHiddenFromInput();
            clearResults();

            if (q.length < 2) return;

            if (timer) clearTimeout(timer);
            timer = setTimeout(() => runSearch(q), 250);
        });

        document.addEventListener('click', function (e) {
            if (!resultsBox) return;
            if (!resultsBox.contains(e.target) && e.target !== input) {
                clearResults();
            }
        });
    }

    syncWrap();
})();
</script>
