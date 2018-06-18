<?php
// TODO: host locally
// Highcharts
echo $this->Html->script('http://code.highcharts.com/highcharts.js');
echo $this->Html->script('http://code.highcharts.com/modules/exporting.js');
// D3
echo $this->Html->script('d3-3.5.6.min.js');
// UniPept's visualizations
echo $this->Html->script('https://rawgit.com/unipept/unipept-visualizations/master/dist/unipept-visualizations.min.js');
?>

<style>
    /* Unused for now */
    /*.selectedText {*/
        /*fill: red;*/
    /*}*/

</style>

<div class="page-header">
    <h1 class="text-primary">Transcripts taxonomic binning</h1>
</div>
<!--<p class="lead text-justify">Explore taxonomic binning results with different visualization! </p>-->
<p class="text-justify"><strong>Usage:</strong> use the tabs to switch from one type of visualization to the other.
Hold the <kbd>CTRL</kbd> key down and click on phylogenetic clades using any of the visualization to select clades. This list can be used to define a sequence subset containing corresponding transcripts. </p>
<p class="text-justify"><strong>Selected clades:</strong><code id="selected-tax">None</code></p>

<div class="row">
    <div class="col-lg-9">
        <?php echo $this->Form->create(false, array("url"=>array("controller" => "tools", "action" => "create_tax_subset/" . $exp_id), "type" => "post", "default"=>"false", "id"=>"create-subset-form", "class"=>"form-inline", "name"=>"create-subset-form")); ?>
            <div class="form-group">
                <input class="form-control" placeholder="Subset name... " maxlength="20" name="subset-name" id="subset-name" type="text" required>
                <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltip_text_subset_name, "tooltip_placement"=>"top", "override_span_class"=>"glyphicon glyphicon-question-sign")); ?>
            </div> &nbsp;
            <input type="submit" class="btn btn-xs btn-primary" id="create-subset" disabled value="Define new subset"/>
            | <a id="reset-tax-list">Reset selected clades</a>
        <?php echo $this->Form->end(); ?>
    </div>
    <div class="pull-right">
            <div id="loading" style="display:none;">
        <p class="text-center text-mute">
            <?php echo $this->Html->image('small-ajax-loader.gif');?>
            loading...
        </p>

    </div>
            <div id="create-subset-result"></div>
    </div>
</div>
<br>

<!-- Tabs  -->
<div id="content">
    <ul class="nav nav-tabs nav-justified" id="tabs" data-tabs="tabs">
        <li class="active"><a href="#krona" data-toggle="tab">Krona</a></li>
        <li><a href="#tree-view" data-toggle="tab">Tree</a></li>
        <li><a href="#piecharts" data-toggle="tab">Pie</a></li>
        <li><a href="#barcharts" data-toggle="tab">Bar</a></li>
        <!--<li><a href="#raw-data" data-toggle="tab">Raw data</a></li>-->
    </ul>
    <div class="tab-content">
        <div id="krona" class="tab-pane active"><br>
<!--            <p class="text-justify">Simply an <code>object</code> element that loads Krona HTML: </p>-->
            <object type="text/html" id="krona-object" class="krona-display" data="<?php echo $display_krona_url; ?>">
                Default content. If this appears, Krona HTML file failed to load.
            </object>
        </div>

        <div id="tree-view" class="tab-pane"><br>
<!--            <p class="text-justify">Unipept's tree viewer, inspired from this <a href="http://bl.ocks.org/robschmuecker/7880033" target="_blank">D3.js code snippet</a>.</p>-->
            <div id="d3-tree-view" style="border:1px solid #aaaaaa;"></div>

        </div>

        <div id="piecharts" class="tab-pane"><br>
<!--            <p class="text-justify">Generated using Highcharts.</p>-->
            <div class="row" style="margin-bottom:6px;">
                <ul class="nav nav-pills small-nav pull-right" id="nav-pills-piechart" data-tabs="pills">
                    <li><strong>View taxonomic rank: </strong></li>
                    <li class="active"><a data-toggle="pill" href="#phylum-top-tax-pie-tab" data-toggle="tab">Phylum</a></li>
                    <li><a data-toggle="pill" href="#order-top-tax-pie-tab" data-toggle="tab">Order</a></li>
                    <li><a data-toggle="pill" href="#genus-top-tax-pie-tab" data-toggle="tab">Genus</a></li>
                </ul>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <?php echo
                        $this->element('charts/pie_domain', array("chart_title"=>"Domain composition", "chart_subtitle"=>"Total transcripts: ".$domain_sum_transcripts, "chart_data"=>$top_tax_domain, "chart_div_id"=>"top_tax_piechart"));
                    ?>
                </div>
                <div class="col-md-8">
                    <div class="tab-content" id="top-tax-piecharts">
                        <div id="phylum-top-tax-pie-tab" class="tab-pane active">
                            <?php
                                echo $this->element('charts/pie_tax', array("chart_title"=>"Phylum composition", "chart_subtitle"=>"Total transcripts classified at that level: ".$phylum_sum_transcripts, "chart_data"=>$top_tax_phylum, "chart_div_id"=>"phylum_top_tax_piechart", "legend_tax_str"=>"phyla"));
                            ?>
                        </div>
                        <div id="order-top-tax-pie-tab" class="tab-pane">
                            <?php
                                echo $this->element('charts/pie_tax', array("chart_title"=>"Order composition", "chart_subtitle"=>"Total transcripts classified at that level: ".$order_sum_transcripts, "chart_data"=>$top_tax_order, "chart_div_id"=>"order_top_tax_piechart", "legend_tax_str"=>"orders"));
                            ?>
                        </div>
                        <div id="genus-top-tax-pie-tab" class="tab-pane">
                            <?php
                                echo $this->element('charts/pie_tax', array("chart_title"=>"Genus composition", "chart_subtitle"=>"Total transcripts classified at that level: ".$genus_sum_transcripts, "chart_data"=>$top_tax_genus, "chart_div_id"=>"genus_top_tax_piechart", "legend_tax_str"=>"genera"));
                            ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div id="barcharts" class="tab-pane"><br>
<!--            <p class="text-justify">Generated using Highcharts.</p>-->
            <div class="row" style="margin-bottom:6px;">
                <ul class="nav nav-pills small-nav pull-right" id="nav-pills-barchart" data-tabs="pills">
                    <li><strong>View taxonomic rank: </strong></li>
                    <li class="active"><a data-toggle="pill" href="#phylum-top-tax-bar-tab" data-toggle="tab">Phylum</a></li>
                    <li><a data-toggle="pill" href="#order-top-tax-bar-tab" data-toggle="tab">Order</a></li>
                    <li><a data-toggle="pill" href="#genus-top-tax-bar-tab" data-toggle="tab">Genus</a></li>
                </ul>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <?php echo
                        $this->element('charts/bar_domain', array("chart_title"=>"Domain composition", "chart_subtitle"=>"Total transcripts: ".$domain_sum_transcripts, "chart_data"=>$top_tax_domain, "chart_div_id"=>"top_tax_barchart"));
                    ?>
                </div>
                <div class="col-md-8">
                    <div class="tab-content" id="top-tax-barcharts">
                        <div id="phylum-top-tax-bar-tab" class="tab-pane active">
                        <?php echo
                            $this->element('charts/bar_tax', array("chart_title"=>"Phylum composition", "chart_subtitle"=>"Total transcripts classified at that level: ".$phylum_sum_transcripts, "chart_data"=>$top_tax_phylum, "chart_div_id"=>"phylum_top_tax_barchart"));
                        ?>
                        </div>
                        <div id="order-top-tax-bar-tab" class="tab-pane">
                            <?php echo
                            $this->element('charts/bar_tax', array("chart_title"=>"Order composition", "chart_subtitle"=>"Total transcripts classified at that level: ".$order_sum_transcripts, "chart_data"=>$top_tax_order, "chart_div_id"=>"order_top_tax_barchart"));
                            ?>
                        </div>
                        <div id="genus-top-tax-bar-tab" class="tab-pane">
                            <?php echo
                            $this->element('charts/bar_tax', array("chart_title"=>"Genus composition", "chart_subtitle"=>"Total transcripts classified at that level: ".$genus_sum_transcripts, "chart_data"=>$top_tax_genus, "chart_div_id"=>"genus_top_tax_barchart"));
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!--<div id="raw-data" class="tab-pane"><br>
        <pre></pre>
        </div>-->
    </div>
</div>

<div id="tax-add-box" class="hidden"><strong>Click to select taxa</strong> (<kbd>CTRL</kbd> key is down)</div>


<script type="text/javascript">
    // Get proper width for tree viewer
    // TODO: on window resize event, wait a bit and resize chart.
    function viz_width(){
        return(window.innerWidth - 390);
    }
    console.log(viz_width().toString());
    $(function () {
        d3.json("<?php echo $treeview_json_url; ?>", function (error, data) {
            if (error) return console.warn(error);
            $("#d3-tree-view").treeview(data, {
//                width: 1120,
                width: viz_width(),
                height: 650,
                getTooltip: function (d) {
                    let numberFormat = d3.format(",d");
                    return "<b>" + d.name + "</b> (" + d.data.rank + ")<br/>" + numberFormat(!d.data.self_count ? "0" : d.data.self_count) + (d.data.self_count && d.data.self_count === 1 ? " sequence" : " sequences") +
                        " specific to this level<br/>" + numberFormat(!d.data.count ? "0" : d.data.count) + (d.data.count && d.data.count === 1 ? " sequence" : " sequences") + " specific to this level or lower";
                }
            });
        });
    });



    /////
    // Tax selection code
    /////

    var ctrlIsPressed = false;
    var create_subset_btn = "#create-subset";
    var create_subset_form = "#create-subset-form";
    var display_tax_element = "#selected-tax";
    var display_div_id = "#create-subset-result";
    var loading_div_id = "#loading";
    var reset_tax_link = "#reset-tax-list";
    var subset_name_input = "#subset-name";
    var tax_add_element = "#tax-add-box";
    var tax_list = [];

    // Handling CTRL pressed/released
    $(document).keydown(function(event){
        if(event.which == "17") {
            ctrlIsPressed = true;
            $(tax_add_element).toggleClass("hidden", !ctrlIsPressed);
            console.log("CTRL is pressed");
        }
    });

    $(document).keyup(function(event){
        if(event.which == "17") {
            ctrlIsPressed = false;
            $(tax_add_element).toggleClass("hidden", !ctrlIsPressed);
            console.log("CTRL is released");
        }
    });

    // Deal with Krona too (it is within an `object` tag!!)
    var krona_object = document.getElementById("krona-object");
    krona_object.onload = function() {
     $(krona_object.contentWindow.document).keydown(function(event){
            if(event.which == "17") {
                ctrlIsPressed = true;
                $(tax_add_element).toggleClass("hidden", !ctrlIsPressed);
                console.log("CTRL is pressed (within Krona)");
            }
        });
     $(krona_object.contentWindow.document).keyup(function(event){
         if(event.which == "17") {
             ctrlIsPressed = false;
             $(tax_add_element).toggleClass("hidden", !ctrlIsPressed);
             console.log("CTRL is released (within Krona)");
         }
     });

    // Handle ctrl+click interaction within Krona chart
    $(krona_object.contentWindow.document).click(function(){
        if(ctrlIsPressed) {
            var selected_tax = krona_object.contentWindow.focusNode.name;
            console.log(selected_tax);
            update_tax_list(tax_list, selected_tax);
        }
    });

    };

    // Handle ctrl+click on TreeViewer. Note: highcharts interaction (bar/pie) is defined within the charts.
    // Tree view, get selected node on CTRL + click
    $("#d3-tree-view").click(function(){
        if(ctrlIsPressed) {
            // Get tooltip content should be equivalent to getting name of node (workaround to get things working in time for the workshop).
            // Hopefully nobody reads this.
            var selected_tax = $("#d3-tree-view-tooltip").text().split(' (')[0];
            console.log(selected_tax);
            update_tax_list(tax_list, selected_tax);
        }
    });


    // Update tax list (called on user clade selection)
    function update_tax_list(tax_list, tax_name) {
        var max_tax = 20;
        var forbidden_names = ["all", "Other", "root", " "];
        if(tax_list.length >= max_tax){
            alert("Too many taxa, cannot add more!");
        }
        if(!forbidden_names.includes(tax_name) && !tax_list.includes(tax_name) && tax_list.length < max_tax) {
            tax_list.push(tax_name);
            // Update display
            append_to_tax_display(tax_list, tax_name);
        }
        console.log(tax_list);
    }

    // Reset tax list
    function reset_tax_list(tax_list) {
        console.log("RESET!");
        return []
    }

    // On click of reset link, reset the tax list and display `None` again
    $(reset_tax_link).click(function(){
        tax_list = reset_tax_list(tax_list);
       $(display_tax_element).text("None");
       update_form_check(tax_list, subset_name_input, create_subset_btn);
    });

    // Append a tax name to the displayed list
    function append_to_tax_display(tax_list, tax_name) {
        if(tax_list.length === 1) {
            $(display_tax_element).text(tax_name);
        }
        else {
            $(display_tax_element).append(", " + tax_name);
        }
        update_form_check(tax_list, subset_name_input, create_subset_btn);
    }


    // Check if there are tax in `tax_list` and if there currently is a subset name defined.
    // If both of these conditions are satisfied, the submission button is enabled.
    function update_form_check(tax_list, subset_text_input, form_sub_btn) {
        if(tax_list.length == 0 || $(subset_text_input).val() == '') {
            $(form_sub_btn).attr("disabled", true);
        }
        else {
            $(form_sub_btn).attr("disabled", false);
        }
    }

    // Bind the update form check function to change event for the input text.
    $(subset_name_input).on('input', function(){
        update_form_check(tax_list, subset_name_input, create_subset_btn);
    });

    // Quickfix, disable submission button on lead (if it was enabled before and a user reloads the page, it should be disabled).
    // Needed or not?
    $(function() {
        update_form_check(tax_list, subset_name_input, create_subset_btn);
        reset_tax_list(tax_list);
    });


    // Subset creation form submission
    $(create_subset_form).submit(function (e) {
        console.log("Subset creation form was submitted! ");
        $(loading_div_id).css("display", "block");
        $(create_subset_btn).attr("disabled", true);
        $(display_div_id).empty();
        // Unclean?
        var submission_data = $(this).serialize() + "&tax-list=" +  JSON.stringify(tax_list);
        console.log($(this).serialize());
        console.log(JSON.stringify(tax_list));
        console.log(submission_data);
        e.preventDefault();
        $.ajax({
            url: "<?php echo $this->Html->url(array("controller" => "tools", "action" => "create_tax_subset", $exp_id), array("escape" => false)); ?>",
            type: 'POST',
            data: submission_data,
            dataType: 'html',
            success: function (data) {
                $(loading_div_id).css("display", "none");
                $(create_subset_btn).attr("disabled", false);
                $(display_div_id).hide().html(data).fadeIn();
            },
            error: function () {
                alert('Unable to submit label creation. If this problems persists, contact us.');
            }
        });
    });
</script>
<!-- Enable bootstrap tooltips -->
<?php echo $this->element("help_tooltips/enable_tooltips",  array("container"=>"#create-subset-form")); ?>