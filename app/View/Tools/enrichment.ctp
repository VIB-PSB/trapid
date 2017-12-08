<?php
// TODO: scrap prototype
//    echo $this->Html->script('prototype-1.7.0.0');
echo $this->Html->script("canvasXpress/canvasXpress.min.js");
echo $this->Html->script("swfobject");
?>

<div>
    <div class="page-header">
        <h1 class="text-primary"><?php echo $available_types[$type]; ?> enrichment</h1>
    </div>
    <div class="alert alert-success"><strong>Vizualisation work ongoing.</strong> Bear with the old (flash) charts until we are done implementing the new ones.     </div>
    <div class="subdiv">
<!--        <div class="row">-->
<!--            <div class="col-sm-8" style="border: 1px red solid;">-->
<!--                --><?php //echo $this->element("trapid_experiment"); ?>
<!--                <div class="panel panel-default">-->
<!--                    <div class="panel-heading">-->
<!--                        <h3 class="panel-title">Subset selection and enrichment parameters</h3>-->
<!--                    </div>-->
<!--                    <div class="panel-body">-->
<!--                        --><?php
//                        if (isset($error)) {
//                            echo "<span class='error'>" . $error . "</span>\n";
//                        }
//                        echo $this->Form->create(false, array("action" => "enrichment/" . $exp_id . "/" . $type, "type" => "post", "id" => "toto", "class" => "form-horizontal"));
//                        echo "<div class=\"form-group\">";
//                        echo "<dl class='standard dl-horizontal'>";
//                        echo "<dt>Subset</dt>";
//                        echo "<dd>";
//                        echo "<select name='subset' style='width:300px;'>";
//                        foreach ($subsets as $subset => $count) {
//                            if (isset($selected_subset) && $selected_subset == $subset) {
//                                echo "<option value='" . $subset . "' selected='selected'>" . $subset . " (" . $count . " transcripts)</option>\n";
//                            } else {
//                                echo "<option value='" . $subset . "'>" . $subset . " (" . $count . " transcripts)</option>\n";
//                            }
//                        }
//                        echo "</select>\n";
//                        echo "</dd>\n";
//                        echo "<dt>P-value</dt>";
//                        echo "<dd>";
//                        echo "<select name='pvalue' style='width:80px;'>";
//                        foreach ($possible_pvalues as $ppv) {
//                            if ($ppv == $selected_pvalue) {
//                                echo "<option value='" . $ppv . "' selected='selected'>" . $ppv . "</option>";
//                            } else {
//                                echo "<option value='" . $ppv . "'>" . $ppv . "</option>";
//                            }
//                        }
//                        echo "</select>\n";
//                        echo "</dd>";
//                        echo "</dl><br/>";
//                        //		echo "<input type='submit' style='width:200px;' value='Compute enrichment' />\n";
//                        echo "<input type='checkbox' style='margin-left:20px;' name='use_cache' checked='checked' />\n";
//                        echo "<span style='margin-left:5px;'>Used cached results</span>\n";
//                        ?>
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->
<!--        <div class="col-sm-4" style="border: 1px yellow solid;">-->
<!--            --><?php //echo "<input type='submit' class='btn btn-primary btn-lg' value='Compute enrichment' id='toto-sub'/>\n";
//            ?><!--</div>-->
<!--    </div>-->
<!--    --><?php //echo "</form>\n"; ?>

    <h3>Subset selection</h3>
    <div class="subdiv">
        <?php
        if (isset($error)) {
            echo "<span class='error'>" . $error . "</span>\n";
        }
        echo $this->Form->create(false, array("action" => "enrichment/" . $exp_id . "/" . $type, "type" => "post"));
        echo "<dl class='standard dl-horizontal'>";
        echo "<dt>Subset</dt>";
        echo "<dd>";
        echo "<select name='subset' style='width:300px;'>";
        foreach ($subsets as $subset => $count) {
            if (isset($selected_subset) && $selected_subset == $subset) {
                echo "<option value='" . $subset . "' selected='selected'>" . $subset . " (" . $count . " transcripts)</option>\n";
            } else {
                echo "<option value='" . $subset . "'>" . $subset . " (" . $count . " transcripts)</option>\n";
            }
        }
        echo "</select>\n";
        echo "</dd>\n";
        echo "<dt>P-value</dt>";
        echo "<dd>";
        echo "<select name='pvalue' style='width:80px;'>";
        foreach ($possible_pvalues as $ppv) {
            if ($ppv == $selected_pvalue) {
                echo "<option value='" . $ppv . "' selected='selected'>" . $ppv . "</option>";
            } else {
                echo "<option value='" . $ppv . "'>" . $ppv . "</option>";
            }
        }
        echo "</select>\n";
        echo "</dd>";
        echo "</dl><br/>";
        //		echo "<input type='submit' style='width:200px;' value='Compute enrichment' />\n";
        echo "<input type='submit' class='btn btn-primary' value='Compute enrichment' />\n";
        echo "<input type='checkbox' style='margin-left:20px;' name='use_cache' checked='checked' />\n";
        echo "<span style='margin-left:5px;'>Used cached results</span>\n";
        echo "</form>\n";
        ?>
    </div>
    <br/><br/>
    <?php if (isset($result_file)) : ?>
        <!--	<h3>Enrichment --><?php //echo "<i>".$selected_subset."</i>"; ?><!--</h3>-->
        <h2>Enrichment <?php echo "<code>" . $selected_subset . "</code>"; ?></h2>
        <br/>
        <div class="subdiv">
            <div class="subdiv">
                <div id="enrichment_div">
                    <div style="width:200px; margin:0 auto;">
                        <center>
                            <?php echo $this->Html->image('ajax-loader.gif'); ?><br/>
                            Loading... Please wait. <br/>
                        </center>
                    </div>
                </div>
                <script type="text/javascript">
                /* Ajax calls were replaced after removal of JS/Ajax helpers in CakePHP 2.0 */
                <?php if(isset($job_id)) : ?>
                var ajax_url = <?php echo "\"" . $this->Html->url("/tools/load_enrichment/" . $exp_id . "/" . $type . "/" . $selected_subset . "/" . $selected_pvalue . "/" . $result_file . "/" . $job_id . "/") . "\"";?>;
                // pr("using job id ".$job_id);
                <?php else : ?>
                var ajax_url = <?php echo "\"" . $this->Html->url("/tools/load_enrichment/" . $exp_id . "/" . $type . "/" . $selected_subset . "/" . $selected_pvalue . "/" . $result_file . "/") . "\"";?>;
                // pr("not using job id");
                <?php endif ?>
                $.ajax({
                    type: "GET",
                    url: ajax_url,
                    dataType:'html',
                    success: function(data){
                        jQuery('#enrichment_div').html(data);
                    },
                    error: function() {
                        console.log("Error - Impossible to retrieve data");
                    }
                });
                </script>
            </div>
        </div>
<!--    <script type="text/javascript">-->
<!--    </script>-->
    <?php endif; ?>
    </div>
</div>
