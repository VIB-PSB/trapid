<div class="page-header">
    <h1 class="text-primary">Experiment status</h1>
</div>
<section class="page-section">
    <h3>Change status</h3>
    <?php
    $message = null;
    $possible_states = [];
    $message_class = 'text-danger';
    if ($exp_info['process_state'] == 'processing') {
        $message =
            'Changing the processing status stops the processing of the experiment. This change is not reversible.';
        $possible_states = ['upload', 'finished'];
    } elseif ($exp_info['process_state'] == 'error') {
        $message =
            'The experiment is in error state (due to problems during the processing of the experiment). You can change the state of the experiment, in order to try and restart the processing.';
        $possible_states = ['empty', 'upload', 'finished'];
    } elseif ($exp_info['process_state'] == 'finished') {
        $message_class = 'text-warning';
        $message =
            'The experiment has passed the processing stage. Changing the status of the experiment might cause certain pages not to load.';
        $possible_states = ['empty', 'upload'];
    } elseif ($exp_info['process_state'] == 'loading_db') {
        $message_class = 'text-warning';
        $message =
            'The experiment has not passed the phase of loading data into the database. Changing the status of the experiment will force to re-uploading of data.';
        $possible_states = ['empty'];
    } else {
        echo "<span>No reason to change the experiment's status. <strong>Current status:</strong> " .
            $exp_info['process_state'] .
            '.</span>';
    }
    if (count($possible_states) > 0) {
        echo $this->Form->create(null, [
            'url' => ['controller' => 'trapid', 'action' => "change_status/$exp_id"],
            'type' => 'post'
        ]);
        echo "<dl class='standard dl-horizontal exp-state'>\n";
        echo "<dt>Warning</dt>\n";
        echo "<dd><span class='" . $message_class . "'>" . $message . "</span></dd>\n";
        echo "<dt>Current status</dt>\n";
        echo '<dd>' . $exp_info['process_state'] . "</dd>\n";
        echo "<dt>New status</dt>\n";
        echo "<dd>\n";
        echo "<select name='new_status' class='form-control change-exp-state'>\n";
        foreach ($possible_states as $ps) {
            echo "<option value='" . $ps . "'>" . $ps . "</option>\n";
        }
        echo "</select>\n";
        echo "</dd>\n";
        echo "</dl>\n";
        echo "<input type='hidden' name='form_type' value='change_status' />\n";
        echo "<br/>\n";
        echo "<button type='submit' class='btn btn-default btn-sm'><span class='glyphicon glyphicon-edit'></span> Change status</button>";
        echo $this->Form->end();
    }
    ?>
</section>
<section class="page-section">
    <h3>Storage information</h3>
    <dl class='standard dl-horizontal'>
        <dt>Disk usage</dt>
        <dd> <?php echo $disk_usage; ?> </dd>
    </dl>
</section>