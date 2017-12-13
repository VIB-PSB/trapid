<?php
/* Table for functional annotation */

// Updated to make it work with jQuery dataTables and Bootstrap + minor improvements
// Removed this functionality for now... Will re-implement it better soon.

$gf_column_class1=null;
if(isset($gf_info)){$gf_column_class1="class='highlight'";}
$go_column_class1=null;$go_column_class2=null;
if(isset($go_info)){$go_column_class1="class='highlight'";$go_column_class2="highlight";}
$ipr_column_class1=null;$ipr_column_class2=null;
if(isset($interpro_info)){$ipr_column_class1="class='highlight'";$ipr_column_class2="highlight";}
$label_column_class1=null;$label_column_class2=null;
if(isset($label)){$label_column_class1="class='highlight'";$label_column_class2="highlight";}
?>
<?php $this->Paginator->options(array("url"=>$this->passedArgs)); ?>
<table id="table_func" class="table table-striped table-bordered table-hover table-condensed display" style="font-size: 14px;">
<!--table cellpadding="0" cellspacing="0" style="width:90%;"-->
	<thead>
		<th>Transcript</th>
		<th <?php echo $gf_column_class1;?> >Gene family</th>
		<th style="width: 27%;" <?php echo $go_column_class1;?> >GO annotation</th>
		<th style="width: 27%;" <?php echo $ipr_column_class1;?> >Protein domain annotation</th>
		<th <?php echo $label_column_class1;?> >Subset</th>
		<th>Meta-annotation</th>
		<!--th>Meta annotation</th-->
		<!--<th style="width:5%">Edit</th>-->
	</thead>
	<?php
	$bad_status	= "unassigned";
	$tr_counter	= 0;
	foreach($transcript_data as $transcript_dat){
		$row_class	= null; // if($tr_counter++%2==0){$row_class=" class='altrow' ";}

		$td=$transcript_dat['Transcripts'];
		echo "<tr $row_class>";

		//TRANSCRIPT ID
		echo "<td>".$this->Html->link($td['transcript_id'],
			array("controller"=>"trapid","action"=>"transcript",$exp_id,urlencode($td['transcript_id'])))."</td>";

		//GF ID
		echo "<td $gf_column_class1 >";
		if($td['gf_id']){
			echo $this->Html->link($td['gf_id'],
			     array("controller"=>"gene_family","action"=>"gene_family",$exp_id,urlencode($td['gf_id'])));
		}
		else{echo "<span class='".$bad_status."'>".$bad_status."</span>";}
		echo "</td>\n";

		//GO annotation
		if(!array_key_exists($td['transcript_id'],$transcripts_go)){
			echo "<td class='$go_column_class2'><span class='disabled'>Unavailable</span></td>";
		}
		else{
			echo "<td class='left $go_column_class2'>";
			for($i=0;$i<count($transcripts_go[$td['transcript_id']]) && $i<3;$i++){
				$go	= $transcripts_go[$td['transcript_id']][$i];
				$go_web	= str_replace(":","-",$go);
				$desc	= $go_info_transcripts[$go]['desc'];
				echo ($i+1).". ".$this->Html->link($desc,array("controller"=>"functional_annotation","action"=>"go",$exp_id,$go_web))."<br/>";
			}
			echo "</td>";
		}

		//InterPro annotation
		if(!array_key_exists($td['transcript_id'],$transcripts_ipr)){
			echo "<td class='$ipr_column_class2'><span class='disabled'>Unavailable</span></td>";
		}
		else{
			echo "<td class='left $ipr_column_class2'>";
			for($i=0;$i<count($transcripts_ipr[$td['transcript_id']]) && $i<3;$i++){
				$ipr	= $transcripts_ipr[$td['transcript_id']][$i];
				$desc	= $ipr_info_transcripts[$ipr]['desc'];
				echo ($i+1).". ".$this->Html->link($desc,array("controller"=>"functional_annotation","action"=>"interpro",$exp_id,$ipr))."</br>";
			}
			echo "</td>";
		}


		//SUBSET
		if(!array_key_exists($td['transcript_id'],$transcripts_labels)){
			echo "<td><span class='disabled'>Unavailable</span></td>";
		}
		else{
			    echo "<td class='left $label_column_class2'>";
			    for($i=0;$i<count($transcripts_labels[$td['transcript_id']]) && $i<3;$i++){
				    $label	= $transcripts_labels[$td['transcript_id']][$i];
				    echo ($i+1).". ".$this->Html->link($label,array("controller"=>"labels","action"=>"view",$exp_id,urlencode($label)))."<br/>";
			    }
			    echo "</td>";
		}

		echo "<td>".$this->Html->link($td['meta_annotation'],array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"meta_annotation",urlencode($td['meta_annotation'])))."</td>";

		//EDIT
		//echo "<td>TODO</td>";

	 	echo "</tr>\n";
	    }
	    ?>

</table>

<div class='paging'>
<?php
	echo $this->Paginator->prev('<< '.__('previous'), array(), null, array('class'=>'disabled'));
    echo "&nbsp;";
    echo $this->Paginator->numbers();
    echo "&nbsp;";
    echo $this->Paginator->next(__('next').' >>', array(), null, array('class'=>'disabled'));
    ?>
</div>

<!-- Pagination is now handled by jQuery DataTables -->
<!--<script type="text/javascript">-->
<!--  $(document).ready(function() {-->
<!--      $('#table_func').DataTable({-->
<!--				scrollY:        '50vh',-->
<!--        scrollCollapse: true,-->
<!--        paging:         false,-->
<!--				dom: 'Bfrtip',-->
<!--buttons: [-->
<!--		'columnsToggle'-->
<!--]-->
<!--			});-->
<!---->
<!--  });-->
<!--</script>-->
