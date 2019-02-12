<!--    <span class="dropdown" style="float: right;"> <a class="ripple-effect dropdown-toggle btn btn-sm btn-default"-->
<!--                                                     href="#" data-toggle="dropdown"> <span class="material-icons"-->
<!--                                                                                            data-toggle="dropdown">settings</span> <b-->
<!--                    class="caret"></b> </a> <ul id="settings-dropdown" class="dropdown-menu dropdown-menu-right"> <li>--><?php //echo $this->Html->link("View log", array("controller" => "trapid", "action" => "view_log", $exp_id)); ?><!--</li> <li>--><?php //echo $this->Html->link("Share experiment", array("controller" => "trapid", "action" => "experiment_access", $exp_id)); ?><!--</li> <li>--><?php //echo $this->Html->link("Change settings", array("controller" => "trapid", "action" => "experiment_settings", $exp_id)); ?><!--</li> <li>--><?php
//                echo $this->Html->link("Reset experiment",
//                    array("controller" => "trapid", "action" => "empty_experiment", $exp_id),
//                    array("class" => "text-info"),
//                    "Are you sure you want to delete all content from this experiment?"); ?>
<!--                                    </li>-->
<!--                                    <li>-->
<!--                                    --><?php //echo $this->Html->link("Delete experiment",
//                                        array("controller" => "trapid", "action" => "delete_experiment", $exp_id),
//                                        array("class" => "test-danger"),
//                                        "Are you sure you want to delete the experiment?");
//                                    ?><!--</li>-->
<!--                                </ul>-->
<!--                            </span>-->

<?php if(isset($admin)) : ?>
<!-- Sidebar options div -->
<div id="sidebar-stuff" style="border: gray 2px dashed; background-color: white;">
    <h3>Sidebar tools
        <small><a onclick="$('#sidebar-stuff').css({'display':'none'});" style="cursor: pointer;">Click to hide</a>
        </small>
    </h3>
    <div class="pull-right">
        <button id="toggle-shadow" class="btn btn-sm btn-primary">Toggle shadow</button>
        <button class="sidebar-toggle btn btn-default btn-sm">Toggle sidebar</button>
    </div>
    <p>
        <label for="sidebar-position">Postion</label>
        <select id="sidebar-position" name="sidebar-position">
<!--            <option value="">Default</option>-->
            <option value="sidebar-fixed-left">Float on left</option>
            <option value="sidebar-fixed-right">Float on right</option>
            <option value="sidebar-stacked" selected>Fixed on left</option>
        </select>
        <label for="sidebar-theme">Colors</label>
        <select id="sidebar-theme" name="sidebar-theme">
            <option value="sidebar-default">Default</option>
            <option value="sidebar-inverse">Inverse</option>
            <option value="sidebar-colored" selected>Colored</option>
            <option value="sidebar-colored-inverse">Colored-Inverse</option>
        </select>
        <!--            <label for="sidebar-header">Sidebar header cover</label>-->
        <!--            <select id="sidebar-header" name="sidebar-header">-->
        <!--                <option value="header-cover">Image cover</option>-->
        <!--                <option value="">Color cover</option>-->
        <!--            </select>-->
    </p>
</div>
<!--    --><?php //pr($standard_experiment_info); ?>

<?php endif; ?>

<div class='page-header'>
    <h1 class="text-primary">Overview
        <small>TRAPID experiment</small>
    </h1> <?php // echo $standard_experiment_info['Experiments']['title'];?>
</div>

<script type="text/javascript">
    $(document).ready(function () {
//        console.log("GONNA PROCESS!");
//        $('#transcriptsTable').dataTable({
//            "bServerSide": true,
//            "bProcessing": true,
//            "sAjaxSource": "http://bioinformatics.psb.ugent.be/testix/trapid_frbuc/trapid/ajaxData"
//        });
//        console.log("PROCESSED!");
    });
    // Not working for security reasons
//    $(document).ready(function () {
//        var table = $('#example').DataTable({
//            scrollY: "300px",
//            scrollX: true,
//            scrollCollapse: true,
//            ajax: "https://datatables.net/examples/server_side/scripts/server_processing.php",
//            serverSide: true,
//            fixedColumns: true
//        });
//    });
</script>
<!--    <table id="example" class="display nowrap" cellspacing="0" width="100%">-->
<!--            <thead>-->
<!--                <tr>-->
<!--                    <th>ID</th>-->
<!--                    <th>First name</th>-->
<!--                    <th>Last name</th>-->
<!--                    <th>ZIP / Post code</th>-->
<!--                    <th>Country</th>-->
<!--                </tr>-->
<!--            </thead>-->
<!--        </table>-->

<section class="page-section-sm">
<div class='row'>
    <div class='col-lg-12'>
        <div class="panel panel-primary">
            <!-- Default panel contents -->
            <div class="panel-heading"><h3 class="panel-title">Experiment information</h3></div>
            <!-- List group -->
            <ul class="list-group">
                <li class="list-group-item">
                    <strong>Description:</strong> <?php echo $standard_experiment_info['Experiments']['description']; ?>
                </li>
                <li class="list-group-item"><strong>Processing
                        status:</strong> <?php echo $standard_experiment_info['Experiments']['process_state']; ?></li>
                <li class="list-group-item"><strong>Transcript
                        count:</strong> <?php echo $transcript_experiment_info[0][0]['transcript_count']; ?> -- <strong>Gene
                        family count: </strong><?php echo $transcript_experiment_info[0][0]['gf_count']; ?></li>
                <li class="list-group-item"><strong>Data source:</strong> <?php
                    if ($datasource_info['URL']) {
                        echo $this->Html->link($datasource_info['name'], $datasource_info['URL'], array('target' => '_blank'));
                    } else {
                        echo $datasource_info['name'];
                    }
                    ?></li>
                <li class="list-group-item"><strong>Similarity search
                        DB:</strong> <?php echo $standard_experiment_info['Experiments']['used_blast_database']; ?></li>
                <li class="list-group-item">
                    <strong>Created:</strong> <?php echo $standard_experiment_info['Experiments']['creation_date']; ?>
                    (last edit: <?php echo $standard_experiment_info['Experiments']['last_edit_date']; ?>)
                </li>
            </ul>
        </div>
    </div>
    <!--                        <div class='col-lg-4'>-->
    <!--                            <div class="panel panel-primary">-->
    <!--                                <!-- Default panel contents -->
    <!--                                <div class="panel-heading">Experiment settings</div>-->
    <!--                                <!-- List group -->
    <!--                                <ul class="list-group">-->
    <!--                                    --><?php //echo $this->Html->link("View log",array("controller"=>"trapid","action"=>"view_log",$exp_id), array("class"=>"list-group-item"));?>
    <!--                                    --><?php //echo $this->Html->link("Share this experiment",array("controller"=>"trapid","action"=>"experiment_access",$exp_id), array("class"=>"list-group-item"));?>
    <!--                                    --><?php //echo $this->Html->link("Change settings",array("controller"=>"trapid","action"=>"experiment_settings",$exp_id), array("class"=>"list-group-item"));?>
    <!--                                    <li class='list-group-item'>--><?php
    //                                        echo $this->Html->link("Empty experiment",
    //                                            array("controller"=>"trapid","action"=>"empty_experiment",$exp_id),
    //                                            array("style"=>"color:#AA0055;font-weight:bold;"),
    //                                            "Are you sure you want to delete all content from this experiment?");
    //                                        echo "&nbsp;/&nbsp;";
    //                                        echo $this->Html->link("Delete experiment",
    //                                            array("controller"=>"trapid","action"=>"delete_experiment",$exp_id),
    //                                            array("style"=>"color:red;font-weight:bold;"),
    //                                            "Are you sure you want to delete the experiment?");
    //                                        ?><!--</li>-->
    <!--                                </ul>-->
    <!--                            </div>-->
    <!--                        </div>-->
</div>
<!--        <button data-toggle="modal" data-target="#squarespaceModal" class="btn btn-primary btn-lg" name="" id="">-->
<!--            <span class="glyphicon glyphicon-plus"> </span> Perform initial processing-->
<!--        </button>-->
<!--        <button data-toggle="modal" data-target="#squarespaceModal" class="btn btn-primary btn-lg" name="" id="">-->
<!--            <span class="glyphicon glyphicon-plus"> </span> Perform initial processing-->
<!--        </button>-->

    <?php
        echo "<p class=\"text-right\">";
    if ($standard_experiment_info['Experiments']['process_state'] == "upload" || isset($admin)) {
        echo $this->Html->link("<span class=\"glyphicon glyphicon-chevron-right\"></span> Process transcripts",
            array("controller" => "trapid", "action" => "initial_processing", $exp_id), array("class"=>"btn btn-primary", "style"=>"margin-left: 10px;", "escape"=>false, "title"=>"Perform transcript initial processing"));
    }



    /*
     * Here we add a (pre)processing link which will initiate a precomputation of the GO enrichments for all defined labels.
     * This should only be visible on several conditions:
     * 0) Reference DB is PLAZA (no GO for orthomcldb)
     * 1) The experiment is in 'finished' state
     * 2) There is at least 1 label defined (--> should be smaller than total dataset, but we won't check for that here).
     * 3) The last_edit_date is younger than the go_enrichment_date (or go_enrichment_date is default). --> not necessary. Yeah, users can overcompensate, but hey.
     * 4) The variable go_enrichment_state is not set to 'processing'
     *
     * The GO enrichment is stored in 1 table, but extra info (go_enrichment_date and go_enrichment_state) are stored in the experiments table.
     */
    if ($standard_experiment_info['Experiments']['process_state'] == "finished" && $num_subsets > 0) {    //(1) and (2)
        if ($standard_experiment_info['Experiments']['enrichment_state'] != 'processing') {    //(4)
            $link_text = "<span class=\"glyphicon glyphicon-chevron-right\"></span> Run functional enrichment (" . $num_subsets . ")";
            if ($standard_experiment_info['Experiments']['enrichment_state'] == "finished") {
                $link_text = "<span class=\"glyphicon glyphicon-chevron-right\"></span> Rerun functional enrichment (" . $num_subsets . ")";
            }
            echo $this->Html->link($link_text,
                array("controller" => "trapid", "action" => "enrichment_preprocessing", $exp_id),
                array("class"=>"btn btn-primary", "style"=>"margin-left: 10px;", "escape"=>false, "title"=>"Perform functional enrichment preprocessing"));
        }
    }
    if ($standard_experiment_info['Experiments']['process_state'] != "upload" && isset($admin)) {
        echo "<br><span style='color:red'><strong>Warning:</strong> Experiment is not in upload state. Override at own risk</span>\n";
    }
    echo "</p>";

    ?>

</section>

<hr>
<!--        <h2>Search this experiment</h2>-->
<!--        <div class="subdiv">-->
<!--            <br/>-->
<?php // echo $this->element("search_element");?>
<!--        </div>-->
<h2 id="transcripts">Transcripts</h2>
<div class="subdiv">
    <?php if ($num_transcripts == 0): ?>
        <p class="lead text-muted">Disabled prior to transcripts import.
            <?php echo $this->Html->link("Import transcripts", array("controller" => "trapid", "action" => "import_data", $exp_id)); ?> to get started.
        </p>
        <!--        <span class='disabled'>Disabled prior to transcripts import</span>-->
    <?php else: ?>
        <?php
        //$this->Paginator->options(array("url"=>$this->passedArgs));
        $this->Paginator->options(array("url" => array("controller" => "trapid", "action" => "experiment", $exp_id, "#" => "transcripts")));
        ?>
        <table cellpadding="0" cellspacing="0" style="width:100%;" class="table table-striped table-bordered" id="transcripts_table">
            <thead>
            <th style="width:10%">Transcript</th>
            <th style="width:15%">Gene family</th>
            <th style="width:20%">GO annotation</th>
            <th style="width:20%">Protein domain annotation</th>
            <th style="width:20%">KO annotation</th>
            <th style="width:10%">Subset</th>
            <th style="width:10%">Meta annotation</th>
            <!--<th style="width:5%">Edit</th>-->
            </thead>
            <tbody>
            <?php
            $bad_status = "Unassigned";
            $tr_counter = 0;
            foreach ($transcript_data as $transcript_dat) {
                $row_class = null;
                if ($tr_counter++ % 2 == 0) {
                    $row_class = " class='altrow' ";
                }

                $td = $transcript_dat['Transcripts'];
                $this->Paginator->options(array("url" => array("controller" => "trapid", "action" => "experiment", $exp_id, "#" => "transcripts")));
                // echo print_r($paginator);
                // echo $td;
                echo "<tr $row_class>";

                //TRANSCRIPT ID
                echo "<td>" . $this->Html->link($td['transcript_id'],
                        array("action" => "transcript", $exp_id, urlencode($td['transcript_id']))) . "</td>";


                //GF ID
                echo "<td>";
                if ($td['gf_id']) {
                    echo $this->Html->link($td['gf_id'],
                        array("controller" => "gene_family", "action" => "gene_family", $exp_id, urlencode($td['gf_id'])));
                } else {
                    echo "<span class='disabled'>" . $bad_status . "</span>";
                }
                echo "</td>\n";


                //GO annotation
                if (!array_key_exists($td['transcript_id'], $transcripts_go)) {
                    echo "<td><span class='disabled'>Unavailable</span></td>";
                } else {
                    echo "<td class='left'>";
                    for ($i = 0; $i < count($transcripts_go[$td['transcript_id']]) && $i < 3; $i++) {
                        $go = $transcripts_go[$td['transcript_id']][$i];
                        $go_web = str_replace(":", "-", $go);
                        $desc = $go_info[$go]['desc'];
                        echo ($i + 1) . ". " . $this->Html->link($desc, array("controller" => "functional_annotation", "action" => "go", $exp_id, $go_web));
                        echo " " . $this->element("go_category_badge", array("go_category"=>$go_info[$go]["type"], "small_badge"=>true, "no_color"=>false));
                        if($i < 2) {
                            echo "<br/>";
                        }
                    }
                        if (count($transcripts_go[$td['transcript_id']]) > 3) {
                            $more_go = count($transcripts_go[$td['transcript_id']]) -3;
                            echo "<label title='" . $more_go . " more GO terms are associated to this transcript" .
                                "' class='pull-right label label-default' style='cursor:help;'>...</label>";
                        }
                    echo "</td>";
                }


                //InterPro annotation
                if (!array_key_exists($td['transcript_id'], $transcripts_ipr)) {
                    echo "<td><span class='disabled'>Unavailable</span></td>";
                } else {
                    echo "<td class='left'>";
                    for ($i = 0; $i < count($transcripts_ipr[$td['transcript_id']]) && $i < 3; $i++) {
                        $ipr = $transcripts_ipr[$td['transcript_id']][$i];
                        $desc = $ipr_info[$ipr]['desc'];
                        echo ($i + 1) . ". " . $this->Html->link($desc, array("controller" => "functional_annotation", "action" => "interpro", $exp_id, $ipr));
                        if($i < 2) {
                            echo "<br/>";
                        }
                    }
                    if (count($transcripts_ipr[$td['transcript_id']]) > 3) {
                        $more_ipr = count($transcripts_ipr[$td['transcript_id']]) -3;
                        echo "<label title='" . $more_ipr . " more InterPro domains are associated to this transcript" .
                            "' class='pull-right label label-default' style='cursor:help;'>...</label>";
                    }
                    echo "</td>";
                }


                //KO annotation
                if (!array_key_exists($td['transcript_id'], $transcripts_ko)) {
                    echo "<td><span class='disabled'>Unavailable</span></td>";
                } else {
                    echo "<td class='left'>";
                    for ($i = 0; $i < count($transcripts_ko[$td['transcript_id']]) && $i < 3; $i++) {
                        $ko = $transcripts_ko[$td['transcript_id']][$i];
                        $desc = $ko_info[$ko]['desc'];
                        echo ($i + 1) . ". " . $this->Html->link($desc, array("controller" => "functional_annotation", "action" => "ko", $exp_id, $ko));
                        if($i < 2) {
                            echo "<br/>";
                        }
                    }
                    if (count($transcripts_ko[$td['transcript_id']]) > 3) {
                        $more_ko = count($transcripts_ko[$td['transcript_id']]) -3;
                        echo "<label title='" . $more_ko . " more KO terms are associated to this transcript" .
                            "' class='pull-right label label-default' style='cursor:help;'>...</label>";
                    }
                    echo "</td>";
                }


                //SUBSET
                if (!array_key_exists($td['transcript_id'], $transcripts_labels)) {
                    echo "<td><span class='disabled'>Unavailable</span></td>";
                } else {
                    echo "<td class='left'>";
                    for ($i = 0; $i < count($transcripts_labels[$td['transcript_id']]) && $i < 3; $i++) {
                        $label = $transcripts_labels[$td['transcript_id']][$i];
                        echo ($i + 1) . ". " . $this->Html->link($label, array("controller" => "labels", "action" => "view", $exp_id, urlencode($label)));
                        if($i < 2) {
                            echo "<br/>";
                        }
                    }
                    if (count($transcripts_labels[$td['transcript_id']]) > 3) {
                        $more_sub = count($transcripts_labels[$td['transcript_id']]) -3;
                        echo "<label title='This transcript is in " . $more_sub . " more subsets" .
                            "' class='pull-right label label-default' style='cursor:help;'>...</label>";
                    }
                    echo "</td>";
                }

                //EDIT
                echo "<td>" . $this->Html->link($td['meta_annotation'], array("controller" => "trapid", "action" => "transcript_selection", $exp_id, "meta_annotation", urlencode($td['meta_annotation']))) . "</td>";

                echo "</tr>\n";
            }
            ?>
            </tbody>
        </table>
        <div class='paging'>
            <?php
            echo $this->Paginator->prev('<< ' . __('previous'), array(), null, array('class' => 'disabled'));
            echo "&nbsp;";
            echo $this->Paginator->numbers();
            echo "&nbsp;";
            echo $this->Paginator->next(__('next') . ' >>', array(), null, array('class' => 'disabled'));
            ?>
        </div>
    <?php endif; ?>
    <br/>
    <?php if(isset($admin)) : ?>
    <div style='height:50px;'></div><hr>
    <h2 class="text-danger">Experimental zone!</h2>

    <h3>Tooltip tests</h3>
    <div id="tooltip-container">
        <?php
            echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>"This is some text with <strong>HTML</strong>", "use_html"=>"true"));
            echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltip_text_test, "tooltip_placement"=>"left"));
            echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltip_text_test, "tooltip_placement"=>"top"));
            echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltip_text_test, "tooltip_placement"=>"right"));
            echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltip_text_test, "tooltip_placement"=>"bottom"));
            echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltip_text_test, "tooltip_placement"=>"left", "override_span_class"=>"glyphicon glyphicon-console help-tooltip-icon small-icon"));
            // This should display nothing as we do not have any tooltip text to display
            echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>null));
        ?>
    </div>
    <?php echo $this->element("help_tooltips/enable_tooltips",  array("container"=>"#tooltip-container")); ?>

    <!--a name='transcripts' /-->
    <h2>Transcripts (jQuery datatables)</h2>
    <!-- table id="browserList" class="table table-striped table-hover"-->
    <!--                        <table id="transcriptsTable">-->
    <!--                            <thead>-->
    <!--                            <tr>-->
    <!--                                <th>Transcript</th>-->
    <!--                            </tr>-->
    <!--                            </thead>-->
    <!--                            <tbody>-->
    <!--                            <tr>-->
    <!--                                <td colspan="1" class="dataTables_empty">Fetching data from server...</td>-->
    <!--                            </tr>-->
    <!--                            </tbody>-->
    <!--                        </table>-->
    <div class="subdiv">

        <div style="float:left;width:1010px;">
            <h2>Experiment information</h2>
            <div class="subdiv well">
                <div style="float:left;width:500px;">
                    <dl class="standard">
                        <dt>Name</dt>
                        <dd><?php echo $standard_experiment_info['Experiments']['title']; ?></dd>
                        <dt>Description</dt>
                        <dd>
                            <div style="width:300px;">
                                <?php
                                if ($standard_experiment_info['Experiments']['description']) {
                                    echo $standard_experiment_info['Experiments']['description'];
                                } else {
                                    echo "<span style='color:#B2B2B2'>No description available</span>";
                                }
                                ?>
                            </div>
                        </dd>
                        <dt>Processing status</dt>
                        <dd><?php echo $standard_experiment_info['Experiments']['process_state']; ?></dd>
                        <dt>Data source</dt>
                        <dd>
                            <?php
                            if ($datasource_info['URL']) {
                                echo $this->Html->link($datasource_info['name'], $datasource_info['URL']);
                            } else {
                                echo $datasource_info['name'];
                            }
                            ?>
                        </dd>
                        <dt>Creation</dt>
                        <dd><?php echo $standard_experiment_info['Experiments']['creation_date']; ?></dd>
                        <dt>Last edit</dt>
                        <dd><?php echo $standard_experiment_info['Experiments']['last_edit_date']; ?></dd>
                    </dl>
                </div>
                <div style="float:left;width:500px;">
                    <dl class="standard">
                        <dt>Transcript count</dt>
                        <dd><?php echo $transcript_experiment_info[0][0]['transcript_count']; ?></dd>
                        <dt>Gene family count</dt>
                        <dd><?php echo $transcript_experiment_info[0][0]['gf_count']; ?></dd>
                        <?php
                        if ($standard_experiment_info['Experiments']['used_blast_database']) {
                            echo "<dt>Used similarity search</dt>";
                            echo "<dd>" . $standard_experiment_info['Experiments']['used_blast_database'] . "</dd>\n";
                        }
                        ?>
                        <dt>Log</dt>
                        <dd><?php echo $this->Html->link("View log", array("controller" => "trapid", "action" => "view_log", $exp_id)); ?></dd>
                        <dt>Experiment access</dt>
                        <dd><?php echo $this->Html->link("Share this experiment", array("controller" => "trapid", "action" => "experiment_access", $exp_id)); ?></dd>
                        <dt>Settings</dt>
                        <dd><?php echo $this->Html->link("Change settings", array("controller" => "trapid", "action" => "experiment_settings", $exp_id)); ?></dd>
                        <dt>Content</dt>
                        <dd><?php
                            echo $this->Html->link("Empty experiment",
                                array("controller" => "trapid", "action" => "empty_experiment", $exp_id),
                                array("style" => "color:#AA0055;font-weight:bold;"),
                                "Are you sure you want to delete all content from this experiment?");
                            echo "&nbsp;/&nbsp;";
                            echo $this->Html->link("Delete experiment",
                                array("controller" => "trapid", "action" => "delete_experiment", $exp_id),
                                array("style" => "color:red;font-weight:bold;"),
                                "Are you sure you want to delete the experiment?");
                            ?>
                        </dd>
                    </dl>
                </div>
                <div style="clear:both;width:700px;font-size:8px;">&nbsp;</div>
            </div>
        </div>
        <div style="float:right;width:100px;text-align:right;margin-right:50px;">
            <?php
            /* 	echo $this->Html->link("Experiments",array("controller"=>"trapid","action"=>"experiments"),array("class"=>"mainref"));
                echo "<br/>\n";
                echo $this->Html->link("Documentation",array("controller"=>"documentation","action"=>"index"),array("target"=>"_blank","class"=>"mainref"));
                echo "<br/>\n"; */
            ?>
        </div>
        <div style="clear:both;width:700px;font-size:8px;">&nbsp;</div>

        <h2>Import/Export</h2>
        <div class="subdiv">
            <dl class="standard">
                <dt>Import transcripts</dt>
                <dd>
                    <?php
                    $process_state = $standard_experiment_info['Experiments']['process_state'];
                    if ($process_state == "empty" || $process_state == "upload") {
                        echo $this->Html->link("Import data", array("controller" => "trapid", "action" => "import_data", $exp_id));
                    } else {
                        echo "<span class='disabled'>Disabled after initial data processing</span>\n";
                    }
                    ?>
                </dd>
                <dt>Import transcript labels</dt>
                <dd>
                    <?php
                    if ($num_transcripts == 0) {
                        echo "<span class='disabled'>Disabled prior to transcripts import</span>\n";
                    } else {
                        echo $this->Html->link("Import data", array("controller" => "trapid", "action" => "import_labels", $exp_id));
                    }
                    ?>
                </dd>
                <dt>Export data</dt>
                <dd>
                    <?php
                    if ($num_transcripts == 0) {
                        echo "<span class='disabled'>Disabled prior to transcripts import</span>\n";
                    } else {
                        echo $this->Html->link("Export data", array("controller" => "trapid", "action" => "export_data", $exp_id));
                    }
                    ?>
                </dd>
                <!--	<dt>Clone experiment</dt>
			<dd>
				<?php
                if ($num_transcripts == 0) {
                    echo "<span class='disabled'>Disabled prior to transcripts import</span>\n";
                } else {
                    echo $this->Html->link("Copy transcripts to new experiment",
                        array("controller" => "trapid", "action" => "clone_experiment", $exp_id));
                }
                ?>
			</dd>
		-->
            </dl>
        </div>
        <br/>
        <br/>


        <?php
        if ($standard_experiment_info['Experiments']['process_state'] == "upload" || isset($admin)) {
            echo "<h2>Initial processing</h2>\n";
            echo "<div class='subdiv'>\n";
            echo "<dl class='standard'>\n";
            if ($standard_experiment_info['Experiments']['process_state'] != "upload") {
                echo "<dt><span style='color:red'>Override</span></dt>\n";
                echo "<dd><span style='color:red'>Experiment is not in upload state. Override at own risk</span></dd>\n";
            }
            echo "<dt>Process</dt>\n";
            echo "<dd>";
            echo $this->Html->link("Perform transcript processing",
                array("controller" => "trapid", "action" => "initial_processing", $exp_id));
            echo "</dd>\n";
            echo "</dl>\n";
            echo "</div><br/>\n";
        }
        ?>

        <?php
        /*
         * Here we add a (pre)processing link which will initiate a precomputation of the GO enrichments for all defined labels.
         * This should only be visible on several conditions:
         * 0) Reference DB is PLAZA (no GO for orthomcldb)
         * 1) The experiment is in 'finished' state
         * 2) There is at least 1 label defined (--> should be smaller than total dataset, but we won't check for that here).
         * 3) The last_edit_date is younger than the go_enrichment_date (or go_enrichment_date is default). --> not necessary. Yeah, users can overcompensate, but hey.
         * 4) The variable go_enrichment_state is not set to 'processing'
         *
         * The GO enrichment is stored in 1 table, but extra info (go_enrichment_date and go_enrichment_state) are stored in the experiments table.
         */
        if ($standard_experiment_info['Experiments']['process_state'] == "finished" && $num_subsets > 0) {    //(1) and (2)
            if ($standard_experiment_info['Experiments']['enrichment_state'] != 'processing') {    //(4)
                echo "<h2>GO enrichment preprocessing</h2>\n";
                echo "<div class='subdiv'>\n";
                echo "<dl class='standard'>\n";
                echo "<dt>Process</dt>\n";
                echo "<dd>";
                $link_text = "Perform GO enrichment preprocessing for " . $num_subsets . " labels";
                if ($standard_experiment_info['Experiments']['enrichment_state'] == "finished") {
                    $link_text = "Rerun GO enrichment preprocessing for " . $num_subsets . " labels";
                }
                echo $this->Html->link($link_text,
                    array("controller" => "trapid", "action" => "enrichment_preprocessing", $exp_id));
                echo "</dd>\n";
                echo "</dl>\n";
                echo "</div>\n";
            }
        }


        ?>
        <div class="alert alert-warning" role="alert">Example warning message!</div>
        <div class="alert alert-danger" role="alert">Example warning message!</div>
        <div class="alert alert-success" role="alert">Example warning message!</div>
        <div class="alert alert-info" role="alert">Example warning message!</div>


        <h2>Toolbox</h2>
        <div class="subdiv">
            <?php

            if ($num_transcripts == 0) {
                echo "<span class='disabled'>Disabled prior to transcripts import and processing</span>\n";
            } else {
                $disable_cluster_tools = false;
                if (isset($max_number_jobs_reached)) {
                    echo "<span class='error'>The maximum number of jobs (" . MAX_CLUSTER_JOBS . ") you can have queued has been reached for this experiment.<br/>Some tools will be unavailable until the currently scheduled jobs have finished or have been deleted.</span><br/><br/>\n";
                    $disable_cluster_tools = true;
                }

                $subset1 = true;
                if ($num_subsets > 0) {
                    $subset1 = false;
                }
                $subset2 = true;
                if ($num_subsets > 1) {
                    $subset2 = false;
                }

                if ($num_subsets == 0) {
                    echo "<span class='warning'>No subsets have been defined. Several options from the 'Explore' section in the toolbox have been disabled</span><br/><br/>\n";
                }


                $toolbox = array("Statistics" => array(
                    array(
                        "General statistics",
                        $this->Html->url(array("controller" => "tools", "action" => "statistics", $exp_id)),
                        "some_image.png"
                    ),
                    array(
                        "Length distribution transcript sequences",
                        $this->Html->url(array("controller" => "tools", "action" => "length_distribution", $exp_id, "transcript")),
                        "some_image.png"
                    ),
                    array(
                        "Length distribution ORF sequences",
                        $this->Html->url(array("controller" => "tools", "action" => "length_distribution", $exp_id, "orf")),
                        "some_image.png"
                    )
                ),
                    "Explore" => array(
                        array(
                            "GO enrichment from a subset compared to background",
                            $this->Html->url(array("controller" => "tools", "action" => "enrichment", $exp_id, "go")),
                            "other_image.png",
                            $subset1 || $disable_cluster_tools
                        ),
                        array(
                            "GO ratios between subsets (table)",
                            $this->Html->url(array("controller" => "tools", "action" => "compare_ratios", $exp_id, "go")),
                            "other_image.png",
                            $subset2
                        ),
                        array(
                            "GO ratios between subsets (chart)",
                            $this->Html->url(array("controller" => "tools", "action" => "compare_ratios_chart", $exp_id, "go")),
                            "other_image.png",
                            $subset2
                        ),
                        array(
                            "Protein domain enrichment from a subset compared to background",
                            $this->Html->url(array("controller" => "tools", "action" => "enrichment", $exp_id, "ipr")),
                            "other_image.png",
                            $subset1 || $disable_cluster_tools
                        ),

                        array(
                            "Protein domain ratios between subsets",
                            $this->Html->url(array("controller" => "tools", "action" => "compare_ratios", $exp_id, "ipr")),
                            "other_image.png",
                            $subset2
                        ),
                        array(
                            "Different subsets",
                            $this->Html->url(array("controller" => "labels", "action" => "subset_overview", $exp_id)),
                            "some_image.png",
                            $subset1
                        )
                    ),
                    "Browse" => array(
                        array(
                            "Gene families",
                            $this->Html->url(array("controller" => "gene_family", "action" => "index", $exp_id)),
                            "other_image.png"
                        )
                    ),
                    "Sankeys" => array(
                        array(
                            "Label →  Enriched GO →  gene family [Improved]",
                            $this->Html->url(array("controller" => "tools", "action" => "label_enrichedgo_gf2", $exp_id)),
                            "some_image.png",
                            $subset1
                        ),
                        array(
                            "Label →  Enriched Interpro →  gene family [Improved]",
                            $this->Html->url(array("controller" => "tools", "action" => "label_enrichedinterpro_gf2", $exp_id)),
                            "some_image.png",
                            $subset1
                        )
                    ),
                    "Find" => array(
                        array(
                            "Expanded/depleted gene families",
                            $this->Html->url(array("controller" => "gene_family", "action" => "expansion", $exp_id)),
                            "image.png"
                        )
                    )
                );
                $this->set("toolbox", $toolbox);
                echo $this->element("toolbox");
            }
            ?>
        </div>
        <br/>
<?php endif ?>

    </div>
</div>


<?php // echo $this->element('sql_dump');  // Dump all MySQL queries (debug) ?>