<?php
// views/carenotes.php

$LEGACY_BASE = 'https://legacy.rescuecentre.org.uk/wp-content/themes/brikk-child/';
$NEW_BASE    = 'https://myrescuecentre.com/';

// ------------------------------------------------------------
// GET EXISTING CARE NOTES
// ------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT * 
    FROM rescue_notes_patients 
    LEFT JOIN rescue_images 
        ON rescue_notes_patients.image_id = rescue_images.image_id
    WHERE rescue_notes_patients.patient_id = :patient_id
      AND deleted = 0
    ORDER BY date DESC
");
$stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
$stmt->execute();

// Fetch all notes (required for empty-state handling)
$carenotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="rc-card rc-card-muted" style="margin-bottom:12px;">
    <?php include __DIR__ . '/../controllers/care_notes_form.php'; ?>
</div>

<?php if (empty($carenotes)): ?>

    <!-- ======================================================
         EMPTY STATE — NO CARE NOTES
    ======================================================= -->
    <div class="rc-alert purple">
        <strong>Care Notes</strong><br>
        No care notes have been recorded for this patient.
    </div>

<?php else: ?>

    <?php foreach ($carenotes as $row): ?>

        <?php
            $note_id       = $row["note_id"];
            $note_message  = $row["message"];
            $note_date     = $row["date"];
            $note_author   = $row["author"];
            $public        = $row["public"];

            $imageurl      = $row["image_url"];
            $imagename     = $row["file_name"];
            $image_id      = $row["image_id"];
            $is_legacy     = isset($row["is_legacy"]) ? (int)$row["is_legacy"] : 0;

            $formatted_date = new DateTime($note_date);
            $formatted_date = $formatted_date->format('jS \o\f F Y');
            $formatted_time = (new DateTime($note_date))->format('H:i');

            $full_src = '';
            if (!empty($imageurl)) {
                $rel = ltrim((string)$imageurl, '/');
                $base = ($is_legacy === 1) ? $LEGACY_BASE : $NEW_BASE;
                $full_src = $base . $rel;
            }
        ?>

        <!-- ======================================================
             CARE NOTE — PINK ALERT
        ======================================================= -->
        <div class="alert-box alert-pink" style="margin-bottom: 6px; padding: 8px 12px;">

            <div style="display:flex; gap:12px; align-items:flex-start;">

                <!-- COLUMN 1 — IMAGE (75 x 75) -->
                <div style="width:75px; flex-shrink:0; text-align:center;">
                    <?php if (!empty($full_src)): ?>
                        <a href="<?= htmlspecialchars($full_src, ENT_QUOTES, 'UTF-8') ?>"
                           target="_blank"
                           title="<?= htmlspecialchars($imagename ?: 'Click to view full image', ENT_QUOTES, 'UTF-8') ?>">
                            <img src="<?= htmlspecialchars($full_src, ENT_QUOTES, 'UTF-8') ?>"
                                 alt="<?= htmlspecialchars($imagename ?: 'Care note image', ENT_QUOTES, 'UTF-8') ?>"
                                 style="
                                    width:75px;
                                    height:75px;
                                    object-fit:cover;
                                    border-radius:4px;
                                    cursor:pointer;
                                 ">
                        </a>
                    <?php endif; ?>
                </div>

                <!-- COLUMN 2 — NOTE CONTENT -->
                <div style="flex:1;">

                    <div style="margin-bottom:6px;">
                        <?php echo nl2br(htmlspecialchars($note_message, ENT_QUOTES, 'UTF-8')); ?>
                    </div>

                    <div style="font-size:0.8rem; opacity:0.9;">
                        Care note added by <strong><?php echo htmlspecialchars($note_author, ENT_QUOTES, 'UTF-8'); ?></strong><br>
                        Visible to public: <strong><?php echo htmlspecialchars((string)$public, ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>

                </div>

                <!-- COLUMN 3 — DATE / TIME -->
                <div style="width:110px; text-align:right; font-size:0.8rem; line-height:1.2;">
                    <div><?php echo htmlspecialchars($formatted_date, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div style="font-weight:600;">
                        <?php echo htmlspecialchars($formatted_time, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>

            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>
