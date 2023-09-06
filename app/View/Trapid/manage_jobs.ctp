<div class="page-header">
    <h1 class="text-primary">Manage TRAPID jobs</h1>
</div>
<section id='cluster-info' class="page-section-sm">
    <?php 
    if (isset($cluster_status)) {
        ksort($cluster_status);
        $status_classes = ['ok' => 'text-success', 'busy' => 'text-warning', 'full' => 'text-danger'];
        echo "<ul class='list-inline'><li><strong>Current cluster status: </strong></li>";
        foreach ($cluster_status as $queue => $status) {
            echo '<li>';
            echo "<span title='Cluster $queue queue status: $status' class='$status_classes[$status]'>&#9679;</span> ";
            echo $queue;
            echo '&nbsp;&nbsp;</li>';
        }
        echo $this->element('help_tooltips/create_tooltip', [
            'tooltip_text' => $tooltip_text_cluster_status,
            'tooltip_placement' => 'right',
            'override_span_class' => 'glyphicon glyphicon-question-sign',
            'use_html' => true
        ]);
        echo '</ul>';
    } else {
        echo "<p class='text-danger'>Unable to retrieve cluster status</p>";
    }
    ?>
</section>
<section id='job-info' class="page-section-sm">
    <?php if (count($running_jobs) == 0) {
        echo "<p class='lead text-muted'>No jobs are currently running for this experiment. </p>\n";
    } else {
        echo $this->Form->create('Experiments', [
            'url' => ['controller' => 'trapid', 'action' => 'manage_jobs', $exp_id],
            'type' => 'post'
        ]);
        $found_done = false;
        echo "<table class='table table-striped table-condensed table-hover'>\n";
        echo '<thead>';
        echo '<tr>';
        echo "<th class='text-center'><input type='checkbox' id='select-job-all'/></th>";
        echo '<th>Job id</th>';
        echo '<th>Date</th>';
        echo '<th>Type</th>';
        echo '<th>Status</th>';
        echo '<th>Description</th>';
        echo '</tr>';
        echo "</thead>\n";
        echo "<tbody>\n";
        foreach ($running_jobs as $job) {
            echo '<tr>';
            $checked = null;
            if ($job['status'] == 'done') {
                $checked = " checked='checked' ";
                $found_done = true;
            }
            echo "<td class='text-center'><input type='checkbox' class='select-job' name='job_" .
                $job['job_id'] .
                "' $checked /></td>";
            echo '<td>' . $job['job_id'] . '</td>';
            echo '<td>' . $job['start_date'] . '</td>';
            echo '<td>' . $job['job_type'] . '</td>';
            echo '<td>' . $job['status'] . '</td>';
            echo '<td>' . $job['comment'] . '</td>';
            echo "</tr>\n";
        }
        echo "</tbody>\n";
        echo "</table>\n";
        echo "<button id='delete-jobs' type=\"submit\" class=\"btn btn-sm btn-default\" disabled>\n";
        echo "Delete selected jobs\n";
        echo '</button>';
        if ($found_done) {
            echo "<p class='text-justify'><strong>Note:</strong> if a job's status is  <code>done</code>, it can deleted without repercussions. This is most likely a remnant of a job which was not cleaned up properly.</p>\n";
        }
        echo "</form>\n";
    }
    ?>
</section>
<script type='text/javascript'>
    $(document).ready(function(){
        var nChecked = $('.select-job:checked').length;  // Number of checked jobs
        // Enable delete button if any job is selected
        $('#delete-jobs').prop('disabled', (nChecked < 1));

        // Handle row checkboxes
        // Select all jobs
        $('#select-job-all').change(function(){
            // Select all jobs if checked
            var allChecked = $(this).prop('checked');
            $('.select-job').prop('checked', allChecked);
            // Enable delete button if any job is selecte
            nChecked = $('.select-job:checked').length;
            $('#delete-jobs').prop('disabled', (nChecked < 1));
        });

        // Select individual job
        $('.select-job').change(function() {
            nChecked = $('.select-job:checked').length;
            // Check 'select all' checkbox if all jobs are selected
            if (nChecked === $('.select-job').length) {
                $('#select-job-all').prop('checked', true);
            }
            else {
                $('#select-job-all').prop('checked', false);
            }
            // Enable delete button if any job is selected
            $('#delete-jobs').prop('disabled', (nChecked < 1));

        });
    });
</script>
<?php echo $this->element('help_tooltips/enable_tooltips', ['container' => '#cluster-info']); ?>
