<?php
// ------------------------------------------------------------
// VIEW ALL THE PATIENT'S LAB RESULTS
// ------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT *
    FROM rescue_labs
    LEFT JOIN rescue_labs_tests
        ON rescue_labs_tests.l_test_id = rescue_labs.lab_test
    LEFT JOIN rescue_sample_types
        ON rescue_sample_types.s_type_id = rescue_labs.sample_type
    WHERE patient_id = :patient_id
    ORDER BY lab_date DESC
");
$stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
$stmt->execute();
$lab_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (empty($lab_results)): ?>

    <div class="rc-alert blue">
        <strong>Laboratory Results</strong><br>
        No laboratory results recorded for this patient.
    </div>

<?php else: ?>

    <?php foreach ($lab_results as $row): ?>

        <?php
            $labs_date        = $row["lab_date"];
            $lab_sample_type  = $row["sample_type"];
            $lab_result       = $row["lab_result"];
            $lab_reported_by  = $row["reported_by"];
            $lab_test         = $row["lab_test"];
            $lab_category     = $row["lab_category"];

            // NEW FLAGS (from rescue_labs + rescue_labs_tests join)
            $is_positive   = isset($row["is_positive"]) ? (int)$row["is_positive"] : 0;
            $is_notifiable = isset($row["is_notifiable"]) ? (int)$row["is_notifiable"] : 0;

            $lab_format_date = (new DateTime($labs_date))->format('d-m-Y');
            $lab_format_time = (new DateTime($labs_date))->format('H:i');
        ?>

        <!-- ==========================================================
             LAB RESULT — COMPACT 3 COLUMN ALERT
        ========================================================== -->
        <div class="rc-card rc-card-muted">

            <table class="rc-table">
                <colgroup>
                    <!-- FLAGS -->
                    <col style="width:46px;">
                    <!-- Test -->
                    <col style="width:42%;">
                    <!-- Result -->
                    <col style="width:38%;">
                    <!-- Date -->
                    <col style="width:20%;">
                </colgroup>

                <thead>
                    <tr style="font-size:0.75rem; opacity:0.85;">
                        <th align="left"></th>
                        <th align="left">Test</th>
                        <th align="left">Result</th>
                        <th align="left">Date</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <!-- FLAGS COLUMN -->
                        <td style="padding:4px 8px 4px 0; vertical-align:top;">
                            <?php if ($is_positive === 1): ?>
                                <span title="Positive result"
                                      style="display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; border-radius:4px; font-weight:700; line-height:1; margin-right:6px;">
                                    +
                                </span>
                            <?php endif; ?>

                            <?php if ($is_notifiable === 1): ?>
                                <span title="Notifiable test"
                                      style="display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; border-radius:4px; font-weight:700; line-height:1;">
                                    &#9888;<!-- warning triangle -->
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- COLUMN 1 -->
                        <td style="padding:4px 8px 4px 0; vertical-align:top; overflow:hidden; text-overflow:ellipsis;">
                            <strong><?= htmlspecialchars($lab_test) ?></strong><br>
                            <span style="font-size:0.8em;">
                                <?= htmlspecialchars($lab_category) ?>
                            </span>
                        </td>

                        <!-- COLUMN 2 -->
                        <td style="padding:4px 8px; vertical-align:top; overflow:hidden;">
                            <strong><?= nl2br(htmlspecialchars($lab_result)) ?></strong><br>
                            <span style="font-size:0.8em;">
                                Sample: <?= htmlspecialchars($lab_sample_type) ?>
                            </span>
                        </td>

                        <!-- COLUMN 3 -->
                        <td style="padding:4px 0; vertical-align:top; white-space:nowrap;">
                            <?= htmlspecialchars($lab_format_date) ?>
                            <strong><?= htmlspecialchars($lab_format_time) ?></strong><br>
                            <span style="font-size:0.8em;">
                                <?= htmlspecialchars($lab_reported_by) ?>
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

<!-- ==========================================================
     ADD LAB RESULT FORM
========================================================== -->
<div class="rc-card rc-card-muted">
    <?php include 'controllers/add_labs.php'; ?>
</div>
