<!-- TODO: move inline style to separate stylesheet -->
<style>
.form-wrapper {
margin-top: 70px;
margin-bottom: 70px;
}

.form-signin,
.form-signup {
max-width: 380px;
padding: 15px 35px 7px;
margin: 0 auto;
background-color: #fff;
border: 1px solid rgba(0,0,0,0.1);

.form-signin-heading,
.checkbox {
  margin-bottom: 30px;
}

.form-control {
  position: relative;
  font-size: 16px;
  height: auto;
  padding: 10px;
  @include box-sizing(border-box);

  &:focus {
    z-index: 2;
  }
}

input[type="email"] {
  margin-bottom: -1px;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
}

input[type="password"] {
  margin-bottom: 20px;
  border-top-left-radius: 0;
  border-top-right-radius: 0;
}
}
</style>

<div class="container">
    <div class="subdiv">
	<?php
	if(isset($error)){
        echo "<div class=\"alert alert-warning alert-dismissible\" role=\"alert\">";
        echo "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button>";
        echo "<strong>Error:</strong> ".$error;
        echo"</div>";
//	    echo "<span class='error'>".$error."</span><br/><br/>\n";
	}
	if(isset($message)){echo "<span class='message'>".$message."</span><br/><br/>\n";}
	?>

	<?php if(isset($registration) && $registration):?>
    <div class="page-header" style="margin-top: 20px;">
      <h1 class="text-primary">Create a TRAPID account</h1>
    </div>
<!--
	<h3>Registration</h3>
-->
<div class="form-wrapper">
	<?php
	echo $form->create("Authentication",array("url"=>array("controller"=>"trapid","action"=>"authentication","registration"),
		"type"=>"post", "class"=>"form-signup"));
	?>
  <input type="hidden" name="registration" value="registration" />
  <div class="form-group">
    <label for="login"><strong>Email</strong></label>
    <input type="email" class="form-control" name="login" id="login" placeholder="you@example.org">
  </div>
  <div class="form-group">
    <label for="organization"><strong>Organization</strong></label>
    <input type="text" class="form-control" name="organization" id="organization" placeholder="Example University">
  </div>
  <div class="form-group">
    <label for="country"><strong>Country</strong></label>
    <input type="text" class="form-control" name="country" id="country" placeholder="Example Country">
  </div>
<!--  <a style="margin-top:30px;" class="btn btn-lg btn-primary btn-block">Register</a>-->
  <button type="submit" style="margin-top:30px;" class="btn btn-lg btn-primary btn-block">Register</button>
  <br>
  <p class="text-justify text-muted">Registration will be complete after the system has sent you a password to the provided email address. </p>
</form>
</div>

<!--
	<input type="hidden" name="registration" value="registration" />
	<dl class="standard">
	    <dt><input type="text" name="login"/></dt>
	    <dd>User login (valid email-address)</dd>
	    <dt><input type="text" name="organization"/></dt>
	    <dd>Organization (e.g. university)</dd>
	    <dt><input type="text" name="country"/></dt>
	    <dd>Country</dd>
	</dl>
	<input type="submit" value="Register" />
	<br/><br/>
	<span>Registration will be complete after the system has sent you a password on the provided email address</span>
	</form>
-->

	<?php elseif(isset($pass_recovery) && $pass_recovery) :?>
	<h3>Password recovery</h3>
	<?php
	echo $form->create("Authentication",array("url"=>array("controller"=>"trapid","action"=>"authentication"),"type"=>"post"));
	?>
	<?php else: ?>
    <div class="page-header" style="margin-top: 20px;">
      <h1 class="text-primary">Login to TRAPID</h1>
    </div>
    <div class="form-wrapper">
	<?php
	echo $form->create("Authentication",array("url"=>array("controller"=>"trapid","action"=>"authentication"),"type"=>"post", "class"=>"form-signin"));
	?>
    <div class="form-group">
      <label for="login"><strong>Email</strong></label>
      <input type="email" class="form-control" name="login" id="login" placeholder="you@example.org">
    </div>
    <div class="form-group">
      <label for="password"><strong>Password</strong></label>
      <input type="password" class="form-control" name="password" id="password" placeholder="********">
    </div>
    <button type="submit" style="margin-top:30px;" class="btn btn-lg btn-primary btn-block">Login</button>
    <br>
    <p class="text-justify text-muted">If you do not have an account, you can create one
      <?php
      echo $html->link("here",array("controller"=>"trapid","action"=>"authentication","registration"));
      // echo $html->link("Recover password",array("controller"=>"trapid","action"=>"authentication","password_recovery"));
      ?>. </p>
  </form>
</div>
<!--
	<dl class="standard">
	    <dt><input type="text" name="login" /></dt>
	    <dd>User login</dd>
	    <dt><input type="password" name="password" /></dt>
	    <dd>Password</dd>
	</dl>
	<input type="submit" value="Login" />
-->
	</form>
	<?php endif; ?>

    </div>
</div>
