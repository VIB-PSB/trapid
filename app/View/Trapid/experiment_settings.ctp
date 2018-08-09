<div class="page-header">
    <h1 class="text-primary">Experiment Settings</h1>
</div>
<div class="subdiv">
    <?php
    //$show_experiment_overview_description	= true;
    // echo $this->element("trapid_experiment");
    ?>

    <h3>Change settings</h3>
    <div class="subdiv">
        <?php
        if (isset($error)) {
            echo "<span class='error'>" . $error . "</span><br/>\n";
        }
        echo $this->Form->create(null, array("url" => array("controller" => "trapid", "action" => "experiment_settings/$exp_id"),
            "type" => "post"));
        ?>
        <dl class="standard">
            <dt>Name</dt>
            <dd><input type="text" name="experiment_name" maxlength="50" style="width:400px;"/></dd>
            <dt>Description</dt>
            <dd><textarea rows="4" name="experiment_description" style="width:400px;"></textarea></dd>
        </dl>
        <input type="submit" class='btn btn-default' value="Update settings"/>
        </form>
    </div>
</div>
