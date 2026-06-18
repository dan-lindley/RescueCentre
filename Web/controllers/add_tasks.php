<?php
// SAFE: unique variable name so it cannot conflict with parent loops
$taskStmt = $pdo->prepare("
    SELECT task_id, task
    FROM rescue_tasks
    ORDER BY task ASC
");
$taskStmt->execute();
?>

<!-- TASK ASSIGNMENT FORM -->
<form action="controllers/form_handler.php" method="post" class="xform" id="addtaskform">

    <div class="xform-grid">

        <!-- TASK SELECT -->
        <div class="xform-field">
            <select name="task_id" class="xform-input" required>
                <option value="" disabled selected>Select a Task</option>

                <?php while ($task = $taskStmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <option value="<?= $task['task_id'] ?>">
                        <?= htmlspecialchars($task['task']) ?>
                    </option>
                <?php endwhile; ?>

            </select>
        </div>

        <div class="xform-field" >
            <!-- Hidden fields -->
            <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
            <button type="submit" name="taskassignform" class="btn purple">
            Assign Task
            </button>
    </form>
        </div>
    </div>


