<div>
<h2>Subset</h2>
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

	<h3>Transcripts</h3>
	<div class="subdiv">
	<?php echo $this->element("table_func");?>
	</div>

	<?php endif;?>	
</div>
</div>