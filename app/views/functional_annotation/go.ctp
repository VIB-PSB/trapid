<div>
<h2>GO term</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>	
	
	<h3>Overview</h3>
	<div class="subdiv">		
		<dl class="standard">
			<dt>GO term</dt>
			<dd>
			<?php
			    $go_web	= str_replace(":","-",$go_info["go"]); 			
			    if(!$exp_info['allow_linkout']){
				echo $go_info["go"];
			    }
			    else{
			       echo $html->link($go_info["go"],$exp_info['datasource_URL']."go/view/".$go_web);
			    }
			?>
			</dd>
			<dt>Description</dt>
			<dd><?php echo $go_info["desc"];?></dd>
			<dt>#transcripts</dt>
			<dd><?php echo $num_transcripts;?></dd>			
		</dl>
	</div>
		

	<h3>Toolbox</h3>		
	<div class="subdiv">
	<?php
	$toolbox	= array("Find"=>array(
					array(
						"The associated gene families",
						$html->url(array("action"=>"assoc_gf",$exp_id,"go",$go_web)),
						"some_image.png"
					),								
				),
				"Explore"=>array(
					array(	
						"Explore the child GO terms",
						$html->url(array("action"=>"child_go",$exp_id,$go_web)),
						"other_image.png"
					),
					array(
						"Explore the parental GO terms",
						$html->url(array("action"=>"parent_go",$exp_id,$go_web)),
						"other_image.png"
					)								
				)
			);
	$this->set("toolbox",$toolbox);
	echo $this->element("toolbox");
	?>	
	</div>

	<h3>Transcripts</h3>
	<div class="subdiv">
	<?php echo $this->element("table_func");?>
	</div>		
	<?php 
		$download_url	= $html->url(array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"go",$go_web),true);	
		$this->set("download_url",$download_url);
		$this->set("allow_reference_aa_download",1);	
		echo $this->element("download"); 
	?>
</div>
</div>
