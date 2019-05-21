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
	$toolbox	= array("Compare"=>array(
					array(
						"Label Gene Family intersection",
						$this->Html->url(array("controller"=>"tools","action"=>"label_gf_intersection",$exp_id,$label)),
						"some_image.png"
					),							
					array(
						"Label Interpro intersection",
						$this->Html->url(array("controller"=>"tools","action"=>"label_interpro_intersection",$exp_id,$label)),
						"some_image.png"
					),							
					array(
						"Label GO intersection",
						$this->Html->url(array("controller"=>"tools","action"=>"label_go_intersection",$exp_id,$label)),
						"some_image.png"			
					)		
				),
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
	<div class="subdiv">
	<?php echo $this->element("table_func");?>
	</div>


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
