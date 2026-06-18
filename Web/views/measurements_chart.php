

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
	$species_measurement_from = $row["species_measurement_from"];
	$species_measurement_to = $row["species_measurement_to"];
	$species_measurement_unit = $row["species_measurement_unit"];
	$scientific_name = $row["scientific_name"];
	$reference = $row["reference"];
	$species_measurement_standard = $row["species_measurement_standard"];
    
    print '
            A typical adult ' . $animal_species . ' <I> (' . $scientific_name . ')</i> ' . $species_measurement_standard . ' should measure between <strong> ' . $species_measurement_from . '' . $species_measurement_unit . '  </strong> and
			<strong> ' . $species_measurement_to . '' . $species_measurement_unit . ' </strong> <BR>Reference: <i> ' . $reference . ' </i>
              ';
               }
              ?>

<canvas id="measurementchart"></canvas>

<?php
/* -------------------------------------------------------
   MEASUREMENT CHART ARRAYS
   1) Baseline = ACTIVE admission (rescue_admissions)
   2) Follow-ups = rescue_measurements
-------------------------------------------------------- */

$makeLabel = function(string $dt): string {
    $ts = strtotime($dt);
    if (!$ts) return 'Admission';
    $day = (int)date('j', $ts);
    $suffix = ($day == 1 || $day == 21 || $day == 31) ? 'st' :
              (($day == 2 || $day == 22) ? 'nd' :
              (($day == 3 || $day == 23) ? 'rd' : 'th'));
    return $day . $suffix . ' ' . date('M y', $ts);
};

$measurementLabels = [];
$patientMeasurements = [];

/* 1) ACTIVE admission baseline */
$admStmt = $pdo->prepare("
    SELECT admission_date, measurement, measurement_unit
    FROM rescue_admissions
    WHERE patient_id = :patient_id
      AND status = 'Active'
    ORDER BY admission_date DESC
    LIMIT 1
");
$admStmt->bindValue(':patient_id', (int)$patient_id, PDO::PARAM_INT);
$admStmt->execute();
$adm = $admStmt->fetch(PDO::FETCH_ASSOC);

// Fallback: if no Active row, use latest admission
if (!$adm) {
    $admStmt2 = $pdo->prepare("
        SELECT admission_date, measurement, measurement_unit
        FROM rescue_admissions
        WHERE patient_id = :patient_id
        ORDER BY admission_date DESC
        LIMIT 1
    ");
    $admStmt2->bindValue(':patient_id', (int)$patient_id, PDO::PARAM_INT);
    $admStmt2->execute();
    $adm = $admStmt2->fetch(PDO::FETCH_ASSOC);
}

// Add admission measurement as FIRST point (if present)
if ($adm && $adm['measurement'] !== null && $adm['measurement'] !== '') {
    $measurementLabels[] = $makeLabel((string)$adm['admission_date']);
    $patientMeasurements[] = (float)$adm['measurement'];
}

/* 2) Append follow-up measurements */
$mStmt = $pdo->prepare("
    SELECT date, measurement
    FROM rescue_measurements
    WHERE patient_id = :patient_id
    ORDER BY date ASC
");
$mStmt->bindValue(':patient_id', (int)$patient_id, PDO::PARAM_INT);
$mStmt->execute();

while ($row = $mStmt->fetch(PDO::FETCH_ASSOC)) {
    if (empty($row['date'])) continue;
    $measurementLabels[] = $makeLabel((string)$row['date']);
    $patientMeasurements[] = (float)($row['measurement'] ?? 0);
}

// Safety fallback (shouldn’t be needed if admissions always has measurement)
if (empty($measurementLabels)) {
    $measurementLabels = ['Admission'];
    $patientMeasurements = [0];
}
?>

<script>
    const measurementLabels   = <?= json_encode($measurementLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const patientMeasurements = <?= json_encode($patientMeasurements, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const minRefM = <?= isset($species_measurement_from) ? (float)$species_measurement_from : 0 ?>;
    const maxRefM = <?= isset($species_measurement_to) ? (float)$species_measurement_to : 0 ?>;

    const minLineM = Array(measurementLabels.length).fill(minRefM);
    const maxLineM = Array(measurementLabels.length).fill(maxRefM);

    new Chart(document.getElementById("measurementchart"), {
        type: 'line',
        data: {
            labels: measurementLabels,
            datasets: [
                {
                    label: "<?= htmlspecialchars($patient_name, ENT_QUOTES, 'UTF-8') ?>'s Measurements",
                    data: patientMeasurements,
                    borderColor: "#148805",
                    borderWidth: 2,
                    fill: false,
                    tension: 0.3
                },
                {
                    label: "Lower",
                    data: minLineM,
                    borderColor: "red",
                    borderWidth: 0.5,
                    fill: false,
                    pointRadius: 0,
                    tension: 0
                },
                {
                    label: "Upper",
                    data: maxLineM,
                    borderColor: "blue",
                    borderWidth: 0.5,
                    fill: false,
                    pointRadius: 0,
                    tension: 0
                },
                {
                    label: "Optimum Range",
                    data: maxLineM,
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
/* -------------------- DELETE MEASUREMENT RECORD -------------------- */
if (isset($_POST['measurement_delete']) && !empty($_POST['measurement_id'])) {
    $delete_id = (int) $_POST['measurement_id'];

    $del = $pdo->prepare("DELETE FROM rescue_measurements WHERE weight_id = :id LIMIT 1");
    $del->execute([':id' => $delete_id]);
}

/* -------------------- GET ALL MEASUREMENTS (NO PAGINATION) -------------------- */
$stmt = $pdo->prepare("
    SELECT *
    FROM rescue_measurements
    WHERE patient_id = :patient_id
    ORDER BY date DESC
");

$stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
$stmt->execute();
$measurements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<br>

<?php if (empty($measurements)): ?>

    <div class="alert-box alert-purple" style="margin-bottom: 12px;">
        <strong>Measurements</strong><br>
        No measurements recorded for this patient.
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

                    <th align="left" style="padding-left:50px;">
                        Measurement
                    </th>

                    <th align="right" style="width:90px;">
                        Action
                    </th>
                </tr>
            </thead>
        </table>

    </div>

    <?php foreach ($measurements as $row): ?>

        <?php
            $formatted_date = (new DateTime($row["date"]))->format('d-m-Y H:i');
        ?>

        <!-- ==========================================================
             MEASUREMENT ENTRY — PURPLE ALERT
        ========================================================== -->
        <div class="alert-box alert-purple measurement-row"
             style="margin-bottom: 6px; padding: 8px 12px;">

            <table style="width:100%; border-collapse:collapse;">
                <tbody>
                    <tr>
                        <!-- Date -->
                        <td style="width:180px; white-space:nowrap;">
                            <?= htmlspecialchars($formatted_date) ?>
                        </td>

                        <!-- Measurement (shifted right) -->
                        <td style="padding-left:50px;">
                            <strong>
                                <?= htmlspecialchars($row["measurement"] . $row["measurement_unit"]) ?>
                            </strong>
                        </td>

                        <!-- Action -->
                        <td style="width:90px; text-align:right; white-space:nowrap;">
                            <form method="post"
                                  style="margin:0; padding:0;"
                                  onsubmit="return confirm('Delete this measurement?');">
                                <input type="hidden"
                                       name="measurement_id"
                                       value="<?= (int)$row["weight_id"]; ?>">
                                <button type="submit"
                                        name="measurement_delete"
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



<!-- Pagination Controls -->
<div id="measurementPagination" class="pagination"></div>

<script>
// -----------------------------
// JAVASCRIPT PAGINATION SYSTEM
// (ALERT-BASED)
// -----------------------------

const mRowsPerPage = 5;

// ✅ Select alert rows instead of table rows
const mRows = Array.from(
    document.querySelectorAll(".measurement-row")
);

const mPagination = document.getElementById("measurementPagination");

let mPage = 1;
const mTotalPages = Math.ceil(mRows.length / mRowsPerPage);

// Safety guard
if (!mRows.length || !mPagination) {
    mPagination.innerHTML = "";
} else {

    function showMeasurementPage(page) {
        mPage = page;

        // Hide all rows
        mRows.forEach(r => r.style.display = "none");

        // Show selected rows
        const start = (page - 1) * mRowsPerPage;
        const end = start + mRowsPerPage;

        for (let i = start; i < end && i < mRows.length; i++) {
            mRows[i].style.display = "block";
        }

        renderMeasurementPagination();
    }

    function renderMeasurementPagination() {
        mPagination.innerHTML = "";

        // Previous
        const prev = document.createElement("button");
        prev.innerHTML = "Previous";
        prev.className = "btn grey smallbtn";
        prev.disabled = mPage === 1;
        prev.onclick = () => showMeasurementPage(mPage - 1);
        mPagination.appendChild(prev);

        // Page numbers
        for (let i = 1; i <= mTotalPages; i++) {
            const btn = document.createElement("button");
            btn.innerHTML = i;
            btn.className = "btn smallbtn " + (i === mPage ? "primary" : "grey");
            btn.style.marginLeft = "5px";
            btn.onclick = () => showMeasurementPage(i);
            mPagination.appendChild(btn);
        }

        // Next
        const next = document.createElement("button");
        next.innerHTML = "Next";
        next.className = "btn grey smallbtn";
        next.disabled = mPage === mTotalPages;
        next.onclick = () => showMeasurementPage(mPage + 1);
        next.style.marginLeft = "5px";
        mPagination.appendChild(next);
    }

    // Initialise
    showMeasurementPage(1);
}
</script>
