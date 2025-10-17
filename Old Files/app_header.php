<!doctype html>
<?php
//ini_set('display_errors', '1');
//ini_set('display_startup_errors', '1');
//error_reporting(E_ALL);

$root = $_SERVER['DOCUMENT_ROOT'];
require_once("".$root."/wp-load.php");
include_once("".$root."/wp-content/themes/brikk-child/get_user_info.php");
include("".$root."/wp-content/themes/brikk-child/lang.php");
//Set timezone. This will be used for PHP "date" functions
date_default_timezone_set('Europe/London');
?>

<!-- Custom fonts for this template-->
<link href="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
<link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
<!-- Custom styles for this template-->
<link href="https://rescuecentre.org.uk/wp-content/themes/brikk-child/css/sb-admin-2.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Page Wrapper -->
<div id="wrapper">

    <!-- Sidebar -->
    <ul class="dan-sidebar navbar-nav  sidebar sidebar-dark accordion" id="accordionSidebar">

        <!-- Sidebar - Brand -->
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="https://rescuecentre.org.uk/dashboard/">
            <img class="icon_only_logo" src="https://rescuecentre.org.uk/wp-content/uploads/2023/04/icon-only.png">
            <div class="sidebar-brand-text mx-3"><img src="https://rescuecentre.org.uk/wp-content/uploads/2023/04/logo-square-white.png">         
        </div>  
    </a>

    <!-- Nav Item - Dashboard -->
 
    <span><H6 class="text-center nav-item"><font color="white"> 
        <?php echo $rescue_name; ?></h6> </font></span>
    
    <hr class="sidebar-divider my-0">

        <li class="nav-item" id="dashboard_link">
            <a class="nav-link" href="https://rescuecentre.org.uk/dashboard/">
                <i class="fa-solid fa-chart-line"></i>
                <span><?php echo $lang['LM_DASHBOARD']; ?></span></a>
				
        </li>
		

    <li class="nav-item" id="patients_link"><a class="nav-link" href="#patients" data-toggle="collapse" data-target="#patients" aria-expanded="false" aria-controls="patients">
         <i class="fas fa-fw fa-paw"></i> <span><?php echo $lang['LM_PATIENTS']; ?></span></a></li>

<div class="collapse" id="patients">
           <li class="nav-item" id="patients_link">
            <a class="nav-link" href="https://rescuecentre.org.uk/patients">
                <i class="fas fa-fw fa-paw"></i>
                <span><?php echo $lang['LM_MY_PATIENTS']; ?></span></a>	
        </li>

        <li class="nav-item" id="patients_archive">
            <a class="nav-link" href=" https://rescuecentre.org.uk/all-patients/">
                <i class="fas fa-fw fa-archive"></i>
                <span><?php echo $lang['LM_PATIENT_ARCHIVE']; ?></span></a>
				
        </li>
        <li class="nav-item" id="residents_link">
            <a class="nav-link" href=" https://rescuecentre.org.uk/residents/">
                <i class="fas fa-fw fa-home"></i>
                <span><?php echo $lang['PAT_RESIDENTS']; ?></span></a>
				
        </li>

    </li>
</div>

        <li class="nav-item" id="tasks_link">
            <a class="nav-link" href=" https://rescuecentre.org.uk/tasks/">
                <i class="fas fa-fw fa-list"></i>
                <span><?php echo $lang['LM_TASKS']; ?></span></a>
				
        </li>

        <li class="nav-item" id="incidents_link">
            <a class="nav-link" href=" https://rescuecentre.org.uk/incidents/">
                <i class="fas fa-fw fa-exclamation-triangle"></i>
                <span><?php echo $lang['LM_INCIDENTS']; ?></span></a>
				
        </li>
		 
       	
		<!--<li class="nav-item" id="admissions_link"><a class="nav-link" href="#admissions" data-toggle="collapse" data-target="#admissions" aria-expanded="false" aria-controls="admissions">
 <i class="fas fa-fw fa-clipboard"></i> <span><?php echo $lang['LM_FORMS']; ?></span></a></li>

	<div class="collapse" id="admissions">
		<li class="nav-item" id="new_patient_link">
    	 <a class="nav-link" href="#"> 
		 <i class="fas fa-fw fa-plus"></i> <span>New Patient</span></a>
    	 </li>
		<<li class="nav-item" id="new_admissions_link">
    	 <a class="nav-link" href="https://rescuecentre.org.uk/new_admission"> 
		 <i class="fas fa-fw fa-plus"></i> <span><?php echo $lang['LM_NEW_ADMISSION']; ?></span></a>
    	 </li>
	
	     <li class="nav-item" id="mop_link">
         <a class="nav-link" href="#">
         <i class="fas fa-fw fa-plus"></i><span>MoP Form</span></a>
          </li>
		
		  <li class="nav-item" id="no_admit_link">
         <a class="nav-link" href="#">
         <i class="fas fa-fw fa-plus"></i><span>No Admit Form</span></a>
          </li>
	</div>-->
		
		<!--<li class="nav-item" id="admissions_link"><a class="nav-link" href="#reports" data-toggle="collapse" data-target="#reports" aria-expanded="false" aria-controls="reportss">
 <i class="fas fa-fw fa-book"></i> <span>Reports</span></a></li>

	<div class="collapse" id="reports">
		 <li class="nav-item" id="new_patient_link">
    	 <a class="nav-link" href="https://rescuecentre.org.uk/annual-report/?centre_id=<?php //echo $centre_id; ?>"> 
		 <i class="fas fa-fw fa-check"></i> <span>Annual Report</span></a>
    	 </li>
	</div>-->
		
         <!-- Divider -->
    <li class="nav-item" id="medications_link"><a class="nav-link" href="#medications" data-toggle="collapse" data-target="#medications" aria-expanded="false" aria-controls="medications">
         <i class="fas fa-fw fa-pills"></i> <span><?php echo $lang['LM_MEDICATION']; ?></span></a></li>

<div class="collapse" id="medications">
        <li class="nav-item" id="medications_link">
            <a class="nav-link" href="https://rescuecentre.org.uk/medication">
                <i class="fas fa-fw fa-pills"></i>
                <span><?php echo $lang['LM_MEDICATION_ROUND']; ?></span></a>
        </li>

        <li class="nav-item" id="medications_link">
            <a class="nav-link" href="https://rescuecentre.org.uk/medications_profile/">
                <i class="fas fa-fw fa-pills"></i>
                <span><?php echo $lang['LM_STOCK_MANAGEMENT']; ?></span></a>
        </li>
    </li>
</div>
        

        <!-- Divider
        <li class="nav-item" id="reports_link">
            <a class="nav-link" href="#">
                <i class="fas fa-fw fa-chart-bar"></i>
                <span>Reports</span></a>
        </li>
        -->
		        <li class="nav-item" id="query_link">
            <a class="nav-link" href="https://rescuecentre.org.uk/query">
                <i class="fas fa-fw fa-filter"></i>
                <span><?php echo $lang['LM_QUERY_BUILDER']; ?></span></a>
        </li>


<!--  Management Settings only shown to owners -->
<?php
if ($accesslevel === "1"){ ;?>
                                    
<li class="nav-item" id="settings_link"><a class="nav-link" href="#settings" data-toggle="collapse" data-target="#settings" aria-expanded="false" aria-controls="settings">
    <i class="fas fa-fw fa-cogs"></i>
	    <span><?php echo $lang['LM_CENTRE_MANAGEMENT']; ?></span></a></li>

<div class="collapse" id="settings">
	<li class="nav-item" id="settings_link_2">  <a class="nav-link" href="https://rescuecentre.org.uk/centre">
                <i class="fas fa-fw fa-cogs"></i>
			  <span><?php echo $lang['MENU_SETTINGS']; ?></span></a></li>
	
	 <li class="nav-item" id="connections_link"><a class="nav-link" href="https://rescuecentre.org.uk/connections" >
                <i class="fas fa-fw fa-user-friends"></i>
		 <span><?php echo $lang['MENU_CONNECTIONS']; ?></span></a></li>
	
	 <li class="nav-item" id="networks_link"><a class="nav-link" href="https://rescuecentre.org.uk/networks">
                <i class="fas fa-fw fa-network-wired"></i>
		 <span><?php echo $lang['MENU_NETWORKS']; ?></span></a></li>

    <li class="nav-item" id="managetasks_link"><a class="nav-link" href="https://rescuecentre.org.uk/managetasks">
                <i class="fas fa-fw fa-list-ol"></i>
		 <span><?php echo $lang['LM_MANAGE_TASKS']; ?></span></a></li>
		 
	<li class="nav-item" id="medications_profile_link"><a class="nav-link" href="https://rescuecentre.org.uk/medications_profile">
                <i class="fas fa-fw fa-tablets"></i>
		 <span><?php echo $lang['LM_STOCK_MEDICATION']; ?></span></a></li>
		 
	<li class="nav-item" id="users_link"><a class="nav-link" href="https://rescuecentre.org.uk/manageusers">
                <i class="fas fa-fw fa-users"></i>
		 <span><?php echo $lang['LM_MANAGE_USERS']; ?></span></a></li>
	
</div><?php ; }
 ?>	


        <!-- Divider -->
          <hr class="sidebar-divider my-0">
			        <li class="nav-item" c>
            <a class="nav-link" href="https://rescuecentre.org.uk/resources">
                <i class="fas fa-fw fa-book-open"></i>
                <span><?php echo $lang['LM_RESOURCES']; ?></span></a>
        </li>
		        <li class="nav-item" id="knowledge_link">
            <a class="nav-link" href="https://rescuecentre.org.uk/knowledge">
                <i class="fas fa-fw fa-graduation-cap"></i>
                <span><?php echo $lang['LM_KNOWLEDGEBASE']; ?></span></a>
        </li>


        <?php
			$stmt = $conn->prepare("SELECT * FROM wpxp_users 
		    WHERE wpxp_users.id = " . $logged_in_id ." AND rescue_role <> 0 LIMIT 1");
			$statement = $conn->prepare($sql);


                            // this array is populated if the access role is right
                            $orgusermatch = array();
                            $stmt->execute();
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $org_id = $row["assigned_org"];

				//This displays sms form if phone number stored for patient
									if (!empty($org_id)){ ?>

                                <li class="nav-item" id="knowledge_link">
            <a class="nav-link" href="https://rescuecentre.org.uk/organisation/?org_id=<?php echo $org_id; ?>">
                <i class="fas fa-fw fa-graduation-cap"></i>
                <span><?php echo $lang['LM_ORG_DASH']; ?></span></a>
        </li>
		 <?php ; }
                            }

                            ?>	

        <hr class="sidebar-divider my-0">
			<li class="nav-item" id="support_link">
            <a class="nav-link" href="https://chat.whatsapp.com/LsMUUtKlBXiAlv3Jl5NAW0">
               <i class="fab fa-whatsapp" style="color:green"></i>
                <span><?php echo $lang['LM_SUPPORT']; ?></span></a>
        </li>

        <!-- Divider -->
   
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

<!-- NETWORKS & Connections only visible to managers -->
<?php
if ($accesslevel === "1"){ ;?>			
<li class="nav-item dropdown no-arrow mx-1">
    <a class="nav-link dropdown-toggle" href="https://rescuecentre.org.uk/networks" id="networks" role="button">
     <i class="fas fa-fw fa-network-wired"></i>
	</a>
</li> 
<li class="nav-item dropdown no-arrow mx-1">
    <a class="nav-link dropdown-toggle" href="https://rescuecentre.org.uk/connections" id="connections" role="button">
     <i class="fas fa-fw fa-user-friends"></i>
    </a>
</li> 
<?php } ;?>

<!-- Nav Item - -->
              
<!-- LANGUAGE SELECT SECTION  -->	
<div class="topbar-divider d-none d-sm-block"></div>         
    
    <li class="nav-item dropdown no-arrow mx-1">
        <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <img src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/languages/<?php echo $lang['LANG_ICON']; ?>" width="18px" height="18px">&nbsp; &nbsp;<?php echo $lang['CURRENT_LANGUAGE']; ?> 
        </a>

    <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="alertsDropdown">
        <h6 class="dropdown-header">Languages </h6>

	    <a class="dropdown-item d-flex align-items-center" href="index.php?lang=es"> <img src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/languages/es.png" width="18px" height="18px"> &nbsp; &nbsp; Español</a>
        <div>
		<a class="dropdown-item d-flex align-items-center" href="index.php?lang=en"> <img src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/languages/en.png" width="18px" height="18px"> &nbsp; &nbsp; English</a>
        <div>
	</div>
    </li> 

<!-- Nav Item - -->

				
					
                    <!-- Nav Item - Messages 
                    <li class="nav-item dropdown no-arrow mx-1">
                        <a class="nav-link dropdown-toggle" href="#" id="messagesDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-envelope fa-fw"></i>

                            
                            <span class="badge badge-danger badge-counter">7</span>
                        </a>
                       
                        <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="messagesDropdown">
                            <h6 class="dropdown-header">
                                Message Center
                            </h6>
                            <a class="dropdown-item d-flex align-items-center" href="#">
                                <div class="dropdown-list-image mr-3">
                                    <img class="rounded-circle" src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/img/undraw_profile_1.svg" alt="...">
                                    <div class="status-indicator bg-success"></div>
                                </div>
                                <div class="font-weight-bold">
                                    <div class="text-truncate">Hi there! I am wondering if you can help me with a
                                        problem I've been having.</div>
                                    <div class="small text-gray-500">Emily Fowler 路 58m</div>
                                </div>
                            </a>
                            <a class="dropdown-item d-flex align-items-center" href="#">
                                <div class="dropdown-list-image mr-3">
                                    <img class="rounded-circle" src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/img/undraw_profile_2.svg" alt="...">
                                    <div class="status-indicator"></div>
                                </div>
                                <div>
                                    <div class="text-truncate">I have the photos that you ordered last month, how
                                        would you like them sent to you?</div>
                                    <div class="small text-gray-500">Jae Chun 路 1d</div>
                                </div>
                            </a>
                            <a class="dropdown-item d-flex align-items-center" href="#">
                                <div class="dropdown-list-image mr-3">
                                    <img class="rounded-circle" src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/img/undraw_profile_3.svg" alt="...">
                                    <div class="status-indicator bg-warning"></div>
                                </div>
                                <div>
                                    <div class="text-truncate">Last month's report looks great, I am very happy with
                                        the progress so far, keep up the good work!</div>
                                    <div class="small text-gray-500">Morgan Alvarez 路 2d</div>
                                </div>
                            </a>
                            <a class="dropdown-item d-flex align-items-center" href="#">
                                <div class="dropdown-list-image mr-3">
                                    <img class="rounded-circle" src="https://source.unsplash.com/Mv9hjnEUHR4/60x60" alt="...">
                                    <div class="status-indicator bg-success"></div>
                                </div>
                                <div>
                                    <div class="text-truncate">Am I a good boy? The reason I ask is because someone
                                        told me that people say this to all dogs, even if they aren't good...</div>
                                    <div class="small text-gray-500">Chicken the Dog 路 2w</div>
                                </div>
                            </a>
                            <a class="dropdown-item text-center small text-gray-500" href="#">Read More Messages</a>
                        </div>
                    </li>
                    -->

                    <div class="topbar-divider d-none d-sm-block"></div>


                    <?php
                    //Get logged in user's name 
                    $user_info = get_userdata(get_current_user_id());
                    $wp_first_name = $user_info->first_name;
                    $wp_last_name = $user_info->last_name;
					$wp_display_name = $user_info->display_name;
					$wp_id = $user_info->id;
				
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

						   <a class="dropdown-item" href="https://rescuecentre.org.uk/centre">
                                <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                                <?php echo $lang['MENU_SETTINGS']; ?>
                            </a>
                            <a class="dropdown-item" href="https://rescuecentre.org.uk/connections">
                                <i class="fas fa-user-friends fa-sm fa-fw mr-2 text-gray-400"></i>
                                <?php echo $lang['MENU_CONNECTIONS']; ?>
                            </a>
                            <a class="dropdown-item" href="https://rescuecentre.org.uk/networks">
                                <i class="fas fa-network-wired fa-sm fa-fw mr-2 text-gray-400"></i>
                                <?php echo $lang['MENU_NETWORKS']; ?>
                            </a>
                            <div class="dropdown-divider"></div>
														    <a class="dropdown-item" href="https://rescuecentre.org.uk/edit-account/">
                                <i class="fas fa-user-edit fa-sm fa-fw mr-2 text-gray-400"></i>
                                <?php echo $lang['MENU_EDIT_ACCOUNT']; ?>
                            </a>
               							<a class="dropdown-item" href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>">
                               <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
  								<?php echo $lang['MENU_LOGOUT']; ?>
                            </a>

                        </div>
                    </li>

                </ul>

            </nav>
            <!-- End of Topbar -->