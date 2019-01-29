<div class="page-header">
    <h1 class="text-primary"><?php echo $ko_info["name"]; ?> <small>KO term</small></h1>
</div>
<div class="subdiv">
    <h3>Overview</h3>
    <div class="subdiv">
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
    </div>


    <div class="subdiv">
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
    </div>

    <h3>Transcripts</h3>
    <div class="subdiv">
        <?php echo $this->element("table_func");?>
    </div>

    <?php
    $download_url	= $this->Html->url(array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"ko",$ko),true);
    $this->set("download_url",$download_url);
    $this->set("allow_reference_aa_download",1);
    echo $this->element("download");
    ?>
</div>
</div>

