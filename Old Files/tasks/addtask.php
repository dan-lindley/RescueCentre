<?php
/*----------------------- FORM PROCESSING ADD TASK ------------------*/

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

/* Form Processing */
include "../connect_to_mysql.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    function test_input($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    
  //Task form post data
  $task_name= test_input($_POST["task"]);
  $task_priority = test_input($_POST["priority"]);
  $task_frequency = test_input($_POST["frequency"]);
  $task_notes = test_input($_POST["notes"]);
  $task_centre_id = test_input($_POST["centre_id"]);
  $task_created_by = test_input($_POST["created_by"]);


    try {
        //First things first, we set up the task in the tasks table
        $query1 = "INSERT INTO rescue_todo
                      (centre_id,
                      task,
                      priority,
                      notes,
                      created_by,
                      frequency)

                      VALUES

                      (:centre_id,
                      :task,
                      :priority,
                      :notes,
                      :created_by,
                      :frequency)";
                      
        


        //then we create a relationship for the completions
        //$query2 = "UPDATE rescue_patients
        //              SET 
        //         rescue_patients.status = :status
        //         WHERE rescue_patients.patient_id = :patient_id";

        $stmt = $conn->prepare($query1);
        $stmt->bindParam('centre_id', $task_centre_id, PDO::PARAM_INT);
        $stmt->bindParam('task', $task_name, PDO::PARAM_STR);
        $stmt->bindParam('priority', $task_priority, PDO::PARAM_INT);
        $stmt->bindParam('notes', $task_notes, PDO::PARAM_STR);
        $stmt->bindParam('created_by', $task_created_by, PDO::PARAM_INT);
        $stmt->bindParam('frequency', $task_frequency, PDO::PARAM_INT);

        $stmt->execute();


       // $stmt2 = $conn->prepare($query2);
       // $stmt2->bindParam('patient_id', $pat_patient_id, PDO::PARAM_INT);
       // $stmt2->bindParam('status', $pat_status, PDO::PARAM_STR);
       // $stmt2->execute();

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
