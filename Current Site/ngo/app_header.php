<?php
$root = $_SERVER['DOCUMENT_ROOT'];

require_once("".$root."/wp-load.php");
include_once("".$root."/wp-content/themes/brikk-child/get_user_info.php");

//Set timezone. This will be used for PHP "date" functions
date_default_timezone_set('Europe/London');
?>
<style>
.ngo-sidebar{
    background: #8d1491;
	font-size: 10px;
}
</style>
<!-- Custom fonts for this template-->
<link href="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
<link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

<!-- Custom styles for this template-->
<link href="https://rescuecentre.org.uk/wp-content/themes/brikk-child/css/sb-admin-2.min.css" rel="stylesheet">


<!-- Page Wrapper -->
<div id="wrapper">

    <!-- Sidebar -->
    <ul class="ngo-sidebar navbar-nav  sidebar sidebar-dark accordion" id="accordionSidebar">

        <!-- Sidebar - Brand -->
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="https://rescuecentre.org.uk/dashboard/">
            <img class="icon_only_logo" src="https://rescuecentre.org.uk/wp-content/uploads/2023/04/icon-only.png">
            <div class="sidebar-brand-text mx-3"><img src="https://rescuecentre.org.uk/wp-content/uploads/2023/04/logo-square-white.png"></div>
        </a>

        <!-- Divider -->
        <hr class="sidebar-divider my-0">
		

        <!-- Nav Item - Dashboard -->
        <li class="nav-item" id="dashboard_link">
            <a class="nav-link" href="https://rescuecentre.org.uk/dashboard/">
                <i class="fas fa-fw fa-tachometer-alt"></i>
                <span>Dashboard</span></a>
				
        </li>
		

   
		        <li class="nav-item" id="query_link">
            <a class="nav-link" href="https://rescuecentre.org.uk/query">
                <i class="fas fa-fw fa-filter"></i>
                <span>Query Builder</span></a>
        </li>


       <!-- <li class="nav-item" id="settings_link">
            <a class="nav-link" href="https://rescuecentre.org.uk/centre">
                <i class="fas fa-fw fa-cogs"></i>
                <span>Settings</span></a>-->
        </li>




        <!-- Divider -->
          <hr class="sidebar-divider my-0">
			       

        <!-- Divider -->
        <hr class="sidebar-divider d-none d-md-block">   
        
        <!-- Sidebar Toggler (Sidebar) -->
        <div class="text-center d-none d-md-inline">
            <button class="rounded-circle border-0" id="sidebarToggle"></button>
        </div>


    </ul>
    <!-- End of Sidebar -->

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

        <!-- Main Content -->
        <div id="content">

            <!-- Topbar -->
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                <!-- Sidebar Toggle (Topbar) -->
                <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                    <i class="fa fa-bars"></i>
                </button>


                <!-- Topbar Navbar -->
                <ul class="navbar-nav ml-auto">

                    <!-- Nav Item - Search Dropdown (Visible Only XS) -->
                    <li class="nav-item dropdown no-arrow d-sm-none">
                        <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-search fa-fw"></i>
                        </a>
                        <!-- Dropdown - Messages -->
                        <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in" aria-labelledby="searchDropdown">
                            <form class="form-inline mr-auto w-100 navbar-search">
                                <div class="input-group">
                                    <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..." aria-label="Search" aria-describedby="basic-addon2">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="button">
                                            <i class="fas fa-search fa-sm"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </li>

                    <div class="topbar-divider d-none d-sm-block"></div>


                    <?php
                    //Get logged in user's name 
                    $user_info = get_userdata(get_current_user_id());
                    $wp_first_name = $user_info->first_name;
                    $wp_last_name = $user_info->last_name;
					$wp_display_name = $user_info->display_name;
					$wp_id = $user_info->id;
					$wp_rescue_role = $user_info->rescue_role;
                    $wp_fullname = "" . $wp_first_name . " " . $wp_last_name . " ";
                    ?>
                    <!-- Nav Item - User Information -->
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="mr-2 d-none d-lg-inline text-gray-600 small"><b><?php echo $wp_fullname; ?></b> <BR><?php echo $accessrole; ?></span>
                            <?php $user = wp_get_current_user();
								if ( $user ) : ?>
								<img class="img-profile rounded-circle" src="<?php echo esc_url( get_avatar_url( $user->ID ) ); ?>" />
							<?php endif; ?>

                        </a>
                        <!-- Dropdown - User Information -->
                        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">

							 <a class="dropdown-item" href="https://rescuecentre.org.uk/edit-account/">
                                <i class="fas fa-user-edit fa-sm fa-fw mr-2 text-gray-400"></i>
                               Edit my Account
                            </a>
               							<a class="dropdown-item" href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>">
                               <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
  								<?php esc_html_e( 'Logout', 'brikk' ); ?>
                            </a>

                        </div>
                    </li>

                </ul>

            </nav>
            <!-- End of Topbar -->