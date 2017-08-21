<?php
// TODO: scrap prototype
    echo $javascript->link('prototype-1.7.0.0');
echo $javascript->link("canvasXpress/canvasXpress.min.js");
echo $javascript->link("swfobject");
?>

<div>
    <div class="page-header">
        <h1 class="text-primary"><?php echo $available_types[$type]; ?> enrichment</h1>
    </div>
    <div class="alert alert-warning"><strong>WORK ONGOING ON THIS PAGE.</strong> Expect the unexpected.</div>
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
//                        echo $form->create("", array("action" => "enrichment/" . $exp_id . "/" . $type, "type" => "post", "id" => "toto", "class" => "form-horizontal"));
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
        echo $form->create("", array("action" => "enrichment/" . $exp_id . "/" . $type, "type" => "post"));
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
                            <?php echo $html->image('ajax-loader.gif'); ?><br/>
                            Loading... Please wait. <br/>
                        </center>
                    </div>
                </div>

                <?php
                if (isset($job_id)) {
//		 pr("using job id ".$job_id);
                    echo $javascript->codeBlock($ajax->remoteFunction(
                        array('url' => "/tools/load_enrichment/" . $exp_id . "/" . $type . "/" . $selected_subset . "/" . $selected_pvalue . "/" . $result_file . "/" . $job_id . "/",
                            'update' => 'enrichment_div')));
                } else {
//		pr("not using job id");
                    echo $javascript->codeBlock($ajax->remoteFunction(
                        array('url' => "/tools/load_enrichment/" . $exp_id . "/" . $type . "/" . $selected_subset . "/" . $selected_pvalue . "/" . $result_file . "/",
                            'update' => 'enrichment_div')));
                }
                ?>
            </div>
        </div>
    <script type="text/javascript">
//        $(function () {
//            var form = $("#toto");
//            var span_id = $("#enrichment_div");
//            form.on('submit', function (e) {
//                e.preventDefault();
//                $("#toto-sub").attr('disabled', true);
//                <?php //if(isset($job_id)) : ?>
////            var ajax_url = <?php ////echo "\"".$html->url(array("controller"=>"tools","action"=>"enrichment"))."\"";?>////;
//                var ajax_url = <?php //echo "\"" . $html->url("/tools/load_enrichment/" . $exp_id . "/" . $type . "/" . $selected_subset . "/" . $selected_pvalue . "/" . $result_file . "/" . $job_id . "/") . "\"";?>//;
//                <?php //else : ?>
//                var ajax_url = <?php //echo "\"" . $html->url(array("controller" => "trapid", "action" => "experiments")) . "\"";?>//;
//                <?php //endif ?>
//                // AJAX call
//                $.ajax({
//                    type: "GET",
//                    url: ajax_url,
//                    contentType: "application/json;charset=UTF-8",
//                    success: function (data) {
//                        alert("Success! ");
//                        $(span_id).hide().html(data).fadeIn();
//                        $(span_id).html(data);
//                    },
//                    error: function () {
//                        alert("Failure - Unable to retrieve results. ");
//                    },
//                    complete: function () {
//                        $("#toto-sub").attr('disabled', false);
//                    }
//                });
//            });
//        });
    </script>
    <?php endif; ?>
    </div>
</div>
