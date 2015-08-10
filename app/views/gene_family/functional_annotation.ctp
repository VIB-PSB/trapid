<div>
<h2>Gene family</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment"); ?>
	
	<h3>Gene family information</h3>
	<div class="subdiv">
		<dl class="standard">
			<dt>Gene family</dt>
			<dd><?php echo $html->link($gf_id,array("controller"=>"gene_family","action"=>"gene_family",$exp_id,$gf_id));?></dd>
			<dt>#transcripts</dt>
			<dd><?php echo $gf_info['GeneFamilies']['num_transcripts'];?></dd>
		</dl>	
	</div>			

	<h3>Associated functional annotation</h3>
	<div class="subdiv">
		
		<h4>Gene Ontology terms</h4>
		<?php if(count($go_descriptions)==0): ?>
		<span class='error'>No GO terms are associated with this gene family</span>
		<?php else: ?>
		<table cellspacing='0' cellpadding='0' style='width:800px;'>
			<tr>
				<th style='width:20%'>GO term</th>
				<th>Description</th>
				<th style='width:15%'>Assoc. transcripts</th>
			</tr>
			<?php
			$i	= 0;
			foreach($go_descriptions as $go=>$desc){
				$class = null;
				if($i++%2==0){$class=" class='altrow' ";}
				$go_web	= str_replace(":","-",$go);
				echo "<tr $class>";				
				echo "<td>".$html->link($go,array("controller"=>"functional_annotation","action"=>"go",$exp_id,$go_web))."</td>";				
				echo "<td>".$desc['desc']."</td>";
				echo "<td>".$html->link("Transcripts",array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"gf_id",$gf_id,"go",$go_web))."</td>";
				echo "</tr>\n";
			}
			?>
		</table>
		<?php endif;?>

		<h4>Protein domains</h4>
		<table cellspacing='0' cellpadding='0' style='width:800px;'>
			<tr>
				<th style='width:20%'>Protein domain</th>
				<th>Description</th>
				<th style='width:15%'>Assoc. transcripts</th>
			</tr>
			<?php
			$i	= 0;
			foreach($interpro_descriptions as $interpro=>$desc){
				$class = null;
				if($i++%2==0){$class=" class='altrow' ";}				
				echo "<tr $class>";				
				echo "<td>".$html->link($interpro,array("controller"=>"functional_annotation","action"=>"interpro",$exp_id,$interpro))."</td>";				
				echo "<td>".$desc['desc']."</td>";
				echo "<td>".$html->link("Transcripts",array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"gf_id",$gf_id,"interpro",$interpro))."</td>";
				echo "</tr>\n";
			}
			?>
		</table>
	</div>
</div>
</div>
