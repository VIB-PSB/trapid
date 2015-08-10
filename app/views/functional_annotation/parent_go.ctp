<div>
<h2>Parental GO terms</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment"); ?>
	
	<h3>Child GO term</h3>
	<div class="subdiv">
		<dl class="standard">
			<dt>GO term</dt>
			<dd><?php echo $html->link($go,array("controller"=>"functional_annotation","action"=>"go",$exp_id,$go_web));?></dd>
			<dt>GO description</dt>
			<dd><?php echo $go_info['desc'];?></dd>	
			<dt>#Transcripts</dt>
			<dd><?php echo $num_transcripts;?></dd>
		</dl>
	</div>

	<?php
	if(isset($max_parent_gos_reached)){
		echo "<div class='subdiv'>\n";
		echo "<span class='error'>\n";
		echo "Too many child-gos present (limit is ".$max_parent_gos_reached.")<br/>";
		echo "Only top ".$max_parent_gos_reached." child gos are displayed";
		echo "</span>";
		echo "</div>\n";
	}	
	?>	
	

	<h3>Parental GO terms</h3>
	<div class="subdiv">
		
		<?php if($num_parent_gos==0):?>
				<span>No parental GO terms with associated transcripts available</span>
		<?php else: ?>
		<?php echo $javascript->link(array("sorttable")); ?>
		<span style='font-size:x-small'>Click on table headers to sort columns</span>	
		<table cellpadding="0" cellspacing="0" style="width:800px;" class='sortable altrow2'>
			<tr>	
				<th style="width:20%;">Parental GO</th>
				<th style="width:15%;">#transcripts</th>
				<th style="width:60%;">Description</th>
			</tr>
			<?php
			$counter	= 1;
			foreach($parent_go_counts as $parent_go=>$parent_go_info){
				$alt	= null; if($counter++%2==0){$alt=" class='altrow2' ";}
				echo "<tr $alt>";
				echo "<td>".$html->link($parent_go,array("controller"=>"functional_annotation","action"=>"go",$exp_id,str_replace(":","-",$parent_go)))."</td>";
				echo "<td>".$parent_go_info['count']."</td>";
				echo "<td>".$parent_go_info['desc']."</td>";
				echo "</tr>\n";
			}	
			?>
		</table>
		<?php endif; ?>	
	</div>	

</div>
</div>