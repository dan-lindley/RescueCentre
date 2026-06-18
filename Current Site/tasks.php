 <?php defined('ABSPATH') or die('This script cannot be accessed directly.');

include_once "authentication.php";
include_once "connect_to_mysql.php";

echo "<div class='app_page_container'>";
/**
 * The template for displaying pages
 *xx
 * Do not overload this file directly. Instead have a look at templates/single.php file in us-core plugin folder:
 * you should find all the needed hooks there.
 */

/* Template Name: Tasks */

get_header();
$current_user_id = get_current_user_id();
include_once "app_header.php";

$current_user_id = get_current_user_id();

//Get the current Rescue Centre data from the database
$sql = 'SELECT * FROM rescue_centres WHERE rescue_id=:centre_id LIMIT 1';
$statement = $conn->prepare($sql);
$statement->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
/*---------------------------------------------------------------------------------*/
if ($result) {
    $rescue_name = $result["rescue_name"];

} else {
    echo "Rescue centre not found";
    exit();

}

?>
 <!-- Begin Page Content -->
<div class="container-fluid">

        <!-- Page Heading -->
        <div>
            <div class="row dashboard_heading_withfilter">
                <div class="col-md-6 my-auto">
                    <h1 class="h3 mb-0 text-gray-800 portal_heading">Tasks</h1>
                    
                </div>
            </div>
        </div>

<div class="card shadow mb-4" id="databasetable">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Tasks Completed Today</h6>
        
	</div>
    <div class="card-body">
    <div class="table-responsive">
    <table class="table table-hover table-sm" width="100%" cellspacing="0">
    <thead class='thead-dark'>
        <tr>
        <th class="align-middle"><h5>Task</H5></th>
        <th class="align-middle"><H5>Notes</h5></th> 
        <th class="align-middle"><h5>Completed by</h5></th>  
        <th></th>                 
        </tr>
    </thead>
    <tbody>
        <?php
        //gets the tasks from the table to display 
        //Priority 0 is deleted from list
        $stmt = $conn->prepare("SELECT to_complete, task, DATE_ADD(to_complete, INTERVAL frequency DAY ) AS nextdue, display_name, notes, priority, frequency, rescue_todo_completions.*
                                FROM rescue_todo_completions
                                LEFT JOIN rescue_todo
                                ON rescue_todo.todo_id = rescue_todo_completions.task_id
                                LEFT JOIN wpxp_users
								ON wpxp_users.ID = rescue_todo_completions.completed_by
                                WHERE rescue_todo_completions.centre_id = :centre_id AND completed=1 
								AND CAST(completed_on AS DATE) = CAST( curdate() AS DATE)
								AND NOT priority=0
                                ORDER BY completed_on, task ASC");
        $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

        // initialise an array for the results
        $centre_tasks = array();
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $task_id = $row["todo_id"];
        $task = $row["task"];
		$priority = $row["priority"];
        $notes = $row["notes"];
        $frequency = $row["frequency"];
		$created_by = $row["created_by"]; 
        $completed_by = $row["display_name"];

 ?>

        <tr>
        <td class="align-middle"><h5><i class="fas fa-check" style="color:green"></i> <?php echo htmlspecialchars($task); ?></h5></td>
        <td class="align-middle"><h5><?php echo htmlspecialchars($notes); ?></h5></td> 
        <td class="align-middle"><h5><?php echo htmlspecialchars($completed_by); ?></h5></td>
        </tr>
    </tbody>
       <?php  }
            ?>
</table>                    
    </div>
    </div>
    </div>

<?php include ("tasks/tasklist.php"); ?>

<script>
    document.getElementById("tasks_link").classList.add("active");
</script>
<?php include_once "app_footer.php";
?>
<!-- End of Main Content -->
<?php get_footer();


echo "</div>";