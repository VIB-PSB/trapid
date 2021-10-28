<?php
	function perc($num,$total,$nr,$textmode=true){
		$perc	= round(100*$num/$total,$nr);
		$res	= "";
		if($textmode){$res = " (".$perc."%)";}
		else{$res = $perc;}
		//$res	= " (".round(100*$num/$total,$nr)."%)";
		return $res;
	}

	function draw_progress_bar($perc) {
	    return "<div class=\"progress stats-progress\"><div class=\"progress-bar\" role=\"progressbar\" style=\"width: " . $perc . "%;\" aria-valuenow=\"" . $perc . "\" aria-valuemin=\"0\" aria-valuemax=\"100\"></div></div>";
    }

    function create_stats_row($metrics_name, $metrics_value, $metrics_perc, $row_id=null, $ajax=false) {
	    if(!$row_id) {
    	    echo "<div class=\"row\">\n";
        }
        else {
    	    echo "<div class=\"row\" id='" . $row_id . "'>\n";
        }
	    echo "<div class=\"col-md-4 col-md-offset-1 col-xs-8 stats-metric\">" . $metrics_name . "</div>\n";
	    if($metrics_perc) {
	    echo "<div class=\"col-md-2 col-xs-4 stats-value\">" . $metrics_value . " (" . $metrics_perc . "%)" . "</div>\n";
	    echo "<div class=\"col-md-4 hidden-sm hidden-xs\">\n";
	    echo draw_progress_bar($metrics_perc);
	    echo "</div>\n";
        }
	    else {
    	    echo "<div class=\"col-md-2 col-xs-4 stats-value\">" . $metrics_value . "</div>\n";
            }
	    echo "</div>\n";
    }

    // A string that contains html for a 'loading' span element
    $loading_span_elmt = "<span class=\"text-muted\">" . $this->Html->image('small-ajax-loader.gif', array("style"=>"max-height: 14px;")) . " &nbsp; loading...</span>";
?>

<?php if(!isset($pdf_view)) : ?>
<div>
<div class="page-header">
    <?php
    echo $this->Form->create(false,array("url"=>array("controller"=>"tools","action"=>"statistics/".$exp_id),
        "type"=>"post"));
    echo "<input type='hidden' name='export_type' value='pdf'/>\n";
    ?>
    <div class='btn-toolbar pull-right'>
        <!-- This line return is an ugly fix to position the export button -->
        <br>
        <button type='submit' class='btn btn-sm btn-default'>
            <span class="glyphicon glyphicon-download-alt"></span>
            Export to PDF
        </button>
    </div>
    <h1 class="text-primary">General statistics</h1>
    </form>
</div>


    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Transcript information</h3>
        </div>
        <div class="panel-body">
            <?php create_stats_row("#Transcripts", $num_transcripts, null); ?>
            <?php create_stats_row("Average sequence length",  $loading_span_elmt, null, "avg_trs_length"); ?>
            <?php create_stats_row("#Transcripts with ORF", $num_orfs, null); ?>
            <?php create_stats_row("Average ORF length", $loading_span_elmt, null,  "avg_orf_length"); ?>
            <?php create_stats_row("#ORFs with a start codon", $num_start_codons, perc($num_start_codons,$num_transcripts,1,false)); ?>
            <?php create_stats_row("#ORFs with a stop codon", $num_stop_codons, perc($num_stop_codons,$num_transcripts,1,false)); ?>
            <?php create_stats_row("#Transcripts with putative frameshift", $num_putative_fs, perc($num_putative_fs,$num_transcripts,1,false)); ?>
        </div>
    </div>

<!--    <div class="panel panel-default">-->
<!--        <div class="panel-heading">-->
<!--            <h3 class="panel-title">Frameshift information</h3>-->
<!--        </div>-->
<!--        <div class="panel-body">-->
<!--            --><?php //create_stats_row("#Transcripts with corrected frameshift", $num_correct_fs, perc($num_correct_fs,$num_transcripts,1,false)); ?>
<!--        </div>-->
<!--    </div>-->

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Meta annotation information</h3>
        </div>
        <div class="panel-body">
            <?php create_stats_row("#Full-length", $meta_annot_fulllength, perc($meta_annot_fulllength,$num_transcripts,1,false)); ?>
            <?php create_stats_row("#Quasi full-length", $meta_annot_quasi, perc($meta_annot_quasi,$num_transcripts,1,false)); ?>
            <?php create_stats_row("#Partial", $meta_annot_partial, perc($meta_annot_partial,$num_transcripts,1,false)); ?>
            <?php create_stats_row("#No information", $meta_annot_noinfo, perc($meta_annot_noinfo,$num_transcripts,1,false)); ?>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Taxonomic classification information (Kaiju)</h3>
        </div>
        <div class="panel-body">
        <?php if($exp_info['perform_tax_binning'] == 1): ?>
            <?php create_stats_row("#Classified", $num_classified_trs, perc($num_classified_trs, $num_transcripts,2,false)); ?>
            <?php create_stats_row("#Unclassified", $num_unclassified_trs, perc($num_unclassified_trs, $num_transcripts,2,false)); ?>
            <h5>Domain composition</h5>
<!--            <p class="text-justify">Number of transcript by domain of life</p>-->
            <?php
            foreach ($top_tax_domain as $top_tax) {
                if($top_tax[0] != "Unclassified") {
                    create_stats_row("#" . $top_tax[0], (int) $top_tax[1], perc((int)$top_tax[1], $num_transcripts,2,false));
                }
            }
            ?>
        <?php else: ?>
            <p class="lead text-muted">No taxonomic classification was performed for this experiment. </p>
        <?php endif; ?>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Similarity search information (DIAMOND)</h3>
        </div>
        <div class="panel-body">
            <p class="text-justify">Best similarity search hit for each transcript. By default, only the top 20 species are shown. If there are more, click the <code>Show all</code> link to display all species.</p>

            <?php
            $split	= explode(";", $exp_info['hit_results']);
            $tmp	= array();
            $sum	= 0;
            $max_species = 20;  // Max. number of species to show by default.. Should this come from the controller instead?
            $extra_div = false;
            foreach($split as $s){
                $k = explode("=",$s);
                $tmp[$k[0]] = $k[1];
                $sum += $k[1];
            }
            arsort($tmp);
            $species_keys = array_keys($tmp);
            $last_species = end($species_keys);
            foreach($tmp as $k=>$v){
                if(array_search($k, $species_keys) == $max_species) {
                    $extra_div = true;
                    echo "<a id=\"toggle-extra-hits\" onclick=\"toggleExtraHits()\">";
                    echo "<span id=\"toggle-extra-hits-icon\" class=\"glyphicon small-icon glyphicon-menu-right\"></span> ";
                    echo "Show all...";
                    echo "</a>\n";
                    echo "<div id='extra-hits' class='hidden'>\n";
                }
                create_stats_row($all_species[$k], $v, perc($v,$sum,2,false));
                if($extra_div && $k == $last_species) {
                    echo "</div>";
                }
            }
            echo "<hr>";
            create_stats_row("Total hits", $sum, null);
            ?>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Gene family information</h3>
        </div>
        <div class="panel-body">
            <?php create_stats_row("#Gene families", $num_gf, null); ?>
            <?php create_stats_row("#Transcripts in GF", $num_transcript_gf, perc($num_transcript_gf,$num_transcripts,1,false)); ?>
            <?php create_stats_row("Largest GF", $this->Html->link($biggest_gf['gf_id'],array("controller"=>"gene_family","action"=>"gene_family",$exp_id,$biggest_gf['gf_id']))." (".$biggest_gf['num_transcripts']." transcripts)", null); ?>
            <?php create_stats_row("#Single copy", $single_copy, null); ?>
        </div>
    </div>


    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">RNA family information</h3>
        </div>
        <div class="panel-body">
            <?php create_stats_row("#RNA families", $num_rf, null); ?>
            <?php create_stats_row("#Transcripts in RF", $num_transcript_rf, perc($num_transcript_rf, $num_transcripts,1,false)); ?>
            <?php
            if($biggest_rf['rf_id']=='N/A') {
                create_stats_row("Largest RF", $biggest_rf['rf_id'] . " (".$biggest_rf['num_transcripts']." transcripts)", null);
            }
            else {
                create_stats_row("Largest RF", $this->Html->link($biggest_rf['rf_id'],array("controller"=>"rna_family","action"=>"rna_family",$exp_id,$biggest_rf['rf_id']))." (".$biggest_rf['num_transcripts']." transcripts)", null);
            }
            ?>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Functional annotation information</h3>
        </div>
        <div class="panel-body">
            <?php if(in_array("go", $exp_info['function_types'])): ?>
            <h5>Gene Ontology</h5>
            <?php create_stats_row("#GO terms", $num_go, null); ?>
            <?php create_stats_row("#Transcripts with GO", $num_transcript_go, perc($num_transcript_go, $num_transcripts,1, false)); ?>
            <?php endif; ?>
            <?php if(in_array("interpro", $exp_info['function_types'])): ?>
            <h5>InterPro</h5>
            <?php create_stats_row("#InterPro domains", $num_interpro, null); ?>
            <?php create_stats_row("#Transcripts with Protein Domain", $num_transcript_interpro,  perc($num_transcript_interpro, $num_transcripts,1, false)); ?>
            <?php endif; ?>
            <?php if(in_array("ko", $exp_info['function_types'])): ?>
            <h5>KEGG Orthology</h5>
            <?php create_stats_row("#KO terms", $num_ko, null); ?>
            <?php create_stats_row("#Transcripts with KO", $num_transcript_ko,  perc($num_transcript_ko, $num_transcripts,1, false)); ?>
            <?php endif; ?>
        </div>
    </div>

</div>

    <?php if ($extra_div) :?>
        <script type="text/javascript" defer="defer">
            // Toggle extra similarity search hits. Called on click of 'toggle-extra-hits' link.
            function toggleExtraHits() {
                var extraHitsDiv = "extra-hits";
                var extraHitsIcon = "toggle-extra-hits-icon";
                var ehIconElmt = document.getElementById(extraHitsIcon);
                document.getElementById(extraHitsDiv).classList.toggle("hidden");
                if(ehIconElmt.classList.contains("glyphicon-menu-right")) {
                    ehIconElmt.classList.replace("glyphicon-menu-right", "glyphicon-menu-down");
                }
                else {
                    ehIconElmt.classList.replace("glyphicon-menu-down", "glyphicon-menu-right");
                }
            }

        </script>
    <?php endif; ?>
    <script type="text/javascript" defer="defer">
        // Retrieve sequence stats data (avg. transcript/orf lengths)
        // TODO: If more than 2 values are retrieved that way, rewrite proper function
        function get_avg_transcript_length(exp_id) {
            var row_id = "#avg_trs_length";
            var stat_val_elmt = document.querySelector(row_id).querySelector('.stats-value');
            var ajax_url = <?php echo "\"".$this->Html->url(array("controller"=>"tools", "action"=>"avg_transcript_length"))."\"";?>+"/" + exp_id + "/";
            $.ajax({
                type: "GET",
                url: ajax_url,
                contentType: "application/json;charset=UTF-8",
                success: function(data) {
                    $(stat_val_elmt).html(data);
                },
                error: function() {
                    console.log("Unable to retrieve average transcript length for experiment \'" + exp_id + "\'. ");
                },
                complete: function() {
                    // Debug
                    // console.log(experiment_id);
                    // console.log(ajax_url);
                }
            });
        }
        function get_avg_orf_length(exp_id) {
            var row_id = "#avg_orf_length";
            var stat_val_elmt = document.querySelector(row_id).querySelector('.stats-value');
            var ajax_url = <?php echo "\"".$this->Html->url(array("controller"=>"tools", "action"=>"avg_orf_length"))."\"";?>+"/" + exp_id + "/";
            $.ajax({
                type: "GET",
                url: ajax_url,
                contentType: "application/json;charset=UTF-8",
                success: function(data) {
                    $(stat_val_elmt).html(data);
                },
                error: function() {
                    console.log("Unable to retrieve average ORF length for experiment \'" + exp_id + "\'. ");
                },
                complete: function() {
                    // Debug
                    // console.log(experiment_id);
                    // console.log(ajax_url);
                }
            });
        }
        get_avg_transcript_length(<?php echo $exp_id; ?>);
        get_avg_orf_length(<?php echo $exp_id; ?>);
    </script>
<?php else: ?>
<?php


    $fpdf->SetFont('Arial','B',16);

	$fpdf->setTitle("TRAPID analysis");
	$fpdf->AliasNbPages();
	$fpdf->AddPage();

	// Ok, first display the standard information about the experiment.
	$fpdf->SetFont('Arial','U',12);
	$fpdf->Cell(60,10,"General TRAPID experiment information");
	$fpdf->Ln();
	$fpdf->SetFont("Arial","",10);
	$standard_info	= array("Title"=>"title","Description"=>"description","Creation date"=>"creation_date",
				"Last edit"=>"last_edit_date","Similarity search database"=>"used_blast_database",
				"Reference database"=>"used_plaza_database");
	foreach($standard_info as $k=>$v){
		$val	= $exp_info[$v];
		$fpdf->SetFont("","B");
		$fpdf->Cell(60,5,$k);
		$fpdf->SetFont("","");
		$fpdf->Cell(10);
		$fpdf->Cell(60,5,$val);
		$fpdf->Ln();
	}
	$fpdf->Ln();
	$fpdf->Ln();

	// Next, transcript counts
	$fpdf->SetFont('Arial','U',12);
	$fpdf->Cell(60,10,"Transcript information");
	$fpdf->Ln();
	$fpdf->SetFont("Arial","",10);
	foreach($pdf_transcript_info as $k=>$v){
		$fpdf->SetFont("","B");
		$fpdf->Cell(60,5,$k);
		$fpdf->SetFont("","");
		$fpdf->Cell(10);
		$fpdf->Cell(60,5,$v);
		$fpdf->Ln();
	}
	$fpdf->Ln();
	$fpdf->Ln();


	//next, frameshift information
/*	$fpdf->SetFont('Arial','U',12);
	$fpdf->Cell(60,10,"Frameshift information");
	$fpdf->Ln();
	$fpdf->SetFont("Arial","",10);
	foreach($pdf_frameshift_info as $k=>$v){
		$fpdf->SetFont("","B");
		$fpdf->Cell(60,5,$k);
		$fpdf->SetFont("","");
		$fpdf->Cell(10);
		$fpdf->Cell(60,5,$v);
		$fpdf->Ln();
	}
	$fpdf->Ln();
	$fpdf->Ln();*/


	//next, meta information
	$fpdf->SetFont('Arial','U',12);
	$fpdf->Cell(60,10,"Meta annotation information");
	$fpdf->Ln();
	$fpdf->SetFont("Arial","",10);
	foreach($pdf_meta_info as $k=>$v){
		$fpdf->SetFont("","B");
		$fpdf->Cell(60,5,$k);
		$fpdf->SetFont("","");
		$fpdf->Cell(10);
		$fpdf->Cell(60,5,$v);
		$fpdf->Ln();
	}
	$fpdf->Ln();
	$fpdf->Ln();

	// Next, taxonomic classification information


	//next, similarity search information
	$fpdf->SetFont('Arial','U',12);
	$fpdf->Cell(60,10,"Similarity search information");
	$fpdf->Ln();
	$fpdf->SetFont("Arial","",10);
	$split	= explode(";",$exp_info['hit_results']);
	$tmp	= array();
	$sum	= 0;
	foreach($split as $s){$k=explode("=",$s);$tmp[$k[0]]=$k[1];$sum+=$k[1];}
	arsort($tmp);
	foreach($tmp as $k=>$v){
		$fpdf->SetFont("","B");
		$fpdf->Cell(60,5,$all_species[$k]);
		$fpdf->SetFont("","");
		$fpdf->Cell(10);
		$fpdf->Cell(60,5,$v.perc($v,$sum,1));
		$fpdf->Ln();
	}
	$fpdf->Ln();
	$fpdf->Ln();


	//next, gene family information
	$fpdf->SetFont('Arial','U',12);
	$fpdf->Cell(60,10,"Gene family information");
	$fpdf->Ln();
	$fpdf->SetFont("Arial","",10);
	foreach($pdf_gf_info as $k=>$v){
		$fpdf->SetFont("","B");
		$fpdf->Cell(60,5,$k);
		$fpdf->SetFont("","");
		$fpdf->Cell(10);
		$fpdf->Cell(60,5,$v);
		$fpdf->Ln();
	}
	$fpdf->Ln();
	$fpdf->Ln();

	// next, RNA family information
	$fpdf->SetFont('Arial','U',12);
	$fpdf->Cell(60,10,"RNA family information");
	$fpdf->Ln();
	$fpdf->SetFont("Arial","",10);
	foreach($pdf_rf_info as $k=>$v){
		$fpdf->SetFont("","B");
		$fpdf->Cell(60,5,$k);
		$fpdf->SetFont("","");
		$fpdf->Cell(10);
		$fpdf->Cell(60,5,$v);
		$fpdf->Ln();
	}
	$fpdf->Ln();
	$fpdf->Ln();

	//next, functional annotation information
	$fpdf->SetFont('Arial','U',12);
	$fpdf->Cell(60,10,"Functional annotation information");
	$fpdf->Ln();
	$fpdf->SetFont("Arial","",10);
	foreach($pdf_func_info as $k=>$v){
		$fpdf->SetFont("","B");
		$fpdf->Cell(60,5,$k);
		$fpdf->SetFont("","");
		$fpdf->Cell(10);
		$fpdf->Cell(60,5,$v);
		$fpdf->Ln();
	}
	$fpdf->Ln();
	$fpdf->Ln();


	echo $fpdf->Output();
?>
<?php endif; ?>
<?php // echo $this->element('sql_dump');  // Dump all MySQL queries (debug) ?>
