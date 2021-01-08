<div class="page-header">
    <h1 class="text-primary">Change experiment settings</h1>
</div>
<section class="page-section">
    <?php
    if (isset($error)) {
        echo "<p class='text-danger'><strong>Error:</strong> " . $error . "</p>\n";
    }
    echo $this->Form->create(null, array("id"=>"exp_settings_form", "url" => array("controller" => "trapid", "action" => "experiment_settings", $exp_id),
        "type" => "post"));
    ?>
    <div class="form-group mw-800">
        <label for="experiment_name"><strong>Name</strong></label>
        <input type="text" maxlength="50" class="form-control" name="experiment_name" id="experiment_name" placeholder="My experiment" required value="<?= $exp_info['title'];?>">
    </div>
    <div class="form-group mw-800">
        <label for="experiment_description" class="optional"><strong>Description</strong></label>
        <textarea rows="4" class="form-control" name="experiment_description" id="experiment_description" placeholder="Experiment description... "><?php if($exp_info['description']) echo $exp_info['description'];?></textarea>
    </div>

    <button type="submit" class="btn btn-default btn-sm" id="exp_settings_btn"><span class="glyphicon glyphicon-edit"></span> Change settings</button>
    &nbsp;| <a id="exp_settings_reset" href="#/">Reset all</a>
    </form>
</section>

<script type="text/javascript">
/*
    // Enable/disable form submission depending on form content
    var expName = document.getElementById("experiment_name");
    var expDesc = document.getElementById("experiment_description");
    var expSettingsBtn = document.getElementById("exp_settings_btn");

    function changeExpSettingsBtn(expName, expDesc, expSettingsBtn) {
        // Check if either name or description differ from their initial value. If yes, enable form submission
        var expNameChanged = expName.value !== expName.defaultValue;
        var expDescChanged = expDesc.value !== expDesc.defaultValue;
        var expSettingsChanged = expNameChanged || expDescChanged;
        expSettingsBtn.disabled = !expSettingsChanged;
    }

    expName.addEventListener("input", function() {
        changeExpSettingsBtn(expName, expDesc, expSettingsBtn)
    }, false);
    expDesc.addEventListener("input", function() {
        changeExpSettingsBtn(expName, expDesc, expSettingsBtn)
    }, false);

*/

    var expSettingsForm = document.getElementById("exp_settings_form");
    var expSettingsReset = document.getElementById("exp_settings_reset");

    expSettingsReset.addEventListener("click", function() {
        expSettingsForm.reset();
    }, false);
</script>