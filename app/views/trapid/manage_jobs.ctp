<div>
<h2>Manage TRAPID jobs</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>
	
	<h3>Job control</h3>
	<div class="subdiv">
		
	<?php	
		//pr($running_jobs);
		if(count($running_jobs)==0){
			echo "<span>No jobs are currently running for this experiment</span>\n";	
		}	
		else{
			echo $form->create("Experiments",array("url"=>array("controller"=>"trapid","action"=>"manage_jobs",$exp_id),
						"type"=>"post"));
			$found_done	= false;
			echo "<table cellpadding='0' cellspacing='0' style='width:800px;'>\n";
			echo "<tr>";
			echo "<th style='width:15%'>Job id</th>";
			echo "<th style='width:22%'>Date</th>";
			echo "<th style='width:20%'>Status</th>";
			echo "<th style='width:30%'>Description</th>";
			echo "<th style='width:10%'>Delete</th>";
			echo "</tr>\n";
			$counter	= 0;
			foreach($running_jobs as $job){						
				$altrow	= null;
				if($counter++%2==0){$altrow=" class='altrow' ";}
				echo "<tr $altrow>";
				echo "<td>".$job['job_id']."</td>";
				echo "<td>".$job['start_date']."</td>";
				echo "<td>".$job['status']."</td>"; 
				echo "<td>".$job['comment']."</td>";
				$checked	= null;
				if($job['status']=="done"){$checked=" checked='checked' "; $found_done=true;}
				echo "<td><input type='checkbox' name='job_".$job['job_id']."' $checked /></td>";
				echo "</tr>\n";
			}
			echo "</table>\n";
			if($found_done){
				echo "<span style='font-weight:bold;'>If a job is in status <i>'done'</i> this job can deleted without repercussions. <br/>This is most likely a remnant of a job which was not cleaned up properly. </span><br/><br/>\n";
			}
			echo "<input type='submit' value='Delete selected jobs' style='width:200px;' />\n";
			echo "</form>\n";
		}

	?>	
	</div>
</div>	
</div>
