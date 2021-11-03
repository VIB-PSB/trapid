<?php if (isset($download_type)): ?>
<?php
    header("Content-disposition: attachment; filename=$file_name");
    header("Content-type: text/plain");
    if ($download_type == "fasta_transcript") {
        foreach ($transcript_data as $td) {
            $t = $td['Transcripts'];
            echo ">" . $t['transcript_id'] . "\n";
            echo $t['transcript_sequence'] . "\n";
        }
    } else if ($download_type == "fasta_orf") {
        foreach ($transcript_data as $td) {
            $t = $td['Transcripts'];
            echo ">" . $t['transcript_id'] . "\n";
            echo $t['orf_sequence'] . "\n";
        }
    } else if ($download_type == "table") {
        // The table content depends on the available functional annotation types.
        $function_headers = array("go"=>"GO annotation", "ko"=>"KO annotation", "interpro"=>"InterPro annotation");
        // Functional annotation types that are displayed in the table: by default, all types are shown.
        // If `$exp_info` is set, use the list of functional annotation types defined there.
        $function_types = ['go', 'interpro', 'ko'];
        if(isset($exp_info)){
            $function_types = $exp_info['function_types'];
        }
        $table_columns = ["Transcript", "Gene Family"]; // First columns
        // Add functional data columns for available functional types
        foreach ($function_types as $ft) {
            $table_columns[] = $function_headers[$ft];
        }
        // Add subset column
        $table_columns[] = "Subsets";
        $table_header = implode("\t", $table_columns) . "\n";

        echo $table_header;
        foreach ($transcript_data as $transcript_dat) {
            $td = $transcript_dat['Transcripts'];
            echo $td['transcript_id'] . "\t";
            if ($td['gf_id']) {
                echo $td['gf_id'] . "\t";
            } else {
                echo "Unavailable\t";
            }
            if(in_array("go", $function_types)) {
                if (array_key_exists($td['transcript_id'], $transcripts_go)) {
                    for ($i = 0; $i < count($transcripts_go[$td['transcript_id']]) && $i < 3; $i++) {
                        $go = $transcripts_go[$td['transcript_id']][$i];
                        echo $go . ",";
                    }
                    echo "\t";
                } else {
                    echo "Unavailable\t";
                }
            }
            if(in_array("interpro", $function_types)) {
                if (array_key_exists($td['transcript_id'], $transcripts_ipr)) {
                    for ($i = 0; $i < count($transcripts_ipr[$td['transcript_id']]) && $i < 3; $i++) {
                        $ipr = $transcripts_ipr[$td['transcript_id']][$i];
                        echo $ipr . ",";
                    }
                    echo "\t";
                } else {
                    echo "Unavailable\t";
                }
            }
            if(in_array("ko", $function_types)) {
                if (array_key_exists($td['transcript_id'], $transcripts_ko)) {
                    for ($i = 0; $i < count($transcripts_ko[$td['transcript_id']]) && $i < 3; $i++) {
                        $ko = $transcripts_ko[$td['transcript_id']][$i];
                        echo $ko . ",";
                    }
                    echo "\t";
                } else {
                    echo "Unavailable\t";
                }
            }
            if (array_key_exists($td['transcript_id'], $transcripts_labels)) {
                for ($i = 0; $i < count($transcripts_labels[$td['transcript_id']]) && $i < 3; $i++) {
                    $label = $transcripts_labels[$td['transcript_id']][$i];
                    echo $label . ",";
                }
                echo "\n";
            } else {
                echo "Unavailable\n";
            }

        }
    } else if ($download_type == "fasta_protein_ref") {
        foreach ($trapid_sequences as $transcript_id => $sequence) {
            echo ">" . $transcript_id . "\n";
            echo $sequence . "\n";
        }
        foreach ($reference_sequences as $gene => $seq) {
            echo ">" . $gene . "\n";
            echo $seq . "\n";
        }
    }

    ?>
<?php else: ?>
    <?php
    // Selectize JS + CSS
    echo $this->Html->script('selectize.min.js');
    echo $this->Html->css('selectize.paper.css');
    ?>
    <div>
        <div class="page-header">
        <h1 class="text-primary">Transcript selection</h1>
        </div>
            <?php // echo $this->element("trapid_experiment"); ?>
            <h3>Selection parameters</h3>
                <?php
                //pr($parameters);
                ?>
                <dl class="standard dl-horizontal">
                    <?php
                    foreach ($parameters as $key => $values) {
                        echo "<dt>" . $key . "</dt>";
                        echo "<dd>";
                        echo "<div>";
                        foreach ($values as $value) {
                            if ($key == "InterPro domain") {
                                echo $this->Html->link($value, array("controller" => "functional_annotation", "action" => "interpro", $exp_id, $value));
                            } else if ($key == "Subset") {
                                echo $this->Html->link($value, array("controller" => "labels", "action" => "view", $exp_id, $value));
                            } else if ($key == "GO label") {
                                echo $this->Html->link($value, array("controller" => "functional_annotation", "action" => "go", $exp_id, str_replace(":", "-", $value)));
                            } else if ($key == "Gene family") {
                                echo $this->Html->link($value, array("controller" => "gene_family", "action" => "gene_family", $exp_id, $value));
                            } else {
                                echo "<span>" . $value . "</span>";
                            }
                            echo "<br/>";
                        }
                        echo "</div>";
                        echo "</dd>";
                    }
                    //echo "<br/>";
                    echo "<dt>#Transcripts</dt>";
                    echo "<dd>";
                    echo $this->Paginator->params['paging']['TranscriptsPagination']['count'];
                    echo "</dd>";
                    ?>
                </dl>
            </div>

            <!--
	<h3>Toolbox</h3>
	<div class="subdiv">
	<?php

            $toolbox = array("Data" => array(
                array(
                    "Create new subset",
                    $this->Html->url(array("controller" => "trapid", "action" => "transcript_selection", implode("/", $this->passedArgs))),
                    "some_image.png"
                ),
            ));
            $this->set("toolbox", $toolbox);
            echo $this->element("toolbox");
            ?>
	</div>
	-->

            <h3>Transcripts</h3>
            <div class="row" id="table-header">
                <div class="col-md-9">
                    <?php echo $this->element("subset_create_form",  array("exp_id"=>$exp_id, "all_subsets"=>$all_subsets, "collection_type"=>"selection", "tooltip_text"=>$tooltip_text_subset_creation, "selection_parameters"=>array_slice($raw_parameters, 1))); ?>
                </div>
                <div class="col-md-3 pull-right text-right">
                    <?php
                    $download_url = "";
                    $this->set("download_url", $download_url);
                    $this->set("allow_reference_aa_download", 0);
                    echo $this->element("download_dropdown", array("align_right"=>true));
                    ?>
                </div>
            </div>

            <section class="page-section-xs">
                <?php echo $this->element("table_func"); ?>
            </section>

<!--            <h3>Download</h3>-->
<!--            <div class="subdiv">-->
                <?php
//                $download_url = "";
//                $this->set("download_url", $download_url);
//                $this->set("allow_reference_aa_download", 0);
//                echo $this->element("download_dropdown");
//                echo $this->element("download");
                ?>
<!--            </div>-->

    <!-- Enable bootstrap tooltips -->
    <?php echo $this->element("help_tooltips/enable_tooltips",  array("container"=>"#table-header")); ?>
<?php endif; ?>
