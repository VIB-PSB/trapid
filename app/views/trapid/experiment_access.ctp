<div>
<h2>Experiment Access</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment"); ?>
	
	<h3>Current Access</h3>
	<div class='subdiv'>
		<table cellpadding='0' cellspacing='0' style='width:500px;'>
			<tr>
				<th style='width:100px;'>Group</th>
				<th>Email address</th>
			</tr>
			<?php
			$style	= null;
			if(count($shared_users['shared'])!=0){
				$style=" style='border-bottom:1px solid #CCCCCC' ";
			}
			foreach($shared_users['owner'] as $k=>$v){
				echo "<tr>";
				echo "<td $style><span style='font-weight:bold;color:blue'>Owner</span></td>";
				echo "<td $style><a href='mailto:".$v."'>".$v."</a></td>";
				echo "</tr>\n";		
			}	
					
			asort($shared_users['shared']);
			foreach($shared_users['shared'] as $k=>$v){
				echo "<tr>";
				echo "<td><span style='font-weight:bold;color:green'>Shared</span></td>";
				echo "<td><a href='mailto:".$v."'>".$v."</a></td>";
				echo "</tr>\n";
			}			
			?>						
		</table>
	</div>

	<?php
	if($is_owner){
	echo "<h3>Change Access</h3>\n";
	echo "<div class='subdiv'>\n";
	
	echo $form->create("",array("action"=>"experiment_access/".$exp_id."/","type"=>"post")); 
	
	echo "<span>Provide the email-addresses of people with a TRAPID account with which you want to share this experiment.</span>\n";
	echo "<br/><br/>\n";
	echo "<textarea name='new_share' rows='5' cols='80'></textarea>\n";
	echo "<br/><br/>\n";
	/*
	echo "<table cellpadding='0' cellspacing='0' style='width:600px;'>\n";
	echo "<tr>";
	echo "<th style='width:100px;'>Group</th>";
	echo "<th style='width:400px;'>Email address</th>";
	echo "<th>Share</th>";
	echo "</tr>\n";		

	$style	= null;
	if(count($shared_users['shared'])!=0){$style=" style='border-bottom:1px solid #CCCCCC' ";}
	foreach($shared_users['owner'] as $k=>$v){
		echo "<tr>";
		echo "<td $style><span style='font-weight:bold;color:blue'>Owner</span></td>";
		echo "<td $style><a href='mailto:".$v."'>".$v."</a></td>";
		echo "<td $style></td>";
		echo "</tr>\n";		
	}	
					
	asort($all_users);
	$counter	= 0;
	foreach($all_users as $k=>$v){
		if(!array_key_exists($k,$shared_users['owner'])){
		    $class=null; if($counter++%2==0){$class=" class='altrow2' ";}
		    echo "<tr $class>";
		    echo "<td><span style='font-weight:bold;color:green'>Shared</span></td>";
		    echo "<td><a href='mailto:".$v."'>".$v."</a></td>";
		    echo "<td>";
		    $checked	= null;
		    if(array_key_exists($k,$shared_users['shared'])){$checked = " checked='checked' ";}				
		    echo "<input type='checkbox'  name='".$k."' $checked />";		
		    echo "</td>";
		    echo "</tr>\n";
		}
	}	
	echo "</table>\n";
	*/

	echo "<input type='submit' value='Update experiment access' />\n";
	echo "</form>\n";

	echo "</div>\n";
	}
	?>
</div>
</div>
