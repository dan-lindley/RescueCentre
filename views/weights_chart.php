<?php
    //gets the targetsizes from the table to display 
    $stmt = $pdo->prepare("SELECT * FROM rescue_patients
                            RIGHT JOIN rescue_animal_species ON rescue_animal_species.species_name = rescue_patients.animal_species
							WHERE patient_id = :patient_id");
    $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

    // initialise an array for the results
    $target_sizes = array();
    $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $animal_species = $row["animal_species"];
				$species_weight_from = $row["species_weight_from"];
				$species_weight_to = $row["species_weight_to"];
				$species_weight_unit = $row["species_weight_unit"];
				$scientific_name = $row["scientific_name"];
				$reference = $row["reference"];
                                        
        print '
                 A typical adult ' . $animal_species . ' <I> (' . $scientific_name . ')</i> should weigh between <strong> ' . $species_weight_from . '' . $species_weight_unit . '  </strong> and
				<strong> ' . $species_weight_to . '' . $species_weight_unit . ' </strong> <BR>Reference: <i> ' . $reference . ' </i>
                                    ';
                                    }
                                    ?>	
<canvas id="weightchart"></canvas>

<?php
/* -------------------------------------------------------
   WEIGHT CHART ARRAYS
   1) Baseline = ACTIVE admission (rescue_admissions)
   2) Follow-ups = rescue_weights
-------------------------------------------------------- */

// Format label like "31st May 23"
$makeLabel = function(string $dt): string {
    $ts = strtotime($dt);
    if (!$ts) return 'Admission';
    $day = (int)date('j', $ts);
    $suffix = ($day == 1 || $day == 21 || $day == 31) ? 'st' :
              (($day == 2 || $day == 22) ? 'nd' :
              (($day == 3 || $day == 23) ? 'rd' : 'th'));
    return $day . $suffix . ' ' . date('M y', $ts);
};

$weightLabels = [];
$patientWeights = [];

/* 1) ACTIVE admission baseline */
$admStmt = $pdo->prepare("
    SELECT admission_date, weight, weight_unit
    FROM rescue_admissions
    WHERE patient_id = :patient_id
      AND status = 'Active'
    ORDER BY admission_date DESC
    LIMIT 1
");
$admStmt->bindValue(':patient_id', (int)$patient_id, PDO::PARAM_INT);
$admStmt->execute();
$adm = $admStmt->fetch(PDO::FETCH_ASSOC);

// If for any reason there is no Active row, fall back to latest admission
if (!$adm) {
    $admStmt2 = $pdo->prepare("
        SELECT admission_date, weight, weight_unit
        FROM rescue_admissions
        WHERE patient_id = :patient_id
        ORDER BY admission_date DESC
        LIMIT 1
    ");
    $admStmt2->bindValue(':patient_id', (int)$patient_id, PDO::PARAM_INT);
    $admStmt2->execute();
    $adm = $admStmt2->fetch(PDO::FETCH_ASSOC);
}

// Add admission weight as the FIRST point (if present)
if ($adm && $adm['weight'] !== null && $adm['weight'] !== '') {
    $weightLabels[] = $makeLabel((string)$adm['admission_date']);
    $patientWeights[] = (float)$adm['weight'];
}

/* 2) Append follow-up weights */
$wStmt = $pdo->prepare("
    SELECT date, weight
    FROM rescue_weights
    WHERE patient_id = :patient_id
    ORDER BY date ASC
");
$wStmt->bindValue(':patient_id', (int)$patient_id, PDO::PARAM_INT);
$wStmt->execute();

while ($row = $wStmt->fetch(PDO::FETCH_ASSOC)) {
    if (empty($row['date'])) continue;
    $weightLabels[] = $makeLabel((string)$row['date']);
    $patientWeights[] = (float)($row['weight'] ?? 0);
}

// Safety fallback (should never be needed if admissions always has weight)
if (empty($weightLabels)) {
    $weightLabels = ['Admission'];
    $patientWeights = [0];
}
?>

<script>
    const weightLabels   = <?= json_encode($weightLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const patientWeights = <?= json_encode($patientWeights, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const minRef = <?= isset($species_weight_from) ? (float)$species_weight_from : 0 ?>;
    const maxRef = <?= isset($species_weight_to) ? (float)$species_weight_to : 0 ?>;

    const minLine = Array(weightLabels.length).fill(minRef);
    const maxLine = Array(weightLabels.length).fill(maxRef);

    new Chart(document.getElementById("weightchart"), {
        type: 'line',
        data: {
            labels: weightLabels,
            datasets: [
                {
                    label: "<?= htmlspecialchars($patient_name, ENT_QUOTES, 'UTF-8') ?>'s Weight",
                    data: patientWeights,
                    borderColor: "#148805",
                    borderWidth: 2,
                    fill: false,
                    tension: 0.3
                },
                {
                    label: "Lower",
                    data: minLine,
                    borderColor: "red",
                    borderWidth: 0.5,
                    fill: false,
                    pointRadius: 0,
                    tension: 0
                },
                {
                    label: "Upper",
                    data: maxLine,
                    borderColor: "blue",
                    borderWidth: 0.5,
                    fill: false,
                    pointRadius: 0,
                    tension: 0
                },
                {
                    label: "Optimum Range",
                    data: maxLine,
                    backgroundColor: "rgba(0, 200, 0, 0.05)",
                    borderWidth: 0,
                    fill: { target: 1 }
                }
            ]
        },
        options: {
            plugins: { legend: { display: true } },
            scales: { y: { beginAtZero: false } }
        }
    });
</script>



<?php
/* -------------------- DELETE WEIGHT RECORD -------------------- */
if (isset($_POST['weight_delete']) && !empty($_POST['wt_id'])) {
    $delete_id = (int) $_POST['wt_id'];

    $del = $pdo->prepare("DELETE FROM rescue_weights WHERE weight_id = :id LIMIT 1");
    $del->execute([':id' => $delete_id]);
}

/* -------------------- GET ALL WEIGHTS (NO PAGINATION) -------------------- */
$stmt = $pdo->prepare("
    SELECT * 
    FROM rescue_weights
    WHERE patient_id = :patient_id
    ORDER BY date DESC
");

$stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
$stmt->execute();
$weights = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<br>


<?php if (empty($weights)): ?>

    <div class="alert-box alert-purple" style="margin-bottom: 12px;">
        <strong>Weights & Measurements</strong><br>
        No weight records recorded for this patient.
    </div>

<?php else: ?>

    <!-- ==========================================================
         HEADER ROW — GREY ALERT
    ========================================================== -->
<div class="alert-box alert-grey"
     style="margin-bottom: 6px; padding: 6px 12px; font-size: 0.75rem; opacity: 0.9;">

    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr>
                <th align="left" style="width:180px;">Date Added</th>

                <!-- Weight header pushed right -->
                <th align="left" style="padding-left:50px;">
                    Weight
                </th>

                <!-- Action header aligned right -->
                <th align="right" style="width:90px;">
                    Action
                </th>
            </tr>
        </thead>
    </table>

</div>

    <?php foreach ($weights as $row): ?>

        <?php
            $wt_format_date = (new DateTime($row["date"]))->format('d-m-Y H:i');
        ?>

        <!-- ==========================================================
             WEIGHT ENTRY — PURPLE ALERT
        ========================================================== -->
        <div class="alert-box alert-purple"
     style="margin-bottom: 6px; padding: 8px 12px;">

    <table style="width:100%; border-collapse:collapse;">
        <tbody>
            <tr>
                <!-- Date -->
                <td style="width:180px; white-space:nowrap;">
                    <?= htmlspecialchars($wt_format_date) ?>
                </td>

                <!-- Weight (shifted right) -->
                <td style="padding-left:50px;">
                    <strong>
                        <?= htmlspecialchars($row["weight"] . $row["weight_unit"]) ?>
                    </strong>
                </td>

                <!-- Action (hard right) -->
                <td style="width:90px; text-align:right; white-space:nowrap;">
                    <form method="post"
                          style="margin:0; padding:0;"
                          onsubmit="return confirm('Delete this entry?');">
                        <input type="hidden"
                               name="wt_id"
                               value="<?= (int)$row["weight_id"]; ?>">
                        <button type="submit"
                                name="weight_delete"
                                class="btn red"
                                style="padding: 2px 6px; font-size: 0.75rem; line-height: 1;">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
        </tbody>
    </table>

</div>

    <?php endforeach; ?>

<?php endif; ?>


<!-- Pagination Controls (JS Only) -->
<div id="weightPagination" class="pagination"></div>

<script>
// -----------------------------
// JAVASCRIPT PAGINATION SYSTEM
// (ALERT-BASED)
// -----------------------------

const rowsPerPage = 5;

// ✅ Select purple alert rows directly
const rows = Array.from(
    document.querySelectorAll(".alert-box.alert-purple")
);

const paginationContainer = document.getElementById("weightPagination");

let currentPage = 1;
const totalPages = Math.ceil(rows.length / rowsPerPage);

// Safety guard
if (!rows.length || !paginationContainer) {
    // No pagination needed
    paginationContainer.innerHTML = "";
} else {

    function showPage(page) {
        currentPage = page;

        // Hide all rows
        rows.forEach(r => r.style.display = "none");

        // Show selected rows
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;

        for (let i = start; i < end && i < rows.length; i++) {
            rows[i].style.display = "block";
        }

        renderPagination();
    }

    function renderPagination() {
        paginationContainer.innerHTML = "";

        // Previous
        const prev = document.createElement("button");
        prev.innerHTML = "Previous";
        prev.className = "btn grey smallbtn";
        prev.disabled = currentPage === 1;
        prev.onclick = () => showPage(currentPage - 1);
        paginationContainer.appendChild(prev);

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement("button");
            btn.innerHTML = i;
            btn.className = "btn smallbtn " + (i === currentPage ? "primary" : "grey");
            btn.style.marginLeft = "5px";
            btn.onclick = () => showPage(i);
            paginationContainer.appendChild(btn);
        }

        // Next
        const next = document.createElement("button");
        next.innerHTML = "Next";
        next.className = "btn grey smallbtn";
        next.disabled = currentPage === totalPages;
        next.onclick = () => showPage(currentPage + 1);
        next.style.marginLeft = "5px";
        paginationContainer.appendChild(next);
    }

    // Initialise
    showPage(1);
}
</script>
