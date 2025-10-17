
<div class="card shadow mb-4" id="databasetable">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Tasks to do</h6>
        
	</div>
    <div class="card-body">

        <div class="table-responsive">  
        <table class="table table-bordered table-sm table-hover" id="addnewpts" width="100%" cellspacing="0">

        <!-- Show locations -->
        <?php
        $stmt = $conn->prepare("SELECT to_complete, task, DATE_ADD(to_complete, INTERVAL frequency DAY ) AS nextdue, notes, priority, frequency, rescue_todo_completions.*
                                FROM rescue_todo_completions
                                LEFT JOIN rescue_todo
                                ON rescue_todo.todo_id = rescue_todo_completions.task_id
                                WHERE rescue_todo_completions.centre_id = :centre_id AND completed=0 AND NOT priority=0
                                ORDER BY to_complete, task ASC");
        $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);



foreach ($data as $to_complete => $day) {

    	$task_day = new DateTime($to_complete);
   		$task_day = $task_day->format('l'); 

    print " <thead class='thead-dark'>
                <tr>
                <th></th>
                    <th class='align-middle'><h4>".htmlspecialchars($task_day)."
                                                (".htmlspecialchars($to_complete).")</h4></th>
                    <th class='align-middle'><h4>Notes</h4></th>
                    <th></th>

                </tr>
            </thead>";


    foreach ($day as $row) {
    
    $priority = $row['priority'];
      
      // TRAFFIC LIGHT SYSTEM FOR PRIORITY
 	  if ($priority == 1 ) {
      $priority_class = 'table-success';
      } elseif ($priority == 2) {
      $priority_class = 'table-warning';
      } elseif ($priority == 3) {
      $priority_class = 'table-danger';
      } 
        print '
            <tbody>
                <tr>
                <td width="20" class="' .$priority_class . '"></tD>
                    <td width="100" class="align-middle"><h5>' . htmlspecialchars($row['task']) . '</h5></td>                  
                    <td width="300" class="align-middle"><h5>' . htmlspecialchars($row['notes']) . '</h5></td>';
                    $completion_id = $row['completion_id'];
                    $frequency = $row['frequency'];
                    $nextdue = $row['nextdue'];
                    $task_id = $row['task_id'];
                  
                     ?>
                    <form action="https://rescuecentre.org.uk/wp-content/themes/brikk-child/tasks/markcomplete.php" method="post">
                    <td width="10" class="align-middle">
                        <input type="hidden" id="completion_id" name="completion_id" value="<?php echo $completion_id;?>">
                        <input type="hidden" id="completed_by" name="completed_by" value="<?php echo $current_user_id; ?>">
                        <input type="hidden" id="completed" name="completed" value="1">
                        <!--Second half of the process -->
                        <input type="hidden" id="task_id" name="task_id" value="<?php echo $task_id; ?>">
                        <input type="hidden" id="nextdue" name="nextdue" value="<?php echo $nextdue; ?>">
                        <input type="hidden" id="centre_id" name="centre_id" value="<?php echo $centre_id; ?>"> 
                        <button type="submit" class="btn btn-secondary btn-info" name="completetask">Mark Complete</button>
                       
                    </td>
                    </form>
                </tr>
            <?php ; } 
        } ?>
        
        </tbody></table>










		
	</div>  <br>
</div>			
	
<script>
    //Add task AJAX
    $(document).ready(function() {
        $('#completetask').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                type: 'post',
                url: 'https://rescuecentre.org.uk/wp-content/themes/brikk-child/tasks/markcomplete.php',
                data: $('#completetask').serialize(),
                success: function() {
                    location.reload();
                }
            });
        });
    });

</script>