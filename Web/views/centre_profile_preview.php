<?php
// views/centre_profile_preview.php
if (!defined('APP_LOADED')) exit;

require_once "dashmain.php";
require_once "getuserinfo.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// CSRF token setup
if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Bind centre_id from logged-in user context (not request)
$centre_id = isset($GLOBALS['centre_id']) ? (int)$GLOBALS['centre_id'] : 0;
if (!$centre_id) {
    echo '<p>' . htmlspecialchars($lang['SETTINGS_NO_CENTRE_SELECTED']) . '</p>';
    return;
}

// Load centre row
$stmt = $pdo->prepare("SELECT * FROM rescue_centres WHERE rescue_id = :cid LIMIT 1");
$stmt->execute([':cid' => $centre_id]);
$centre = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$centre) {
    echo '<p>' . htmlspecialchars($lang['SETTINGS_CENTRE_NOT_FOUND']) . '</p>';
    return;
}

// Load or create meta
$stmt = $pdo->prepare("SELECT * FROM rescue_centre_meta WHERE centre_id = :cid LIMIT 1");
$stmt->execute([':cid' => $centre_id]);
$meta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$meta) {
    $pdo->prepare("INSERT INTO rescue_centre_meta (centre_id, centre_bio) VALUES (:cid, '')")
        ->execute([':cid' => $centre_id]);
    $meta = [
        'meta_id'              => (int)$pdo->lastInsertId(),
        'centre_bio'           => '',
        'centre_logo'          => null,
        'custom_colour'        => null,
        'centre_profile_image' => null,
        'centre_cover_image'   => null,
        'cover_offset'         => 0
    ];
}

$cover_offset = isset($meta['cover_offset']) ? (int)$meta['cover_offset'] : 0;
$custom_colour = strtoupper(trim((string)($meta['custom_colour'] ?? '')));
if (!preg_match('/^#[0-9A-F]{6}$/', $custom_colour)) {
    $custom_colour = '#0B3A6F';
}

// Simple cache-buster so reloads always fetch latest files
$cacheBust = '?v=' . time();

$coverBase   = $meta['centre_cover_image']   ?: "/assets/placeholders/cover_placeholder.jpg";
$profileBase = $meta['centre_profile_image'] ?: "/assets/placeholders/profile_placeholder.png";
$logoBase    = $meta['centre_logo']          ?: "/assets/placeholders/logo_placeholder.png";

$coverUrl   = $coverBase   . $cacheBust;
$profileUrl = $profileBase . $cacheBust;
$logoUrl    = $logoBase    . $cacheBust;
?>

<!-- Expose CSRF token to JS -->
<meta name="csrf-token" content="<?= htmlspecialchars($csrf_token, ENT_QUOTES) ?>">

<style>
.centre-cover {
    width: 100%;
    height: 220px;
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center <?= $cover_offset ?>px;
    border-radius: 6px;
    position: relative;
    overflow: hidden;
}
.centre-profile-wrap {
    position: relative;
}
.centre-profile {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    border: 4px solid white;
    position: absolute;
    bottom: -70px;
    left: 20px;
    object-fit: cover;
}
.centre-info {
    margin-top: 80px;
    padding-left: 20px;
}
.centre-name {
    font-size: 1.8em;
    font-weight: bold;
}
.centre-type {
    color: #555;
    font-size: 1.1em;
}
.preview-img-small {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #ccc;
}
.section-title {
    margin-top: 40px;
    font-size: 1.4em;
}
#coverControls {
    position:absolute;
    right: 20px;
    bottom: 10px;
    display:flex;
    gap:6px;
}
.reposition-active {
    cursor: grab;
    outline: 2px dashed #fff;
}
.reposition-active:active {
    cursor: grabbing;
}
</style>

<div class="content-block">

    <!-- COVER IMAGE -->
    <div class="centre-cover"
         id="coverImage"
         data-offset="<?= $cover_offset ?>"
         style="background-image:url('<?= htmlspecialchars($coverUrl) ?>');">

        <!-- Change Cover -->
        <div style="position:absolute; right:20px; top:20px;">
            <input type="file" id="uploadCover" style="display:none;">
            <button type="button" class="btn blue" data-file-input="uploadCover"><?= htmlspecialchars($lang['SETTINGS_CHANGE_COVER_IMAGE']) ?></button>
        </div>

        <!-- Reposition controls -->
        <div id="coverControls">
            <button type="button" class="btn orange" id="btnReposition"><?= htmlspecialchars($lang['SETTINGS_REPOSITION']) ?></button>
            <button type="button" class="btn green" id="btnSavePos" style="display:none;"><?= htmlspecialchars($lang['SAVE']) ?></button>
            <button type="button" class="btn grey" id="btnCancelPos" style="display:none;"><?= htmlspecialchars($lang['CANCEL']) ?></button>
        </div>
    </div>

    <!-- PROFILE IMAGE -->
    <div class="centre-profile-wrap">
        <img src="<?= htmlspecialchars($profileUrl) ?>" id="profileImage" class="centre-profile">

        <div style="position:absolute; left:180px; bottom:-40px;">
            <input type="file" id="uploadProfile" style="display:none;">
            <button type="button" class="btn blue" data-file-input="uploadProfile"><?= htmlspecialchars($lang['SETTINGS_CHANGE_PROFILE_PHOTO']) ?></button>
        </div>
    </div>

    <!-- CENTRE INFO -->
    <div class="centre-info">
        <div class="centre-name"><?= htmlspecialchars($centre['rescue_name']) ?></div>
        <div class="centre-type"><?= htmlspecialchars($centre['centre_type'] ?? '') ?></div>
    </div>

    <!-- LOGO -->
    <h3 class="section-title"><?= htmlspecialchars($lang['SETTINGS_CENTRE_BRANDING']) ?></h3>
    <div class="rc-card-grid">
        <div class="rc-card rc-card-muted">
            <h3><?= htmlspecialchars($lang['SETTINGS_CENTRE_LOGO']) ?></h3>
            <div class="rc-split-head">
                <img src="<?= htmlspecialchars($logoUrl) ?>" id="logoPreview" class="preview-img-small">
                <div class="rc-actions">
                    <input type="file" id="uploadLogo" style="display:none;">
                    <button type="button" class="btn blue" data-file-input="uploadLogo"><?= htmlspecialchars($lang['SETTINGS_CHANGE_LOGO']) ?></button>
                </div>
            </div>
        </div>
        <form action="controllers/centre_config_handler.php" method="post" class="xform rc-card rc-card-muted">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES) ?>">
            <input type="hidden" name="action" value="update_custom_colour">
            <h3><?= htmlspecialchars($lang['SETTINGS_CORPORATE_COLOUR']) ?></h3>
            <div class="xform-grid">
                <div class="xform-field span-2">
                    <label class="xform-label" for="customColourText"><?= htmlspecialchars($lang['SETTINGS_HEX_VALUE']) ?></label>
                    <input id="customColourText" type="text" name="custom_colour" class="xform-input"
                           value="<?= htmlspecialchars($custom_colour, ENT_QUOTES) ?>"
                           pattern="#[0-9A-Fa-f]{6}" maxlength="7" required>
                </div>
                <div class="xform-field">
                    <label class="xform-label" for="customColourPicker"><?= htmlspecialchars($lang['SETTINGS_PICKER']) ?></label>
                    <input id="customColourPicker" type="color" class="xform-input"
                           value="<?= htmlspecialchars($custom_colour, ENT_QUOTES) ?>">
                </div>
                <div class="xform-field">
                    <button type="submit" class="btn blue"><?= htmlspecialchars($lang['SETTINGS_SAVE_COLOUR']) ?></button>
                </div>
            </div>
        </form>
    </div>

    <!-- CENTRE DETAILS -->
    <h3 class="section-title"><?= htmlspecialchars($lang['SETTINGS_CENTRE_DETAILS']) ?></h3>
    <div class="xform-grid">
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['SETTINGS_EMAIL']) ?></label>
            <div><?= htmlspecialchars($centre['email']) ?></div>
        </div>
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['SETTINGS_OFFICE_TEL']) ?></label>
            <div><?= htmlspecialchars($centre['office_tel']) ?></div>
        </div>
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['SETTINGS_MOBILE']) ?></label>
            <div><?= htmlspecialchars($centre['mobile']) ?></div>
        </div>
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['SETTINGS_24H_TEL']) ?></label>
            <div><?= htmlspecialchars($centre['24_hour']) ?></div>
        </div>
        <div class="xform-field" style="grid-column: span 2;">
            <label class="xform-label"><?= htmlspecialchars($lang['ADDRESS']) ?></label>
            <div>
                <?= htmlspecialchars($centre['address_line_one']) ?><br>
                <?= htmlspecialchars($centre['address_line_two']) ?><br>
                <?= htmlspecialchars($centre['city']) ?> <?= htmlspecialchars($centre['postcode']) ?>
            </div>
        </div>
        <div class="xform-field" style="grid-column: span 2;">
            <label class="xform-label"><?= htmlspecialchars($lang['SETTINGS_OPENING_HOURS']) ?></label>
            <div><?= nl2br(htmlspecialchars($centre['opening_hours'])) ?></div>
        </div>
        <div class="xform-field" style="grid-column: span 2;">
            <label class="xform-label"><?= htmlspecialchars($lang['SETTINGS_ACCEPTING_ADMISSIONS']) ?></label>
            <div><?= htmlspecialchars($centre['accepting_admissions']) ?></div>
        </div>
    </div>

    <!-- CENTRE BIO -->
    <h3 class="section-title"><?= htmlspecialchars($lang['SETTINGS_ABOUT_CENTRE']) ?></h3>
    <form action="controllers/centre_config_handler.php" method="post" class="xform">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES) ?>">
        <input type="hidden" name="action" value="update_meta">

        <div class="xform-field" style="grid-column: span 2;">
            <label class="xform-label"><?= htmlspecialchars($lang['SETTINGS_CENTRE_BIO']) ?></label>
            <textarea name="centre_bio" class="xform-input" rows="8"><?= htmlspecialchars($meta['centre_bio']) ?></textarea>
        </div>

        <button type="submit" class="btn blue"><?= htmlspecialchars($lang['SETTINGS_SAVE_BIO']) ?></button>
    </form>

    <input type="hidden" id="cover_offset" value="<?= $cover_offset ?>">
</div>

<script>
// ---------------------
// CSRF helper
// ---------------------
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || "";

// ---------------------
// AJAX upload helper
// ---------------------
const coverDiv   = document.getElementById('coverImage');
const profileImg = document.getElementById('profileImage');
const logoImg    = document.getElementById('logoPreview');
const customColourText = document.getElementById('customColourText');
const customColourPicker = document.getElementById('customColourPicker');
const uploadFailedMessage = <?= json_encode($lang['SETTINGS_UPLOAD_FAILED']) ?>;
const positionSaveFailedMessage = <?= json_encode($lang['SETTINGS_POSITION_SAVE_FAILED']) ?>;

document.querySelectorAll('[data-file-input]').forEach(function (button) {
    button.addEventListener('click', function () {
        document.getElementById(this.dataset.fileInput)?.click();
    });
});

customColourPicker.addEventListener('input', function () {
    customColourText.value = this.value.toUpperCase();
});

customColourText.addEventListener('input', function () {
    const value = this.value.trim();
    if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
        customColourPicker.value = value;
    }
});

function uploadImage(type, fileInput, previewEl = null) {
    const file = fileInput.files[0];
    if (!file) return;

    const fd = new FormData();
    fd.append("action", "upload_image");
    fd.append("image_type", type);
    fd.append("image_file", file);
    // If you prefer POST token instead of header, uncomment:
    // fd.append("_csrf", CSRF_TOKEN);

    fetch("controllers/centre_config_handler.php", {
        method: "POST",
        headers: { "X-CSRF-Token": CSRF_TOKEN },
        body: fd
    })
    .then(r => r.text())
    .then(text => {
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error("Upload response not JSON:", text);
            alert(uploadFailedMessage);
            return;
        }

        if (!data.success) {
            alert(data.message || uploadFailedMessage);
            return;
        }

        if (type === "cover") {
            coverDiv.style.backgroundImage = "url('" + data.url.replace(/'/g, "\\'") + "')";
        } else if (previewEl) {
            previewEl.src = data.url;
        }
    })
    .catch(err => {
        console.error("Upload error:", err);
        alert(uploadFailedMessage);
    });
}

// ---------------------
// Client-side previews
// ---------------------
document.getElementById("uploadCover").addEventListener("change", function () {
    const file = this.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        coverDiv.style.backgroundImage = "url('" + e.target.result + "')";
    };
    reader.readAsDataURL(file);

    uploadImage("cover", this);
});

document.getElementById("uploadProfile").addEventListener("change", function () {
    const file = this.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        profileImg.src = e.target.result;
    };
    reader.readAsDataURL(file);

    uploadImage("profile", this, profileImg);
});

document.getElementById("uploadLogo").addEventListener("change", function () {
    const file = this.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        logoImg.src = e.target.result;
    };
    reader.readAsDataURL(file);

    uploadImage("logo", this, logoImg);
});

// ---------------------
// Drag-to-reposition cover
// ---------------------
const coverOffsetInput = document.getElementById('cover_offset');
const btnReposition    = document.getElementById('btnReposition');
const btnSavePos       = document.getElementById('btnSavePos');
const btnCancelPos     = document.getElementById('btnCancelPos');

let repositionMode = false;
let dragging       = false;
let dragStartY     = 0;
let startOffset    = parseInt(coverOffsetInput.value || "0", 10);
let originalOffset = startOffset;

function applyOffset(offset) {
    coverDiv.style.backgroundPosition = "center " + offset + "px";
    coverDiv.dataset.offset = offset;
    coverOffsetInput.value = offset;
}

// initial
applyOffset(startOffset);

btnReposition.addEventListener("click", function() {
    repositionMode = true;
    originalOffset = parseInt(coverOffsetInput.value || "0", 10);
    coverDiv.classList.add("reposition-active");
    btnReposition.style.display = "none";
    btnSavePos.style.display    = "inline-block";
    btnCancelPos.style.display  = "inline-block";
});

btnCancelPos.addEventListener("click", function() {
    repositionMode = false;
    dragging       = false;
    applyOffset(originalOffset);
    coverDiv.classList.remove("reposition-active");
    btnReposition.style.display = "inline-block";
    btnSavePos.style.display    = "none";
    btnCancelPos.style.display  = "none";
});

btnSavePos.addEventListener("click", function() {
    const offset = parseInt(coverOffsetInput.value || "0", 10);

    const fd = new FormData();
    fd.append("action", "save_cover_offset");
    fd.append("cover_offset", offset);
    // If you prefer POST token instead of header, uncomment:
    // fd.append("_csrf", CSRF_TOKEN);

    fetch("controllers/centre_config_handler.php", {
        method: "POST",
        headers: { "X-CSRF-Token": CSRF_TOKEN },
        body: fd
    })
    .then(r => r.text())
    .then(text => {
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error("Offset response not JSON:", text);
            alert(positionSaveFailedMessage);
            return;
        }

        if (!data.success) {
            alert(positionSaveFailedMessage);
            return;
        }

        repositionMode = false;
        dragging       = false;
        coverDiv.classList.remove("reposition-active");
        btnReposition.style.display = "inline-block";
        btnSavePos.style.display    = "none";
        btnCancelPos.style.display  = "none";
    })
    .catch(err => {
        console.error("Offset save error:", err);
        alert(positionSaveFailedMessage);
    });
});

coverDiv.addEventListener("mousedown", function(e) {
    if (!repositionMode) return;
    dragging   = true;
    dragStartY = e.clientY;
    startOffset = parseInt(coverOffsetInput.value || "0", 10);
});

document.addEventListener("mousemove", function(e) {
    if (!dragging || !repositionMode) return;
    const delta = e.clientY - dragStartY;
    const newOffset = startOffset + delta;
    applyOffset(newOffset);
});

document.addEventListener("mouseup", function() {
    dragging = false;
});
</script>
