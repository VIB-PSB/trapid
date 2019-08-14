    <div class="page-header">
        <h1 class="text-primary"><?php echo $transcript_info['transcript_id']; ?> <small>transcript</small></h1>
    </div>

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
    $toolbox = array(
//            "Structural data" => array(
//        array(
//            "Correct frameshifts with FrameDP",
//            $this->Html->url(array("controller" => "tools", "action" => "framedp", $exp_id, $transcript_info['gf_id'], $transcript_info['transcript_id'])),
//            "some_image.png",
//            $disabled_framedp || $disable_cluster_tools
//        ),
//    ),
        "Similarity search" => array(
            array(
                "Browse similarity search output",
                $this->Html->url(array("controller" => "trapid", "action" => "similarity_hits", $exp_id, urlencode($transcript_info['transcript_id']))),
                "some_image.png"
            )
        ),
        "RNA similarity search" => array(
            array(
                "Browse RNA similarity search output (Infernal)",
                $this->Html->url(array("controller" => "trapid", "action" => "rna_similarity_hits", $exp_id, $transcript_info['transcript_id'])),
                "some_image.png",
                !$transcript_info['is_rna_gene'] // Cannot be clicked if the transcript wasn't flag as RNA gene
            )
        )
    );
    $this->set("toolbox", $toolbox);
    // echo $this->element("toolbox");
    ?>


        <div class="row">
            <div class="col-md-9 col-lg-10" id="transcript-data-col">
                <section class="page-section-sm">
                    <a class="fixed-header-anchor" id="gf-rf-data"></a>
                    <h3>Gene / RNA family</h3>
                    <div class="row">
                        <div class="col-md-2 col-xs-4 transcript-attr">Gene family</div>
                        <div class="col-md-10 col-xs-8">
                            <?php
                            if ($transcript_info['gf_id'] != "") {
                                echo $this->Html->link($transcript_info['gf_id'], array("controller" => "gene_family", "action" => "gene_family", $exp_id, $transcript_info['gf_id']));
                            } else {
                                echo "Unavailable\n";
                            }
                            if ($exp_info['genefamily_type'] == "HOM" && $transcript_info['full_frame_info']) {
                                // Link is displayed only if correct GF type + there is any similarity search hit
                                echo $this->Html->link("<span class='glyphicon glyphicon-edit'></span> change gene family", array("controller" => "trapid", "action" => "similarity_hits", $exp_id, $transcript_info['transcript_id']),
                                    array("escape"=>false, "class"=>"btn btn-xs btn-default pull-right", "title"=>"Browse similarity search results to change gene family"));
                            }
                            else {
                                // Should that be displayed at all? Mayebe confusing for users...
                                echo $this->Html->link("<span class='glyphicon glyphicon-edit'></span> change gene family", "#",
                                    array("escape"=>false, "class"=>"btn btn-xs btn-default pull-right disabled", "title"=>"Browse similarity search results to change gene family"));
                            }
                            ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2 col-xs-4 transcript-attr">RNA family</div>
                        <div class="col-md-10 col-xs-8">
                            <?php if ($transcript_info['rf_ids'] != "") {
                                echo $this->Html->link($transcript_info['rf_ids'], array("controller" => "rna_family", "action" => "rna_family", $exp_id, $transcript_info['rf_ids']));
                            } else {
                                echo "Unavailable\n";
                            }
                            ?>
                        </div>
                    </div>
                <hr>
                </section> <!-- end 'gf-rf-data' section -->


                <section class="page-section-sm">
                    <a class="fixed-header-anchor" id="tax-data"></a>
                    <h3>Taxonomic classification</h3>
                    <?php if($exp_info['perform_tax_binning'] == 1): ?>
                        <div class="row">
                            <div class="col-md-2 col-xs-4 transcript-attr">Tax. identifier</div>
                            <div class="col-md-10 col-xs-8">
                                <?php
                                if($transcript_txid == 0) {
                                    echo $transcript_txname;
                                }
                                else {
                                    // Linkout to NCBI taxonomy
                                    $ncbi_linkout_prefix = "https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=";
                                    echo "<a href=\"" . $ncbi_linkout_prefix . $transcript_txid . "\" target=\"_blank\" class=\"linkout\" title=\"View on NCBI Taxonomy\">" . $transcript_txname . " [" . $transcript_txid. "]</a>";
                                }
                                ?>
                            </div>
                        </div>
                    <div class="row">
                        <div class="col-md-2 col-xs-4 transcript-attr">Tax. lineage</div>
                        <div class="col-md-10 col-xs-8">
                            <?php
                            if(!$transcript_lineage) {
                                echo "None";
                            }
                            else {
                                echo "<ol class='tax-lineage'>\n";
                                if(sizeof($transcript_lineage) <= 12) {
                                    foreach($transcript_lineage as $tl) {
                                        echo "<li>" . $tl . "</li>";
                                    }
                                }
                                else {
                                    $n_show = 4; // Number of first/last clades to show.
                                    $first_clades = array_slice($transcript_lineage, 0, $n_show);
                                    $last_clades = array_slice($transcript_lineage, -$n_show, $n_show);
                                    $intermediate_clades = array_slice($transcript_lineage, $n_show, sizeof($transcript_lineage) - $n_show);
                                    foreach($first_clades as $clade) {
                                        echo "<li>" . $clade . "</li>";
                                    }
                                    foreach($intermediate_clades as $clade) {
                                        echo "<li class='tax-intermediate hidden'>" . $clade . "</li>";
                                    }
                                    echo "<li id='show-clades'><label title='Show all intermediate clades (" . sizeof($intermediate_clades) . ")' class='label label-default toggle-clades' style='cursor:col-resize;'>...</label></li>";
                                    foreach($last_clades as $clade) {
                                        echo "<li>" . $clade . "</li>";
                                    }
                                }
                                echo "&nbsp;&nbsp;<label id='hide-clades' title='Hide intermediate clades' class='label label-default toggle-clades hidden' style='cursor:w-resize;'><<</label>";
                                echo "</ol>\n";
                            }
                            ?>
                        </div>
                    </div>

                    <?php else: ?>
                    <p class="text-justify text-muted">No taxonomic classification was performed during initial processing. </p>
                    <?php endif; ?>
                <hr>
                </section>


                <section class="page-section-sm">
                    <a class="fixed-header-anchor" id="sqce-data"></a>

                    <h3>Sequences</h3>
                        <?php
                        if ($transcript_info['putative_frameshift'] == 1) {
                            $is_corrected = ($transcript_info['is_frame_corrected'] == 1);
                            $style1 = " style='color:orange' ";
                            if ($is_corrected) {
                                $style1 = " style='color:blue' ";
                            }
                            echo "<div class='row'>\n";
                            echo "<div class='col-md-2 col-xs-4 transcript-attr'>Frameshift</div>\n";
                            echo "<div class=\"col-md-10 col-xs-8\">\n";
                            echo "<ul class='list-unstyled'>";
                            echo "<li $style1>A putative frameshift was detected in this sequence</li>";
                            if ($transcript_info['is_frame_corrected'] == 1) {
                                echo "<li style='color:green'>A putative frameshift was corrected with FrameDP</li>";
                            }
                            echo "</ul>\n";
                            echo "</div>\n";
                            echo "</div>\n";
                        }
                        ?>

                    <a class="fixed-header-anchor" id="transcript-sqce"></a>
                    <div class="panel panel-default container-fluid">
                        <div class="panel-heading row">
                            <h3 class="panel-title">Uploaded sequence</h3>
                        </div>
                        <div class="panel-body">
                            <div class="textarea-wrapper">
                            <textarea readonly class='fixed-width-text textarea-sqce'
                                      name="transcript_sequence" id="transcript_sequence"><?php echo $transcript_info['transcript_sequence']; ?></textarea>
                            </div>
                        </div>
                        <div class="panel-footer row">
                            <strong>Length: </strong> <?php echo strlen($transcript_info['transcript_sequence']); ?> nt
                            <span class="pull-right">
                        <button class='btn btn-default btn-xs' id="copy_transcript_sequence"><span class="glyphicon glyphicon-copy"></span> Copy to clipboard</button>
                    </span>
                        </div>
                    </div>

<!--
                    <dl class="standard dl-horizontal" id="corrected-sqce">
                        <dt>Frameshift corrected<br>sequence</dt>
                        <dd>
                            <div>
                                <?php
/*                                if ($transcript_info['transcript_sequence_corrected'] != "") {
                                    echo $this->Form->create(false, array("url"=>array("controller"=>"trapid", "action" => "transcript", $exp_id, $transcript_info['transcript_id']), "type" => "post"));
                                    echo "<div class=\"textarea-wrapper\">";
                                    echo "<textarea class='fixed-width-text' cols='80' rows='5'  name='corrected_sequence' id='corrected_sequence'>" . $transcript_info['transcript_sequence_corrected'] . "</textarea>\n";
                                    echo "<br><div class='clipboard-copy' id=\"copy_corrected_sequence\">Copy to clipboard</div>";
                                    echo "</div>";
                                    echo "<br/>\n";
                                    echo "<span>Length: " . strlen($transcript_info['transcript_sequence_corrected']) . " nt</span>\n";
                                    echo "<br/>\n";
                                    echo "<input type='submit' class='btn btn-sm btn-default' value='Store changed corrected sequence' />\n";
                                    echo "</form>\n";
                                } else {
                                    echo "<span class='disabled'>Unavailable</span>\n";
                                }
                                */?>
                            </div>
                        </dd>

                    </dl>-->

                    <a class="fixed-header-anchor" id="orf-sqce"></a>
                    <div class="panel panel-default container-fluid">
                        <div class="panel-heading row">
                            <h3 class="panel-title">ORF sequence</h3>
                        </div>
                        <?php if($transcript_info['orf_sequence'] != ""): ?>
                            <?php echo $this->Form->create(false, array("url"=>array("controller"=>"trapid", "action" => "transcript", $exp_id, $transcript_info['transcript_id'], "#"=>"sqce-data"), "type" => "post")); ?>
                            <div class="panel-body">
                                <div class="textarea-wrapper">
                                    <textarea class='fixed-width-text textarea-sqce' name="orf_sequence" id="orf_sequence"><?php echo $transcript_info['orf_sequence']; ?></textarea>
                                </div>
                            </div>
                            <div class="panel-footer row row-no-padding">
                                <div class="col-sm-8">
                                    <strong>Length: </strong> <?php echo strlen($transcript_info['orf_sequence']) . " nt"; ?>
                                    --
                                    <strong>Frame & strand: </strong>
                                    <?php echo "<span title='Detected frame and strand' class='badge'>" .  $transcript_info['detected_frame'] . " / ". $transcript_info['detected_strand'] . "</span>\n"; ?>
                                    --
                                    <strong>Translation table:</strong> <?php echo "<span title='Translation table used to translate ORF sequence' class='badge'>" . $transcript_info['transl_table'] . "</span>\n"; ?> <br>
                                    <strong>Starts with a start codon: </strong>
                                    <?php
                                    // Use `label-*` instead?
                                    if ($transcript_info['orf_contains_start_codon'] == 1) {
                                        // echo "<label title='The ORF sequence starts with a start codon' class='label label-success'>yes</label>\n";
                                        echo "<label title='The ORF sequence starts with a start codon' class='text-success'>yes</label>\n";
                                    } else {
                                        // echo "<label title='The ORF sequence does not start with a start codon'  class='label label-danger'>no</label>\n";
                                        echo "<label title='The ORF sequence does not start with a start codon'  class='text-danger'>no</label>\n";
                                    }
                                    ?>
                                    --
                                    <strong>Ends with a stop codon: </strong>
                                    <?php
                                    // Use `label-*` instead?
                                    if ($transcript_info['orf_contains_stop_codon'] == 1) {
                                        // echo "<label title='The ORF sequence ends with a stop codon' class='label label-success'>yes</label>\n";
                                        echo "<label title='The ORF sequence ends with a stop codon' class='text-success'>yes</label>\n";
                                    } else {
                                        // echo "<label title='The ORF sequence does not end with a stop codon'  class='label label-danger'>no</label>\n";
                                        echo "<label title='The ORF sequence does not end with a stop codon'  class='text-danger'>no</label>\n";
                                    }
                                    ?>
                                </div>
                                <div class="col-sm-4">
                                    <a class='btn btn-default btn-xs pull-right' id="copy_orf_nt_sequence"><span class="glyphicon glyphicon-copy"></span> Copy to clipboard</a><br>
                                    <button type='submit' class='btn btn-xs btn-default pull-right' id='change-orf-btn' disabled><span class='glyphicon glyphicon-edit'></span> Change ORF sequence</button>
                                </div>
                                <?php echo $this->Form->end(); ?>
                            </div>
                        <?php else: ?>
                            <div class="panel-body">
                                <p class="text-justify text-muted"><strong>No ORF sequence available.</strong></p>
                            </div>
                        <?php endif; ?>
                    </div>


                    <a class="fixed-header-anchor" id="protein-sqce"></a>
                    <div class="panel panel-default container-fluid">
                        <div class="panel-heading row">
                            <h3 class="panel-title">Protein sequence (translated ORF)</h3>
                        </div>
                        <?php if($transcript_info['orf_sequence'] != ""): ?>
                            <div class="panel-body">
                                <div class="textarea-wrapper">
                                    <textarea readonly class='fixed-width-text textarea-sqce-aa' name="aa_sequence" id="aa_sequence"><?php echo $transcript_info['aa_sequence']; ?></textarea>
                                </div>
                            </div>
                            <div class="panel-footer row row-no-padding">
                                <div class="col-sm-8">
                                    <strong>Length: </strong> <?php echo number_format(strlen($transcript_info['orf_sequence']) / 3, 0) . " aa"; ?>
                                </div>
                                <div class="col-sm-4">
                                    <a class='btn btn-default btn-xs pull-right' id="copy_orf_aa_sequence"><span class="glyphicon glyphicon-copy"></span> Copy to clipboard</a><br>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="panel-body">
                                <p class="text-justify text-muted"><strong>No ORF sequence available.</strong></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <hr>
                </section> <!-- end 'sqce-data' section -->


                <section class="page-section-sm">
                    <a class="fixed-header-anchor" id="meta-data"></a>
                    <h3>Meta-annotation</h3>

                    <div class="row">
                        <div class="col-md-2 col-xs-4 transcript-attr">Meta-annotation</div>
                        <div class="col-md-10 col-xs-8">
                            <?php
                            $possible_meta = array("No Information" => array("color" => "#bbbbbb", "css-class"=>"meta-ni"),
                                "Partial" => array("color" => "#33638d", "css-class"=>"meta-p"),
                                "Quasi Full Length" => array("color" => "#1f968b",  "css-class"=>"meta-qfl"),
                                "Full Length" => array("color" => "#73d055", "css-class"=>"meta-fl")
                            );
                            echo $this->Form->create(false, array("url"=>array("controller"=>"trapid", "action" => "transcript", $exp_id, $transcript_info['transcript_id'], "#"=>"meta-data"), "type" => "post", "class"=>"form-inline"));
                            ?>
                            <div class="form-group">
                                <select name='meta_annotation' id="meta_annotation" class='form-control'>
                                    <?php
                                    foreach ($possible_meta as $pm => $pm_data) {
                                        $sel = null;
                                        if ($pm == $transcript_info['meta_annotation']) {
                                            $sel = " selected='selected' ";
                                        }
                                        echo "<option value='" . $pm . "' $sel>" . $pm . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            &nbsp;<span class="meta-square <?php echo $possible_meta[$transcript_info['meta_annotation']]['css-class']; ?>"></span>
                            &nbsp;&nbsp;<button type='submit' class='btn btn-xs btn-default pull-right' id='change-meta-btn' disabled><span class='glyphicon glyphicon-edit'></span> Change meta-annotation</button>
                            <?php echo $this->Form->end();?>
                        </div>
                    </div>
                <hr>
                </section> <!-- end 'meta-data' section -->


                <section class="page-section-sm">
                    <a class="fixed-header-anchor" id="functional-data"></a>
                    <h3>Functional annotation</h3>
                        <section class="page-section-xs">
                            <a class="fixed-header-anchor" id="funct-go"></a>
                            <h4>Gene Ontology</h4>
                            <?php
                            if ($associated_go) {
                                // Create three GO arrays (one for each GO ontology)
                                $go_terms_bp = array();  // Biological process
                                $go_terms_mf = array();  // Molecular function
                                $go_terms_cc = array();  // Cellular component
                                // Loop over found GO terms to map them to their ontologies
                                foreach ($associated_go as $ag) {
                                    $go_id = $ag['TranscriptsGo']['name'];
                                    $is_hidden = $ag['TranscriptsGo']['is_hidden'];
                                    $go_data = array("go_id"=>$go_id, "is_hidden"=>$is_hidden);
                                    switch ($go_info[$go_id]['type']) {
                                        case "BP":
                                            $go_terms_bp[] = $go_data;
                                            break;
                                        case "MF":
                                            $go_terms_mf[] = $go_data;
                                            break;
                                        case "CC":
                                            $go_terms_cc[] = $go_data;
                                            break;
                                        default:
                                            break;
                                    }
                                }
                                $all_gos = array(
                                    "BP"=>array("title"=>"Biological process", "go_terms"=>$go_terms_bp),
                                    "MF"=>array("title"=>"Molecular function", "go_terms"=>$go_terms_mf),
                                    "CC"=>array("title"=>"Cellular component", "go_terms"=>$go_terms_cc)
                                );
                                // Collapsed/all GO choice
                                echo "<ul class='tabbed_header list-unstyled list-inline'>\n";
                                echo "<strong>Show: </strong>";
                                echo "<li id='tab_collapsed' class='selected_tab'>";
                                echo "<a href=\"javascript:switch_go_display('tab_collapsed','div_collapsed');\">Collapsed GO data</a> ";
                                echo "</li>\n";
                                echo "<li id='tab_all'>";
                                echo "<a href=\"javascript:switch_go_display('tab_all','div_all');\">All GO data</a> &nbsp;";
                                echo "</li>\n";
                                echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips["transcript_go_collapsed"], "tooltip_placement"=>"right", "override_span_class"=>"glyphicon glyphicon-question-sign"));
                                echo "</ul>\n";

                                // Collapsed GOs div
                                echo "<div class='tabbed_div selected_tabbed_div' id='div_collapsed'>\n";
                                foreach($all_gos as $go_cat_id => $go_category) {
                                    if(!empty($go_category["go_terms"])) {
                                        echo "<h5>" . $go_category["title"] . " ";
                                        echo $this->element("go_category_badge", array("go_category"=>$go_cat_id, "small_badge"=>false));
                                        echo "</h5>";
                                        echo "<table class='table table-striped table-condensed table-bordered table-hover'>\n";
                                        echo "<thead><tr><th style='width:20%;'>GO term</th><th style='width:80%;'>Description</th></tr></thead>\n";
                                        foreach($go_category["go_terms"] as $go_term) {
                                            if ($go_term["is_hidden"] == 0) {
                                                $web_go = str_replace(":", "-", $go_term["go_id"]);
                                                echo "<tr>";
                                                echo "<td>" . $this->Html->link($go_term["go_id"], array("controller" => "functional_annotation", "action" => "go", $exp_id, $web_go)) . "</td>";
                                                echo "<td>" . $go_info[$go_term["go_id"]]['desc'] . "</td>";
                                                echo "</tr>\n";
                                            }
                                        }
                                        echo "</tbody>\n";
                                        echo "</table>\n<br>\n";
                                    }
                                }
                                echo "</div>\n";

                                // All GOs div
                                echo "<div class='tabbed_div' id='div_all'>\n";
                                foreach($all_gos as $go_cat_id => $go_category) {
                                    if(!empty($go_category["go_terms"])) {
                                        echo "<h5>" . $go_category["title"] . " ";
                                        echo $this->element("go_category_badge", array("go_category"=>$go_cat_id, "small_badge"=>false));
                                        echo "</h5>";
                                        echo "<table class='table table-striped table-condensed table-bordered table-hover'>\n";
                                        echo "<thead><tr><th style='width:20%;'>GO term</th><th style='width:80%;'>Description</th></tr></thead>\n";
                                        foreach($go_category["go_terms"] as $go_term) {
                                            $class = null;
                                            if ($go_term["is_hidden"] == 0) {
                                                $class = "class='success'";  // highlight row
                                            }
                                            $web_go = str_replace(":", "-", $go_term["go_id"]);
                                            echo "<tr " . $class . ">";
                                            echo "<td>" . $this->Html->link($go_term["go_id"], array("controller" => "functional_annotation", "action" => "go", $exp_id, $web_go)) . "</td>";
                                            echo "<td>" . $go_info[$go_term["go_id"]]['desc'] . "</td>";
                                            echo "</tr>\n";
                                        }
                                    echo "</tbody>\n";
                                    echo "</table>\n<br>\n";
                                    }
                                }

                                // Old table
                                /*                        echo "<table class='table table-striped table-condensed table-bordered table-hover'>\n";
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
                                                        echo "</tbody>\n</table>\n";*/
                                echo "</div>\n";
                            } else {
                                echo "<span class='disabled'>Unavailable</span>";
                            }
                            ?>
                        </section>

                        <section class="page-section-sm">
                            <a class="fixed-header-anchor" id="funct-ipr"></a>
                            <h4>InterPro domains</h4>
                            <dd>
                                <?php
                                if ($associated_interpro) {
                                    echo "<table class='table table-striped table-condensed table-bordered table-hover'>\n";
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
                        </section>

                        <section class="page-section-sm">
                            <a class="fixed-header-anchor" id="funct-ko"></a>
                            <h4>KEGG Orthology</h4>
                            <dd>
                                <?php
                                if ($associated_ko) {
                                    echo "<table class='table table-striped table-condensed table-bordered table-hover'>\n";
                                    echo "<thead>\n";
                                    echo "<tr><th style='width:20%;'>KO term</th><th style='width:80%;'>Description</th></tr>\n";
                                    echo "</thead>\n";
                                    echo "<tbody>\n";
                                    foreach ($associated_ko as $ko) {
                                        $ko_id = $ko['TranscriptsKo']['name'];
                                        echo "<tr>";
                                        echo "<td>" . $this->Html->link($ko_id, array("controller" => "functional_annotation", "action" => "ko", $exp_id, $ko_id)) . "</td>";
                                        echo "<td>" . $ko_info[$ko_id]['desc'] . "</td>";
                                        echo "</tr>\n";
                                    }
                                    echo "</tbody>\n";
                                    echo "</table>\n";
                                } else {
                                    echo "<span class='disabled'>Unavailable</span>";
                                }
                                ?>
                        </section>
                <hr>
                </section> <!-- end 'functional-data' section -->


                <section class="page-section-sm">
                    <a class="fixed-header-anchor" id="subset-data"></a>
                    <h3>Subset information</h3>
                     <?php if (count($transcript_subsets) == 0): ?>
                            <p class="text-justify text-muted"><strong>This transcript does not belong to any subset yet! </strong></p>
                        <?php endif; ?>

                        <?php
                        // Subset table
                        echo "<div id='all-subsets'>\n";
                        echo $this->Form->create(false, array("url" => array("controller" => "trapid", "action" => "transcript", $exp_id, $transcript_info['transcript_id'], "#"=>"subset-data"), "type" => "post"));
                        echo "<input type='hidden' name='subsets' value='subsets'/>";
                        echo "<table class='table table-striped table-bordered table-hover'>\n";
                        echo "<thead><tr><th>Included</th><th>Subset name</th><th># Transcripts</th></tr></thead>\n";
                        echo "<tbody>\n";
                        foreach ($available_subsets as $subset => $count) {
                            echo "<tr>";
                            $checked = null;
                            if (in_array($subset, $transcript_subsets)) {
                                $checked = " checked='checked' ";
                            }
                            echo "<td class='text-center'><input type='checkbox' class='subset-checkbox' name='" . $subset . "' $checked /></td>";
                            echo "<td>" . $this->Html->link($subset, array("controller" => "labels", "action" => "view", $exp_id, urlencode($subset))) . "</td>";
                            echo "<td>" . $count . "</td>";
                            echo "</tr>\n";
                        }
                        echo "<tr>";
                        echo "<td class='text-center'><input type='checkbox' name='new_subset' id='new_subset'  class='subset-checkbox'/></td>";
                        echo "<td><input type='text' maxlength=50 name='new_subset_name' id='new_subset_name' class='form-control' placeholder='Type in new subset...'/> </td>";
                        echo "<td>0</td>";
                        echo "</tr>\n";
                        echo "</tbody>\n";
                        echo "</table>\n";
                        echo "<button type='submit' id='change-subsets-btn' class='btn btn-xs btn-default pull-right' disabled><span class='glyphicon glyphicon-edit'></span> Change subset information</button>\n";
                        echo $this->Form->end();
                        echo "</div>\n";

                        ?>
                <br>
                <hr>
                </section> <!-- end 'subset-data' section -->


                <section class="page-section-sm">
                    <a class="fixed-header-anchor" id="simsearch-data"></a>
                    <h3>Similarity search</h3>

                    <div class="row">
                        <div class="col-md-2 col-xs-4 transcript-attr">Similarity search</div>
                        <div class="col-md-10 col-xs-8">
                            <?php
                            if ($transcript_info['full_frame_info']) { // Link is displayed only if the transcript had any similarity search hit
                                echo $this->Html->link("Browse transcript's similarity search output (DIAMOND)",
                                    array("controller" => "trapid", "action" => "similarity_hits", $exp_id, urlencode($transcript_info['transcript_id'])));
                            }
                            else {
                                echo "Unavailable";
                            }
                            ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2 col-xs-4 transcript-attr">RNA similarity search</div>
                        <div class="col-md-10 col-xs-8">
                            <?php
                            if ($transcript_info['is_rna_gene']) { // Link is displayed only if the transcript was flagged as RNA gene
                                echo $this->Html->link("Browse transcript's RNA similarity search output (Infernal)",
                                    array("controller" => "trapid", "action" => "rna_similarity_hits", $exp_id, $transcript_info['transcript_id']));

                            }
                            else {
                                echo "Unavailable";
                            }
                            ?>
                        </div>
                    </div>
                </section> <!-- end 'simsearch-data' section -->
            </div> <!-- End content col -->

            <div class="col-md-3 col-lg-2 hidden-sm hidden-xs">
                <nav class="scrollspy">
                    <ul class="nav transcript-nav" id="sidebar-nav" data-spy="affix">
                        <h4 style="padding-top: 15px;">
<!--                            <span class="glyphicon glyphicon-list-alt"></span>-->
                            Jump to...</h4><br>
                        <li><a href="#gf-rf-data">Gene / RNA family</a></li>
                        <li><a href="#tax-data">Tax. classification</a></li>
                        <li><a href="#sqce-data">Sequences</a>
                            <ul class="nav">
                                <li><a href="#transcript-sqce">Uploaded sequence</a></li>
<!--                                <li><a href="#corrected-sqce">Frameshift corrected sequence</a></li>-->
                                <li><a href="#orf-sqce">ORF sequence</a></li>
                                <li><a href="#protein-sqce">Protein sequence</a></li>
                            </ul>
                        </li>
                        <li><a href="#meta-data">Meta-annotation</a></li>
                        <li><a href="#functional-data">Functional annotation</a>
                            <ul class="nav">
                                <li><a href="#funct-go">Gene Ontology</a></li>
                                <li><a href="#funct-ipr">InterPro</a></li>
                                <li><a href="#funct-ko">KEGG Orthology</a></li>
                            </ul>
                        </li>
                        <li><a href="#subset-data">Subset information</a></li>
                        <li><a href="#simsearch-data">Similarity search</a></li>
                        <li class="sidebar-nav-to-top"><a href="#top">Back to top</a></li>
                    </ul>
                </nav>
            </div><!-- End navigation col -->
        </div> <!-- End row -->
    </div>

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

    /* Copy the content of `textarea` elements to clipboard */
    function copy_to_clipboard(element_id) {
        var textarea = document.getElementById(element_id);
        textarea.select();
        document.execCommand("Copy");
    }

    // Add event listeners to 'copy' elements on the page
    document.getElementById("copy_transcript_sequence").addEventListener("click", function(){ copy_to_clipboard("transcript_sequence");}, false);
    <?php if ($transcript_info['transcript_sequence_corrected'] != ""): ?>
    document.getElementById("copy_corrected_sequence").addEventListener("click", function(){ copy_to_clipboard("corrected_sequence");}, false);
    <?php endif; ?>
    <?php if  ($transcript_info['orf_sequence'] != ""): ?>
    document.getElementById("copy_orf_nt_sequence").addEventListener("click", function(){ copy_to_clipboard("orf_sequence");}, false);
    document.getElementById("copy_orf_aa_sequence").addEventListener("click", function(){ copy_to_clipboard("aa_sequence");}, false);
    var orf_textarea = document.getElementById("orf_sequence");
    orf_textarea.addEventListener("input", function(){
        var orf_changed = orf_textarea.value !== orf_textarea.defaultValue;
        document.getElementById("change-orf-btn").disabled = !orf_changed;
    });
    <?php endif; ?>

    <?php if($exp_info['perform_tax_binning'] == 1): ?>
    // Toggle intermediate taxonomic lcades in the taxonomic lineage
    $(".toggle-clades").on("click", function(){
        var $hide_clades_elmt = $("#hide-clades");
        var clades_shown = $hide_clades_elmt.hasClass("hidden");
        $(".tax-lineage li.tax-intermediate").toggleClass("hidden", !clades_shown);
        $hide_clades_elmt.toggleClass("hidden", !clades_shown);
        $("#show-clades").toggleClass("hidden", clades_shown);
    });
    <?php endif; ?>

    var meta_select = document.getElementById("meta_annotation");
    meta_annotation.addEventListener("change", function(){
        var meta_changed = false;
        for (var i = 0 ; i < meta_select.length ; i++) {
            if (meta_select[i].defaultSelected && meta_select.value !== meta_select[i].value) {
                meta_changed = true;
                break;
            }
        }
        document.getElementById("change-meta-btn").disabled = !meta_changed;
    });

    // When user types in the name of a new subset, check the 'included' box if it wasn't.
    var new_subset_name_input = document.getElementById("new_subset_name");
    var new_subset_checkbox = document.getElementById("new_subset");
    var change_event = new Event('change');
    new_subset_name_input.addEventListener("input", function(){
        if(new_subset_name_input.value.length === 0) {
            new_subset_checkbox.checked = false;
            new_subset_checkbox.dispatchEvent(change_event);  // Trigger 'change; event
        }
        else {
            new_subset_checkbox.checked = true;
            new_subset_checkbox.dispatchEvent(change_event);  // Trigger 'change; event
        }
    });
    // If any of the subset checkbox differ from their initial state, the 'save subset information' button is enabled
    $('#all-subsets .subset-checkbox').on("change", function() {
        var subsets_changed = false;
        $('#all-subsets .subset-checkbox').each(function() {
            var checkbox_elmt = $(this)[0];
            if(checkbox_elmt.checked !== checkbox_elmt.defaultChecked) {
                subsets_changed = true;
            }
        });
        document.getElementById("change-subsets-btn").disabled = !subsets_changed;
    });

    // Affix navigation (bootstrap)
    $('body').attr('data-spy', 'scroll');
    $('body').attr('data-target', '.scrollspy');
    $("#sidebar-nav").affix({
        offset: {
            top: $("#sidebar-nav").offset().top - 50
        }
    });
    // Scroll to anchors smoothly
    $('a[href^="#"]').click(function () {
        var the_id = $(this).attr("href");
        $('html, body').animate({
            scrollTop: $(the_id).offset().top
        }, 250, 'swing');
        return false;
    });


</script>
<!-- Enable bootstrap tooltips -->
<?php echo $this->element("help_tooltips/enable_tooltips",  array("container"=>"#transcript-data-col")); ?>


<?php  // echo $this->element('sql_dump');  // Dump all MySQL queries (debug) ?>