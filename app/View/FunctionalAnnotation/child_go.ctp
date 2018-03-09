<div>
<div class="page-header">
    <h1 class="text-primary">Child GO terms</h1>
</div>
<div class="subdiv">
	<?php // echo $this->element("trapid_experiment"); ?>
	
	<h3>Parental GO term</h3>
	<div class="subdiv">
		<dl class="standard dl-horizontal">
			<dt>GO term</dt>
			<dd><?php echo $this->Html->link($go,array("controller"=>"functional_annotation","action"=>"go",$exp_id,$go_web));?>
                &nbsp; &nbsp;
                <?php echo  $this->element("linkout_func", array("linkout_type"=>"amigo", "query_term"=>$go_info["name"]));?>
                <?php echo  $this->element("linkout_func", array("linkout_type"=>"quickgo", "query_term"=>$go_info["name"]));?>

            </dd>
			<dt>GO description</dt>
			<dd><?php echo $go_info['desc'];?></dd>	
			<dt>#Transcripts</dt>
			<dd><?php echo $num_transcripts;?></dd>
		</dl>
	</div>

	<?php
	if(isset($max_child_gos_reached)){
		echo "<div class='subdiv'>\n";
		echo "<span class='error'>\n";
		echo "Too many child-gos present (limit is ".$max_child_gos_reached.")<br/>";
		echo "Only top ".$max_child_gos_reached." child gos are displayed";
		echo "</span>";
		echo "</div>\n";
	}	
	?>	
	

	<h3>Child GO terms</h3>
	<div class="subdiv">
		
		<?php if($num_child_gos==0):?>
				<span>No child GO terms with associated transcripts available</span>
		<?php else: ?>
		<?php echo $this->Html->script(array("sorttable")); ?>
		<span style='font-size:x-small'>Click on table headers to sort columns</span>	
		<table class='table table-striped table-condensed table-bordered table-hover sortable altrow2' cellpadding="0" cellspacing="0">
			<thead>
            <tr>
				<th style="width:20%;">Child GO</th>
				<th style="width:15%;">#transcripts</th>
				<th style="width:60%;">Description</th>
			</tr>
            </thead>
            <tbody>
			<?php
			$counter	= 1;
			foreach($child_go_counts as $child_go=>$child_go_info){
				$alt	= null; if($counter++%2==0){$alt=" class='altrow2' ";}
				echo "<tr $alt>";
				echo "<td>".$this->Html->link($child_go,array("controller"=>"functional_annotation","action"=>"go",$exp_id,str_replace(":","-",$child_go)))."</td>";
				echo "<td>".$child_go_info['count']."</td>";
				echo "<td>".$child_go_info['desc']."</td>";
				echo "</tr>\n";
			}	
			?>
            </tbody>
		</table>
		<?php endif; ?>	
	</div>	

</div>
</div>