<?php
/* Table for functional annotation */

// It should be updated to make it work with jQuery dataTables!
// There are also missing data types that should be displayed here (e.g. RNA family, taxonomic classification)


// Highlight column depending on the type of data the user is browsing (css class)
// Two classes can be set: one for the header of the column (`*_header_class`) and one for the cells (`*_cell_class`)
$gf_header_class=null;$gf_cell_class=null;
if(isset($gf_info)){$gf_header_class="class='warning'";$gf_cell_class="warning-faint";}
$go_header_class=null;$go_cell_class=null;
if(isset($go_info)){$go_header_class="class='warning'";$go_cell_class="warning-faint";}
$ipr_header_class=null;$ipr_cell_class=null;
if(isset($interpro_info)){$ipr_header_class="class='warning'";$ipr_cell_class="warning-faint";}
$ko_header_class=null;$ko_cell_class=null;
if(isset($ko_info)){$ko_header_class="class='warning'";$ko_cell_class="warning-faint";}
$label_header_class=null;$label_cell_class=null;
if(isset($label)){$label_header_class="class='warning'";$label_cell_class="warning-faint";}

// Functional annotation types that are displayed in the table: by default, all types are shown.
// If `$exp_info` is set, use the list of functional annotation types defined there.
$function_types = ['go', 'interpro', 'ko'];
if(isset($exp_info)){
    $function_types = $exp_info['function_types'];
}

$function_headers = array(
    "go"=>array("highlight_class"=>$go_header_class, "label"=>"GO annotation"),
    "interpro"=>array("highlight_class"=>$ipr_header_class, "label"=>"Protein domain annotation"),
    "ko"=>array("highlight_class"=>$ko_header_class, "label"=>"KO annotation")
);
?>

<?php $this->Paginator->options(array("url"=>$this->passedArgs)); ?>
<table id="table_func" class="table table-striped table-bordered table-hover table-condensed small">
	<thead>
		<th>Transcript</th>
		<th <?php echo $gf_header_class;?> >Gene family</th>
        <?php foreach ($function_types as $ft): ?>
        <!-- <?php echo "<th style=\"" . 60/count($function_types) . "%;\" " . $function_headers[$ft]['highlight_class'] . ">" ?> -->
        <th <?php echo $function_headers[$ft]['highlight_class']; ?>>
            <?php echo $function_headers[$ft]['label']; ?>
        </th>
        <?php endforeach; ?>
		<th <?php echo $label_header_class;?> >Subset</th>
		<th>Meta-annotation</th>
		<!--th>Meta annotation</th-->
		<!--<th style="width:5%">Edit</th>-->
	</thead>
	<?php
	$unassigned_str = "Unassigned";
	$no_data_str = "Unavailable";
	$tr_counter	= 0;
	$max_items = 3;
	foreach($transcript_data as $transcript_dat){
		$td=$transcript_dat['Transcripts'];
		echo "<tr>";

		//TRANSCRIPT ID
		echo "<td>".$this->Html->link($td['transcript_id'],
			array("controller"=>"trapid","action"=>"transcript",$exp_id,urlencode($td['transcript_id'])))."</td>";

		//GF ID
		echo "<td class='$gf_cell_class'>";
		if($td['gf_id']){
			echo $this->Html->link($td['gf_id'],
			     array("controller"=>"gene_family","action"=>"gene_family",$exp_id,urlencode($td['gf_id'])));
		}
		else{echo "<span class='text-muted'>" . $unassigned_str . "</span>";}
		echo "</td>\n";

		//GO annotation
        if(in_array("go", $function_types)) {
            if(!array_key_exists($td['transcript_id'],$transcripts_go)){
                echo "<td class='$go_cell_class'><span class='text-muted'>Unavailable</span></td>";
            }
            else{
                $n_trs_go = count($transcripts_go[$td['transcript_id']]);
                echo "<td class='$go_cell_class'>";
                echo "<ul class='table-items'>";
                for($i=0;$i<$n_trs_go && $i<$max_items;$i++){
                    $go	= $transcripts_go[$td['transcript_id']][$i];
                    $go_web	= str_replace(":","-",$go);
                    $desc	= $go_info_transcripts[$go]['desc'];
                    echo "<li>";
                    echo $this->Html->link($desc,array("controller"=>"functional_annotation","action"=>"go",$exp_id,$go_web));
                    echo " " . $this->element("go_category_badge", array("go_category"=>$go_info_transcripts[$go]["type"], "small_badge"=>true, "no_color"=>false));
                    if(($i == $max_items - 1) && ($n_trs_go > $max_items)) {
                        echo $this->element("table_more_label", array("trs_data"=>$transcripts_go[$td['transcript_id']], "data_desc"=>$go_info_transcripts, "data_type"=>"go", "data_offset"=>$max_items, "data_title"=>"GO term"));
                    }
                    echo "</li>";
                }
                echo "</ul>";
                echo "</td>";
            }
        }
		//InterPro annotation
        if(in_array("interpro", $function_types)) {
            if(!array_key_exists($td['transcript_id'],$transcripts_ipr)){
                echo "<td class='$ipr_cell_class'><span class='text-muted'>". $no_data_str . "</span></td>";
            }
            else{
                $n_trs_ipr = count($transcripts_ipr[$td['transcript_id']]);
                echo "<td class='$ipr_cell_class'>";
                echo "<ul class='table-items'>";
                for($i=0;$i<$n_trs_ipr && $i<$max_items;$i++){
                    $ipr	= $transcripts_ipr[$td['transcript_id']][$i];
                    $desc	= $ipr_info_transcripts[$ipr]['desc'];
                    echo "<li>";
                    echo $this->Html->link($desc,array("controller"=>"functional_annotation","action"=>"interpro",$exp_id,$ipr));
                    if(($i == $max_items - 1) && ($n_trs_ipr > $max_items)) {
                        echo $this->element("table_more_label", array("trs_data"=>$transcripts_ipr[$td['transcript_id']], "data_desc"=>$ipr_info_transcripts, "data_type"=>"ipr", "data_offset"=>$max_items, "data_title"=>"InterPro domain"));
                    }
                    echo "</li>";
                }
                echo "</ul>";
                echo "</td>";
            }
        }

		// KO annotation
        if(in_array("ko", $function_types)) {
            if(!array_key_exists($td['transcript_id'],$transcripts_ko)){
                echo "<td class='$ko_cell_class'><span class='text-muted'>" . $no_data_str . "</span></td>";
            }
            else{
                $n_trs_ko = count($transcripts_ko[$td['transcript_id']]);
                echo "<td class='$ko_cell_class'>";
                echo "<ul class='table-items'>";
                for($i=0;$i<$n_trs_ko && $i<$max_items;$i++){
                    $ko	= $transcripts_ko[$td['transcript_id']][$i];
                    $desc	= $ko_info_transcripts[$ko]['desc'];
                    echo "<li>";
                    echo $this->Html->link($desc,array("controller"=>"functional_annotation","action"=>"ko",$exp_id,$ko));
                    if(($i == $max_items - 1) && ($n_trs_ko > $max_items)) {
                        echo $this->element("table_more_label", array("trs_data"=>$transcripts_ko[$td['transcript_id']], "data_desc"=>$ko_info_transcripts, "data_type"=>"ko", "data_offset"=>$max_items, "data_title"=>"KO term"));
                    }
                    echo "</li>";
                }
                echo "</ul>";
                echo "</td>";
            }
        }

		//SUBSET
		if(!array_key_exists($td['transcript_id'],$transcripts_labels)){
			echo "<td><span class='text-muted'>" . $no_data_str . "</span></td>";
		}
		else{
		    $n_trs_sub = count($transcripts_labels[$td['transcript_id']]);
            echo "<td class='$label_cell_class'>";
            echo "<ul class='table-items'>";
            for($i=0;$i<$n_trs_sub && $i<$max_items;$i++){
                $label	= $transcripts_labels[$td['transcript_id']][$i];
                echo "<li>";
                echo $this->Html->link($label,array("controller"=>"labels","action"=>"view",$exp_id,urlencode($label)));
                if(($i == $max_items - 1) && ($n_trs_sub > $max_items)) {
                    echo $this->element("table_more_label", array("trs_data"=>$transcripts_labels[$td['transcript_id']], "data_type"=>"subset", "data_offset"=>$max_items, "data_title"=>"subset"));
                }
                echo "</li>";
            }
            echo "</ul>";
            echo "</td>";
		}

		echo "<td>".$this->Html->link($td['meta_annotation'],array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"meta_annotation",urlencode($td['meta_annotation'])))."</td>";

		//EDIT
		//echo "<td>TODO</td>";

	 	echo "</tr>\n";
	    }
	    ?>

</table>

<!--<div class='paging'>-->
<!--<div class='pagination pull-right'>-->
<div class="text-right">
    <div class='pagination pagination-sm no-margin-top'>
    <?php
        echo $this->Paginator->prev(__('Previous'), array('tag'=>'li'), null, array('tag' => 'li','class' => 'disabled','disabledTag' => 'a'));
        echo $this->Paginator->numbers(array('separator' => '','currentTag' => 'a', 'currentClass' => 'active','tag' => 'li','first' => 1));
        echo $this->Paginator->next(__('Next'), array('tag' => 'li','currentClass' => 'disabled'), null, array('tag' => 'li','class' => 'disabled','disabledTag' => 'a'));
    ?>
    </div>
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
<script type="text/javascript">
    console.log("Activate popover!");
    $('[data-toggle="popover"]').popover({
        placement: 'bottom',
        content: function() {
            return $(this).children(".table-more-content:first").html();
        },
//        placement: 'right',
        template: '<div class="popover"><div class="arrow"></div><div class="popover-content"></div></div>',
        html: true,
        delay: 50
    });
</script>
