<div style='float:left;width:700px;'>
<h3>Experiment overview</h3>
<div class="subdiv">
	<div>
		<dl class="standard">
		<dt>Name</dt>	
		<dd><?php echo $html->link($exp_info['title'],array("controller"=>"trapid","action"=>"experiment",$exp_info['experiment_id']));?></dd>
		<dt>Processing status</dt>
		<dd><?php echo $exp_info['process_state'];?></dd>
		<dt>Last edit</dt>
		<dd><?php echo $exp_info['last_edit_date'];?></dd>
		<dt>Data source</dt>
		<dd>
			<?php
			if($exp_info['datasource_URL']){
				echo $html->link($exp_info['datasource'],$exp_info['datasource_URL']);
			}
			else{
				echo $exp_info['datasource'];
			}			
			?>			
		</dd>
		<dt>Transcript count</dt>
		<dd><?php echo $exp_info['transcript_count'];?></dd>	
		
		<?php
		if(isset($show_experiment_overview_description)){
			echo "<dt>Description</dt>";
			echo "<dd>".$exp_info['description']."</dd>";
		}	
		?>
		</dl>
	</div>
</div>
</div>
<div style='float:right;width:200px;text-align:right;margin-right:50px;'>
	<?php
	echo $html->link("Experiments",array("controller"=>"trapid","action"=>"experiments"),array("class"=>"mainref"));
	echo "<br/>\n";
	echo $html->link("Manage jobs",array("controller"=>"trapid","action"=>"manage_jobs",$exp_id),array("class"=>"mainref"));
	echo "<br/>\n";
	echo $html->link("Documentation",array("controller"=>"documentation","action"=>"index"),array("target"=>"_blank","class"=>"mainref"));
	?>	
</div>
<div style='clear:both;width:800px;font-size:x-small;'>&nbsp;</div>
