<?php
// Selectize JS + CSS
echo $this->Html->script('selectize.min.js');
echo $this->Html->css('https://cdn.jsdelivr.net/gh/Syone/selectize-bootswatch@master/css/selectize.paper.css');
?>
<div class="page-header">
    <h1 class="text-primary"><?php echo $ko_info["name"]; ?> <small>KO term</small></h1>
</div>
    <h3>Overview</h3>
    <section class="page-section-xs">
        <dl class="standard dl-horizontal">
            <dt>KO term</dt>
            <dd>
                <?php
                $ko = $ko_info["name"];
//                if(!$exp_info['allow_linkout']){
                    echo $ko;
//                }
//                else{
//                    echo $this->Html->link($ko, $exp_info['datasource_URL'] . "ko/view/" . $ko);
//                }
                ?>
                &nbsp; &nbsp;
                <?php echo  $this->element("linkout_func", array("linkout_type"=>"kegg_ko", "query_term"=>$ko));?>
            </dd>
            <dt>Description</dt>
            <dd><?php echo $ko_info["desc"];?></dd>
            <dt># transcripts</dt>
            <dd><?php echo $num_transcripts;?></dd>
        </dl>
    </section>

    <section class="page-section-sm">
        <?php
        $toolbox	= array("Find"=>array(
            array(
                "The associated gene families table",
                $this->Html->url(array("action"=>"assoc_gf",$exp_id, "ko", $ko)),
                "some_image.png"
            ),
            array(
                "The associated gene families visualization",
                $this->Html->url(array("controller"=>"tools","action"=>"KOSankey",$exp_id,$ko)),
                "some_image.png"
            ),
        ));
        $this->set("toolbox",$toolbox);
        echo $this->element("toolbox");
        ?>
    </section>

    <h3>Transcripts</h3>
    <div class="row" id="table-header">
        <div class="col-md-9">
            <?php echo $this->element("subset_create_form",  array("exp_id"=>$exp_id, "all_subsets"=>$all_subsets, "collection_type"=>"ko", "tooltip_text"=>$tooltip_text_subset_creation, "selection_parameters"=>[$ko])); ?>
        </div>
        <div class="col-md-3 pull-right text-right">
            <?php
            $download_url	= $this->Html->url(array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"ko",$ko));
            $this->set("download_url", $download_url);
            $this->set("allow_reference_aa_download", 1);
            echo $this->element("download_dropdown", array("align_right"=>true));
            ?>
        </div>
    </div>

    <?php echo $this->element("table_func");?>

    <?php
//    $download_url	= $this->Html->url(array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"ko",$ko),true);
//    $this->set("download_url",$download_url);
//    $this->set("allow_reference_aa_download",1);
//    echo $this->element("download");
    ?>
<?php echo $this->element("help_tooltips/enable_tooltips",  array("container"=>"#table-header")); ?>

