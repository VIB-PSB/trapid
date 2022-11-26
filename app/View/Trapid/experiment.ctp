<div class="page-header">
    <h1 class="text-primary">
        Overview <small>TRAPID experiment</small>
    </h1>
</div>

<section class="page-section-sm">
    <div class='row'>
        <div class='col-lg-12'>
            <div class="panel panel-slim panel-primary">
                <div class="panel-heading">Experiment information</div>
                <ul class="list-group">
                    <li class="list-group-item">
                        <strong>Description:</strong>
                        <?php
                        if ($standard_experiment_info['Experiments']['description']) {
                            echo $standard_experiment_info['Experiments']['description'];
                        } else {
                            echo "<span class='text-muted'>No description available</span>";
                        }
                        ?>
                    </li>
                    <li class="list-group-item"><strong>Processing
                            status:</strong> <?php echo $standard_experiment_info['Experiments']['process_state']; ?></li>
                    <li class="list-group-item"><strong>Transcript
                            count:</strong> <?php echo $transcript_experiment_info[0][0]['transcript_count']; ?> -- <strong>Gene
                            family count: </strong><?php echo $transcript_experiment_info[0][0]['gf_count']; ?></li>
                    <li class="list-group-item">
                        <strong>Data source:</strong>
                        <?php if ($datasource_info['URL']) {
                                echo $this->Html->link($datasource_info['name'], $datasource_info['URL'], array('target' => '_blank', 'class' => 'linkout'));
                            } else {
                                echo $datasource_info['name'];
                            }
                        ?>
                    </li>
                    <li class="list-group-item">
                        <strong>Similarity search DB:</strong>
                        <?php echo $standard_experiment_info['Experiments']['used_blast_database']; ?>
                    </li>
                    <li class="list-group-item">
                        <strong>Created:</strong> <?php echo $standard_experiment_info['Experiments']['creation_date']; ?>
                        (last edit: <?php echo $standard_experiment_info['Experiments']['last_edit_date']; ?>)
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <?php
    echo "<p class=\"text-right\">";
    if ($standard_experiment_info['Experiments']['process_state'] == "upload" || isset($admin)) {
        echo $this->Html->link(
            "<span class=\"glyphicon glyphicon-chevron-right\"></span> Process transcripts",
            array("controller" => "trapid", "action" => "initial_processing", $exp_id),
            array("class" => "btn btn-primary", "escape" => false, "title" => "Perform transcript initial processing")
        );
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
            echo $this->Html->link(
                $link_text,
                array("controller" => "trapid", "action" => "enrichment_preprocessing", $exp_id),
                array("class" => "btn btn-primary", "style" => "margin-left: 10px;", "escape" => false, "title" => "Perform functional enrichment preprocessing")
            );
        }
    }
    if ($standard_experiment_info['Experiments']['process_state'] != "upload" && isset($admin)) {
        echo "<br><span class='text-danger'><strong>Warning:</strong> Experiment is not in upload state. Override at own risk.</span>\n";
    }
    echo "</p>";
    ?>
</section>
<hr>
<section class="page-section-sm">
    <h2 id="transcripts">Transcripts</h2>
    <?php if ($num_transcripts == 0) : ?>
        <p class="lead text-muted">Disabled prior to transcripts import.
            <?php echo $this->Html->link("Import transcripts", ["controller" => "trapid", "action" => "import_data", $exp_id]); ?> to get started.
        </p>
    <?php else : ?>
        <?php
        //$this->Paginator->options(array("url"=>$this->passedArgs));
        $this->Paginator->options(array("url" => array("controller" => "trapid", "action" => "experiment", $exp_id, "#" => "transcripts")));

        // Functional annotation types that are displayed in the table: by default, all types are shown.
        // If `$exp_info` is set, use the list of functional annotation types defined there.
        $function_types = ['go', 'interpro', 'ko'];
        if (isset($exp_info)) {
            $function_types = $exp_info['function_types'];
        }

        $function_headers = array("go" => "GO annotation", "interpro" => "Protein domain annotation", "ko" => "KO annotation");
        ?>

        <table class="table table-striped table-bordered table-condensed table-hover small" id="transcripts_table">
            <thead>
                <th style="width:10%">Transcript</th>
                <th style="width:15%">Gene family</th>
                <?php foreach ($function_types as $ft) : ?>
                    <th><?php echo $function_headers[$ft]; ?></th>
                <?php endforeach; ?>
                <th style="width:10%">Subset</th>
                <th style="width:10%">Meta annotation</th>
            </thead>
            <tbody>
                <?php
                $bad_status = "Unassigned";
                $max_items = 3;
                foreach ($transcript_data as $transcript_dat) {

                    $td = $transcript_dat['Transcripts'];
                    $this->Paginator->options(array("url" => array("controller" => "trapid", "action" => "experiment", $exp_id, "#" => "transcripts")));
                    echo "<tr>";

                    // Transcript Id
                    echo "<td>" . $this->Html->link(
                        $td['transcript_id'],
                        array("action" => "transcript", $exp_id, urlencode($td['transcript_id']))
                    ) . "</td>";


                    // GF Id
                    if ($td['gf_id']) {
                        echo "<td>";
                        echo $this->Html->link(
                            $td['gf_id'],
                            array("controller" => "gene_family", "action" => "gene_family", $exp_id, urlencode($td['gf_id']))
                        );
                        echo "</td>\n";
                    } else {
                        echo "<td class='text-muted'>";
                        echo $bad_status;
                        echo "</td>\n";
                    }


                    // GO annotation
                    if (in_array("go", $function_types)) {
                        if (!array_key_exists($td['transcript_id'], $transcripts_go)) {
                            echo "<td class='text-muted'>Unavailable</td>";
                        } else {
                            $n_trs_go = count($transcripts_go[$td['transcript_id']]);
                            echo "<td>";
                            echo "<ul class='table-items'>";
                            for ($i = 0; $i < $n_trs_go && $i < $max_items; $i++) {
                                $go = $transcripts_go[$td['transcript_id']][$i];
                                $go_web = str_replace(":", "-", $go);
                                $desc = $go_info[$go]['desc'];
                                $type = $go_info[$go]['type'];
                                echo "<li>";
                                echo $this->Html->link($desc, array("controller" => "functional_annotation", "action" => "go", $exp_id, $go_web));
                                echo " " . $this->element("go_category_badge", array("go_category" => $type, "small_badge" => true, "no_color" => false));
                                // If there are more items to show and this is the last item of the array to print,
                                // add 'more' label to the item
                                if (($i == $max_items - 1) && ($n_trs_go > $max_items)) {
                                    echo $this->element("table_more_label", array("trs_data" => $transcripts_go[$td['transcript_id']], "data_desc" => $go_info, "data_type" => "go", "data_offset" => $max_items));
                                }
                                echo "</li>";
                            }
                            echo "</ul>";
                            echo "</td>";
                        }
                    }

                    // InterPro annotation
                    if (in_array("interpro", $function_types)) {
                        if (!array_key_exists($td['transcript_id'], $transcripts_ipr)) {
                            echo "<td class='text-muted'>Unavailable</td>";
                        } else {
                            $n_trs_ipr = count($transcripts_ipr[$td['transcript_id']]);
                            echo "<td>";
                            echo "<ul class='table-items'>";
                            for ($i = 0; $i < $n_trs_ipr && $i < $max_items; $i++) {
                                $ipr = $transcripts_ipr[$td['transcript_id']][$i];
                                $desc = $ipr_info[$ipr]['desc'];
                                echo "<li>";
                                echo $this->Html->link($desc, array("controller" => "functional_annotation", "action" => "interpro", $exp_id, $ipr));
                                // If there are more items to show and this is the last item of the array to print,
                                // add 'more' label to the item
                                if (($i == $max_items - 1) && ($n_trs_ipr > $max_items)) {
                                    echo $this->element("table_more_label", array("trs_data" => $transcripts_ipr[$td['transcript_id']], "data_desc" => $ipr_info, "data_type" => "ipr", "data_offset" => $max_items));
                                }
                                echo "</li>";
                            }
                            echo "</ul>";
                            echo "</td>";
                        }
                    }

                    // KO annotation
                    if (in_array("ko", $function_types)) {
                        if (!array_key_exists($td['transcript_id'], $transcripts_ko)) {
                            echo "<td class='text-muted'>Unavailable</td>";
                        } else {
                            $n_trs_ko = count($transcripts_ko[$td['transcript_id']]);
                            echo "<td>";
                            echo "<ul class='table-items'>";
                            for ($i = 0; $i < $n_trs_ko && $i < $max_items; $i++) {
                                $ko = $transcripts_ko[$td['transcript_id']][$i];
                                $desc = $ko_info[$ko]['desc'];
                                echo "<li>";
                                echo $this->Html->link($desc, array("controller" => "functional_annotation", "action" => "ko", $exp_id, $ko));
                                if (($i == $max_items - 1) && ($n_trs_ko > $max_items)) {
                                    echo $this->element("table_more_label", array("trs_data" => $transcripts_ko[$td['transcript_id']], "data_desc" => $ko_info, "data_type" => "ko", "data_offset" => $max_items));
                                }
                                echo "</li>";
                            }
                            echo "</ul>";
                            echo "</td>";
                        }
                    }

                    // Subset (label)
                    if (!array_key_exists($td['transcript_id'], $transcripts_labels)) {
                        echo "<td class='text-muted'>Unavailable</span></td>";
                    } else {
                        $n_trs_sub = count($transcripts_labels[$td['transcript_id']]);
                        echo "<td>";
                        echo "<ul class='table-items'>";
                        for ($i = 0; $i < $n_trs_sub && $i < $max_items; $i++) {
                            $label = $transcripts_labels[$td['transcript_id']][$i];
                            echo "<li>";
                            echo $this->Html->link($label, array("controller" => "labels", "action" => "view", $exp_id, urlencode($label)));
                            // If there are more items to show and this is the last item of the array to print,
                            // add 'more' label to the item
                            if (($i == $max_items - 1) && ($n_trs_sub > $max_items)) {
                                echo $this->element("table_more_label", array("trs_data" => $transcripts_labels[$td['transcript_id']], "data_type" => "subset", "data_offset" => $max_items));
                            }
                            echo "</li>";
                        }
                        echo "</ul>";
                        echo "</td>";
                    }

                    // Meta-annotation
                    echo "<td>" . $this->Html->link($td['meta_annotation'], array("controller" => "trapid", "action" => "transcript_selection", $exp_id, "meta_annotation", urlencode($td['meta_annotation']))) . "</td>";

                    echo "</tr>\n";
                }
                ?>
            </tbody>
        </table>
        <div class="text-right">
            <div class='pagination pagination-sm no-margin-top'>
                <?php
                echo $this->Paginator->prev(__('Previous'), array('tag' => 'li'), null, array('tag' => 'li', 'class' => 'disabled', 'disabledTag' => 'a'));
                echo $this->Paginator->numbers(array('separator' => '', 'currentTag' => 'a', 'currentClass' => 'active', 'tag' => 'li', 'first' => 1));
                echo $this->Paginator->next(__('Next'), array('tag' => 'li', 'currentClass' => 'disabled'), null, array('tag' => 'li', 'class' => 'disabled', 'disabledTag' => 'a'));
                ?>
            </div>
        </div>
        <script type="text/javascript">
            $('[data-toggle="popover"]').popover({
                placement: 'bottom',
                content: function() {
                    return $(this).children(".table-more-content:first").html();
                },
                //        placement: 'right',
                template: '<div class="popover"><div class="arrow"></div><div class="popover-content"></div></div>',
                html: true,
                delay: 50
            });
        </script>
    <?php endif; ?>
</section>

<?php if (isset($admin)) : ?>
    <hr>
    <h2 class="text-danger">Experimental zone <small>admin</small></h2>
    <section class="page-section-sm">
        <!-- Legacy experiment overview / search -->
        <h3>Experiment information</h3>
        <div class="well">
            <div class="row">
                <div class="col-md-6 col-12">
                    <dl>
                        <dt>Name</dt>
                        <dd><?php echo $standard_experiment_info['Experiments']['title']; ?></dd>
                        <dt>Description</dt>
                        <dd>
                            <div>
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
                <div class="col-md-6 col-12">
                    <dl>
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
                            echo $this->Html->link(
                                "Empty experiment",
                                array("controller" => "trapid", "action" => "empty_experiment", $exp_id),
                                array("style" => "color:#AA0055;font-weight:bold;"),
                                "Are you sure you want to delete all content from this experiment?"
                            );
                            echo "&nbsp;/&nbsp;";
                            echo $this->Html->link(
                                "Delete experiment",
                                array("controller" => "trapid", "action" => "delete_experiment", $exp_id),
                                array("style" => "color:red;font-weight:bold;"),
                                "Are you sure you want to delete the experiment?"
                            );
                            ?>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </section>
    <section class="page-section-sm">
        <h3>Import/Export</h3>
        <dl>
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
        </dl>
    </section>
    <section class="page-section-sm">
        <?php
        if ($standard_experiment_info['Experiments']['process_state'] == "upload" || isset($admin)) {
            echo "<h3>Initial processing</h3>\n";
            echo "<dl>\n";
            if ($standard_experiment_info['Experiments']['process_state'] != "upload") {
                echo "<dt><span style='color:red'>Override</span></dt>\n";
                echo "<dd><span style='color:red'>Experiment is not in upload state. Override at own risk</span></dd>\n";
            }
            echo "<dt>Process</dt>\n";
            echo "<dd>";
            echo $this->Html->link(
                "Perform transcript processing",
                array("controller" => "trapid", "action" => "initial_processing", $exp_id)
            );
            echo "</dd>\n";
            echo "</dl>\n";
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
                echo "<h3>GO enrichment preprocessing</h3>\n";
                echo "<dl>\n";
                echo "<dt>Process</dt>\n";
                echo "<dd>";
                $link_text = "Perform GO enrichment preprocessing for " . $num_subsets . " labels";
                if ($standard_experiment_info['Experiments']['enrichment_state'] == "finished") {
                    $link_text = "Rerun GO enrichment preprocessing for " . $num_subsets . " labels";
                }
                echo $this->Html->link(
                    $link_text,
                    array("controller" => "trapid", "action" => "enrichment_preprocessing", $exp_id)
                );
                echo "</dd>\n";
                echo "</dl>\n";
            }
        }
        ?>
    </section>
    <section class="page-section-sm">
        <h3>Toolbox</h3>
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


            $toolbox = array(
                "Statistics" => array(
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
    </section>
    <section class="page-section-sm">
        <?php // echo $this->element("search_element");?>	
    </section>
<?php endif; ?>
