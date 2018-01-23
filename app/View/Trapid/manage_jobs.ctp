<div class="page-header">
    <h1 class="text-primary">Manage TRAPID jobs</h1>
</div>
<div class="subdiv">
    <?php // echo $this->element("trapid_experiment"); ?>

<!--    <h3>Job control</h3>-->
<!--    <div class="subdiv">-->

        <?php
        //pr($running_jobs);
        if (count($running_jobs) == 0) {
            echo "<p class='lead'>No jobs are currently running for this experiment. </p>\n";
        } else {
            echo $this->Form->create("Experiments", array("url" => array("controller" => "trapid", "action" => "manage_jobs", $exp_id),
                "type" => "post"));
            $found_done = false;
            echo "<table cellpadding='0' cellspacing='0' style='width:800px;' class='table table-striped table-condensed table-bordered table-hover'>\n";
            echo "<thead>";
            echo "<tr>";
            echo "<th style='width:15%'>Job id</th>";
            echo "<th style='width:22%'>Date</th>";
            echo "<th style='width:20%'>Status</th>";
            echo "<th style='width:30%'>Description</th>";
            echo "<th style='width:10%'>Select</th>";
            echo "</tr>";
            echo "</thead>\n";
            echo "<tbody>\n";
            $counter = 0;
            foreach ($running_jobs as $job) {
                $altrow = null;
                if ($counter++ % 2 == 0) {
                    $altrow = " class='altrow' ";
                }
                echo "<tr $altrow>";
                echo "<td>" . $job['job_id'] . "</td>";
                echo "<td>" . $job['start_date'] . "</td>";
                echo "<td>" . $job['status'] . "</td>";
                echo "<td>" . $job['comment'] . "</td>";
                $checked = null;
                if ($job['status'] == "done") {
                    $checked = " checked='checked' ";
                    $found_done = true;
                }
                echo "<td class='text-center'><input type='checkbox' name='job_" . $job['job_id'] . "' $checked /></td>";
                echo "</tr>\n";
            }
            echo "</tbody>\n";
            echo "</table>\n";
            if ($found_done) {
                echo "<p class='text-justify'><strong>If a job is in status <em>'done'</em> this job can deleted without repercussions. <br/>This is most likely a remnant of a job which was not cleaned up properly. </strong></p>\n";
            }
            echo "<input type='submit' value='Delete selected jobs' class='btn btn-default'/>\n";
            echo "</form>\n";
        }

        ?>
<!--    </div>-->
</div>
