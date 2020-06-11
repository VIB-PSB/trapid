<?php
// Selectize JS + CSS
echo $this->Html->script('selectize.min.js');
echo $this->Html->css('https://cdn.jsdelivr.net/gh/Syone/selectize-bootswatch@master/css/selectize.paper.css');
?>
<div>
    <div class="page-header">
      <h1 class="text-primary">
        <?php echo $interpro_info['name']; ?> <small>Protein domain</small>
      </h1>
    </div>

<div class="subdiv">
	<?php // echo $this->element("trapid_experiment");?>

	<h3>Overview</h3>
	<div class="subdiv">
		<dl class="standard dl-horizontal">
			<dt>Protein domain</dt>
			<dd>
			<?php
				// $interpro	= $interpro_info['motif_id'];
				$interpro	= $interpro_info['name'];
				if(!$exp_info['allow_linkout']){
					echo $interpro;
				}
				else{
					echo $this->Html->link($interpro,$exp_info['datasource_URL']."interpro/view/".$interpro);
					// TODO: add link to InterPro itself. Link is formed as such: http://www.ebi.ac.uk/interpro/entry/<motif_id>
				}

                echo "&nbsp; &nbsp;";
                echo  $this->element("linkout_func", array("linkout_type"=>"interpro", "query_term"=>$interpro));
            ?>
            </dd>
			<dt>Description</dt>
			<dd><?php echo $interpro_info["desc"];?></dd>
			<dt>#transcripts</dt>
			<dd><?php echo $num_transcripts;?></dd>
		</dl>
	</div>


	<h3>Toolbox</h3>
	<div class="subdiv">
	<?php
	$toolbox	= array("Associated gene families"=>array(
					array(
						"Table",
						$this->Html->url(array("action"=>"assoc_gf",$exp_id,"interpro",$interpro)),
						"some_image.png"
					),
                    array(
						"Visualization",
						$this->Html->url(array("controller"=>"tools","action"=>"interproSankey",$exp_id,$interpro)),
						"some_image.png"
					),
				)
			);
	$this->set("toolbox",$toolbox);
	echo $this->element("toolbox");
	?>
	</div>

	<h3>Transcripts</h3>
    <div class="row" id="table-header">
        <div class="col-md-9">
            <?php echo $this->element("subset_create_form",  array("exp_id"=>$exp_id, "all_subsets"=>$all_subsets, "collection_type"=>"ipr", "tooltip_text"=>$tooltip_text_subset_creation, "selection_parameters"=>[$interpro])); ?>
        </div>
        <div class="col-md-3 pull-right text-right">
            <?php
            $download_url	= $this->Html->url(array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"interpro",$interpro));
            $this->set("download_url", $download_url);
            $this->set("allow_reference_aa_download", 1);
            echo $this->element("download_dropdown", array("align_right"=>true));
            ?>
        </div>
    </div>

	<?php echo $this->element("table_func");?>

	<?php
//		$download_url	= $this->Html->url(array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"interpro",$interpro),true);
//		$this->set("download_url",$download_url);
//		$this->set("allow_reference_aa_download",1);
//		echo $this->element("download");
	?>

</div>
</div>
<?php echo $this->element("help_tooltips/enable_tooltips",  array("container"=>"#table-header")); ?>
