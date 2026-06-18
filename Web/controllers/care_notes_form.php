<?php
// controllers/care_notes_form.php

// URL prefixes for existing images (legacy vs new)
$LEGACY_BASE = 'https://legacy.rescuecentre.org.uk/wp-content/themes/brikk-child/';
$NEW_BASE    = 'https://myrescuecentre.com/';

// Load images safely (include is_legacy for correct prefix)
$imgStmt = $pdo->prepare("
    SELECT image_id, image_url, file_name, is_legacy
    FROM rescue_images
    WHERE patient_id = :pid
    ORDER BY image_id ASC
");
$imgStmt->execute([':pid' => (int)$patient_id]);
$images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
// Ensure session exists (likely already started in your includes, but safe)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Create token if missing
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


?>

<form action="controllers/form_handler.php" method="post" class="xform" id="carenoteform" enctype="multipart/form-data">

    <!-- WRAP IN 2-COLUMN LAYOUT -->
    <div class="xform-grid">

        <!-- ===========================
             LEFT COLUMN — FORM FIELDS
        ============================ -->
        <div class="xform-field" style="grid-column: span 2;">

            <div class="xform-field" style="grid-column: span 2;">
                <label class="xform-label" for="new_note"><?= htmlspecialchars($lang['CARE_NOTE']) ?></label>
                <textarea name="new_note" id="new_note" class="xform-input" rows="4" required></textarea>
            </div>

            <!-- AUTHOR -->
            <div class="xform-field">
                <label class="xform-label" for="note_author"><?= htmlspecialchars($lang['AUTHOR']) ?></label>
                <input type="text" name="note_author" id="note_author" class="xform-input"
                       value="<?= htmlspecialchars((string)$record_name, ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <!-- PUBLIC TOGGLE -->
            <div class="xform-field">
                <label class="xform-label"><?= htmlspecialchars($lang['PUBLIC']) ?>?</label><br>

                <label class="switch">
                    <input type="checkbox" name="public" value="1">
                    <span class="slider round"></span>
                </label>

            </div>

            <!-- SUBMIT BUTTON -->
            <div class="xform-field" style="grid-column: span 2; margin-top:10px;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="patient_id" value="<?= (int)$patient_id ?>">
                <input type="hidden" name="audit_action" value="Care note added for CRN-<?= $patient_id ?>">
                <button type="submit" name="care_note_form" class="btn blue">
                    <?= htmlspecialchars($lang['SAVE'] . ' ' . $lang['CARE_NOTE']) ?>
                </button>
            </div>

        </div>


        <!-- ===========================
             RIGHT COLUMN — GALLERY + UPLOAD
        ============================ -->
        <div class="xform-field" style="grid-column: span 2;">

            <label class="xform-label"><?= htmlspecialchars($lang['ATTACH'] . ' ' . $lang['IMAGE'] . ' (' . strtolower($lang['OPTIONAL']) . ')') ?></label>

            <!-- Upload input (uploads ONLY on Save) -->
            <div class="xform-field" style="margin:6px 0 10px 0;">
                <input type="file"
                       name="care_note_image"
                       id="care_note_image"
                       class="xform-input"
                       accept="image/jpeg,image/png,image/webp">
                <div style="font-size:0.8rem; opacity:0.8; margin-top:6px;">
                    <?= htmlspecialchars($lang['CN_IMAGE_HELP']) ?>
                </div>
            </div>

            <!-- Gallery always rendered so the upload preview can appear here -->
            <div class="cngallery">

                <!-- Left Arrow -->
                <button type="button" class="cng-left">&#10094;</button>

                <!-- Track -->
                <div class="cng-track">

                    <!-- Sentinel radio for "no existing image / upload preview selected" -->
                    <input type="radio" name="image_id" value="0" id="cn_image_none" checked style="display:none;">

                    <?php foreach ($images as $img):
                        $rel  = ltrim((string)$img['image_url'], '/');
                        $base = ((int)$img['is_legacy'] === 1) ? $LEGACY_BASE : $NEW_BASE;
                        $src  = $base . $rel;
                    ?>
                        <div class="cng-item" style="flex:0 0 25%; padding:5px; height:150px;">
                            <label>
                                <input type="radio" name="image_id" value="<?= (int)$img['image_id'] ?>">
                                <img src="<?= htmlspecialchars($src, ENT_QUOTES, 'UTF-8') ?>"
                                     alt="<?= htmlspecialchars((string)$img['file_name'], ENT_QUOTES, 'UTF-8') ?>">
                            </label>
                        </div>
                    <?php endforeach; ?>

                </div>

                <!-- Right Arrow -->
                <button type="button" class="cng-right">&#10095;</button>

                <!-- Overlay -->
                <div class="cn-overlay">
                    <img src="">
                </div>

            </div>

            <?php if (empty($images)): ?>
                <div style="font-size:0.85rem; opacity:0.75; margin-top:8px;">
                    <?= htmlspecialchars($lang['CN_NO_IMAGES']) ?>
                </div>
            <?php endif; ?>

        </div>

    </div> <!-- END GRID -->

</form>


<script>
document.addEventListener("DOMContentLoaded", () => {

    // ==========================
    // Upload -> preview in gallery
    // ==========================
    const fileInput = document.getElementById("care_note_image");
    const noneRadio = document.getElementById("cn_image_none");

    // Target rules (no warnings; just handle it)
    const TARGET_MAX_BYTES = 2 * 1024 * 1024;   // aim ~2MB
    const MAX_DIM          = 2000;              // preserve aspect ratio, fit within 2000px
    const MIN_QUALITY      = 0.55;

    function clearUpload(gallery) {
        if (fileInput) fileInput.value = "";
        const existing = gallery.querySelector(".cng-upload-item");
        if (existing) existing.remove();
        if (noneRadio) noneRadio.checked = true;
    }

    function setInputFileFromBlob(blob, filename) {
        try {
            const file = new File([blob], filename, { type: blob.type || "image/jpeg" });
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
        } catch (e) {
            // If browser blocks setting File input programmatically, we fall back to original selection.
        }
    }

    async function compressImageToJpeg(file) {
        // Load image
        const img = new Image();
        const url = URL.createObjectURL(file);

        const loaded = await new Promise((resolve, reject) => {
            img.onload = () => resolve(true);
            img.onerror = reject;
            img.src = url;
        }).catch(() => false);

        if (!loaded) {
            URL.revokeObjectURL(url);
            return null;
        }

        // Compute resized dimensions (aspect ratio preserved)
        const srcW = img.naturalWidth || img.width;
        const srcH = img.naturalHeight || img.height;

        let scale = 1;
        const maxSide = Math.max(srcW, srcH);
        if (maxSide > MAX_DIM) scale = MAX_DIM / maxSide;

        const dstW = Math.max(1, Math.round(srcW * scale));
        const dstH = Math.max(1, Math.round(srcH * scale));

        const canvas = document.createElement("canvas");
        canvas.width = dstW;
        canvas.height = dstH;

        const ctx = canvas.getContext("2d", { alpha: false });
        // White background for transparency-safe conversion
        ctx.fillStyle = "#fff";
        ctx.fillRect(0, 0, dstW, dstH);
        ctx.drawImage(img, 0, 0, dstW, dstH);

        URL.revokeObjectURL(url);

        // Encode with quality tuning to hit target size (best-effort)
        let qHigh = 0.88;
        let qLow  = MIN_QUALITY;
        let best  = null;

        for (let i = 0; i < 7; i++) {
            const q = (qHigh + qLow) / 2;

            const blob = await new Promise(resolve => {
                canvas.toBlob(b => resolve(b), "image/jpeg", q);
            });

            if (!blob) break;

            best = blob;

            if (blob.size > TARGET_MAX_BYTES) {
                qHigh = q; // need smaller -> reduce quality
            } else {
                qLow = q;  // can afford higher quality
            }
        }

        return best;
    }

    function injectUploadPreview(gallery, previewSrc, displayName) {
        const track = gallery.querySelector(".cng-track");
        if (!track) return;

        // Remove any existing upload preview tile
        track.querySelector(".cng-upload-item")?.remove();

        // Build tile (same sizing as others)
        const wrap = document.createElement("div");
        wrap.className = "cng-item cng-upload-item";
        wrap.style.cssText = "flex:0 0 25%; padding:5px; height:150px; position:relative;";

        // Label wrapper consistent with existing structure
        const label = document.createElement("label");
        label.style.cssText = "display:block; height:100%; position:relative;";

        // Radio uses value 0 (handler will attach upload regardless; this keeps UI selection consistent)
        const radio = document.createElement("input");
        radio.type = "radio";
        radio.name = "image_id";
        radio.value = "0";
        radio.checked = true;

        // Image element
        const img = document.createElement("img");
        img.src = previewSrc;
        img.alt = displayName || "New upload";
        img.style.cssText = "max-width:100%; max-height:100%; object-fit:cover; display:block;";

        // Remove button
        const removeBtn = document.createElement("button");
        removeBtn.type = "button";
        removeBtn.textContent = "×";
        removeBtn.title = <?= json_encode($lang['CN_REMOVE_UPLOAD']) ?>;
        removeBtn.style.cssText = `
            position:absolute;
            top:8px;
            right:8px;
            width:28px;
            height:28px;
            border-radius:999px;
            border:1px solid rgba(0,0,0,0.2);
            background:rgba(255,255,255,0.95);
            cursor:pointer;
            font-size:18px;
            line-height:24px;
            padding:0;
        `;

        removeBtn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            // Clear file input and remove the tile, nothing uploads
            clearUpload(gallery);
        });

        // Small "NEW" badge
        const badge = document.createElement("div");
        badge.textContent = "NEW";
        badge.style.cssText = `
            position:absolute;
            bottom:8px;
            left:8px;
            padding:2px 6px;
            border-radius:6px;
            font-size:11px;
            font-weight:700;
            background:rgba(0,0,0,0.65);
            color:#fff;
        `;

        label.appendChild(radio);
        label.appendChild(img);
        label.appendChild(removeBtn);
        label.appendChild(badge);
        wrap.appendChild(label);

        // Insert upload tile at the START of the track so it’s immediately visible
        track.insertBefore(wrap, track.firstChild);

        // Also ensure the hidden noneRadio isn’t left checked
        if (noneRadio) noneRadio.checked = false;
        radio.checked = true;
    }

    // When user picks a file:
    // - compress it (best-effort)
    // - show as preview tile in gallery and auto-select it
    fileInput?.addEventListener("change", async () => {
        const f = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
        if (!f) return;

        const gallery = document.querySelector(".cngallery");
        if (!gallery) return;

        // Best-effort client-side compression (no warnings)
        const compressed = await compressImageToJpeg(f);

        if (compressed) {
            // Replace the file input payload with compressed JPG so server always receives a manageable file
            const base = (f.name || "upload").replace(/\.[^/.]+$/, "");
            const safeName = (base || "upload").replace(/[^A-Za-z0-9\-_]+/g, "-").replace(/-+/g, "-").replace(/^-|-$/g, "");
            const filename = (safeName || "upload") + ".jpg";
            setInputFileFromBlob(compressed, filename);

            // Preview uses blob URL from compressed payload
            const previewUrl = URL.createObjectURL(compressed);
            injectUploadPreview(gallery, previewUrl, filename);
        } else {
            // If compression fails, still preview original selection
            const previewUrl = URL.createObjectURL(f);
            injectUploadPreview(gallery, previewUrl, f.name || "upload");
        }
    });

    // ==========================
    // Existing gallery JS (your logic, extended slightly)
    // - selecting existing image clears any pending upload
    // ==========================
    document.querySelectorAll(".cngallery").forEach(gallery => {

        const track   = gallery.querySelector(".cng-track");
        const overlay = gallery.querySelector(".cn-overlay");
        const imgTag  = overlay.querySelector("img");

        let offset = 0;

        const visible   = 4;

        function refreshItems() {
            return gallery.querySelectorAll(".cng-item");
        }

        function maxOffsetFor(items) {
            return Math.max(0, items.length - visible);
        }

        // LEFT
        gallery.querySelector(".cng-left")?.addEventListener("click", () => {
            const items = refreshItems();
            const maxOffset = maxOffsetFor(items);

            offset = Math.max(0, offset - 1);
            track.style.transform = `translateX(${-(offset * 25)}%)`;
            // clamp
            if (offset > maxOffset) offset = maxOffset;
        });

        // RIGHT
        gallery.querySelector(".cng-right")?.addEventListener("click", () => {
            const items = refreshItems();
            const maxOffset = maxOffsetFor(items);

            offset = Math.min(maxOffset, offset + 1);
            track.style.transform = `translateX(${-(offset * 25)}%)`;
        });

        // IMAGE CLICK → Preview (and selection behaviour)
        gallery.addEventListener("click", (e) => {
            const img = e.target.closest(".cng-item img");
            if (!img) return;

            // If clicked an existing image (not the upload preview), clear any pending upload
            const isUploadTile = img.closest(".cng-upload-item");
            if (!isUploadTile) {
                // Clear file input + remove upload tile (so nothing uploads unless re-selected)
                clearUpload(gallery);
            }

            // Ensure radio checks
            const radio = img.closest("label")?.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;

            // Overlay preview
            imgTag.src = img.src;
            overlay.style.display = "flex";
        });

        // CLOSE PREVIEW
        overlay.addEventListener("click", () => {
            overlay.style.display = "none";
            imgTag.src = "";
        });

    });

});
</script>
