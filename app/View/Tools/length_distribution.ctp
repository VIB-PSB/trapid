<?php
// TODO: host locally
// Highcharts insert
echo $this->Html->script('https://code.highcharts.com/highcharts.js');
echo $this->Html->script('https://code.highcharts.com/modules/exporting.js');
?>
<div class="page-header">
    <h1 class="text-primary">Sequence length distribution</h1>
</div>

<section class="page-section">
<!--    <h3>Work in progress! </h3>-->
    <?php
        echo $this->Form->create(false, array("url"=>array("controller"=>"tools", "action"=>"length_distribution", $exp_id),"type"=>"post", "id"=>"graph-update-form"));
    ?>
    <div class="row">

        <div id="options-col" class="col-md-3 vcenter">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Display settings</h3>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <strong>Sequence type</strong>
                        <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['seq_len_sequence_type'], "tooltip_placement"=>"top")); ?>
                        <div class="radio">
                            <label>
                                <input id="sequence-type-tr" name="sequence_type" type="radio" value="transcript" onchange="toggleOrfOptions();" checked> Transcript &nbsp;
                            </label>
                            <?php if($exp_info['process_state'] == 'finished'): ?>
                                <label>
                                    <input id="sequence-type-orf" name="sequence_type" type="radio" value="orf" onchange="toggleOrfOptions();"> ORF
                                </label>
                            <?php else: ?>
                                <label title="Cannot visualize ORF length distribution before initial processing" style="cursor: not-allowed;">
                                    <input id="sequence-type-orf"  style="cursor: not-allowed;" name="sequence_type" type="radio" value="orf" disabled title="Cannot visualize ORF length distribution before initial processing"> ORF
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['seq_len_n_bins'], "tooltip_placement"=>"top")); ?>

                        <label><strong>Number of bins</strong>
                            (current: <span id="num-bins-value"></span>)
                        </label><br>

                        <div id="bins-row" class="row" style="">
                            <div id="bins-min" class="col-sm-2 hidden-xs text-right text-muted" style="font-size:88%;"><?php echo $range_bins[0]; ?></div>
                            <div id="bins-slider" class="col-sm-8">
                                <input id='num_bins' name='num_bins' type="range" min="<?php echo $range_bins[0]; ?>" max="<?php echo $range_bins[1]; ?>" step="5" value="<?php echo $default_bins; ?>" onchange="updateNumBins(this.value);">
                            </div>
                            <div id="bins-max" class="col-sm-2 hidden-xs text-left text-muted" style="font-size:88%;"><?php echo $range_bins[1]; ?></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="checkbox" id="meta-annotation-checkboxes">
                            <strong>Transcript meta-annotation</strong>
                            <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['seq_len_meta_annotation'], "tooltip_placement"=>"top", "use_html"=>"true")); ?>
                            <br>
                            <label>
                                <input type="checkbox" id="meta_noinfo" name="meta_noinfo"> Show 'no information'
                            </label><br>
                            <label>
                                <input type="checkbox" id="meta_partial" name="meta_partial"> Show 'partial'
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><strong>Reference species (ORF)</strong></label>
                        <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['seq_len_orf_ref_species'], "tooltip_placement"=>"top", "use_html"=>"true")); ?>
                        <br>
                        <?php
                        echo "<select name='reference_species' id='reference_species' class='form-control'>\n";
                        echo "<option value=''>None</option>\n";
                        foreach($available_reference_species as $s=>$cn){
                            echo "<option value='".$s."'>".$cn."</option>\n";
                        }
                        echo "</select>\n";
                        ?>
                        <div class="checkbox" id="normalize-checkbox">
                        <label class="checkbox-inline">
                            <input type="checkbox" id="normalize" name="normalize"> Normalize data
                        </label>
                            <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['seq_len_orf_ref_normalize'], "tooltip_placement"=>"top")); ?>
                        </div>
                    </div>

                    <div class="form-group">
                            <strong>Graph type</strong></label>
                        <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['seq_len_graph_type'], "tooltip_placement"=>"top")); ?>
                        <div class="radio">
                            <label>
                                <input id="graph-type-grouped" name="graph_type" type="radio" value="grouped" checked> Grouped &nbsp;
                            </label>
                            <label>
                                <input id="graph-type-stacked" name="graph_type" type="radio" value="stacked"> Stacked
                            </label>
                        </div>
                    </div>

                </div>
            </div><!-- end panel -->
            <!-- Graph update form submission -->
            <p class="text-center">
                <input type="submit" class="btn btn-primary" id="graph-update-submit" value="Create graph"/>
                | <a id="graph-update-reset" style="cursor:pointer;" onclick="resetGraphForm('graph-update-form');">Reset all</a>
            </p>
        </div><!--
        --><div id="chart-col" class="col-md-9 vcenter">
            <div id="loading" class="hidden">
                <p class="text-center">
                    <?php echo $this->Html->image('ajax-loader.gif'); ?><br>
                    Loading... Please wait.
                </p>
            </div>
            <div id="chart-container"></div>
        </div>
    </div>
    <?php echo $this->Form->end(); ?>
</section>


<script type="text/javascript">
    // Various elements ids (jQuery selectors)
    var display_div_id = "#chart-container";
    var loading_div_id = "#loading";
    var sub_form_id = "#graph-update-form";
    var sub_btn_id = "#graph-update-submit";
    var reset_btn_id = "#graph-update-reset";


    function populateChartCol() {
        console.log("[Message] Graph update form was submitted! ");
        $(loading_div_id).removeClass("hidden");
        $(sub_btn_id).attr("disabled", true);
        $(display_div_id).empty();
        $.ajax({
            url: "<?php echo $this->Html->url(array("controller" => "tools", "action" => "length_distribution", $exp_id), array("escape" => false)); ?>",
            type: 'POST',
            data: $(sub_form_id).serialize(),
            dataType: 'html',
            success: function (data) {
                $(sub_btn_id).attr("disabled", false);
                $(loading_div_id).addClass("hidden");
                $(display_div_id).fadeOut('slow', function () {
                    $(display_div_id).hide().html(data).fadeIn();
                });
            },
            error: function () {
                console.log('[Error] Unable to update chart data!');
            }
        });
    }

    $(function() {
        $(sub_form_id).submit(function (e) {
            e.preventDefault();
            populateChartCol();
        });
    });


    // Display current number of bins
    function updateNumBins(val) {
        document.getElementById('num-bins-value').textContent=val;
    }


    // Reset graph update form
    function resetGraphForm(formId) {
        document.getElementById(formId).reset();
        updateNumBins(document.getElementById('num_bins').value);
    }


    // Disable / enable ORF or transcript related form input elements
    function toggleOrfOptions() {
        // Check the value of `sequence_type`
        var seqType = "transcript";
        var radioBtns = document.getElementsByName('sequence_type');
        for (var i = 0, length = radioBtns.length; i < length; i++) {
            if (radioBtns[i].checked) {
                seqType = radioBtns[i].value;
                break;
            }
        }
        if(seqType === "transcript") {
            // Disable ORF options
            document.getElementById("reference_species").disabled = true;
            document.getElementById("normalize").disabled = true;
            document.getElementById("normalize-checkbox").classList.add("disabled");
            document.getElementById("meta-annotation-checkboxes").classList.remove("disabled");
            // Enable transcript options
            document.getElementById("meta_noinfo").disabled = false;
            document.getElementById("meta_partial").disabled = false;
        }
        if(seqType === "orf") {
            // Enable ORF options
            document.getElementById("reference_species").disabled = false;
            document.getElementById("normalize").disabled = false;
            document.getElementById("normalize-checkbox").classList.remove("disabled");
            document.getElementById("meta-annotation-checkboxes").classList.add("disabled");
            // Disable transcript options
            document.getElementById("meta_noinfo").disabled = true;
            document.getElementById("meta_partial").disabled = true;
        }

    }


    // Resize bar chart on toggling of the side menu
    // TODO: implement that everywhere where we have highcharts?
    function resize_charts() {
        setTimeout(function() {
            jQuery( ".hc" ).each(function() { // target each element with the .hc class
                var chart = jQuery(this).highcharts(); // target the chart itself
                console.log(chart);
                chart.reflow()  // reflow that chart
            });
        }, 420);
    }

    $('.sidebar-toggle').on('click', function () {
        resize_charts();
    });


    // Do stuff on page load
    window.onload = (function(){
        // Display current  number of bins
        updateNumBins(document.getElementById('num_bins').value);
        // Toggle ORF-related form options
        toggleOrfOptions();
        // Load default length distribution chart
        populateChartCol();
    });
</script>
<?php echo $this->element("help_tooltips/enable_tooltips",  array("container"=>"#graph-update-form")); ?>
<?php // echo $this->element('sql_dump');  // Dump all MySQL queries (debug) ?>