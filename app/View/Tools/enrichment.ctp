<?php
    // TODO: host highcahrts locally
    echo $this->Html->script(
        array(
            'https://code.highcharts.com/highcharts.js',
            'https://code.highcharts.com/modules/exporting.js',
            'dagre.js',
            'd3-3.5.6.min.js',
            'd3/d3-color.v1.min.js',
            'd3/d3-interpolate.v1.min.js',
            'd3/d3-scale-chromatic.v1.min.js',
            'jQuery/jquery.contextMenu.js',
            'jQuery/jquery.tipsy.js',
//            'enricher.min.js'
            'enricher/ContextMenu.js',
            'enricher/DirectedAcyclicGraph.js',
            'enricher/Enricher.js',
            'enricher/Graph.js',
            'enricher/History.js',
            'enricher/List.js',
            'enricher/Minimap.js',
            'enricher/MinimapZoom.js',
            'enricher/Selectable.js',
            'enricher/Tooltip.js',
            'enricher/Utils.js'
        )
    );

    echo $this->Html->css(
        array(
            'enricher.css'
        )
);
?>

    <div class="page-header">
        <h1 class="text-primary">Subset functional enrichment</h1>
    </div>
    <section class="page-section">
        <p class="text-justify">
            Functional enrichment analysis determines the over-representation of a certain functional annotation term in
            an input set of objects (here transcripts) compared to the background frequency (here transcriptome-wide).
        </p>
        <p class="text-justify">
            This procedure will compare the occurrence of a certain functional annotation term  in a transcript subset
            with the occurrence in the complete dataset. The significance of over-representation is determined using the
            hypergeometric distribution, and the Benjamini & Hochberg method is applied to correct for multiple testing.
        </p>
        <p class="text-justify">
            Note that enrichment folds, which indicate the ratio of the frequency in the subset
            over the frequency in the complete dataset, are reported in log<sub>2</sub> scale
            (e.g. <samp>value = 1</samp> is two-fold enriched).
        </p>
    </section>

    <h3>Subset selection</h3>
    <section class="page-section-sm">
        <?php
        if (isset($error)) {
            echo "<p class='text-justify text-danger'><strong>Error: " . $error . "</strong></p>\n";
        }
        echo $this->Form->create(false, array("url"=>array("controller"=>"tools", "action" => "enrichment", $exp_id), "type" => "post", "id"=>"enrichment-form"));
        echo "<dl class='standard dl-horizontal' style='max-width:530px;'>";
        echo "<dt>Annotation type</dt>";
        echo "<dd>";
        echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['enrichment_type'], "tooltip_placement"=>"right"));
        $i = 0;
        foreach($available_types as $type_id=>$type_str) {
            if($i == 0 || (isset($type) && $type == $type_id)) {
                $checked = "checked";
            } else {
                $checked = '';
            }
            echo "<label><input type='radio' name='annotation_type' id='annotation_type' value='" . $type_id . "' " . $checked . "> <strong>" . $type_str . "</strong></label>&nbsp; &nbsp;";
            $i++;
        }
        echo "</dd>";
        echo "<dt>Subset";
        echo "</dt>";
        echo "<dd>";
        echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['enrichment_subset'], "tooltip_placement"=>"right"));
        echo "<select name='subset' style='width:300px;' class='form-control'>";
        foreach ($subsets as $subset => $count) {
            if (isset($selected_subset) && $selected_subset == $subset) {
                echo "<option value='" . $subset . "' selected='selected'>" . $subset . " (" . $count . " transcripts)</option>\n";
            } else {
                echo "<option value='" . $subset . "'>" . $subset . " (" . $count . " transcripts)</option>\n";
            }
        }
        echo "</select>\n";
        echo "</dd>\n";
        echo "<dt>Maximum q-value</dt>";
        echo "<dd>";
        echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['enrichment_pvalue'], "tooltip_placement"=>"right"));
        echo "<select name='pvalue' style='width:80px;' class='form-control'>";
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
        echo "<input type='checkbox' style='margin-left:20px;' id='use_cache' name='use_cache' checked='checked' />\n";
        echo "<label style='margin-left:5px;' for='use_cache'><strong>Use cached results</strong></label>\n";
        echo $this->Form->end();
        ?>
    </section>
    <?php if (isset($load_results)) : ?>
        <hr>
        <h3>Enrichment - <?php echo "<code>" . $selected_subset . "</code>"; ?></h3>
        <br/>
                <div id="enrichment_div">
                    <div id="loading">
                        <div class="text-center">
                            <div class="ajax-spinner text-center"></div><br>
                            Loading... Please wait.
                        </div>
                    </div>
                </div>
                <script type="text/javascript">
                /* Ajax calls were replaced after removal of JS/Ajax helpers in CakePHP 2.0 */
                <?php if(isset($job_id)) : ?>
                var ajax_url = <?php echo "\"" . $this->Html->url("/tools/load_enrichment/" . $exp_id . "/" . $type . "/" . $selected_subset . "/" . $selected_pvalue . "/" . "/" . $job_id . "/") . "\"";?>;
                // pr("using job id ".$job_id);
                <?php else : ?>
                var ajax_url = <?php echo "\"" . $this->Html->url("/tools/load_enrichment/" . $exp_id . "/" . $type . "/" . $selected_subset . "/" . $selected_pvalue . "/" ) . "\"";?>;
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
    <?php endif; ?>
    </div>
<script type="text/javascript">
    // Resize bar chart on toggling of the side menu
    // TODO: implement that everywhere where we have highcharts?
    function resize_charts() {
        setTimeout(function() {
            jQuery( ".hc" ).each(function() { // target each element with the .hc class
                var chart = jQuery(this).highcharts(); // target the chart itself
                console.log(chart);
                chart.reflow();  // reflow that chart
            });
        }, 410);
    }

    $('.sidebar-toggle').on('click', function () {
        resize_charts();
    });
</script>
<?php echo $this->element("help_tooltips/enable_tooltips",  array("container"=>"#enrichment-form")); ?>