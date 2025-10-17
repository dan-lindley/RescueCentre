<?php
include_once ("add_user.php");

?>


<form method="POST" action="">
<div class="row lead_form_row"> 
    <div class="col-md-4 my-auto">  
        <p class="angelo_form_label">Login (no spaces)</p>
        <input type="text" name="login" id="login" placeholder="Login name (no spaces)">
    </div>
    <div class="col-md-4 my-auto">  
        <p class="angelo_form_label">Display Name</p>
        <input type="text" name="username" id="username" placeholder="Display Name">
    </div>
        <div class="col-md-4 my-auto">  
        <p class="angelo_form_label">Role</p>
            <select name="role" id="role">
                <option value="4" selected>Vet</option>
				<option value="5">Vet Nurse</option>
			
			</select>
        </div>
</div>
<div class="row lead_form_row">   
    <div class="col-md-6 my-auto">  
        <p class="angelo_form_label">Email</p>
        <input type="text" name="email" id="email" placeholder="Users email adress">
        <small id="emailHelpBlock" class="form-text text-muted">
            <label class="form-check-label" for="flexSwitchCheckDefault">Users email must be unique and not already registered.</label>
        </small>
    </div>
    <div class="col-md-6 my-auto">  
      
        <p class="angelo_form_label">Password</p>
        <input type="password" name="pass" id="pass" placeholer = "Create a password"> 
        <BR>
        <div class="form-check form-switch">
            <small id="passwordHelpBlock" class="form-text text-muted">
              <input class="form-check-input" type="checkbox" role="switch" id="flexSwitchCheckDefault" onclick="myFunction()">
            <label class="form-check-label" for="flexSwitchCheckDefault">Show password</label>
        </small>
        </div>
        
    </div>
    <div class="col-md-2 my-auto">   
    </div>
</div>

<div class="row lead_form_row">   
    <div class="col-md-3 my-auto"> 



        <input type="hidden" id="centre_id" name="centre_id" value="<?php echo $centre_id; ?>">
        <button type="submit" class="btn btn-info" name="adduser">Add User</button> <BR>
        </form>
   </div>
</div>

<script>
function myFunction() {
  var x = document.getElementById("pass");
  if (x.type === "password") {
    x.type = "text";
  } else {
    x.type = "password";
  }
}
</script>