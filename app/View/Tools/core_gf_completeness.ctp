<div class="page-header">
    <h1 class="text-primary">Core GF completeness</h1>
</div>
<p class="text-justify">Core GFs are a set of gene families that you expect to find at a given taxonomic level. They are
    defined as gene families that are present in at least a certain percentage of species, at a given taxonomic level.
    Give more explanations here.
    For more details, please have a look at
    the <?php echo $this->Html->link("documentation", array("controller" => "documentation", "action" => "index")); ?>.
</p>
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
            <?php echo $this->Form->create(false, array("action" => "core_gf_completeness/" . $exp_id, "type" => "post")); ?>

            <div class="row">
                <div class="col-lg-4">
                    <!-- Data -->
                    <div class="panel panel-default">
                        <div class="panel-heading"><h3 class="panel-title">Data</h3></div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="transcripts-choice"><strong>Transcript selection</strong></label>
                                        <select id="transcripts-choice" name="transcripts-choice" class="form-control">
                                            <option value="all" selected="selected">All transcripts</option>
                                            <?php foreach ($subsets as $subset_name => $n_transcripts)
                                                // Add select option for each subset
                                                echo "<option value=\"" . $subset_name . "\">" . $subset_name . " (" . $n_transcripts . " transcripts)</option>";
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
<!--                                        <label for="clade"><strong>Phylogenetic clade</strong>-->
<!--                                            (<code>tax_id</code>)</label>-->
                                        <label for="clade"><strong>Phylogenetic clade</strong></label>
                                        <input class="form-control" id="clade" name="clade" placeholder="Clade tax ID"
                                               type="text" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <!-- Settings -->
                    <div class="panel panel-default">
                        <div class="panel-heading"><h3 class="panel-title">Settings</h3></div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="species-perc"><strong>Conservation threshold</strong></label>
                                        <input class="form-control" id="species-perc" max="1" min="0.5"
                                               name="species-perc" step="0.01" value="0.9" required type="number">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="top-hits"><strong>Top hits</strong></label>
                                        <input class="form-control" id="top-hits" max="10" min="1" name="top-hits"
                                               step="0.01" value="5" required type="number">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="tax-radio"><strong>Taxonomy source</strong></label>
                                        <div class="radio" id="tax-radio" name="tax-radio">
                                            <label><input type="radio" name="tax-radio-ncbi" checked>NCBI</label>
                                            <label><input type="radio" name="tax-radio-db" disabled>PLAZA config.
                                            </label>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>

            </div> <!-- end .row -->
            <!-- Form submission -->
            <p class="text-center">
                <input type="submit" class="btn btn-primary" value="Run analysis"/>
                | <a style="cursor: pointer;" onclick="alert('Reset not implemented yet');">Reset all</a>

            </p>
            <?php echo $this->Form->end(); ?>


        </div>


        <?php if (!empty($previous_completeness_jobs)): ?>
            <div id="previous-jobs" class="tab-pane"><br>
                <p class="text-justify">Here a table summarizing all the core GF completeness analyses performed for
                    that experiment. The plan is to add links to visualize the results of previous jobs. </p>
                <table class="table table-responsive table-striped table-hover table-bordered">
                    <thead>
                    <th>Phylogenetic clade</th>
                    <th>Transcript subset</th>
                    <th>Used taxonomy</th>
                    <th>Conservation threshold</th>
                    <th>Top hits</th>
                    <th>Completeness score</th>
                    <th>Link</th>
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
                        echo "<td>" . $tax_source ."</td>";
                        echo "<td>" . $species_perc ."</td>";
                        echo "<td>" . $top_hits ."</td>";
                        echo "<td>" . $completeness_job['CompletenessResults']['completeness_score'] . "</td>";
                        echo "<td><a class=\"result_link\" id=\"" . "results_".implode("_", array($completeness_job['CompletenessResults']['clade_txid'], $completeness_job['CompletenessResults']['label'], $tax_source, $species_perc, $top_hits)) . "\">View results</a></td>";
                        echo "</tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </div>
    <br>
    <div id="display-results" style="border: 1px red solid;"></div>
</div>

<script type="text/javascript">
    // Load completeness data when user clicks on a result link
    $(function() {
        // Text areas id, as JS vars
        var display_div_id = "#display-results";
        $(".result_link").click(function() {
            var param_list = $(this).attr("id").split("_").slice(1);
            var ajax_url =  "<?php echo $this->Html->url(array("controller"=>"tools","action"=>"load_core_gf_completeness")) . "/" . $exp_id . "/"; ?>" + param_list.join('/');
            // Load data!
            $.ajax({
                type: "GET",
                url: ajax_url,
                contentType: "application/json;charset=UTF-8",
                success: function(data) {
                    // alert("Success! ");
                    $(display_div_id).hide().html(data).fadeIn();
                    document.querySelector(display_div_id).scrollIntoView({
                        behavior: 'smooth'
                    });
                },
                error: function() {
                    // alert("Failure - Unable to retrieve transcripts count. ");
                    console.log("Failure - Unable to retrieve data for this core GF completeness analysis. ");
                },
                complete: function() {
                    // Debug
                    // console.log(display_div_id);
                    // console.log(param_list);
                    // console.log(ajax_url);
                }
            });
        });
    });
</script>
