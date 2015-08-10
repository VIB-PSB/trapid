<div>
	<h2>Change password</h2>
	<div class="subdiv">
		<?php
		if(isset($error)){echo "<span class='error'>".$error."</span><br/><br/>\n";}
		if(isset($message)){echo "<span class='message'>".$message."</span><br/><br/>\n";}

		echo $form->create(null,array("url"=>array("controller"=>"trapid","action"=>"change_password"),"type"=>"post"));
		echo "<dl class='standard'>\n";
		echo "<dt>New password</dt>\n";
		echo "<dd><input type='password' name='new_password1' required='true'/></dd>";
		echo "<dt>Repeat new password</dt>\n";
		echo "<dd><input type='password' name='new_password2' required='true'/></dd>";
		echo "</dl>\n";
		echo "<br/>";
		echo "<input type='submit' value='Change password' />\n";
		echo "</form>\n";
		?>
	</div>	
</div>
