<?php
/*----------FORM PROCESSING - remove the task from the main screen ---------------*/
// This adds a task to the main task page by adding it as an entry to the completions table
// removed by setting completed to 9
if (isset($_POST['removefromlist'])) {
    $completion_id = $_POST["completion_id"];
    $completed = $_POST["completed"];

    try {
        $statement = $conn->prepare('INSERT INTO rescue_todo_completions
            ( 
            completion_id,
			completed)            
            VALUES (
            :completion_id,
			:completed) 			
			ON DUPLICATE KEY UPDATE
			completed = :completed
			');

        $statement->execute([
            'completion_id' => $completion_id,
            'completed' => $completed
			            
        ]);
		echo "<meta http-equiv='refresh' content='0'>";
		
    } catch (PDOException $e) {
        echo "Database Error: The note could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The note could not be added.<br>" . $e->getMessage();
        exit();
    }

}


/*----------FORM PROCESSING - ADD the task to the task list ---------------*/
// This adds a task to the main task page by adding it as an entry to the completions table
if (isset($_POST['addtolist'])) {
    $task_id = $_POST["todo_id"];
    $centre_id = $_POST["centre_id"];
    $to_complete = date('Y-m-d H:i:s');

    try {
        $statement = $conn->prepare('INSERT INTO rescue_todo_completions
            (centre_id,
            task_id,
			to_complete)            
            VALUES (
            :centre_id,
			:task_id,
            :to_complete) 			
			');

        $statement->execute([
            'centre_id' => $centre_id,
            'task_id' => $task_id,
            'to_complete' => $to_complete
			            
        ]);
		echo "<meta http-equiv='refresh' content='0'>";
		
    } catch (PDOException $e) {
        echo "Database Error: The task could not be deleted.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The task could not be deleted.<br>" . $e->getMessage();
        exit();
    }

}
/*----------------- FORM PROCESSING - ADD new TASK------------------------*/
// This Deletes a task by setting the priority to 0
if (isset($_POST['deletetask'])) {
    $task_id = $_POST["todo_id"];
    $priority = $_POST["priority"];

    try {
        $statement = $conn->prepare('INSERT INTO rescue_todo
            (todo_id,
			priority)            
            VALUES (
            :todo_id,
			:priority) 			
			ON DUPLICATE KEY UPDATE
			priority = :priority
			');

        $statement->execute([
            'todo_id' => $task_id,
            'priority' => $priority
			            
        ]);
		echo "<meta http-equiv='refresh' content='0'>";
		
    } catch (PDOException $e) {
        echo "Database Error: The task could not be deleted.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The task could not be deleted.<br>" . $e->getMessage();
        exit();
    }

}

?>
<!-- TASK SETTINGS CARD -->
<div class="card shadow mb-4" id="databasetable">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Manage Tasks</h6>
        <p class="card_subheading">You can add and review tasks for your rescue</p>
    </div>
    
    <div class="card-body">

<h5><b>Tasks on task list for users </b></h5><br>
<!-- Tasks simpliefied table  in the tasks list-->
 <div class="table-responsive">
    <table class="table table-hover table-sm" width="100%" cellspacing="0">
    <thead class='thead-dark'>
        <tr>
        <th class="align-middle"><h5>Task</H5></th>
        <th class="align-middle"><H5>Notes</h5></th> 
        <th class="align-middle"><H5>Frequency<BR>(days)</h5></th> 
        <th class="align-middle"><h5>Priority</h5></th>  
        <th></th>                 
        </tr>
    </thead>
    <tbody>
        <?php
        //gets the tasks from the table to display 
        //Priority 0 is deleted from list
        $stmt = $conn->prepare("SELECT to_complete, task, notes, priority, frequency, rescue_todo_completions.*
                                FROM rescue_todo_completions
                                LEFT JOIN rescue_todo
                                ON rescue_todo.todo_id = rescue_todo_completions.task_id
                                WHERE rescue_todo_completions.centre_id = :centre_id AND completed=0 AND NOT priority=0
								GROUP BY task
                                ORDER BY to_complete, task ASC");
        $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

        // initialise an array for the results
        $centre_alltasks = array();
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $task_id = $row["todo_id"];
        $completion_id = $row["completion_id"];
        $task = $row["task"];
		$priority = $row["priority"];
        $notes = $row["notes"];
        $frequency = $row["frequency"];
		$created_by = $row["created_by"]; 

        if ($priority == 1 ) {
     	$priority_text = 'Low';
 		} elseif ($priority == 2) {
		$priority_text = 'Normal';
  		} elseif ($priority == 3) {
    	$priority_text = 'High';
  		}
 ?>

        <tr>
        <td class="align-middle"><?php echo htmlspecialchars($task); ?></td>
        <td class="align-middle"><?php echo htmlspecialchars($notes); ?></td>
        <td class="align-middle"><?php echo htmlspecialchars($frequency); ?></td>
        <td class="align-middle"><?php echo htmlspecialchars($priority_text); ?></td>

       
	 <form method="post" action=""><td class="align-middle">
    <input type="hidden" id="completion_id" name="completion_id" value="<?php echo $completion_id; ?>">
    <input type="hidden" id="completed" name="completed" value="9">
    <button type="submit" class="btn btn-secondary btn-danger btn-sm" name="removefromlist">Remove for users</button> 

    </td>
    </form>
    </tr></tbody>
       <?php  }
            ?>
</table>                    
<hr>
<h5><b>All centre Tasks</b></h5><br>
<!-- Tasks table -->
 <div class="table-responsive">
    <table class="table table-hover table-sm" width="100%" cellspacing="0">
    <thead class='thead-dark'>
        <tr>
        <th class="align-middle"><h5>Task</H5></th>
        <th class="align-middle"><H5>Notes</h5></th> 
        <th class="align-middle"><H5>Frequency<BR>(days)</h5></th> 
        <th class="align-middle"><h5>Priority</h5></th>  
        <th></th>                 
        </tr>
    </thead>
    <tbody>
        <?php
        //gets the tasks from the table to display 
        //Priority 0 is deleted from list
        $stmt = $conn->prepare("SELECT * FROM rescue_todo WHERE centre_id = :centre_id 
        AND NOT priority=0 ORDER by task DESC");
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

        if ($priority == 1 ) {
     	$priority_text = 'Low';
 		} elseif ($priority == 2) {
		$priority_text = 'Normal';
  		} elseif ($priority == 3) {
    	$priority_text = 'High';
  		}
 ?>

        <tr>
        <td class="align-middle"><?php echo htmlspecialchars($task); ?></td>
        <td class="align-middle"><?php echo htmlspecialchars($notes); ?></td>
        <td class="align-middle"><?php echo htmlspecialchars($frequency); ?></td>
        <td class="align-middle"><?php echo htmlspecialchars($priority_text); ?></td>

       
	 <form method="post" action="">
        <td class="align-middle">
    <input type="hidden" id="todo_id" name="todo_id" value="<?php echo $task_id; ?>">
    <input type="hidden" id="centre_id" name="centre_id" value="<?php echo $centre_id; ?>">
    <input type="hidden" id="priority" name="priority" value="0">
    <button type="submit" class="btn btn-secondary btn-danger btn-sm" name="deletetask">Delete</button> 
    <button type="submit" class="btn btn-secondary btn-info btn-sm" name="addtolist">Add to Centre Tasks</button> 
    
        </td>
    </form>
    </tr></tbody>
       <?php  }
            ?>
</table>                    
  
    </div>
    </div>
    </div>
    </div>
<!-- END OF TASK TABLE and CARD -->	


<div class="card shadow mb-4" id="databasetable">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Add Tasks</h6>
        <p class="card_subheading">You can add new tasks for your rescue</p>
    </div>
    
    <div class="card-body">

    <!--form for putting the tasks into the database -->
    <form action="https://rescuecentre.org.uk/wp-content/themes/brikk-child/tasks/addtask.php" method="post">

    <div class="row lead_form_row">
        <div class="col-md-6">
            <p class="angelo_form_label">Task</p>
            <input type="text" name="task" id="task" placeholder="The name of the task" required>
        </div>
    
       <div class="col-md-6">
            <p class="angelo_form_label">Priority</p>
                <select name="priority" id="priority">
                    <option value="2" selected>Normal/No Priority</option>
                    <option value="1">Low</option>
                    <option value="3">High</option>
                </select>
        </div>
    </div>

    <div class="row lead_form_row">
        <div class="col-md-6">
            <p class="angelo_form_label">Frequency (days)</p>
               <input type="text" name="frequency" id="frequency" placeholder="Input 1 for everyday, 7 for weekly etc" required>
        </div>
        <div class="col-md-6">
            <p class="angelo_form_label">Notes</p>
                <textarea id="notes" name="notes" rows="4" cols="50">Add some notes specific to the task, e.g instructions</textarea>
        </div>
    </div>
    <br />
        <input type="hidden" id="centre_id" name="centre_id" value="<?php echo $centre_id; ?>">
        <input type="hidden" id="created_by" name="created_by" value="<?php echo $current_user_id; ?>">
        <input type="submit" id="submit" name="taskform" value="Add a Task" class="form_submit">

        </form>

    </div>
</div>
<!-- END OF TASK SETTINGS CARD ---->
 
<script>
    document.getElementById("managetasks_link").classList.add("active");
</script>
<script>
    //Add task AJAX
    $(document).ready(function() {
        $('#taskform').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                type: 'post',
                url: 'https://rescuecentre.org.uk/wp-content/themes/brikk-child/tasks/addtask.php',
                data: $('#taskform').serialize(),
                success: function() {
                    location.reload();
                }
            });
        });
    });

</script>
