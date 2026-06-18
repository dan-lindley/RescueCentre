<?php
/*----------------------- FORM PROCESSING DISPOSITION-------------------*/
// disposition fields for admission table: disposition, disposition_date, disposition_user, 
// disposition_centre, disposition_comment, euthanasia_method 
// update for the patient form: status (Captive, Released or Deceased)
// disposition lookup (Held in captivity, Released, Transferred to another rescue, Died - Euthanised, Died - within 48 hours, Died - after 48 hours, Died - On admmission )

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* Edit Details Form Processing */
include "../connect_to_mysql.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    function test_input($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    
  //First part sets the current task to complete
  $complete_id = test_input($_POST["completion_id"]);
  $task_completed = test_input($_POST["completed"]);
  $task_completed_by = test_input($_POST["completed_by"]);
  
  $task_completed_on = date('Y-m-d H:i:s');
  
  //second half post values
  $task_id = test_input($_POST["task_id"]);
  $task_centre_id = test_input($_POST["centre_id"]);
  $task_nextdue = test_input($_POST["nextdue"]);


    try {
        //First things set the completed task
        $query1 = "UPDATE rescue_todo_completions
                      SET 
                  rescue_todo_completions.completed = :completed,
                  rescue_todo_completions.completed_by = :completed_by,
                  rescue_todo_completions.completed_on = :completed_on
                  WHERE completion_id = :completion_id";
                      
        
        //then we create a new entry once marked complete and set the complete date for the interval added on
       $query2 = "INSERT INTO rescue_todo_completions
                (centre_id,
                task_id,
                to_complete)

                VALUES

                (:centre_id,
                :task_id,
                 :to_complete)";

        $stmt = $conn->prepare($query1);
        $stmt->bindParam('completion_id', $complete_id, PDO::PARAM_INT);
        $stmt->bindParam('completed', $task_completed, PDO::PARAM_INT);
        $stmt->bindParam('completed_by', $task_completed_by, PDO::PARAM_INT);
        $stmt->bindParam('completed_on', $task_completed_on, PDO::PARAM_STR);    
        $stmt->execute();

        //Adds a new entry to the completions for the task in the future
        $stmt2 = $conn->prepare($query2);
        $stmt2->bindParam('centre_id', $task_centre_id, PDO::PARAM_INT);
        $stmt2->bindParam('task_id', $task_id, PDO::PARAM_INT);
        $stmt2->bindParam('to_complete', $task_nextdue, PDO::PARAM_STR);
        $stmt2->execute();

    } catch (PDOException $e) {
		echo $e->getMessage();
        die($e->getMessage());
    }
} else {
    echo "Error from the addtask file in tasks folder";
    exit();
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
/*---------------------------------------------------------------------------------*/
