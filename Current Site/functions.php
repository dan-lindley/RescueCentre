<?php

/*
 * enqueue child css
 *
 */
add_action( 'wp_enqueue_scripts', function() {
	wp_enqueue_style( 'brk-child-style', get_template_directory_uri() . '/style.css', ['brk-style'] );
});

function custom_login_redirect( $redirect, $request, $user ) {
    // Change the URL below to the desired redirect URL
    $redirect_url = 'https://rescuecentre.org.uk/dashboard';

    // Check if there is a valid redirect URL
    if ( !empty( $redirect_url ) && is_string( $redirect_url ) ) {
        return $redirect_url;
    }

    // If no custom redirect URL is set, return the default redirect
    return $redirect;
}
add_filter( 'login_redirect', 'custom_login_redirect', 10, 3 );


function custom_woocommerce_login_redirect( $redirect, $user ) {
    // Change the URL below to the desired redirect URL
     $redirect_url = 'https://rescuecentre.org.uk/dashboard';

    // Check if there is a valid redirect URL
    if ( !empty( $redirect_url ) && is_string( $redirect_url ) ) {
        return $redirect_url;
    }

    // If no custom redirect URL is set, return the default redirect
    return $redirect;
}
add_filter( 'woocommerce_login_redirect', 'custom_woocommerce_login_redirect', 10, 2 );

//login logo Change
function wpb_login_logo() { ?>
<style type="text/css">
#login h1 a, .login h1 a {
background-image: url(https://rescuecentre.org.uk/wp-content/uploads/2023/04/Black-and-Green-Loog.png);
height:64px;
width:250px;
background-size: 250px 64px;
background-repeat: no-repeat;
padding-bottom: 20px;
}
</style>
<?php }
add_action( 'login_enqueue_scripts', 'wpb_login_logo' );
?>
<?php // Shortcode to locad a search form
/*
if (isset($_GET['error']) && $_GET['error'] == 'not_found') {
echo '<div class="alert alert-danger">Patient not found or incorrect passphrase.</div>';
   }
add_shortcode( 'search_error', 'wpc_elementor_shortcode'); 

//This is shortcode for the searchbox not in use
//function wpc_elementor_shortcode( $atts ) {
//    include ("public/searchpatient.php");
//}
//add_shortcode( 'search_patient', 'wpc_elementor_shortcode');*/ ?>






