<?php
// Selectize JS + CSS
echo $this->Html->script('selectize.min.js');
echo $this->Html->css('selectize.paper.css');
?>

<div class="page-header">
    <h1 class="text-primary"><?php echo $label; ?> <small>Transcript subset</small></h1>
</div>
    <?php
    // Display error message if there was any problem with form submission (retranslate subset sequences)
    if(isset($error)){
        echo "<p class='text-danger error'><strong>Error: </strong>".$error."</p>\n";
    }
    ?>
<div class="subdiv">
	<h3>Overview</h3>

    <div class="subdiv">
		<dl class="standard dl-horizontal">
			<dt>Subset</dt>
			<dd><?php echo $label; ?></dd>			
			<dt>#transcripts</dt>
			<dd><?php echo $num_transcripts;?></dd>
		</dl>
	</div>

	<div class="subdiv">
	<?php

    // Sankey intersection toolbox items
    $sankey_intersection_fcts = array(
        "go"=>array(
            "Subset - GO intersection",
            $this->Html->url(array("controller"=>"tools","action"=>"label_go_intersection",$exp_id,$label)),
            "some_image.png"
        ),
        "interpro"=>array(
            "Subset - InterPro intersection",
            $this->Html->url(array("controller"=>"tools","action"=>"label_interpro_intersection",$exp_id,$label)),
            "some_image.png"
        ),
        "ko"=>array(
            "Subset - KO intersection",
            $this->Html->url(array("controller"=>"tools","action"=>"label_ko_intersection",$exp_id,$label)),
            "some_image.png"
        )
    );
    $sankey_intersection_toolbox = array(
        "gf"=>array(
            "Subset - Gene Family intersection",
            $this->Html->url(array("controller"=>"tools","action"=>"label_gf_intersection",$exp_id,$label)),
            "some_image.png"
        )
    );
    // Add toolbox items depending on the available functional annotation types
    foreach ($exp_info['function_types'] as $fct_type) {
        $sankey_intersection_toolbox[$fct_type] = $sankey_intersection_fcts[$fct_type];
    }

	$toolbox	= array("Compare"=>$sankey_intersection_toolbox,
                "Sequences"=>array(
                        array(
                            "Predict ORF sequences using another genetic code",
                            array("href"=>"#", "data-toggle"=>"modal", "data-target"=>"#retranslate-modal"),
                            "placeholder.png"))
			);
	$this->set("toolbox",$toolbox);
	echo $this->element("toolbox");
	?>
	<h3>Transcripts</h3>
        <div class="row" id="table-header">
            <div class="col-md-9">
                <?php echo $this->element("subset_create_form",  array("exp_id"=>$exp_id, "all_subsets"=>$all_subsets, "collection_type"=>"subset", "tooltip_text"=>$tooltip_text_subset_creation, "selection_parameters"=>[$label])); ?>
            </div>
            <div class="col-md-3 pull-right text-right">
                <?php
//                $download_url	= $this->Html->url(array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"ko",$ko));
//                $this->set("download_url", $download_url);
//                $this->set("allow_reference_aa_download", 1);
//                echo $this->element("download_dropdown", array("align_right"=>true));
                ?>
            </div>
        </div>

	<?php echo $this->element("table_func");?>


    <!-- "Retranslate modal -->
    <div class="modal fade" id="retranslate-modal" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>
                    <h3 class="modal-title" id="lineModalLabel">Retranslate subset sequences</h3>
                </div>
                <div class="modal-body">
                    <?php
                    echo $this->Form->create("Experiments",array("url"=>array("controller"=>"labels","action"=>"retranslate_sqces", $exp_id, $label),
                        "type"=>"post"));
                    ?>
                    <div class="form-group">
                        <label for="transl_table"><strong>Genetic code to use</strong></label>
                        <select class="form-control" name="transl_table">
                            <?php
                            foreach($transl_table_descs as $idx=>$desc){
                                echo "<option value='" . $idx . "'>" . $idx . " - " . $desc . "</option>\n";
                            }
                            ?>
                        </select>
                        <p class="help-block" style="font-size: 88%;"><strong>Note:</strong> More information about genetic codes can be found on the <a href="https://www.ncbi.nlm.nih.gov/Taxonomy/taxonomyhome.html/index.cgi?chapter=cgencodes" class="linkout" target="_blank">NCBI Taxonomy</a>.</p>
                    </div>
                    <p class="text-center">
                        <button type="submit" class="btn btn-primary">Retranslate</button></p>
                    <?php echo $this->Form->end(); ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php echo $this->element("help_tooltips/enable_tooltips",  array("container"=>"#table-header")); ?>
