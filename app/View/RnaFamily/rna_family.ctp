<?php
// Selectize JS + CSS
echo $this->Html->script('selectize.min.js');
echo $this->Html->css('selectize.paper.css');
?>

    <div class="page-header">
        <h1 class="text-primary"><?php echo $rf_data['rf_id']; ?> <small>RNA family</small></h1>
    </div>
    <div class="subdiv">
        <h3>Overview</h3>
        <div class="subdiv">
            <dl class="standard dl-horizontal">
                <dt>RNA Family</dt>
                <dd><?php echo $rf_data['rf_id'];?></dd>
                <dt>Description</dt>
                <dd><?php echo $rf_data['description'];?></dd>
                <dt>Transcript count</dt>
                <dd><?php echo $rf_data['num_transcripts'];?></dd>
                <dt>Original RNA Family</dt>
                <dd>
                    <?php
                    echo "<a class='linkout' target='_blank' href='". $rfam_linkouts["base_url"] . $rfam_linkouts["family"] . $rf_data['rfam_rf_id'] . "'>" . $rf_data['name'] . " (" . $rf_data['rfam_rf_id'] . ")</a>";
                    if(isset($rf_data['rfam_clan_id'])){
                        echo ", member of clan "
                            . "<a class='linkout' target='_blank' href='". $rfam_linkouts["base_url"] . $rfam_linkouts["clan"] . $rf_data['rfam_clan_id'] . "'>" . $rf_data['clan_name'] . " (" . $rf_data['rfam_clan_id'] . ")</a>"
                            . ". ";
                    }
                    ?>
                </dd>

            </dl>
        </div>

        <h3>Transcripts</h3>

        <div class="row" id="table-header">
            <div class="col-md-9">
                <?php echo $this->element("subset_create_form",  array("exp_id"=>$exp_id, "all_subsets"=>$all_subsets, "collection_type"=>"rf", "tooltip_text"=>$tooltip_text_subset_creation, "selection_parameters"=>[$rf_data['rf_id']])); ?>
            </div>
            <div class="col-md-3 pull-right text-right">
                <?php
//                $download_url = $this->Html->url(array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"gf_id",$gf_info['gf_id']));
//                $this->set("download_url", $download_url);
//                $this->set("allow_reference_aa_download", 1);
//                echo $this->element("download_dropdown", array("align_right"=>true));
                ?>
            </div>
        </div>


        <?php echo $this->element("table_func");?>

        <?php
        /*
        $download_url	= $this->Html->url(array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"rf_id",$rf_data['rf_id']),true);
        $this->set("download_url",$download_url);
        $this->set("allow_reference_aa_download",1);
        echo $this->element("download");
        */
        ?>
    </div>
<?php echo $this->element("help_tooltips/enable_tooltips",  array("container"=>"#table-header")); ?>
