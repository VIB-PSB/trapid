<?php
// TODO: move to controller?
// TODO: rethink the display of different elements of the page. It works but could be cleaner.
// Define two variables to decide if the use should be allowed to upload transcripts, subsets, or both
// Depending on the status of the experiments, not everything on the page should be displayed!

// By default everything is disabled
$enable_transcript_upload = false;
$enable_subset_upload = false;

// Uploading transcript is possible only if the experiment is in 'empty' or 'upload' state
if(in_array($exp_info['process_state'], ['empty', 'upload'])) {
    $enable_transcript_upload = true;
}
// Uploading subsets is possible only if the experiment is in 'upload' or 'finished' state and if it contains > 0 transcripts
if(in_array($exp_info['process_state'], ['upload', 'finished']) && $exp_info['transcript_count'] > 0) {
    $enable_subset_upload = true;
}

// If there are any subset information to display (i.e. either `subset_error` or `subset_message` is set), the subset tab should be active
$active_subset_tab = false;
if(isset($subset_error) || isset($subset_message)) {
    $active_subset_tab = true;
}
?>


<div class="page-header">
    <h1 class="text-primary">Import data</h1>
</div>

<!--Tabs-->
<ul class="nav nav-tabs nav-justified" id="tabs" data-tabs="tabs">
    <?php if ($enable_transcript_upload && !$active_subset_tab):?>
    <li class="active"><a href="#transcripts-import" data-toggle="tab">Transcript sequences</a></li>
    <?php elseif ($enable_transcript_upload):?>
    <li><a href="#transcripts-import" data-toggle="tab">Transcript sequences</a></li>
    <?php else: ?>
    <li class="disabled">
        <a>Transcript sequences
        <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['data_upload_transcripts_disabled'], "override_span_class"=>"glyphicon glyphicon-question-sign")); ?>
        </a>
    </li>
    <?php endif; ?>
    <?php if($enable_subset_upload && $enable_transcript_upload && !$active_subset_tab):?>
    <li><a href="#subset-import" data-toggle="tab">Transcript subset</a></li>
    <?php elseif($enable_subset_upload):?>
    <li class="active"><a href="#subset-import" data-toggle="tab">Transcript subset</a></li>
    <?php else: ?>
    <li class="disabled">
        <a>Transcript subset
        <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['data_upload_subsets_disabled'], "override_span_class"=>"glyphicon glyphicon-question-sign")); ?>
        </a>
    </li>
    <?php endif; ?>
</ul>

<!--Tab content-->
<div class="tab-content">
    <!-- Transcripts import -->
    <?php if ($enable_transcript_upload):?>
        <?php if($active_subset_tab):?>
        <div id="transcripts-import" class="tab-pane">
        <?php else: ?>
        <div id="transcripts-import" class="tab-pane active">
        <?php endif; ?>
        <br>

        <section class="page-section">
            <p class="text-justify">Transcript files can be uploaded from your machine or from a URL. Maximum allowed file-size is 32 Mb. If your file is larger, compress the file (using <code>zip</code> or <code>gzip</code>) and
                upload the compressed file.</p>
            <p class="text-justify">Input data must be formatted as multi-fasta file(s), with each transcript identifier's length <strong>not exceeding 50
                    characters</strong>. For example: </p>
            <div class="well fixed-width-text well-sm"a>
                >transcript_identifier1<br>AAGCTAGAGATCTCGAGAGAGAGAGCTAGAGCTAGC...<br>>transcript_identifier2<br>AAGCTAGAGAGCTCTAGGAATCGAC...<br>[...]
            </div>
        </section>

        <section class="page-section-sm">
            <div class="row">
                <!-- Add transcripts -->
                <div class="col-lg-6">
                    <div class="panel panel-default" id="add-transcripts">
                        <div class="panel-heading"><h3 class="panel-title">Add transcript files</h3></div>
                        <div class="panel-body">
                            <?php
                            echo $this->Form->create(false, array("url"=>array("controller" => "trapid", "action" => "import_data", $exp_id),
                                "type" => "post", "enctype" => "multipart/form-data",
                                "id" => "import_data_form", "name" => "import_data_form"));
                            ?>
                            <input name="type" type="hidden" value="upload_file"/>
                            <div class="form-group">
                                <label>
                                    <input type="radio" name="uploadtype" value="file" checked="checked" id="r1"> File
                                </label>
                                <span style="margin-right:8px;margin-left:5px;"></span>
                                <input name="uploadedfile" type="file" id="ri1" style="display:inline;"/>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="radio" name="uploadtype" value="url" id="r2"/> URL
                                </label>
                                <input type="text" name="uploadedurl" size="35" id="ri2" placeholder="Transcript file URL..." disabled="disabled" style="margin-left:5px;"/>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="include_label" id="include_label"/>
                                    <span style="margin-left:5px;">Assign subset to uploaded transcripts</span>
                                </label>
                            <input type="text" id="label_name" name="label_name" placeholder="Subset name..." style="margin-left:9px;"
                                   disabled="disabled"/>
                            </div>
                        </div>
                        <div class="panel-footer">
                            <div class="text-right">
                                <input type="submit" value="Add file / URL" class="btn btn-default btn-sm"/>
                                </form>
                            </div>
                        </div>
                        </div>
                </div>

                <!-- Delete transcripts -->
                <div class="col-lg-6">
                    <div class="panel panel-default" id="del-transcripts">
                        <div class="panel-heading"><h3 class="panel-title">Delete transcript files</h3></div>
                        <?php if (count($uploaded_files) > 0): ?>
                            <?php
                            echo $this->Form->create(false, array("url"=>array("controller" => "trapid", "action" => "import_data", $exp_id),
                                        "type" => "post", "enctype" => "multipart/form-data",
                                        "id" => "import_data_form", "name" => "import_data_form"));
                            ?>
                            <input name="type" type="hidden" value="delete_file"/>
                                    <!--                <p class="text-justify">Current files:</p>-->
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-condensed table-bordered">
                                    <thead>
                                    <tr>
                                        <th style="width:10%">Source</th>
                                        <th style="width:50%">Name</th>
                                        <th style="width:20%">Subset</th>
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
                            </div>
                            <?php else: ?>
                            <div class="panel-body">
                                <p class="text-muted"><strong>No transcript files uploaded</strong></p>
                            </div>
                            <?php endif; ?>
                            <div class="panel-footer">
                                <div class="text-right">
                                    <?php
                                    $del_disabled_str = null;
                                    if (count($uploaded_files) == 0) {
                                        $del_disabled_str = " disabled='disabled' ";
                                    }
                                    ?>
                                    <input type="submit" <?php echo $del_disabled_str ?> value="Delete selected files/URLs" class="btn btn-default btn-sm"/>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div> <!-- end .row -->

            <?php if (isset($error)) {
                // echo "<div class=\"alert alert-danger\" role=\"alert\"><strong>Error: </strong>".$error."<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button></div>";
                echo "<span class='error text-danger'><strong>Error: </strong>" . $error . "</span><br>\n";
            } ?>

        <!-- DB upload  -->
                <div class="row">
            <?php
            echo $this->Form->create(false, array("url"=>array("controller" => "trapid", "action" => "import_data", $exp_id),
                "type" => "post", "enctype" => "multipart/form-data",
                "id" => "import_data_form", "name" => "import_data_form"));
            ?>
            <input name="type" type="hidden" value="database_file"/>
        <p class="text-center">
            <?php
            $disabled = null;
            if (count($uploaded_files) == 0) {
                $disabled = " disabled='disabled' ";
            }
            echo "<button type='submit' $disabled class=\"btn btn-primary\" style='margin-bottom:10px;margin-top:5px;'>\n";
//                        echo "<span class=\"glyphicon glyphicon-arrow-up\"> </span> ";
            echo "Load data into database";
            echo "</button>";
            if (count($uploaded_files) == 0) {
                echo "<span style='margin-left:20px;font-weight:bold;' class='text-danger'>No files have been uploaded or URLs defined for data transfer</span>\n";
            }
            ?>
            </form>
        </p>
        <?php echo $this->Form->end(); ?>
                </div>
        </section>

    </div>
    <?php endif;?>

    <!-- Subset import -->
    <?php if ($enable_subset_upload): ?>
        <?php if (!$enable_transcript_upload || $active_subset_tab): ?>
        <div id="subset-import" class="tab-pane active">
        <?php else:?>
        <div id="subset-import" class="tab-pane">
        <?php endif;?>
            <br>
            <div class="subdiv">
                <?php if (isset($subset_error)) {
                    echo "<span class='text-danger'><strong>Error:</strong> " . $subset_error . "</span><br>\n";
                } ?>
                <?php if (isset($subset_message)) {
                    echo "<span class='text-primary'><strong>Message: </strong>" . $subset_message . "</span><br>\n";
                } ?>
                <section class="page-section">
                    <p class="text-justify">Please upload a file containing transcript identifiers which should form a subset.
                    Each line of the file must contain a transcript identifier. For example:</p>
                <div class="fixed-width-text well well-sm">
                    transcript1<br/>
                    transcript3<br/>
                    transcript1012<br/>
                    [...]<br/>
                </div>
                </section>


                <section class="page-section">
                <?php
                echo $this->Form->create(false, array("url"=>array("controller" => "trapid", "action" => "import_labels", $exp_id),
                    "type" => "post", "enctype" => "multipart/form-data",
                    "id" => "import_labels_form", "name" => "import_labels_form"));
                ?>

                 <div class="form-group">
                    <input name="uploadedfile" type="file"/>
                 </div>

                <div class="form-group">
                    <span style="margin-right: 8px;"><strong>Name of transcripts subset</strong></span>
                    <input type="text" placeholder="New subset..." name="label">
                </div>
                    <p class="text-center">
                <input type="submit" value="Import subset" class="btn btn-primary"/>
                    </p>
                </form>
                </section>
    </div>
    <?php endif;?>
</div>

    <script type="text/javascript">
        $("#ri1").change(function () {
            var max_size = 32000000;
            if (this.files[0].size > max_size) {
                alert("Maximum size of file upload is 32MB. This upload will not work!");
            }
        });


        $("#include_label").change(function () {
            if ($("#include_label").is(':checked')) {
                // console.log("Checked");
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
    </script>
</div>
<?php echo $this->element("help_tooltips/enable_tooltips",  array("container"=>"#tabs")); ?>
