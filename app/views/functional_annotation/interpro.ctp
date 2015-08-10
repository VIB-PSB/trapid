<div>
<h2>Protein domain</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>	
	
	<h3>Overview</h3>
	<div class="subdiv">		
		<dl class="standard">
			<dt>Protein domain</dt>
			<dd>
			<?php
				$interpro	= $interpro_info['motif_id'];
				if(!$exp_info['allow_linkout']){
					echo $interpro;
				}
				else{
					echo $html->link($interpro,$exp_info['datasource_URL']."interpro/view/".$interpro);
				}			 
			?>
			</dd>
			<dt>Description</dt>
			<dd><?php echo $interpro_info["desc"];?></dd>
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
						$html->url(array("action"=>"assoc_gf",$exp_id,"interpro",$interpro)),
						"some_image.png"
					),								
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
		$download_url	= $html->url(array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"interpro",$interpro),true);	
		$this->set("download_url",$download_url);
		$this->set("allow_reference_aa_download",1);	
		echo $this->element("download"); 
	?>
	
</div>
</div>
