<div>
    <h2>Authentication</h2>
    <div class="subdiv">	
	<?php
	if(isset($error)){echo "<span class='error'>".$error."</span><br/><br/>\n";}
	if(isset($message)){echo "<span class='message'>".$message."</span><br/><br/>\n";}
	?>
	
	<?php if(isset($registration) && $registration):?>	
	<h3>Registration</h3>	
	<?php
	echo $form->create("Authentication",array("url"=>array("controller"=>"trapid","action"=>"authentication","registration"),
		"type"=>"post"));	
	?>
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
	<span>Registration will be complete after the system has send you your password on the provided email-address</span>
	</form>

	<?php elseif(isset($pass_recovery) && $pass_recovery) :?>
	<h3>Password recovery</h3>
	<?php
	echo $form->create("Authentication",array("url"=>array("controller"=>"trapid","action"=>"authentication"),"type"=>"post"));
	?>	
	<?php else: ?>
	<?php 	
	echo $form->create("Authentication",array("url"=>array("controller"=>"trapid","action"=>"authentication"),"type"=>"post"));
	?>	
	<dl class="standard">
	    <dt><input type="text" name="login" /></dt>
	    <dd>User login</dd>
	    <dt><input type="password" name="password" /></dt>
	    <dd>Password</dd>
	</dl>
	<input type="submit" value="Login" />
	<br/><br/>
	<?php 
		echo $html->link("Registration",array("controller"=>"trapid","action"=>"authentication","registration"));
		echo "&nbsp; &nbsp;\n";
		//echo $html->link("Recover password",array("controller"=>"trapid","action"=>"authentication","password_recovery"));
	?>	
	</form>
	<?php endif; ?>

    </div>			
</div>
