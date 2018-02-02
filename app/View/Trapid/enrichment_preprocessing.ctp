<div>
<div class="page-header">
<h1 class="text-primary">Preprocess functional enrichments</h1>
</div>
<div class="subdiv">
	<?php // echo $this->element("trapid_experiment");?>
	
	<section class="page-section">
        <p class="text-justify">The preprocessing of functional enrichments within the TRAPID framework is <strong>only available when at least 1 transcript subset is defined</strong>: indeed, the enrichments are computed by taking the ratio of transcripts assigned to a label, with the transcripts in the entire data set. </p>
        <p class="text-justify">Preprocessing the functional enrichments is required when viewing the Sankey diagrams (computing enrichments on-the-fly would make loading the Sankey diagrams very long). </p>
        <p class="text-justify"><strong>Nb: </strong>the preprocessing of the functional enrichments is not done during the 'initial processing' phase because labels can be added afterwards, and they do not play a role in the initial processing.</p>
	</section>

	
	<h3>Enrichment preprocessing options</h3>
	<div class="subdiv">
		<?php
		echo $this->Form->create(false,array("url"=>array("controller"=>"trapid","action"=>"enrichment_preprocessing",$exp_id),"type"=>"post"));
		?>
			<div class="form-group">
                <label for="type"><strong>Data Type</strong></label><br>
					<select name="type" id="type" style="float:left;max-width:200px;" class="form-control">
						<?php
						foreach($possible_types as $k=>$v){
							echo "<option value='".$k."' >".$v."</option>\n";
						}
						?>
					</select>	
			<input class='btn btn-primary' type="submit" value="Run enrichment preprocessing" style="clear:both; margin-left: 25px;"/>
			</div>
		</form>
	</div>

</div>

</div>
