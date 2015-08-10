<div>
<h2>Gene family expansion/depletion</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>
	<br/><br/>
	<div style='color:blue;font-weight:bold;'>
		Warning: <br/>
		detected expansions can be due to splice variants, allelic variation or fragmented transcripts.<br/>
		detected depletions can be due to fragmented transcripts and/or insufficient transcriptome coverage.<br/>
	</div>
	<?php
		if(isset($error)){
			echo "<br/><span class='error'>".$error."</span><br/>";
		}
	?>	

	<h3>Options</h3>
	<div class="subdiv">
	<?php
		echo $form->create("",array("action"=>"expansion/".$exp_id,"type"=>"post"));
	?>
	<dl class="standard">
		<dt>Reference species</dt>
		<dd>
			<select name="reference_species" style="width:200px;">
			<?php 
				foreach($available_species as $k=>$v){
					$s = null; if(isset($selected_species) && $selected_species==$k){$s=" selected='selected' ";}
					echo "<option value='".$k."' $s>".$v."</option>\n";
				}
			?>
			</select>
		</dd>
		<dt>Type</dt>
		<dd>
			<select name="type" style="width:200px;">
			<?php
				foreach($available_types as $k=>$v){
					$s = null; if(isset($selected_type) && $selected_type==$k){$s=" selected='selected' ";}
					echo "<option value='".$k."' $s>".$v."</option>\n";
				}
			?>				
			</select>
		</dd>
		<dt>Minimal ratio</dt>
		<dd>
			<select name="ratio" style="width:200px;">
			<?php
				foreach($available_ratios as $k){
					$s = null; if(isset($selected_ratio) && $selected_ratio==$k){$s=" selected='selected' ";}
					echo "<option value='".$k."' $s>".$k."</option>\n";	
				}
			?>	
			</select>					
		</dd>
		<dt>Extra</dt>
		<dd>
			<div>
				<!--<input type="checkbox" name="zero_transcript" <?php if(isset($zero_transcript)){echo " checked='checked' ";}?> /><span>Include zero values in transcript dataset</span><br/>-->
				<input type="checkbox" name="zero_reference" <?php if(isset($zero_reference)){echo " checked='checked' ";} ?> /><span>Include zero values in reference species</span>
	
			</div>
		</dd>
	</dl>
	<br/>	
	<input type='submit' value='Find gene families' style='width:120px;'/>	
	</form>
	</div>
	<?php
	if(isset($result)){
	echo $javascript->link("sorttable");
	echo "<h3>Result</h3>\n";
	echo "<div class='subdiv'>\n";
	echo "<span style='font-size:x-small;margin-left:30px;'>Click table-header(s) to enable sorting</span>\n";
	echo "<table cellpadding='0' cellspacing='0' style='width:600px;' class='sortable'>\n";
	echo "<tr>";
	echo "<th>Gene family</th>";
	echo "<th>Transcript count</th>";
	echo "<th>".$available_species[$selected_species]."</th>";	
	echo "</tr>\n";
	foreach($transcripts_counts as $gf_id=>$transcript_count){
		$reference_count	= 0;
		if(array_key_exists($gf_id,$reference_counts)){$reference_count = $reference_counts[$gf_id];}
		$display		= false;
		if($reference_count!=0 || isset($zero_reference)){
			if($selected_type=="expansion"){if($transcript_count>=$selected_ratio*$reference_count){$display=true;}}
			if($selected_type=="depletion"){if($reference_count>=$selected_ratio*$transcript_count){$display=true;}}	
		}
		if($display){
			echo "<tr>";
			echo "<td>".$html->link($gf_id,array("controller"=>"gene_family","action"=>"gene_family",$exp_id,urlencode($gf_id)))."</td>";
			echo "<td>".$transcript_count."</td>";
			echo "<td>".$reference_count."</td>";
			echo "</tr>\n";
		}
	}
	echo "</table>\n";
	echo "</div>\n";		
	}
	?>	
</div>
</div>
