<div>
    <div class="page-header">
<h1 class="text-primary">Protein domain</h1>
    </div>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>	
	
	<h3>Overview</h3>
	<div class="subdiv">		
		<dl class="standard">
			<dt>Protein domain</dt>
			<dd>
			<?php
				// $interpro	= $interpro_info['motif_id'];
				$interpro	= $interpro_info['name'];
				if(!$exp_info['allow_linkout']){
					echo $interpro;
				}
				else{
					echo $this->Html->link($interpro,$exp_info['datasource_URL']."interpro/view/".$interpro);
					// TODO: add link to InterPro itself. Link is formed as such: http://www.ebi.ac.uk/interpro/entry/<motif_id>
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
	$toolbox	= array("Associated gene families"=>array(
					array(
						"Table",
						$this->Html->url(array("action"=>"assoc_gf",$exp_id,"interpro",$interpro)),
						"some_image.png"
					),
                    array(
						"Visualization",
						$this->Html->url(array("controller"=>"tools","action"=>"interproSankey",$exp_id,$interpro)),
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
		$download_url	= $this->Html->url(array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"interpro",$interpro),true);	
		$this->set("download_url",$download_url);
		$this->set("allow_reference_aa_download",1);	
		echo $this->element("download"); 
	?>
	
</div>
</div>
