<?php
// TODO: host locally
// Highcharts
echo $this->Html->script('https://code.highcharts.com/highcharts.js');
echo $this->Html->script('https://code.highcharts.com/modules/exporting.js');
// Selectize JS + CSS
echo $this->Html->script('selectize.min.js');
echo $this->Html->css('selectize.paper.css');
// jQuery dataTable buttons
echo $this->Html->script('https://cdn.datatables.net/buttons/1.5.6/js/dataTables.buttons.min.js');
echo $this->Html->script('https://cdn.datatables.net/buttons/1.5.6/js/buttons.html5.min.js');
?>

<style>
    /*.choices__inner {*/
    /*display: inline-block;*/
    /*vertical-align: top;*/
    /*width: 100%;*/
    /*background-color: #f9f9f9;*/
    /*padding: 3.75px 7.5px 3.75px;*/
    /*}*/
    /*.choices * {*/
    /*font-size: 13px;*/
    /*}*/
    /*.choices__list * {*/
    /*font-size: 13px;*/
    /*}*/
    /*.choices__list--dropdown .choices__item--selectable {*/
    /*padding-right: 52px;*/
    /*}*/

    /*
        #core-gf-settings {
            min-height: 151px;
        }
    */
    .dt-button {
        margin-left: 15px;
    }

    .gf-table tr td:first-child:hover .gf-tooltip {
        display: block;
    }

    .gf-tooltip {
        font-size: 82%;
        display: none;
        background: rgb(255,255,255,0.9);
        border: 1px #cccccc solid;
        margin-left: 28px;
        padding: 8px;
        position: absolute;
        z-index: 1000;
        max-width:400px;
        /*height:200px;*/
    }

    .selectize-control.single .selectize-input::after {
        right: 5px;
    }
</style>

<div class="page-header">
    <h1 class="text-primary">Core GF completeness</h1>
</div>
<p class="text-justify">Core gene families (core GFs) are defined as a set of gene families that we expect to be represented in the species of a given taxonomic level.
    They correspond to gene families that are present in at least a certain percentage of species (referred as the conservation threshold), at a given taxonomic level. </p>
<p class="text-justify">The core GF completeness score provides a quick measurement of how complete the gene space is, at a certain taxonomic level. Exploring the list of missing and represented core GFs can also give an idea of the function of missing/represented core GFs. </p>
<p class="text-justify">For more details and explanations about the methods, please have a look at the <?php echo $this->Html->link("documentation", array("controller" => "documentation", "action" => "index")); ?>.</p>
<br>

<div id="content">
    <ul class="nav nav-tabs nav-justified" id="tabs" data-tabs="tabs">
        <li class="active"><a href="#new-job" data-toggle="tab">New analysis</a></li>
        <?php if (empty($previous_completeness_jobs)): ?>
            <li class="disabled"><a href="#previous-jobs">Previous analyses</a></li>
        <?php else: ?>
            <li><a href="#previous-jobs" data-toggle="tab">Previous analyses</a></li>
        <?php endif ?>
    </ul>

    <div class="tab-content"> <!-- style="border: 1px lightgray solid;"> -->
        <div id="new-job" class="tab-pane active"><br>
            <?php if(isset($error)){
                echo "<div class=\"alert alert-warning alert-dismissible\" role=\"alert\"><strong>Watch out! </strong>";
                echo "<a href=\"#\" class=\"close\" data-dismiss=\"alert\" aria-label=\"close\">&times;</a>";
                echo $error;
                echo "</div>";
            }
            ?>
            <?php
            // echo $this->Form->create(false, array("action" => "core_gf_completeness/" . $exp_id, "type" => "post", "default"=>"false", "id"=>"completeness-form"));
            echo $this->Form->create(false, array("url"=>array("controller"=>"tools", "action"=>"core_gf_completeness", $exp_id), "type" => "post", "default"=>"false", "id"=>"completeness-form"));
            ?>

            <div class="row">
                <!-- Data -->
                <div class="col-lg-6">
                    <div class="panel panel-default">
                        <div class="panel-heading"><h3 class="panel-title">Data</h3></div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="transcripts-choice"><strong>Transcript selection</strong></label>
                                        <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['core_gf_transcripts'], "tooltip_placement"=>"top")); ?>
                                        <select id="transcripts-choice" name="transcripts-choice" class="form-control">
                                            <option value="all" selected="selected">All transcripts</option>
                                            <?php foreach ($subsets as $subset_name => $n_transcripts)
                                                // Add select option for each subset
                                                echo "<option value=\"" . $subset_name . "\">" . $subset_name . " (" . $n_transcripts . " transcripts)</option>";
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="form-group">
                                        <label for="clade"><strong>Phylogenetic clade</strong></label>
                                        <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['core_gf_clade'], "tooltip_placement"=>"top", "use_html"=>true)); ?>
                                        <!--                                        <select class="form-control" id="clade" name="clade" placeholder="Clade selection" required>-->
                                        <!--                                            <option placeholder>Select clade</option>-->
                                        <select class="form-control" id="clade" name="clade" required>
                                            <option value="">Select a clade...</option>
                                            <?php foreach($core_gf_clades as $tax_id=>$tax_name) {
                                                echo "<option value=\"" . $tax_id . "\">" . $tax_name . " [" . $tax_id . "]</option>";
                                            }
                                            ?>
                                        </select>
                                        <!--                                        <input class="form-control" id="clade" name="clade" placeholder="Clade tax ID"-->
                                        <!--                                               type="text" required>-->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Settings -->
                <div class="col-lg-6">
                    <div class="panel panel-default" id="core-gf-settings">
                        <div class="panel-heading"><h3 class="panel-title">Settings</h3></div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="species-perc"><strong>Conservation %</strong></label>
                                        <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['core_gf_species_perc'], "tooltip_placement"=>"top")); ?>
                                        <input class="form-control" id="species-perc" max="1" min="0.5"
                                               name="species-perc" step="0.01" value="0.9" required type="number">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="top-hits"><strong>Top hits</strong></label>
                                        <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['core_gf_top_hits'], "tooltip_placement"=>"top")); ?>
                                        <input class="form-control" id="top-hits" max="10" min="1" name="top-hits"
                                               step="1" value="1" required type="number">
                                    </div>
                                </div>
                                <!--                                <div class="col-md-4">-->
                                <!--                                    <div class="form-group">-->
                                <!--                                        <label for="tax-radio"><strong>Taxonomy source</strong></label>-->
                                <!--                                        --><?php //echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['core_gf_tax_source'], "tooltip_placement"=>"top")); ?>
                                <!--                                        <div class="radio" id="tax-radio" name="tax-radio">-->
                                <!--                                            <label><input type="radio" name="tax-radio-ncbi" checked>NCBI</label>-->
                                <!--                                            <label class="unavailable"><input type="radio" name="tax-radio-db" disabled>PLAZA</label>-->
                                <!--                                        </div>-->
                                <!--                                    </div>-->
                                <!--                                </div>-->

                            </div>
                        </div>
                    </div>

                </div>

            </div> <!-- end .row -->
            <!-- Form submission -->
            <p class="text-center">
                <input type="submit" class="btn btn-primary" id="completeness-submit" value="Run analysis"/>
                | <a id="completeness-reset" style="cursor: pointer;">Reset all</a>

            </p>
            <?php echo $this->Form->end(); ?>


        </div>


        <?php if (!empty($previous_completeness_jobs)): ?>
            <div id="previous-jobs" class="tab-pane"><br>
                <p class="text-justify">This table summarizes the core GF completeness analyses performed for
                    that experiment. </p>
                <table class="table table-responsive table-striped table-hover table-bordered">
                    <thead>
                    <th>Phylogenetic clade</th>
                    <th>Transcript subset</th>
                    <!--                    <th>Used taxonomy</th>-->
                    <th>Conservation threshold</th>
                    <th>Top hits</th>
                    <th>Completeness score</th>
                    <th>View</th>
                    <th>Delete</th>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($previous_completeness_jobs as $completeness_job) {
                        $exp_method_str = explode(';', $completeness_job['CompletenessResults']['used_method']);
                        $species_perc = explode("=", $exp_method_str[0]);
                        $species_perc = $species_perc[1];
                        $tax_source = explode("=", $exp_method_str[1]);
                        $tax_source = $tax_source[1];
                        $top_hits = explode("=", $exp_method_str[2]);
                        $top_hits = $top_hits[1];
                        echo "<tr>";
                        echo "<td>" . $completeness_job['CompletenessResults']['clade_name'] . " (" . $completeness_job['CompletenessResults']['clade_txid'] . ")</td>";
                        if ($completeness_job['CompletenessResults']['label'] == "None") {
                            echo "<td>All transcripts</td>";
                        } else {
                            echo "<td>" . $completeness_job['CompletenessResults']['label'] . "</td>";
                        }
//                        echo "<td>" . $tax_source ."</td>";
                        echo "<td>" . $species_perc ."</td>";
                        echo "<td>" . $top_hits ."</td>";
                        echo "<td>" . number_format((float)$completeness_job['CompletenessResults']['completeness_score'], 3, '.', ',') . "</td>";
                        echo "<td><a class=\"result_link\" id=\"" . "results_".implode("_", array($completeness_job['CompletenessResults']['clade_txid'], $completeness_job['CompletenessResults']['label'], $tax_source, $species_perc, $top_hits)) . "\">View results</a></td>";
                        echo "<td class='text-center'>".$this->Html->link("<span class='material-icons'>delete</span>",
                                array("controller"=>"tools","action"=>"delete_core_gf_results", $exp_id,
                                    $completeness_job['CompletenessResults']['clade_txid'],
                                    $completeness_job['CompletenessResults']['label'],
                                    $tax_source, $species_perc, $top_hits),
                                array("style"=>"color: #666;", "escape"=>false, "title"=>"Delete results"),
                                "Are you sure you want to delete these core GF completeness results? This action is permanent. ")."</td>";
                        echo "</tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </div>
    <br>
    <div id="loading" style="display:none;">
        <div class="text-center">
            <div class="ajax-spinner"></div><br>
            Loading... Please wait.
        </div>
    </div>
    <div id="display-results"></div>
</div>

<script type="text/javascript">
    // Load completeness data when user clicks on a result link ('previous jobs' table)
    $(function() {
        // Various elements id, as JS vars
        var display_div_id = "#display-results";
        var loading_div_id = "#loading";
        var sub_form_id = "#completeness-form";
        var sub_btn_id = "#completeness-submit";
        var reset_btn_id = "#completeness-reset";
        $(".result_link").click(function () {
            var param_list = $(this).attr("id").split("_").slice(1);
            var ajax_url = "<?php echo $this->Html->url(array("controller" => "tools", "action" => "load_core_gf_completeness")) . "/" . $exp_id . "/"; ?>" + param_list.join('/');
            // Load data!
            $.ajax({
                type: "GET",
                url: ajax_url,
                contentType: "application/json;charset=UTF-8",
                success: function (data) {
                    // alert("Success! ");
                    $(display_div_id).hide().html(data).fadeIn();
                    document.querySelector(display_div_id).scrollIntoView({
                        behavior: 'smooth'
                    });
                },
                error: function () {
                    // alert("Failure - Unable to retrieve transcripts count. ");
                    console.log("Failure - Unable to retrieve data for this core GF completeness analysis. ");
                },
                complete: function () {
                    // Debug
                    // console.log(display_div_id);
                    // console.log(param_list);
                    // console.log(ajax_url);
                }
            });
        });
        $(sub_form_id).submit(function (e) {
            console.log("Completeness job form was submitted! ");

            $(loading_div_id).css("display", "block");
            $(sub_btn_id).attr("disabled", true);
            $(display_div_id).empty();
            e.preventDefault();
            $.ajax({
                url: "<?php echo $this->Html->url(array("controller" => "tools", "action" => "core_gf_completeness", $exp_id), array("escape" => false)); ?>",
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'html',
                success: function (data) {
                    $(loading_div_id).css("display", "none");
                    $(sub_btn_id).attr("disabled", false);
                    $(display_div_id).fadeOut('slow', function () {
                        $(display_div_id).hide().html(data).fadeIn();
                        document.querySelector(display_div_id).scrollIntoView({behavior: 'smooth'});
                    });
                },
                error: function () {
                    alert('Unable to submit completeness job!');
                }
            });
        });

    });

    /* Quick test: choices-js for clade selection (with autocompletion).
     * Adapted/simplified from: https://github.com/jshjohnson/Choices/issues/162 */

    // Some configuration and variables for ajax calls
    /*
     var ajaxUrl = "<?php echo $this->Html->url(array("controller" => "tools", "action" => "search_tax")); ?>";
     var lookupDelay = 250;
     var lookupTimeout = null;
     var lookupCache = {};
     var choices_elmt = document.getElementById("clade");
     var config = {
     searchFloor: 3,
     searchChoices: false,
     shouldSort: false,
     itemSelectText: "Select",
     searchPlaceholderValue: "Search a taxon name... "
     // searchResultLimit: 100,
     };

     // Construct the Choices object
     var choices = new Choices(choices_elmt, config);

     var choices_elmt = document.getElementById("clade");
     var choices = new Choices(choices_elmt);
     */

    /*

     // Function to perform the lookup.
     var serverLookup = function () {
     // we can't use choices.currentValue because this is blank if we set searchChoices
     // to false. Instead we use the actual input field's value.
     var query = choices.input.value;
     if (query in lookupCache) {
     populateOptions(lookupCache[query]);
     } else {
     // console.log("Data retrieval...");
     $.get(ajaxUrl + "/" + query, function (data) {
     var results = JSON.parse(data);
     lookupCache[query] = results;
     populateOptions(results);
     });
     }
     };

     // Function to actually populate the results from the lookup.
     var populateOptions = function (options) {
     // console.log("Populating Choices element. ");
     // We removed the part about duplicate data removal from the original snippet because we should not have any
     var toKeep = [];
     for (var key in options) {
     toKeep.push({value: key, label: options[key] + " [" + key + "]"});
     }
     // console.log(toKeep);  // Debug
     // Now set results.
     choices.setChoices(toKeep, 'value', 'label', true);
     };

     // Trigger the lookup when the user pauses after typing.
     choices_elmt.addEventListener('search', function (event) {
     clearTimeout(lookupTimeout);
     lookupTimeout = setTimeout(serverLookup, lookupDelay);
     });

     // Clear the options when a choice is selected.
     choices_elmt.addEventListener('choice', function (event) {
     choices.setChoices([], 'value', 'label', true);
     });

     */


    // Replacement by selectize
    $("#clade").selectize();

    function reset_clade_selectize() {
        $("#clade")[0].selectize.setValue('');
    }

    $("#completeness-reset").on('click', function() {
        document.getElementById('completeness-form').reset();
        reset_clade_selectize();
    });



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

</script>
<?php echo $this->element("help_tooltips/enable_tooltips",  array("container"=>"#completeness-form")); ?>
