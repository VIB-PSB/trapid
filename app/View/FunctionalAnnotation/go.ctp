<?php
  echo $this->Html->script(array('dataTables.min.js', 'dataTables.bootstrap.min.js'));
  echo $this->Html->script(array('dataTables.min.js', 'dataTables.bootstrap.min.js'));
	echo $this->Html->css(array('dataTables.bootstrap.min.css'))."\n";

// Selectize JS + CSS
echo $this->Html->script('selectize.min.js');
echo $this->Html->css('https://cdn.jsdelivr.net/gh/Syone/selectize-bootswatch@master/css/selectize.paper.css');

?>
<div class="page-header">
    <h1 class="text-primary"><?php echo $go_info["name"]; ?> <small>GO term</small></h1>
</div>
<div class="subdiv">
	<?php // echo $this->element("trapid_experiment"); ?>

	<h3>Overview</h3>
	<div class="subdiv">
		<dl class="standard dl-horizontal">
			<dt>GO term</dt>
			<dd>
			<?php
			    $go_web	= str_replace(":","-",$go_info["name"]);
			    if(!$exp_info['allow_linkout']){
				echo $go_info["name"];
                echo "&nbsp;";
                echo $this->element("go_category_badge", array("go_category"=>$go_info["info"], "small_badge"=>true));
			    }
			    else{
			       echo $this->Html->link($go_info["name"],$exp_info['datasource_URL']."go/view/".$go_web);
			       echo "&nbsp;";
                   echo $this->element("go_category_badge", array("go_category"=>$go_info["info"], "small_badge"=>true));
                }
			?>
                &nbsp; &nbsp;
                <?php echo  $this->element("linkout_func", array("linkout_type"=>"amigo", "query_term"=>$go_info["name"]));?>
                <?php echo  $this->element("linkout_func", array("linkout_type"=>"quickgo", "query_term"=>$go_info["name"]));?>
			</dd>
			<dt>Description</dt>
			<dd><?php echo $go_info["desc"];?></dd>
			<dt>#transcripts</dt>
			<dd><?php echo $num_transcripts;?></dd>
		</dl>
	</div>


<!--	<h3>Toolbox</h3>-->
	<div class="subdiv">
	<?php
	$toolbox	= array("Find"=>array(
					array(
						"The associated gene families table",
						$this->Html->url(array("action"=>"assoc_gf",$exp_id,"go",$go_web)),
						"some_image.png"
					),
                    array(
						"The associated gene families visualization",
						$this->Html->url(array("controller"=>"tools","action"=>"GOSankey",$exp_id,$go_web)),
						"some_image.png"
					),
				),
				"Explore"=>array(
					array(
						"Explore the child GO terms",
						$this->Html->url(array("action"=>"child_go",$exp_id,$go_web)),
						"other_image.png"
					),
					array(
						"Explore the parental GO terms",
						$this->Html->url(array("action"=>"parent_go",$exp_id,$go_web)),
						"other_image.png"
					)
				)
			);
	$this->set("toolbox",$toolbox);
	echo $this->element("toolbox");
	?>
	</div>

	<h3>Transcripts</h3>

    <div class="row" id="table-header">
        <div class="col-md-9">
            <?php echo $this->element("subset_create_form",  array("exp_id"=>$exp_id, "all_subsets"=>$all_subsets, "collection_type"=>"go", "tooltip_text"=>$tooltip_text_subset_creation, "selection_parameters"=>[$go_web])); ?>
        </div>
        <div class="col-md-3 pull-right text-right">
            <?php
                $download_url	= $this->Html->url(array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"go",$go_web));
                $this->set("download_url", $download_url);
                $this->set("allow_reference_aa_download", 1);
                echo $this->element("download_dropdown", array("align_right"=>true));
            ?>
        </div>
    </div>


    <?php echo $this->element("table_func");?>
	<?php
//		$download_url	= $this->Html->url(array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"go",$go_web));
//		$this->set("download_url",$download_url);
//		$this->set("allow_reference_aa_download",1);
//		echo $this->element("download");
	?>
</div>
</div>
<?php echo $this->element("help_tooltips/enable_tooltips",  array("container"=>"#table-header")); ?>
