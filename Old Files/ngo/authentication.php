<?php
// Start the session
session_start();

// Check if the user is not logged in
if ( ! is_user_logged_in() ) {
   // Set the redirect URL
   $redirect_url = home_url();

   // Save the requested URL to redirect the user after login
   $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];

   // Redirect the user to the login page
   wp_redirect( $redirect_url );
   exit;
}
?>