<div class="container">
    <div class="page-header">
        <h1 class="text-primary">Experiments overview</h1>
    </div>
    <p class="text-justify">Current experiments for
        <strong><?php echo $user_email['Authentication']['email'];?></strong>:
    </p>
    <table class="table table-hover table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>#Transcripts</th>
                <th>Status</th>
                <th>Last edit</th>
                <th>Reference database</th>
                <th>Log</th>
                <th>Jobs</th>
                <th>Reset</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
        <?php if (count($experiments) == 0) {
            echo "<p class='text-justify lead'>No experiments... Create one?</p>";
        } else {
            foreach ($experiments as $experiment) {
                $e = $experiment['Experiments'];
                echo "<tr>";
                if ($e['process_state'] == 'error') {
                    echo "<td>" . $e['title'] . "</td>";
                    echo "<td><span id='exp_count_" . $e['experiment_id'] . "'>" . $experiment['count'] . "</span></td>";
                    echo "<td>" . $this->Html->link($e['process_state'], ["controller" => "trapid", "action" => "change_status", $e['experiment_id']], ["class" => "text-danger"]) . "</td>";
                    echo "<td>" . $e['last_edit_date'] . "</td>";
                    echo $experiment['DataSources']['URL'] ?
                        "<td>" . $this->Html->link($experiment['DataSources']['name'], $experiment['DataSources']['URL']) . "</td>" :
                        "<td>" . $experiment['DataSources']['name'] . "</td>";
                    echo "<td>" . $this->Html->link("View log", ["controller" => "trapid", "action" => "view_log", $e['experiment_id']]) . "</td>\n";
                    echo count($experiment['experiment_jobs']) > 0 ?
                        "<td>" . $this->Html->link(count($experiment['experiment_jobs']) . " jobs", ["controller" => "trapid","action" => "manage_jobs", $e['experiment_id']]) . "</td>" :
                        "<td class='text-muted'>NA</td>";
                    echo "<td class='text-center text-muted'> - </td>";
                    echo "<td class='text-center text-muted'> - </td>";
                } else if ($e['process_state'] == "loading_db") {
                    echo "<td>" . $e['title'] . "</td>";
                    echo "<td><span class='exp_count' id='exp_count_" . $e['experiment_id'] . "'>" . $experiment['count'] . "</span></td>";
                    echo "<td>" . $this->Html->link($e['process_state'], ["controller" => "trapid", "action" => "change_status", $e['experiment_id']]) . "</td>";
                    echo "<td>" . $e['last_edit_date'] . "</td>";
                    echo $experiment['DataSources']['URL'] ?
                        "<td>" . $this->Html->link($experiment['DataSources']['name'], $experiment['DataSources']['URL']) . "</td>" :
                        "<td>" . $experiment['DataSources']['name'] . "</td>";
                    echo "<td>" . $this->Html->link("View log", ["controller" => "trapid", "action" => "view_log", $e['experiment_id']]) . "</td>\n";
                    echo count($experiment['experiment_jobs']) > 0 ?
                        "<td>" . $this->Html->link(count($experiment['experiment_jobs']) . " jobs", ["controller" => "trapid","action" => "manage_jobs", $e['experiment_id']]) . "</td>" :
                        "<td class='text-muted'>NA</td>";
                    echo "<td class='text-center text-muted'> - </td>";
                    echo "<td class='text-center text-muted'> - </td>";
                } else if ($e['process_state'] == "processing") {
                    echo "<td>" . $e['title'] . "</td>";
                    echo "<td><span id='exp_count_" . $e['experiment_id'] . "'>" . $experiment['count'] . "</span></td>";
                    echo "<td>" . $this->Html->link($e['process_state'], ["controller" => "trapid", "action" => "change_status", $e['experiment_id']]) . "</td>";
                    echo "<td>" . $e['last_edit_date'] . "</td>";
                    echo $experiment['DataSources']['URL'] ?
                        "<td>" . $this->Html->link($experiment['DataSources']['name'], $experiment['DataSources']['URL']) . "</td>" :
                        "<td>" . $experiment['DataSources']['name'] . "</td>";
                    echo "<td>" . $this->Html->link("View log", ["controller" => "trapid", "action" => "view_log", $e['experiment_id']]) . "</td>\n";
                    echo count($experiment['experiment_jobs']) > 0 ?
                        "<td>" . $this->Html->link(count($experiment['experiment_jobs']) . " jobs", ["controller" => "trapid","action" => "manage_jobs", $e['experiment_id']]) . "</td>" :
                        "<td class='text-muted'>NA</td>";
                    echo "<td class='text-center text-muted'> - </td>";
                    echo "<td class='text-center text-muted'> - </td>";
                } else if ($e['process_state'] == "deleting") {
                    echo "<td class='text-muted'>" . $e['title'] . "</td>";
                    echo "<td class='text-muted'><span id='exp_count_" . $e['experiment_id'] . "'>" . $experiment['count'] . "</span></td>";
                    echo "<td class='text-muted'>" . $e['process_state'] . "</td>";
                    echo "<td class='text-muted'>" . $e['last_edit_date'] . "</td>";
                    echo "<td class='text-muted'>" . $experiment['DataSources']['name'] . "</td>";
                        echo "<td class='text-center text-muted'> - </td>";
                        echo count($experiment['experiment_jobs']) > 0 ?
                        "<td>" . $this->Html->link(count($experiment['experiment_jobs']) . " jobs", ["controller" => "trapid","action" => "manage_jobs", $e['experiment_id']]) . "</td>" :
                        "<td class='text-muted'>NA</td>";
                    echo "<td class='text-center text-muted'> - </td>";
                    echo "<td class='text-center text-muted'> - </td>";
                }
                else {
                    echo "<td>" . $this->Html->link($e['title'], ["action"=>"experiment",$e['experiment_id']]) . "</td>";
                    echo "<td><span id='exp_count_" . $e['experiment_id'] . "'>" . $experiment['count'] . "</span></td>";
                    echo "<td>" . $e['process_state'] . "</td>";
                    echo "<td>" . $e['last_edit_date'] . "</td>";
                    echo $experiment['DataSources']['URL'] ?
                        "<td>" . $this->Html->link($experiment['DataSources']['name'], $experiment['DataSources']['URL']) . "</td>" :
                        "<td>" . $experiment['DataSources']['name'] . "</td>";
                    echo "<td>" . $this->Html->link("View log", ["controller" => "trapid", "action" => "view_log", $e['experiment_id']]) . "</td>\n";
                    echo count($experiment['experiment_jobs']) > 0 ?
                        "<td>" . $this->Html->link(count($experiment['experiment_jobs']) . " jobs", ["controller" => "trapid","action" => "manage_jobs", $e['experiment_id']]) . "</td>" :
                        "<td class='text-muted'>NA</td>";
                    echo "<td class='text-center'>" .
                        $this->Html->link("<span class='material-icons text-info'>replay</span>",
                                          ["controller" => "trapid", "action" => "empty_experiment", $e['experiment_id']],
                                          ["escape" => false, "title" => "Reset (empty) experiment"],
                                          "Are you sure you want to reset this experiment? All its content will be deleted.") . "</td>";
                    // Note: to use Bootstrap's glyphicon: `<span class='glyphicon glyphicon-remove text-danger'></span>`
                    echo "<td class='text-center'>" .
                        $this->Html->link("<span class='material-icons text-danger'>delete</span>",
                                          ["controller" => "trapid", "action" => "delete_experiment", $e['experiment_id']],
                                          ["escape" => false, "title" => "Delete experiment"],
                                          "Are you sure you want to delete this experiment?") . "</td>";
                }
                echo "</tr>\n";
            }
        }
        ?>
        </tbody>
    </table>

    <?php if (count($experiments) < $max_user_experiments): ?>
        <p class="text-right">
            <button data-toggle="modal" data-target="#new-exp-modal" class="btn btn-primary btn-lg" name="" id="">
                <span class="glyphicon glyphicon-plus"> </span> Add new experiment
            </button>
        </p>
        <div class="modal fade" id="new-exp-modal" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true">×</span><span class="sr-only">Close</span>
                        </button>
                        <h3 class="modal-title" id="lineModalLabel">New experiment</h3>
                    </div>
                    <div class="modal-body">
                        <?php
                            if (isset($error)){
                                echo "<span class='error'>" . $error . "</span><br/>\n";
                            }
                            echo $this->Form->create("Experiments", [
                                "url" => ["controller" => "trapid", "action" => "experiments"],
                                "type" => "post"
                            ]);
                        ?>
                        <div class="form-group">
                            <label for=""><strong>Name</strong> (max. 30 characters)</label>
                            <input type="text" maxlength="30" class="form-control" id="experiment_name" name="experiment_name" placeholder="My experiment" required>
                        </div>
                        <div class="form-group">
                            <label for="experiment_description" class="optional"><strong>Description</strong></label>
                                            <textarea rows="4" name="experiment_description" id="experiment_description" class="form-control" placeholder="Experiment description... "></textarea>
                        </div>
                        <div class="form-group">
                            <label for=""><strong>Reference database</strong></label>
                                            <select class="form-control" name="data_source">
                                            <?php
                                            foreach($available_sources as $av){
                                                echo "<option value='" . $av['DataSources']['db_name'] . "'>" . $av['DataSources']['name'] . "</option>\n";
                                            }
                                            ?>
                                            </select>
                            <p class="help-block" style="font-size: 88%;"><strong>Note:</strong> Protein domain annotations are only available for the PLAZA reference databases, and KO annotations for EggNOG 4.5.</p>
                        </div>
                                        <p class="text-center">
                        <button type="submit" class="btn btn-primary">Create experiment</button></p>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <p class="text-right">
            <span class="text-danger">Maximum number of experiments reached, cannot create more experiments!</span>
        </p>
    <?php endif;?>

    <?php if (count($shared_experiments) > 0): ?>
        <br>
        <p class="text-justify">Experiments shared with
            <strong><?php echo $user_email['Authentication']['email']; ?></strong>:
        </p>
        <table class="table table-hover table-striped" id="experiments-table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Owner</th>
                <th>Reference database</th>
                <th>Log</th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($shared_experiments as $experiment) {
                $e = $experiment['Experiments'];
                $owner_email = $all_user_ids[$e['user_id']];
                echo "<tr>";
                echo "<td>" . $this->Html->link($e['title'], ["controller" => "trapid", "action" => "experiment", $e['experiment_id']]) . "</td>";
                echo "<td><a href='mailto:" . $owner_email . "'>" . $owner_email . "</a></td>";
                echo $experiment['DataSources']['URL'] ?
                    "<td>" . $this->Html->link($experiment['DataSources']['name'], $experiment['DataSources']['URL']) . "</td>" :
                    "<td>" . $experiment['DataSources']['name'] . "</td>";
                echo "<td>" . $this->Html->link("View log", ["controller" => "trapid", "action" => "view_log", $e['experiment_id']]) . "</td>\n";
                echo "</tr>\n";
            }
            ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script type='text/javascript' defer="defer">
    var experiments = <?php echo json_encode($experiments); ?>;
    function get_exp_num_trancripts(exp_id) {
        var span_id = "#exp_count_" + exp_id;
        var ajax_url = <?php echo "\"" . $this->Html->url(["controller" => "trapid", "action" => "experiments_num_transcripts"]) . "\""; ?> + "/" + exp_id + "/";
        $.ajax({
            type: "GET",
            url: ajax_url,
            contentType: "application/json;charset=UTF-8",
            success: function(data) {
                $(span_id).html(data);
            },
            error: function() {
                console.log("Unable to retrieve transcripts count for experiment \'" + exp_id + "\'. ");
            }
        });
    }

    for(var i=0;i<experiments.length;i++) {
        var experiment_id = experiments[i]["Experiments"]["experiment_id"];
        get_exp_num_trancripts(experiment_id);
    }

    // Reload transcript count of experiments in `loading_db` state every x milliseconds
    // TODO: although this is working, execution is not perfect (page is reloaded after 2 loops, not 1)
    $(document).ready(function() {
        // Check if there are any experiments loading data
        var loading_exps = document.querySelectorAll(".loading_state");
        var exps_trs = {};
        var timeout_ms = 5000;
        // Reload data after `timeout_ms` milliseconds
        function reload_transcript_count() {
            // Get loading experiments and their IDs
            loading_exps = document.querySelectorAll(".loading_state");
            // If none were found, stop running the function
            if(loading_exps.length === 0){
                clearInterval(func_loop);
            }
            var loading_exps_span = document.querySelectorAll(".loading_state [id^=\"exp_count\"]");
            for(var i=0 ; i<loading_exps_span.length ; i++) {
                var span_id = loading_exps_span[i].id;
                var exp_id = span_id.split("_").slice(-1)[0];
                var exp_trs = exps_trs[exp_id];
                get_exp_num_trancripts(parseInt(exp_id));
                exps_trs[exp_id] = document.getElementById(span_id).textContent;
                // Check if transcript count matches the updated count (i.e. no change for `timeout_ms`)
                if((exp_trs === exps_trs[exp_id]) && (exps_trs[exp_id] !== "NA")) {
                    // Check if the user is doing something (new experiment creation modal is open)
                    // If it is not the case, refresh the page: at least one experiment (probably) finished loading
                    var exp_modal = document.getElementById("new-exp-modal");
                    if(!exp_modal.classList.contains("in")) {
                        location.reload(true);
                    }
                }
            }
        }
        var func_loop = setInterval(reload_transcript_count, timeout_ms);
    });
</script>
