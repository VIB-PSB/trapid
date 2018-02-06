<div class="page-header">
    <h1 class="text-primary">Import transcripts</h1>
    <!--                <h1 class="text-primary">Transcript file management</h1>-->
</div>
<div class="subdiv">
    <?php // echo $this->element("trapid_experiment"); ?>
    <section class="page-section">
        <p class="text-justify">Transcript files can be uploaded from your machine or from a URL. Maximum allowed file-size is 30 Mb. If your file is larger, compress the file (using <code>zip</code> or <code>gzip</code>) and
                upload the compressed file.</p>
                <p class="text-justify">Input data must be formatted as multi-fasta file(s), with each transcript identifier's length <strong>not exceeding 50
                        characters</strong>. For example: </p>
            <div class="well fixed-width-text well-sm">
                >transcript_identifier1<br>AAGCTAGAGATCTCGAGAGAGAGAGCTAGAGCTAGC...<br>>transcript_identifier2<br>AAGCTAGAGAGCTCTAGGAATCGAC...<br>[...]
            </div>
    </section>

        <section class="page-section-sm">
            <h3>Add transcript files</h3>
            <?php if (isset($error)) {
                // echo "<div class=\"alert alert-danger\" role=\"alert\"><strong>Error: </strong>".$error."<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button></div>";
                echo "<span class='error text-danger'><strong>Error: </strong>" . $error . "</span><br/><br/>\n";
            } ?>

            <?php
            echo $this->Form->create(false, array("controller" => "trapid", "action" => "import_data/" . $exp_id,
                "type" => "post", "enctype" => "multipart/form-data",
                "id" => "import_data_form", "name" => "import_data_form"));
            ?>
            <input name="type" type="hidden" value="upload_file"/>
            <input type="radio" name="uploadtype" value="file" checked="checked" id="r1"/>
            <span style="margin-right:8px;margin-left:5px;">File</span>
            <input name="uploadedfile" type="file" id="ri1" style="display:inline;"/>
            <br/><br/>
            <input type="radio" name="uploadtype" value="url" id="r2"/>
            <span style="margin-left:5px;margin-right:5px;">URL</span>
            <input type="text" name="uploadedurl" size="35" id="ri2" placeholder="Transcript file URL..." disabled="disabled"/>
            <br/><br/>
            <input type="checkbox" name="include_label" id="include_label"/>
            <span style="margin-left:5px;">Assign label to uploaded transcripts</span>
            <input type="text" id="label_name" name="label_name" placeholder="Subset name..." style="margin-left:5px;"
                   disabled="disabled"/>
            <br/><br/>
            <input type="submit" value="Upload file / define URL" class="btn btn-default btn-sm"/>
<!--                   style="width:200px;margin-bottom:10px;margin-top:5px;"/>-->
            </form>
        </section>
    </div>

    <section class="page-section-sm">
    <h3>Delete transcript files</h3>
        <?php if (count($uploaded_files) > 0): ?>
            <div>
                <?php if (isset($error)) {
                    echo "<span class='error text-danger'><strong>Error: </strong>" . $error . "</span><br/><br/>\n";
                } ?>
                <?php
                echo $this->Form->create(false, array("controller" => "trapid", "action" => "import_data/" . $exp_id,
                    "type" => "post", "enctype" => "multipart/form-data",
                    "id" => "import_data_form", "name" => "import_data_form"));
                ?>
                <input name="type" type="hidden" value="delete_file"/>
<!--                <p class="text-justify">Current files:</p>-->
                <table class="table table-striped table-hover table-condensed table" style="max-width:800px;">
                    <thead>
                    <tr>
                        <th style="width:10%">Source</th>
                        <th style="width:50%">Name</th>
                        <th style="width:20%">Label</th>
                        <th style="width:10%">Status</th>
                        <th style="width:10%">Delete</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($uploaded_files as $data_upload) {
                        $du = $data_upload['DataUploads'];
                        echo "<tr>";
                        echo "<td>" . $du["type"] . "</td>";
                        echo "<td class='fixed-width-text'>" . $du["name"] . "</td>";
                        echo "<td>" . $du["label"] . "</td>";
                        if ($du['status'] == "uploaded" || $du['status'] == "to_download") {
                            echo "<td>Ready</td>";
                        } else if ($du['status'] == "error") {
                            echo "<td style='color:red'>Error</td>";
                        } else {
                            echo "<td></td>";
                        }
                        echo "<td class='text-center'><input type='checkbox' name='id_" . $du['id'] . "'/></td>\n";
                        echo "</tr>\n";
                    }
                    ?>
                    </tbody>
                </table>
                <input type="submit" value="Delete selected files/URLs" class="btn btn-default btn-sm"/>
<!--                       style="width:200px;margin-bottom:10px;margin-top:5px;"/>-->
                </form>
            </div>
        <?php else: ?>
            <span>No transcript files uploaded</span>
        <?php endif; ?>
    </section>


    <section class="page-section-sm">
    <h3>Upload transcripts to database</h3>
        <div>
            <?php
            echo $this->Form->create(false, array("controller" => "trapid", "action" => "import_data/" . $exp_id,
                "type" => "post", "enctype" => "multipart/form-data",
                "id" => "import_data_form", "name" => "import_data_form"));
            ?>
            <input name="type" type="hidden" value="database_file"/>
            <?php
            $disabled = null;
            if (count($uploaded_files) == 0) {
                $disabled = " disabled='disabled' ";
            }
            echo "<button type='submit' $disabled class=\"btn btn-primary\" style='margin-bottom:10px;margin-top:5px;'>\n";
//            echo "<span class=\"glyphicon glyphicon-arrow-up\"> </span> ";
            echo "Load data into database";
            echo "</button>";
            if (count($uploaded_files) == 0) {
                echo "<span style='margin-left:20px;font-weight:bold;' class='text-danger'>No files have been uploaded or URLs defined for data transfer</span>\n";
            }
            ?>
            </form>
        </div>
    </section>
</div>

    <script type="text/javascript">
        //<![CDATA[

        $("#ri1").change(function () {
            var max_size = 32000000;
            if ($("#ri1").files[0].size > max_size) {
                alert("Maximum size of file upload is 32MB. This upload will not work!");
            }
        });


        $("#include_label").change(function () {
            if ($("#include_label").is(':checked')) {
                console.log("Checked");
                $('#label_name').attr('disabled', false);
            }
            else {
                $('#label_name').attr('disabled', true);
            }
        });

        $("#r1").change(function () {
            sl();
        });
        $("#r2").change(function () {
            sl();
        });
        function sl() {
            var ena = "";
            var dis = "";
            if ($("#r1").is(':checked')) {
                ena = "#ri1";
                dis = "#ri2";
            }
            else if ($("#r2").is(':checked')) {
                ena = "#ri2";
                dis = "#ri1";
            }
            if (ena != "" && dis != "") {
                $(ena).attr('disabled', false);
//                delete $(ena).disabled;
                $(dis).attr('disabled', true);
//                $(dis).disabled = "disabled";
            }
        }

        //]]>
    </script>
</div>
