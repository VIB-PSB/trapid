<div>
<div class="page-header">
    <h1 class="text-primary"><?php echo $label; ?> <small>Transcript subset</small></h1>
</div>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>
	
	<?php if(isset($error)): ?>
		<?php echo "<br/><br/><span class='error'>".$error."</span><br/>";?>
	<?php else: ?>
	<h3>Overview</h3>
	<div class="subdiv">
		<dl class="standard">
			<dt>Subset</dt>
			<dd><?php echo $label; ?></dd>			
			<dt>#transcripts</dt>
			<dd><?php echo $num_transcripts;?></dd>
		</dl>
	</div>

	<h3>Toolbox</h3>		
	<div class="subdiv">
	<?php
	$toolbox	= array("Compare"=>array(
					array(
						"Label Gene Family intersection",
						$this->Html->url(array("controller"=>"tools","action"=>"label_gf_intersection",$exp_id,$label)),
						"some_image.png"
					),							
					array(
						"Label Interpro intersection",
						$this->Html->url(array("controller"=>"tools","action"=>"label_interpro_intersection",$exp_id,$label)),
						"some_image.png"
					),							
					array(
						"Label GO intersection",
						$this->Html->url(array("controller"=>"tools","action"=>"label_go_intersection",$exp_id,$label)),
						"some_image.png"			
					)		
				)
			);
	$this->set("toolbox",$toolbox);
	echo $this->element("toolbox");
	?>	

	<h3>Transcripts</h3>
	<div class="subdiv">
	<?php echo $this->element("table_func");?>
	</div>

	<?php endif;?>	
</div>
</div>
