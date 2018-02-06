<div class="page-header">
    <h1 class="text-primary">Import subset</h1>
</div>
<div class="subdiv">
    <?php // echo $this->element("trapid_experiment"); ?>

<!--    <h3>Import labels</h3>-->
    <div class="subdiv">
        <?php if (isset($error)) {
            echo "<span class='text-danger'><strong>Error:</strong> " . $error . "</span><br>\n";
        } ?>
        <?php if (isset($message)) {
            echo "<span class='text-primary'><strong>Message: </strong>" . $message . "</span><br>\n";
        } ?>

        <div style="margin-bottom:10px;font-weight:bold;width:700px;">
        </div>
        <div style="margin-bottom:10px;">
                <p class="text-justify">Please upload a file containing transcript identifiers which should form a subset.</p>
                <p class="text-justify">Each line of the file must contain a transcript identifier. For example:</p>
            </div>
            <div class="fixed-width-text well well-sm">
                transcript1<br/>
                transcript3<br/>
                transcript1012<br/>
                [...]<br/>
            </div>
            <br/>


            <?php
            echo $this->Form->create(false, array("controller" => "trapid", "action" => "import_labels/" . $exp_id,
                "type" => "post", "enctype" => "multipart/form-data",
                "id" => "import_labels_form", "name" => "import_labels_form"));
            ?>

            <input name="uploadedfile" type="file"/>
            <br/>
            <input type="text" placeholder="New subset..." name="label"/> <span>Name of transcripts subset</span>
            <br/><br/>
            <input type="submit" value="Import labels" class="btn btn-primary btn-sm"/>
            </form>
        </div>
    </div>
</div>
