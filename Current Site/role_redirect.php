<?php 
/**
 * The template for displaying pages
 *
 * Do not overload this file directly. Instead have a look at templates/single.php file in us-core plugin folder:
 * you should find all the needed hooks there.
 */

/* Template Name: Redirect */
include_once "authentication.php";
include_once "connect_to_mysql.php";
$root = $_SERVER['DOCUMENT_ROOT'];
require_once("".$root."/wp-load.php");
include_once("get_user_info.php");


if ($accesslevel == 1) {
   header("Location: https://www.rescuecentre.org.uk/dashboard/");
  die();
} elseif ($accesslevel == 2){
  header("Location: https://www.rescuecentre.org.uk/dashboard/");
  die();
} elseif ($accesslevel == 3) {
   header("Location: https://www.rescuecentre.org.uk/dashboard/");
  die();
} else if ($accesslevel == 4) {
  header("Location: https://www.rescuecentre.org.uk/vet/");
  die();
} else if ($accesslevel == 5) {
  header("Location: https://www.rescuecentre.org.uk/vet/");
  die();
} else if ($accesslevel == 6) {
  header("Location: https://www.rescuecentre.org.uk/dashboard/");
  die();
} else if ($accesslevel == 7) {
  header("Location: https://www.rescuecentre.org.uk/dashboard/");
  die();
} else if ($accesslevel == 9) {
   echo $assignedorg;
   header("Location: https://www.rescuecentre.org.uk/organisation/?org_id=$assignedorg");
   die();
}