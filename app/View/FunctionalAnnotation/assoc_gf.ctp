<div>
    <div class="page-header">
<h1 class="text-primary">Associated gene families</h1>
    </div>
<div class="subdiv">
	<?php // echo $this->element("trapid_experiment"); ?>
	<h3>Overview</h3>
	<div class="subdiv">
		<dl class="standard dl-horizontal">
			<?php
			if($type=="go"){
				echo "<dt>GO term</dt>";
				echo "<dd>";
				$go_web	= str_replace(":","-",$go); 			
			    	if(!$exp_info['allow_linkout']) {
			    	    echo $go;
			    	}
			    	else {
			    	    echo $this->Html->link($go,$exp_info['datasource_URL']."go/view/".$go_web);
			    	}
				echo "</dd>\n";
			}
			else if($type=="ipr"){
			}		
			?>
			<dt>Description</dt>
			<dd><?php echo $description;?></dd>
			<dt>#transcripts</dt>
			<dd><?php echo $num_transcripts;?></dd>
		</dl>
	</div>
	<br/>
	<h3>Associated gene families</h3>
	<div class="subdiv">
		<?php if(isset($error)):?>
		<span class="error"><?php echo $error;?></span>
		
		<?php else: ?>

		<?php echo $this->Html->script("sorttable");?>
		<?php echo $this->element("sorttable");?>
		<table cellpadding="0" cellspacing="0" class='table table-striped table-condensed table-bordered table-hover sortable'>
            <thead>
			<tr>
				<th style="width:15%">Gene family</th>
				<th style="width:15%">#transcripts</th>
				<th style="width:35%">GO terms</th>
				<th style="width:35%">Protein domains</th>
			</tr>
            </thead>
            <tbody>
			<?php
			$j=0;
			foreach($gene_families as $gf_id=>$transcript_count){							
				$class=null; if($j++%2==0){$class=" class='altrow' ";}
				echo "<tr $class>";
				echo "<td>".$this->Html->link($gf_id,array("controller"=>"gene_family","action"=>"gene_family",$exp_id,$gf_id))."</td>";
				echo "<td>".$transcript_count."</td>";
				echo "<td style='text-align:left;'>";			
				for($i=0;$i<count($extra_annot_go[$gf_id]) && $i<2;$i++){	
					$go	= $extra_annot_go[$gf_id][$i];
					$desc	= $go_descriptions[$go];
					echo $this->Html->link(($i+1).")".$desc,array("controller"=>"functional_annotation","action"=>"go",$exp_id,str_replace(":","-",$go)));
					echo "<br/>";
				}
				echo "</td>";				

				echo "<td style='text-align:left;'>";
				for($i=0;$i<count($extra_annot_ipr[$gf_id]) && $i<2;$i++){
					$ipr	= $extra_annot_ipr[$gf_id][$i];
					$desc	= $ipr_descriptions[$ipr];
					echo $this->Html->link(($i+1).")".$desc,array("controller"=>"functional_annotation","action"=>"interpro",$exp_id,$ipr));
					echo "<br/>";
				}
				echo "</td>";

				echo "</tr>\n";	
			}
			?>
            </tbody>
		</table>
		<?php endif;?>
	</div>
</div>
</div>
