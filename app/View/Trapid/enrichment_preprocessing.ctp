<div>
<div class="page-header">
<h1 class="text-primary">Preprocess functional enrichments</h1>
</div>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>
	
	<h3>Overview</h3>
	<div class="subdiv" style="width:700px;">
		The preprocessing of functional enrichments within the TRAPID framework is only available when at least 1 label is defined for the data set: indeed, the enrichments are computed by taking the ratio of transcripts assigned to a label, with the transcripts in the entire data set. <br/>
		The preprocessing of the functional enrichments is required when viewing the Sankey diagrams, because of the otherwise long waiting times.
		<br/>
		The preprocessing of the functional enrichments is not done during the 'initial processing' phase because labels can be added afterwards, and they do not play a role in the initial processing.
	</div>

	
	<h3>Options</h3>
	<div class="subdiv">
		<?php
		echo $this->Form->create(false,array("url"=>array("controller"=>"trapid","action"=>"enrichment_preprocessing",$exp_id),"type"=>"post"));
		?>
			<dl class="standard2">
				<dt>Data Type</dt>
				<dd>
					<select name="type" id="type" style="width:300px;">
						<?php
						foreach($possible_types as $k=>$v){
							echo "<option value='".$k."' >".$v."</option>\n";
						}
						?>
					</select>	
				</dd>
			</dl>
			<br/>
			<input type="submit" value="Start enrichment preprocessing" style="width:200px;" />	
		</form>
	</div>

</div>

</div>
