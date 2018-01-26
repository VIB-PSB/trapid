<div>
    <div class="page-header">
        <h1 class="text-primary">Experiment status</h1>
    </div>
    <div class="subdiv">
        <?php // echo $this->element("trapid_experiment"); ?>
<section class="page-section">
        <h2>Change status</h2>
        <div class="subdiv">
            <?php
            $message = null;
            $possible_states = array();
            if ($exp_info['process_state'] == "processing") {
                $message = "<span class='text-danger'>Changing the processing status stops the processing of the experiment. This change is not reversible.</span>";
                $possible_states = array("upload", "finished");
            } else if ($exp_info['process_state'] == "error") {
                $message = "<span class='text-danger'>The experiment is in error state (due to problems during the processing of the experiment). You can change the state of the experiment, in order to try and restart the processing.</span>";
                $possible_states = array("empty", "upload", "finished");
            } else if ($exp_info['process_state'] == "finished") {
                $message = "<span class='text-warning'>The experiment has passed the processing stage. Changing the status of the experiment might cause certain pages not to load.</span>";
                $possible_states = array("empty", "upload");
            } else if ($exp_info['process_state'] == "loading_db") {
                $message = "<span class='text-warning'>The experiment has not passed the phase of loading data into the database. Changing the status of the experiment will force to re-uploading of data.</span>";
                $possible_states = array("empty");
            } else {
                echo "<span>No reason the change status</span>";
            }
            if (count($possible_states) > 0) {
                echo $this->Form->create(null, array("url" => array("controller" => "trapid", "action" => "change_status/$exp_id"),
                    "type" => "post"));
                echo "<dl class='standard dl-horizontal'>\n";
                echo "<dt>Warning</dt>\n";
                echo "<dd>" . $message . "</dd>\n";
                echo "<dt>New status</dt>\n";
                echo "<dd>\n";
                echo "<select name='new_status' style='width:200px;'>\n";
                foreach ($possible_states as $ps) {
                    echo "<option value='" . $ps . "'>" . $ps . "</option>\n";
                }
                echo "</select>\n";
                echo "</dd>\n";
                echo "</dl>\n";
                echo "<input type='hidden' name='form_type' value='change_status' />\n";
                echo "<br/>\n";
                echo "<input type='submit' value='Change status' class='btn btn-default'/>\n";
                echo "</form>\n";
            }
            ?>
        </div>


        <h3>Storage</h3>
        <div class="subdiv">
            <?php
            echo "<dl class='standard dl-horizontal'>\n";
            echo "<dt>Disk usage</dt>\n";
            echo "<dd>" . $disk_usage . "</dd>\n";
            echo "</dl>\n";
            /*
            echo "<br/>\n";
            echo $this->Form->create(null,array("url"=>array("controller"=>"trapid","action"=>"change_status/$exp_id"),"type"=>"post"));
            echo "<input type='hidden' name='form_type' value='clear_storage' />\n";
            echo "<input type='submit' value='Clear storage' />\n";
            echo "</form>\n";
            */
            ?>
        </div>
</section>
    </div>

</div>
