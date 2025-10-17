<?php
//error_reporting(E_ALL);
//ini_set('display_errors', '1');
/*----------------------- FORM PROCESSING CARE NOTES-------------------*/
//Check if the notes form was submitted
if (isset($_POST['dutyform'])) {

	$duty = $_POST["duty"];
    $person = $_POST["person"];
    $dutycentre_id = $_POST["centre_id"];
	$date = $_POST["date"];
    try {
        $statement = $conn->prepare('INSERT INTO rescue_duties
            (duty, 
            person,
            centre_id,
            date)
            
            VALUES (:duty, 
            :person,
            :centre_id,
            :date)');

        $statement->execute([
            'duty' => $duty,
            'person' => $person,
            'centre_id' => $dutycentre_id,
			'date' => $date,
        ]);
		
		  echo "<script>window.location = window.location</script>";
		
    } catch (PDOException $e) {
        echo "Database Error: The note could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The note could not be added.<br>" . $e->getMessage();
        exit();
    }
}
/*------------ END FORM ----------------*/

if (isset($_POST['newduty'])) {

	$dutyname = $_POST["dutyname"];
    $colour= $_POST["colour"];
    $newdutycentre_id = $_POST["centre_id"];
    try {
        $statement = $conn->prepare('INSERT INTO rescue_duty_type
            (duty, 
            duty_colour,
            centre_id)
            
            VALUES (:duty, 
            :duty_colour,
            :centre_id)');

        $statement->execute([
            'duty' => $dutyname,
            'duty_colour' => $colour,
            'centre_id' => $newdutycentre_id,
        ]);
		
		  echo "<script>window.location = window.location</script>";
		
    } catch (PDOException $e) {
        echo "Database Error: The note could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The note could not be added.<br>" . $e->getMessage();
        exit();
    }
}

/*---------------------------------------------------------------------*/


date_default_timezone_set('Europe/London'); // adjust if needed

function mask($s) {
    if ($s === '') return '(empty)';
    return substr($s, 0, 2) . str_repeat('*', max(0, strlen($s) - 4)) . substr($s, -2);
}

/* ---------- Build 42-day grid starting from Monday of current week ---------- */
$today = new DateTimeImmutable('today');
$isoDay = (int)$today->format('N'); // 1 (Mon) .. 7 (Sun)
$monday = $today->modify('-' . ($isoDay - 1) . ' days'); // back to Monday
$days = [];
for ($i = 0; $i < 42; $i++) {
    $days[] = $monday->add(new DateInterval("P{$i}D"));
}
$start_date = $monday->format('Y-m-d');
$end_date   = $days[41]->format('Y-m-d');

/* ---------- Attempt to fetch duties into $duties_by_date ---------- */
$duties_by_date = [];
$driver_used = null;
$rows_count = 0;
$sql_debug = "SELECT DATE(`date`) AS duty_date, t.duty AS duty, display_name AS person, duty_colour, d.centre_id FROM
            rescue_duties AS d
                JOIN rescue_duty_type AS t
                ON t.duty_type_id = d.duty
                JOIN wpxp_users AS u
                ON u.ID = d.person
              WHERE DATE(`date`) BETWEEN :start AND :end
              ORDER BY duty_date ASC";

try {
        $stmt = $conn->prepare($sql_debug);
        $stmt->execute(['start' => $start_date, 'end' => $end_date]);
        $rows = $stmt->fetchAll();
        $rows_count = count($rows);
        foreach ($rows as $r) {
            $d = $r['duty_date'];
            $duties_by_date[$d][] = ['duty' => $r['duty'], 'person' => $r['person']];
        }
 
} catch (Exception $ex) {
   $duties_by_date = [];
}

/* ---------- Render HTML calendar ---------- */
?>
<script>
$(function () {
  //triggered when modal is about to be shown
  $("#dutyModal").on("show.bs.modal", function (e) {
    //get data-id attribute of the clicked element
    var formdate = $(e.relatedTarget).data("date");

    //populate the form
	$(e.currentTarget).find(".dateDisplay").text(formdate);
    $(e.currentTarget).find('input[name="date"]').val(formdate);
  });
});
</script>
<style>
    .caltable { border-collapse: collapse; width: 100%; }
    .calth, .caltd { border: 1px solid #ddd; vertical-align: top; padding: 8px; }
    th { background:#f4f4f4; text-align: center; }
    .caltd { height:110px; width:14.2857%; }
    .date-label { font-weight:700; display:block; margin-bottom:6px;  text-align: center;  background: #e3ffa2ff;}
    .today { background: #e3ffa2ff; }
    .duty { margin:4px 0; font-size:.95em; }
</style>

<div class="row">
    <div class="col">
        Use the form to the right to add new duties to your duties dropdown.
        <BR><BR>
        Then add a member of staff to a duty on a specific day by clicking the + symbol under the 
        day you wish to assign them the duty.
    </div>
    <div class="col">
        <div class="card mb-4">
            <div class="card-header py-3">
                <h5 class="m-0 font-weight-bold text-primary">Add new Duty</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-8">
                        <form method="POST" action="">
                            <p class="angelo_form_label">Duty</p>
                            <input type="text" name="dutyname" id="dutyname" placeholder="Duty" required>
                    </div>
                    <div class="col">
                        <p class="angelo_form_label">Select a colour</p>
                        <select name="colour" id="colour" required>
                            <option value="alert-info" class="alert alert-info">Blue</option>
                            <option value="alert-success" class="alert alert-success">Green</option>
                            <option value="alert-warning" class="alert alert-warning">Amber</option>
                            <option value="alert-danger" class="alert alert-danger">Red</option>
                            <option value="alert-dark" class="alert alert-dark">Grey</option>
                            <option value="alert-light" class="alert alert-light">White</option>
                        </select>
                    </div>
                    <input type="hidden" name="centre_id" id="centre_id" value="<?php echo $centre_id; ?>">
                    <input type="submit" id="submit" name="newduty" value="Add duty" class="form_submit">
                </form>
                </div>
            <div>
        </div>
    </div>
</div>
</div>





<table class="caltable">
    <tr>
        <th class="calth">Monday</th>
        <th class="calth">Tuesday</th>
        <th class="calth">Wednesday</th>
        <th class="calth">Thursday</th>
        <th class="calth">Friday</th>
        <th class="calth">Saturday</th>
        <th class="calth">Sunday</th>
    </tr>

    <?php
    $todayStr = $today->format('Y-m-d');
    for ($r = 0; $r < 6; $r++) {
        echo "<tr>";
        for ($c = 0; $c < 7; $c++) {
            $i = $r * 7 + $c;
            $dObj = $days[$i];
            $dStr = $dObj->format('Y-m-d');
            $day = $dObj->format('jS');
            $month = $dObj->format('F');
            $cls = ($dStr === $todayStr) ? "today" : "";

            //echo "<td class='caltd'>"; 
            echo "<td class=\"$cls\">";
            //echo "<span class='date-label'>" . htmlspecialchars($dStr, ENT_QUOTES, 'UTF-8') . " </span>";
            echo "<span class='date-label'>" . htmlspecialchars($day, ENT_QUOTES, 'UTF-8') . " of " . htmlspecialchars($month, ENT_QUOTES, 'UTF-8') . " </span>";

            if (!empty($duties_by_date[$dStr])) {
                foreach ($duties_by_date[$dStr] as $entry) {
                    $dutyEsc = htmlspecialchars($entry['duty'], ENT_QUOTES, 'UTF-8');
                    $personEsc = htmlspecialchars($entry['person'], ENT_QUOTES, 'UTF-8');
                    echo "<div class='duty'><strong>{$dutyEsc}</strong><br> — {$personEsc}</div>";
                                    }
            }
            echo "
            <button type='button' class='btn btn-info' data-toggle='modal' data-target='#dutyModal' data-date='$dStr'>+</button>
            </td>";
        }
        echo "</tr>";
    }
    ?>
</table>

<!-- Add Duty Modal -->
<div class="modal fade" id="dutyModal" tabindex="-1" role="dialog" aria-labelledby="dutyModal" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="font-weight-bold text-primary">Add Task on: <span class="dateDisplay"></span></h4> <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span> </button>
            </div>
            <div class="modal-body">
                <p>Here you can assign users to the duties and populate this to the calendar
                <form action="" method="post">

                 <p class="angelo_form_label">Select staff</p>
                    <select name="person" name="person" id="person">
                        <option value="" disabled selected>Staff member</option>
                        <?php
                        //Find locations stored in the patients table 
                        $stmt = $conn->prepare("SELECT ID, display_name, centre_id, role_name
                        FROM wpxp_users
                        JOIN rescue_roles
						ON rescue_roles.role_id = wpxp_users.rescue_role
                        WHERE centre_id = :centre_id ORDER BY 'display_name' DESC");
                        $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
                        // initialise an array for the results
                        $mystaff = array();
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                        $user_id = $row["ID"];
                        $user_name = $row["display_name"];
                        $user_role = $row["role_name"];

                        print '<option value="' . $user_id . '">' . $user_name . ' (' . $user_role. ')</option>'; }?>
                    </select>

                                     <p class="angelo_form_label">Select Duty</p>
                    <select name="duty" id="duty">
                        <option value="" disabled selected>Duty</option>
                        <?php
                        //Find locations stored in the patients table 
                        $stmt = $conn->prepare("SELECT *
                        FROM rescue_duty_type
                        WHERE centre_id = :centre_id ORDER BY 'display_name' DESC");
                        $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
                        // initialise an array for the results
                        $duties = array();
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                        $duty = $row["duty"];
                        $duty_id = $row["duty_type_id"];

                        print '<option value="' . $duty_id . '">' . $duty . '</option>'; }?>
                    </select>

          
				    <input type="hidden" id="centre_id" name="centre_id" value="<?php echo $centre_id; ?>">
                    <input type="hidden" id="date" name="date" value="">
                    <input type="submit" id="submit" name="dutyform" value="Submit" class="form_submit">
                </form>
            </div>
            <br />
        </div>
    </div>
</div>
<!---------------END of Area modal ----------------------------------------------------------->	