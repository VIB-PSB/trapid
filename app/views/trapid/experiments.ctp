<?php 
//phpinfo(); 
//pr(phpversion());
?>


<div>

<h2>User information</h2>
<div class="subdiv">
	<dl class="standard">
		<dt>User id</dt>
		<dd><?php echo $user_email['Authentication']['email'];?></dd>
		<dt>Change password</dt>
		<dd><?php echo $html->link("Define a new TRAPID password",array("controller"=>"trapid","action"=>"change_password"));?></dd>
		<dt>Exit trapid</dt>
		<dd><?php echo $html->link("Log out",array("controller"=>"trapid","action"=>"log_off"));?></dd>
	</dl>
</div>
<br/><br/>
<h2>Experiments overview</h2>
<div class="subdiv">
	
	<dl class="standard">	
	<dt>Current experiments</dt>
	<dd>
	<div>			
		<table cellspacing="0" cellpadding="0" style="width:900px;">
		<tr>
			<th style="width:20%;">Name</th>
			<th style="width:10%;">#Transcripts</th>
			<th style="width:10%;">Status</th>
			<th style="width:20%;">Last edit</th>
			<th style="width:13%;">PLAZA version</th>			
			<th style="width:5%;">Empty</th>
			<th style="width:5%;">Delete</th>			
			<th style="width:10%;">Log</th>
			<th style="width:10%;">Jobs</th>
		</tr>
		<?php
	
		//pr($experiments);

		if(count($experiments)==0){
			echo "<tr class='disabled'>";
			echo "<td>Unavailable</td><td>0</td><td>Unavailable</td>";
			echo "<td>Unavailable</td><td>Unavailable</td><td></td><td></td>";
			echo "<td></td>";	
			echo "</tr>\n";
		}
		else{
			foreach($experiments as $experiment){
			$e	= $experiment['Experiments'];
			if($e['process_state']=="error"){
				echo "<tr class='error_state'>";
			    	echo "<td>".$e['title']."</td>";
				echo "<td><span id='exp_count_".$e['experiment_id']."'>".$experiment['count']."</span></td>";
				echo "<td>".$html->link($e['process_state'],array("controller"=>"trapid","action"=>"change_status",$e['experiment_id']),array("style"=>"color:red;text-decoration:underline;"))."</td>";
				echo "<td>".$e['last_edit_date']."</td>";
				if($experiment['DataSources']['URL']){
				    echo "<td>".$html->link($experiment['DataSources']['name'],$experiment['DataSources']['URL'])."</td>";
			    	}
			    	else{
				    echo "<td>".$experiment['DataSources']['name']."</td>";
			    	}
				echo "<td></td>";
				echo "<td></td>";
				echo "<td>".$html->link("View log",array("controller"=>"trapid","action"=>"view_log",$e['experiment_id']))."</td>\n";	
				if(count($experiment['experiment_jobs'])==0){echo "<td>NA</td>";}
				else{echo "<td>".$html->link(count($experiment['experiment_jobs'])." jobs",array("controller"=>"trapid","action"=>"manage_jobs",$e['experiment_id']))."</td>";}				
			    	echo "</tr>\n";
			}
			else if ($e['process_state']=="loading_db"){
				echo "<tr class='processing_state'>";
			    	echo "<td>".$e['title']."</td>";
				//echo "<td>".$experiment['count']."</td>";
				echo "<td><span id='exp_count_".$e['experiment_id']."'>".$experiment['count']."</span></td>";
				echo "<td>".$html->link($e['process_state'],array("controller"=>"trapid","action"=>"change_status",$e['experiment_id']),array("style"=>"color:blue;text-decoration:underline;"))."</td>";
				echo "<td>".$e['last_edit_date']."</td>";
				if($experiment['DataSources']['URL']){
				    echo "<td>".$html->link($experiment['DataSources']['name'],$experiment['DataSources']['URL'])."</td>";
			    	}
			    	else{
				    echo "<td>".$experiment['DataSources']['name']."</td>";
			    	}
				echo "<td></td>";
				echo "<td></td>";
				echo "<td>".$html->link("View log",array("controller"=>"trapid","action"=>"view_log",$e['experiment_id']))."</td>\n";		
				if(count($experiment['experiment_jobs'])==0){echo "<td>NA</td>";}
				else{echo "<td>".$html->link(count($experiment['experiment_jobs'])." jobs",array("controller"=>"trapid","action"=>"manage_jobs",$e['experiment_id']))."</td>";}
			    	echo "</tr>\n";
			}
			else if ($e['process_state']=="processing"){
				echo "<tr class='processing_state'>";
			    	echo "<td>".$e['title']."</td>";
				//echo "<td>".$experiment['count']."</td>";
				echo "<td><span id='exp_count_".$e['experiment_id']."'>".$experiment['count']."</span></td>";
				echo "<td>".$html->link($e['process_state'],array("controller"=>"trapid","action"=>"change_status",$e['experiment_id']),array("style"=>"color:blue;text-decoration:underline;"))."</td>";
				echo "<td>".$e['last_edit_date']."</td>";
				if($experiment['DataSources']['URL']){
				    echo "<td>".$html->link($experiment['DataSources']['name'],$experiment['DataSources']['URL'])."</td>";
			    	}
			    	else{
				    echo "<td>".$experiment['DataSources']['name']."</td>";
			    	}
				echo "<td></td>";
				echo "<td></td>";
				echo "<td>".$html->link("View log",array("controller"=>"trapid","action"=>"view_log",$e['experiment_id']))."</td>\n";		
				if(count($experiment['experiment_jobs'])==0){echo "<td>NA</td>";}
				else{echo "<td>".$html->link(count($experiment['experiment_jobs'])." jobs",array("controller"=>"trapid","action"=>"manage_jobs",$e['experiment_id']))."</td>";}
			    	echo "</tr>\n";
			}
			else{
			    echo "<tr>";			
			    echo "<td>".$html->link($e['title'],array("action"=>"experiment",$e['experiment_id']))."</td>";
			    //echo "<td>".$experiment['count']."</td>";
			    echo "<td><span id='exp_count_".$e['experiment_id']."'>".$experiment['count']."</span></td>";
			    echo "<td>".$e['process_state']."</td>";
			    echo "<td>".$e['last_edit_date']."</td>";
			    if($experiment['DataSources']['URL']){
				    echo "<td>".$html->link($experiment['DataSources']['name'],$experiment['DataSources']['URL'])."</td>";
			    }
			    else{
				    echo "<td>".$experiment['DataSources']['name']."</td>";
			    }
			    echo "<td>".$html->link("E",
				    array("controller"=>"trapid","action"=>"empty_experiment",$e['experiment_id']),
				    array("style"=>"color:#AA0055;font-weight:bold;"),
				    "Are you sure you want to delete all content from this experiment?")."</td>";
			    echo "<td>".$html->link("X",
				    array("controller"=>"trapid","action"=>"delete_experiment",$e['experiment_id']),
				    array("style"=>"color:red;font-weight:bold;"),
				    "Are you sure you want to delete the experiment?")."</td>";
			    echo "<td>".$html->link("View log",array("controller"=>"trapid","action"=>"view_log",$e['experiment_id']))."</td>\n";					
			    if(count($experiment['experiment_jobs'])==0){echo "<td>NA</td>";}
				else{echo "<td>".$html->link(count($experiment['experiment_jobs'])." jobs",array("controller"=>"trapid","action"=>"manage_jobs",$e['experiment_id']))."</td>";}
			    echo "</tr>\n";
			}
		    }
		}			
		?>			
		</table>
		<script type='text/javascript'>
		   	//<![CDATA[
				var experiments = <?php echo $javascript->object($experiments); ?>;
				for(var i=0;i<experiments.length;i++){
					var experiment_id = experiments[i]["Experiments"]["experiment_id"];
					var span_id = "exp_count_"+experiment_id;
					var ajax_url = <?php echo "\"".$html->url(array("controller"=>"trapid","action"=>"experiments_num_transcripts"))."\"";?>+"/"+experiment_id+"/";				
					new Ajax.Updater(span_id,ajax_url, {asynchronous:true, evalScripts:true, requestHeaders:['X-Update', span_id]});
				}
			//]]>
		</script>
	</div>
	</dd>

	<?php if(count($shared_experiments)!=0): ?>
	<dt>Shared experiments</dt>
	<dd>
	<div>		
		<table cellspacing="0" cellpadding="0" style="width:900px;">
		<tr>
			<th style="width:30%;">Name</th>
			<th style="width:40%;">Owner</th>	
			<th style="width:20%;">PLAZA version</th>	
			<th style="width:10%;">Log</th>
		</tr>
		<?php
		foreach($shared_experiments as $experiment){
			$e	= $experiment['Experiments'];
			echo "<tr>";
			echo "<td>".$html->link($e['title'],array("controller"=>"trapid","action"=>"experiment",$e['experiment_id']))."</td>";				
			$owner_email	= $all_user_ids[$e['user_id']];
			echo "<td><a href='mailto:".$owner_email."'>".$owner_email."</a></td>";
			if($experiment['DataSources']['URL']){	
				echo "<td>".$html->link($experiment['DataSources']['name'],$experiment['DataSources']['URL'])."</td>";
			}
			else{
				echo "<td>".$experiment['DataSources']['name']."</td>";
			}
			 echo "<td>".$html->link("View log",array("controller"=>"trapid","action"=>"view_log",$e['experiment_id']))."</td>\n";		
			echo "</tr>\n";
		}
		?>
		</table>
	</div>	
	</dd>		

	<?php endif; ?>


	<?php if(count($experiments)<$max_user_experiments): ?>
	<dt>Add new experiment</dt>
	<dd>
	<div>		
		<?php
		    if(isset($error)){
			echo "<span class='error'>".$error."</span><br/>\n";
		    }
		    echo $form->create("Experiments",array("url"=>array("controller"=>"trapid","action"=>"experiments"),
						"type"=>"post"));	
		?>
		<dl class="nb">
			<dt>Name</dt>
			<dd><input type="text" name="experiment_name" maxlength="50" style="width:400px;"/></dd>
			<dt>Description</dt>
			<dd><textarea rows="4" name="experiment_description" style="width:400px;"></textarea></dd>
			<dt>Reference DB</dt>
			<dd>	
				<div>
				<select name="data_source" style="width:150px;">
				<?php
				foreach($available_sources as $av){
				echo "<option value='".$av['DataSources']['db_name']."'>".$av['DataSources']['name']."</option>\n";
				}
				?>
				</select>
				<span style='margin-left:20px;font-weight:bold;color:black'>Note: GO annotations are only available for the PLAZA reference database</span>
				
				</div>	
			</dd>		
		</dl>					
		<input type="submit" value="Create experiment" style="width:150px;margin-top:1em;" />
		</form>
	</div>
	</dd>
	<?php else: ?>
	<dt>Add new experiment</dt>
	<dd>
	<div>
		<span class="error">Maximum number of experiments reached for this user account</span>	
	</div>	
	</dd>		
	<?php endif;?>
	</dl>	
</div>


<div class="subdiv" style="width:800px;margin-top:60px;margin-left:16em;">	
	<?php		
		echo "<span class='startlink'>Login</span>\n";
		echo "<span class='line'>&#8226;</span>\n";	
		echo "<span class='startlink'>Register</span>\n";
		echo "<span class='line'>&#8226;</span>\n";
		echo $html->link("Documentation",array("controller"=>"documentation","action"=>"index"),
					array("class"=>"startlink","target"=>"_blank"));
		echo "<span class='line'>&#8226;</span>\n";
		//echo "<a href='mailto:plaza@psb.vib-ugent.be' class='startlink'>Contact</a>\n";
		echo $html->link("About",array("controller"=>"documentation","action"=>"about"),
					array("class"=>"startlink","target"=>"_blank"));

	?>	
</div>


</div>
