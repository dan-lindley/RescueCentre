<?php
require_once('../connect_to_mysql.php');

$id = intval($_GET['id'] ?? 0);

// Fetch record
$sql = "SELECT * FROM rescue_admissions WHERE admission_id = :id LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo "Invalid admission ID.";
    exit;
}

// Handle Save
if (isset($_POST['save'])) {

    $lat = $_POST['location_lat'];
    $lon = $_POST['location_long'];

    $update = $conn->prepare("
        UPDATE rescue_admissions
        SET location_lat = :lat, location_long = :lon
        WHERE admission_id = :id
    ");
    $update->bindParam(':lat', $lat);
    $update->bindParam(':lon', $lon);
    $update->bindParam(':id', $id);
    $update->execute();

    header("Location: geocode_all.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Fix Geocode</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
input[type=text] {
    width: 350px;
    padding: 6px;
}
#suggestions {
    position: absolute;
    width: 350px;
    background: #fff;
    border: 1px solid #ccc;
    display: none;
    max-height: 200px;
    overflow-y: auto;
    z-index: 9999;
}
.suggestion-item {
    padding: 6px;
    cursor: pointer;
}
.suggestion-item:hover {
    background: #eee;
}
</style>

</head>
<body>

<h2>Fix Geocode for Admission #<?= $id ?></h2>

<form method="post">

    <label><strong>Search Location:</strong></label><br>
    <input 
        type="text" 
        id="searchBox" 
        autocomplete="off"
        value="<?= htmlspecialchars($row['collection_location']) ?>" 
    >
    <div id="suggestions"></div>

    <br><br>

    <label>Latitude:</label><br>
    <input type="text" name="location_lat" id="location_lat" readonly>

    <br><br>

    <label>Longitude:</label><br>
    <input type="text" name="location_long" id="location_long" readonly>

    <br><br>

    <button type="submit" name="save">Save</button>
</form>


<script>
let delayTimer;

// 🔍 LIVE AUTOCOMPLETE SEARCH
$("#searchBox").on("input", function() {
    clearTimeout(delayTimer);
    const query = $(this).val().trim();

    if (query.length < 3) {
        $("#suggestions").hide();
        return;
    }

    delayTimer = setTimeout(function() {

        $.ajax({
            url: "https://rescuecentre.org.uk/wp-content/themes/brikk-child/ajax/nominatim.php",
            data: { q: query },
            success: function(results) {

                if (!results || results.length === 0) {
                    $("#suggestions").hide();
                    return;
                }

                let html = "";
                results.forEach(item => {
                    html += `
                        <div class="suggestion-item" 
                             data-lat="${item.lat}" 
                             data-lon="${item.lon}">
                            ${item.display_name}
                        </div>
                    `;
                });

                $("#suggestions").html(html).show();
            },
            error: function() {
                console.log("Autocomplete AJAX failed");
            }
        });

    }, 300);
});

// ✔ Clicking fills lat/long
$(document).on("click", ".suggestion-item", function() {

    const lat = $(this).data("lat");
    const lon = $(this).data("lon");
    const text = $(this).text();

    $("#searchBox").val(text);
    $("#location_lat").val(lat);
    $("#location_long").val(lon);

    $("#suggestions").hide();
});
</script>

</body>
</html>
