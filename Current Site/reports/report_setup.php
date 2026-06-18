<?php

//Get logged in user's name 
$user_info = get_userdata(get_current_user_id());
$wp_first_name = $user_info->first_name;
$wp_last_name = $user_info->last_name;
$wp_fullname = "" . $wp_first_name . " " . $wp_last_name . "";

echo "<div class='app_page_container'>";

get_header();

//Retrieve the GET value from the URL, and sanitise it for security purposes
function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if (isset($_GET["centre_id"])) {
    $centre_id = test_input($_GET["centre_id"]);
} else {
    echo "Error I couldnt find a centre with that value.";
    exit();
}

?>