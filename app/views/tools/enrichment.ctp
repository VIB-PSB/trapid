<div>
<h2><?php echo $available_types[$type];?> enrichment</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>
	<h3>Subset selection</h3>
	<div class="subdiv">
		<?php
		if(isset($error)){echo "<span class='error'>".$error."</span>\n";}		
		echo $form->create("",array("action"=>"enrichment/".$exp_id."/".$type,"type"=>"post"));
		echo "<dl class='standard'>";
		echo "<dt>Subset</dt>";
		echo "<dd>";
		echo "<select name='subset' style='width:300px;'>";
		foreach($subsets as $subset=>$count){
			if(isset($selected_subset) && $selected_subset==$subset){
				echo "<option value='".$subset."' selected='selected'>".$subset." (".$count." transcripts)</option>\n";
			}
			else{
				echo "<option value='".$subset."'>".$subset." (".$count." transcripts)</option>\n";
			}
		}
		echo "</select>\n";
		echo "</dd>\n";
		echo "<dt>P-value</dt>";
		echo "<dd>";	
		echo "<select name='pvalue' style='width:80px;'>";	
		foreach($possible_pvalues as $ppv){
			if($ppv==$selected_pvalue){
				echo "<option value='".$ppv."' selected='selected'>".$ppv."</option>";
			}
			else{
				echo "<option value='".$ppv."'>".$ppv."</option>";
			}
		}
		echo "</select>\n";
		echo "</dd>";
		echo "</dl><br/><br/>";
		echo "<input type='submit' style='width:200px;' value='Compute enrichment' />\n";
		echo "<input type='checkbox' style='margin-left:20px;' name='use_cache' checked='checked' />\n";
		echo "<span style='margin-left:5px;'>Used cached results</span>\n";
		echo "</form>\n";	
		?>		
	</div>
	<br/><br/>
	<?php	if(isset($result_file)) : ?>
	<h3>Enrichment <?php echo "<i>".$selected_subset."</i>"; ?></h3>
	<br/>
	<div class="subdiv">	
		<div class="subdiv">
		<div id="enrichment_div">		
		<div style="width:200px;">
		<center>
		<?php echo $html->image('ajax-loader.gif'); ?><br/>
		Loading...please wait<br/>
		</center>
		</div>
		</div>

		<?php 		
		if(isset($job_id)){
		//pr("using job id ".$job_id);
		    echo $javascript->codeBlock($ajax->remoteFunction(
			array('url'=>"/tools/load_enrichment/".$exp_id."/".$type."/".$selected_subset."/".$selected_pvalue."/".$result_file."/".$job_id."/",
			'update'=>'enrichment_div')));
		}
		else{
		//pr("not using job id");
		   echo $javascript->codeBlock($ajax->remoteFunction(
			array('url'=>"/tools/load_enrichment/".$exp_id."/".$type."/".$selected_subset."/".$selected_pvalue."/".$result_file."/",
			'update'=>'enrichment_div')));
		}
		?>
		</div>
	</div>
	<?php endif; ?>
</div>
</div>
