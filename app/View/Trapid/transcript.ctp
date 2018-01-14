<div>
    <?php
    //echo $this->Html->script(array('prototype-1.7.0.0'));
    ?>
    <div class="page-header">
        <h1 class="text-primary"><?php echo $transcript_info['transcript_id']; ?> <small>transcript</small></h1>
    </div>

    <?php // echo $this->element("trapid_experiment"); ?>

        <?php
        /* Toolbox code */
        $disable_cluster_tools = false;
        if (isset($max_number_jobs_reached)) {
            echo "<span class='error'>The maximum number of jobs (" . MAX_CLUSTER_JOBS . ") you can have queued has been reached for this experiment.<br/>Some tools will be unavailable until the currently scheduled jobs have finished or have been deleted.</span><br/><br/>\n";
            $disable_cluster_tools = true;
        }

        $disabled_framedp = true;
        if ($transcript_info['gf_id']) {
            $disabled_framedp = false;
        }
        $toolbox = array("Structural data" => array(
            array(
                "Correct frameshifts with FrameDP",
                $this->Html->url(array("controller" => "tools", "action" => "framedp", $exp_id, $transcript_info['gf_id'], $transcript_info['transcript_id'])),
                "some_image.png",
                $disabled_framedp || $disable_cluster_tools
            ),
        ),
            "Similarity search" => array(
                array(
                    "Browse similarity search output",
                    $this->Html->url(array("controller" => "trapid", "action" => "similarity_hits", $exp_id, $transcript_info['transcript_id'])),
                    "some_image.png"
                )
            ),
        );
        $this->set("toolbox", $toolbox);
        echo $this->element("toolbox");
        ?>

        <!-- Navigation tabs -->
    <br>
    <ul class="nav nav-tabs" id="tabs" data-tabs="tabs">
        <li class="active"><a href="#structural-data" data-toggle="tab">Structural information</a></li>
        <li><a href="#functional-data" data-toggle="tab">Functional information</a></li>
        <li><a href="#subset-data" data-toggle="tab">Subset information</a></li>
    </ul>

    <!-- Tab content -->
    <div class="tab-content">
        <div id="structural-data" class="tab-pane active"><br>
            <dl class="standard2 dl-horizontal">
                <dt>Gene family</dt>
                <dd>
                        <?php
                        if ($transcript_info['gf_id'] != "") {
                            echo $this->Html->link($transcript_info['gf_id'], array("controller" => "gene_family", "action" => "gene_family", $exp_id, $transcript_info['gf_id']));
                        } else {
                            echo "<span class='disabled'>Unavailable</span>\n";
                        }
                        ?>
                        <?php if ($exp_info['genefamily_type'] == "HOM"): ?>
                            <span style='margin-left:5px;'>
			<?php
            echo "(" . $this->Html->link("change gene family", array("controller" => "trapid", "action" => "similarity_hits", $exp_id, $transcript_info['transcript_id'])) . ")";
            ?>
			</span>
                        <?php endif; ?>
                </dd>
                <dt>Uploaded sequence</dt>
                <dd>
                    <div>
                        <textarea class='fixed-width-text' cols="80" rows="5"
                                  name="transcript_sequence"><?php echo $transcript_info['transcript_sequence']; ?></textarea>
                        <br/>
                        <?php echo "<span>Sequence length: " . strlen($transcript_info['transcript_sequence']) . " nt</span>"; ?>
                    </div>
                </dd>

                <dt>Frameshift corrected<br>sequence</dt>
                <dd>
                    <div>
                        <?php
                        if ($transcript_info['transcript_sequence_corrected'] != "") {
                            echo $this->Form->create(false, array("action" => "transcript/" . $exp_id . "/" . $transcript_info['transcript_id'], "type" => "post"));
                            echo "<textarea class='fixed-width-text' cols='80' rows='5'  name='corrected_sequence'>" . $transcript_info['transcript_sequence_corrected'] . "</textarea>\n";
                            echo "<br/>\n";
                            echo "<span>Sequence length: " . strlen($transcript_info['transcript_sequence_corrected']) . " nt</span>\n";
                            echo "<br/>\n";
                            echo "<input type='submit' class='btn btn-sm btn-default' value='Store changed corrected sequence' />\n";
                            echo "</form>\n";
                        } else {
                            echo "<span class='disabled'>Unavailable</span>\n";
                        }
                        ?>
                    </div>
                </dd>


                <dt>ORF sequence</dt>
                <dd>
                    <div>
                        <?php
                        if ($transcript_info['orf_sequence'] != "") {
                            echo $this->Form->create(false, array("action" => "transcript/" . $exp_id . "/" . $transcript_info['transcript_id'], "type" => "post"));
                            echo "<textarea class='fixed-width-text' cols='80' rows='5' name='orf_sequence'>" . $transcript_info['orf_sequence'] . "</textarea>\n";
                            echo "<br/>\n";
                            echo "<span>Sequence length: " . strlen($transcript_info['orf_sequence']) . " nt &nbsp; / &nbsp; " . (number_format(strlen($transcript_info['orf_sequence']) / 3, 0)) . " aa ";
                            echo "(<a href='javascript:show_aa();'>show protein sequence</a>)</span>\n";
                            echo "<br/>\n";
                            echo "<input type='submit' class='btn btn-sm btn-default' value='Store changed ORF sequence' style='margin-bottom:10px;'/>\n";
                            echo "</form>\n";
                            echo "<script type='text/javascript'>\n";
                            echo "//<![CDATA[\n";
                            echo "function show_aa(){\n";
                            echo "document.getElementById('aa_seq_dt').style.display='block';\n";
                            echo "document.getElementById('aa_seq_dd').style.display='block';\n";
                            echo "}\n";
                            echo "//]]>\n";
                            echo "</script>\n";
                        } else {
                            echo "<span class='disabled'>Unavailable</span>\n";
                        }
                        ?>
                    </div>
                </dd>

                <dt id='aa_seq_dt' style='display:none;'>AA sequence</dt>
                <dd id='aa_seq_dd' style='display:none;'>
                    <div><textarea class='fixed-width-text' cols='80' rows='3'><?php echo $transcript_info['aa_sequence']; ?></textarea></div>
                </dd>


                <dt>Detected frame</dt>
                <dd><?php echo $transcript_info['detected_frame']; ?></dd>

                <dt>Detected strand</dt>
                <dd><?php echo $transcript_info['detected_strand']; ?></dd>

                <dt>Start/stop codon</dt>
                <dd>
                    <div>
                        <?php
                        if ($transcript_info['orf_contains_start_codon'] == 1) {
                            echo "The ORF sequence <span style='color:green'>starts with a start codon</span>\n";
                        } else {
                            echo "The ORF sequence <span style='color:red'>does not start with a start codon</span>\n";
                        }
                        echo "<br/>\n";
                        if ($transcript_info['orf_contains_stop_codon'] == 1) {
                            echo "The ORF sequence <span style='color:green'>ends with a stop codon</span>\n";
                        } else {
                            echo "The ORF sequence <span style='color:red'>does not end with a stop codon</span>\n";
                        }
                        ?>
                    </div>
                </dd>

                <?php
                if ($transcript_info['putative_frameshift'] == 1) {

                    $is_corrected = ($transcript_info['is_frame_corrected'] == 1);
                    $style1 = " style='color:orange' ";
                    if ($is_corrected) {
                        $style1 = " style='color:blue' ";
                    }
                    echo "<dt>Frameshift</dt>\n";
                    echo "<dd>";
                    echo "<div style='padding-left:20px;'>";
                    echo "<ul>";
                    echo "<li $style1>A putative frameshift was detected in this sequence</li>";
                    if ($transcript_info['is_frame_corrected'] == 1) {
                        echo "<li style='color:green'>A putative frameshift was corrected with FrameDP</li>";
                    }
                    echo "</ul>";
                    echo "</div>";
                    echo "</dd>\n";
                }
                ?>

                <dt>Meta annotation</dt>
                <dd>
                    <div>
                        <?php
                        $possible_meta = array("No Information" => array("color" => "#D2D2D2"),
                            "Partial" => array("color" => "#D23333"),
                            "Full Length" => array("color" => "#000000"),
                            "Quasi Full Length" => array("color" => "#000000")
                        );
                        echo $this->Form->create(false, array("action" => "transcript/" . $exp_id . "/" . $transcript_info['transcript_id'], "type" => "post"));
                        echo "<span style='color:" . $possible_meta[$transcript_info['meta_annotation']]['color'] . "'>";
                        echo $transcript_info['meta_annotation'];
                        echo "</span>\n";
                        echo "<select name='meta_annotation' style='width:150px;margin-left:50px;'>";
                        foreach ($possible_meta as $pm => $pm_data) {
                            $sel = null;
                            if ($pm == $transcript_info['meta_annotation']) {
                                $sel = " selected='selected' ";
                            }
                            echo "<option value='" . $pm . "' $sel>" . $pm . "</option>";
                        }
                        echo "</select>\n";
                        echo "<input type='submit' class='btn btn-sm btn-default' value='Store changed meta annotation' style='margin-left:20px;' />\n";
                        echo "</form>\n";
                        ?>
                    </div>
                </dd>
            </dl>

        </div>

        <div id="functional-data" class="tab-pane"><br>

            <dl class="standard2 dl-horizontal">

                <dt>Gene Ontology</dt>
                <dd>
                    <?php
                    if ($associated_go) {
                        echo "<ul class='tabbed_header list-unstyled list-inline'>\n";
                        echo "<li id='tab_collapsed' class='selected_tab'>";
                        echo "<a href=\"javascript:switch_go_display('tab_collapsed','div_collapsed');\">Collapsed GO data</a>";
                        echo "</li>\n";
                        echo "<li id='tab_all'>";
                        echo "<a href=\"javascript:switch_go_display('tab_all','div_all');\">All GO data</a>";
                        echo "</li>\n";
                        echo "</ul>\n";

                        echo "<div class='tabbed_div selected_tabbed_div' id='div_collapsed'>\n";
                        echo "<table class='table table-striped table-condensed table-bordered table-hover' cellpadding='0' cellspacing='0' style='width:600px;'>\n";
                        echo "<thead><tr><th style='width:20%;'>GO term</th><th style='width:80%;'>Description</th></tr></thead>\n";
                        echo "<tbody>\n";
                        foreach ($associated_go as $ag) {
                            $go = $ag['TranscriptsGo']['name'];
                            $is_hidden = $ag['TranscriptsGo']['is_hidden'];
                            if ($is_hidden == 0) {
                                $web_go = str_replace(":", "-", $go);
                                echo "<tr>";
                                echo "<td>" . $this->Html->link($go, array("controller" => "functional_annotation", "action" => "go", $exp_id, $web_go)) . "</td>";
                                echo "<td>" . $go_info[$go]['desc'] . "</td>";
                                echo "</tr>\n";
                            }
                        }
                        echo "</tbody>\n";
                        echo "</table>\n";
                        echo "</div>\n";

                        echo "<div class='tabbed_div' id='div_all'>\n";
                        echo "<table class='table table-striped table-condensed table-bordered table-hover' cellpadding='0' cellspacing='0' style='width:600px;'>\n";
                        echo "<thead>\n";
                        echo "<tr><th style='width:20%;'>GO term</th><th style='width:80%;'>Description</th></tr>\n";
                        echo "</thead>\n";
                        echo "<tbody>\n";
                        foreach ($associated_go as $ag) {
                            $go = $ag['TranscriptsGo']['name'];
                            $web_go = str_replace(":", "-", $go);
                            $is_hidden = $ag['TranscriptsGo']['is_hidden'];
                            $class = null;
                            if ($is_hidden == 0) {
                                $class = " class='altrow' ";
                            }
                            echo "<tr $class >";
                            echo "<td>" . $this->Html->link($go, array("controller" => "functional_annotation", "action" => "go", $exp_id, $web_go)) . "</td>";
                            echo "<td>" . $go_info[$go]['desc'] . "</td>";
                            echo "</tr>\n";
                        }
                        echo "</tbody>\n</table>\n";
                        echo "</div>\n";
                    } else {
                        echo "<span class='disabled'>Unavailable</span>";
                    }
                    ?>
                </dd>

                <dt>Interpro domains</dt>
                <dd>
                    <?php
                    if ($associated_interpro) {
                        echo "<table class='table table-striped table-condensed table-bordered table-hover' cellpadding='0' cellspacing='0' style='width:600px;'>\n";
                        echo "<thead>\n";
                        echo "<tr><th style='width:20%;'>InterPro domain</th><th style='width:80%;'>Description</th></tr>\n";
                        echo "</thead>\n";
                        echo "<tbody>\n";
                        foreach ($associated_interpro as $ai) {
                            $ipr = $ai['TranscriptsInterpro']['name'];
                            echo "<tr>";
                            echo "<td>" . $this->Html->link($ipr, array("controller" => "functional_annotation", "action" => "interpro", $exp_id, $ipr)) . "</td>";
                            echo "<td>" . $interpro_info[$ipr]['desc'] . "</td>";
                            echo "</tr>\n";
                        }
                        echo "</tbody>\n";
                        echo "</table>\n";
                    } else {
                        echo "<span class='disabled'>Unavailable</span>";
                    }
                    ?>
                </dd>

            </dl>


        </div>

        <div id="subset-data" class="tab-pane"><br>
            <?php if (count($transcript_subsets) == 0) { ?>
            <p class="text-justify">This transcript does not belong to any subset. </p>
            <?php
            }
            else {
            ?>
            <p class="text-justify">This transcript is in the following subset(s):
            <ul>
                <?php
                for ($i = 0; $i < count($transcript_subsets); $i++) {
                    echo "<li>" . $this->Html->link($transcript_subsets[$i], array("controller" => "labels", "action" => "view", $exp_id, urlencode($transcript_subsets[$i]))) . "</li>";
                }
            }
            ?>
            </ul></p>
            <p class="text-justify"><a href='javascript:show_subsets();'>Add / change subsets</a></p>

            <?php
            // Subset table
            echo "<div id='all_subsets' style='display:none;'>\n";
            echo $this->Form->create(false, array("action" => "transcript/" . $exp_id . "/" . $transcript_info['transcript_id'], "type" => "post"));
            echo "<input type='hidden' name='subsets' value='subsets'/>";
            echo "<table class='table table-striped table-condensed table-bordered table-hover' cellpadding='0' cellspacing='0' style='width:430px;'>\n";
            echo "<thead><tr><th style='width:15%'>Include</th><th style='width:60%'>Subset</th><th>#Transcripts</th></tr></thead>\n";
            echo "<tbody>\n";
            foreach ($available_subsets as $subset => $count) {
                echo "<tr>";
                $checked = null;
                if (in_array($subset, $transcript_subsets)) {
                    $checked = " checked='checked' ";
                }
                echo "<td><input type='checkbox' name='" . $subset . "' $checked /></td>";
                echo "<td>" . $this->Html->link($subset, array("controller" => "labels", "action" => "view", $exp_id, urlencode($subset))) . "</td>";
                echo "<td>" . $count . "</td>";
                echo "</tr>\n";
            }
            echo "<tr>";
            echo "<td><input type='checkbox' name='new_subset' /></td>";
            echo "<td><input type='text' name='new_subset_name' placeholder='New subset...'/> </td>";
            echo "<td>0</td>";
            echo "</tr>\n";
            echo "</tbody>\n";
            echo "</table>\n";
            echo "<input type='submit' class='btn btn-sm btn-default' value='Store changed subset information' />\n";
            echo "</form>\n";
            echo "</div>\n";
            ?>
        </div>
    </div>

        <br><br>
        </div>
    </div>

<script type="text/javascript">
    //<![CDATA[
    /* Show subset table */
    function show_subsets() {
        document.getElementById('all_subsets').style.display = 'block';
    }


    /* Modify CSS classes to show/hide GO data */
    function switch_go_display(tab_id, div_id) {
        // 1. Make all tabs and divs 'normal'
        document.getElementById("tab_collapsed").className = "";
        document.getElementById("tab_all").className = "";
        document.getElementById("div_collapsed").className = "tabbed_div";
        document.getElementById("div_all").className = "tabbed_div";
        // 2. Create extra class for the correct tab and div
        document.getElementById(tab_id).className = "selected_tab";
        document.getElementById(div_id).className = "tabbed_div selected_tabbed_div";
    }
    //]]>
</script>
