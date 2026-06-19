<?php 
//$centre_id = $_SESSION['centre_id'];
$centre_id = $account['centre_id'];




$sql = 'SELECT * FROM rescue_centres WHERE rescue_id=:centre_id LIMIT 1';
$statement = $pdo->prepare($sql);
$statement->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
/*---------------------------------------------------------------------------------*/
if ($result) {
    $rescue_name = $result["rescue_name"];
}
$_SESSION["rescue_name"] = $rescue_name;


/* --------------------------------------------------------------------------
   AUTO-PROVISION PERMISSIONS FOR A CENTRE
   Ensures every role has every permission for this centre.
   Missing rows are added with allow = 1 (default allow).
   This prevents a brand-new centre from seeing 403 errors.
-------------------------------------------------------------------------- */

if (!empty($centre_id)) {

    // 1. Fetch ALL global roles
    $roles = $pdo->query("SELECT role_id FROM rescue_roles")->fetchAll(PDO::FETCH_COLUMN);

    // 2. Fetch ALL global permissions
    $permissions = $pdo->query("SELECT permission_id FROM rescue_permissions")->fetchAll(PDO::FETCH_COLUMN);

    if ($roles && $permissions) {

        // 3. Prepare safe insert for missing permissions
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO rescue_role_permissions
                (centre_id, role_id, permission_id, allow)
            VALUES (:centre_id, :role_id, :permission_id, 1)
        ");

        // 4. Loop through roles × permissions and fill in any missing rows
        foreach ($roles as $rid) {
            foreach ($permissions as $pid) {
                $stmt->execute([
                    ':centre_id'     => $centre_id,
                    ':role_id'       => $rid,
                    ':permission_id' => $pid
                ]);
            }
        }
    }
}


?>