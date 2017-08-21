<?php if (isset($download_type)): ?>
    <?php
//pr($transcript_data);
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
        echo "Transcript\tGene Family\tGO annotation\tInterPro annotation\tSubsets\n";
        foreach ($transcript_data as $transcript_dat) {
            $td = $transcript_dat['Transcripts'];
            echo $td['transcript_id'] . "\t";
            if ($td['gf_id']) {
                echo $td['gf_id'] . "\t";
            } else {
                echo "Unavailable\t";
            }
            if (array_key_exists($td['transcript_id'], $transcripts_go)) {
                for ($i = 0; $i < count($transcripts_go[$td['transcript_id']]) && $i < 3; $i++) {
                    $go = $transcripts_go[$td['transcript_id']][$i];
                    echo $go . ",";
                }
                echo "\t";
            } else {
                echo "Unavailable\t";
            }
            if (array_key_exists($td['transcript_id'], $transcripts_ipr)) {
                for ($i = 0; $i < count($transcripts_ipr[$td['transcript_id']]) && $i < 3; $i++) {
                    $ipr = $transcripts_ipr[$td['transcript_id']][$i];
                    echo $ipr . ",";
                }
                echo "\t";
            } else {
                echo "Unavailable\t";
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
    <div>
        <h2>Transcript subsets</h2>
        <div class="subdiv">
            <?php echo $this->element("trapid_experiment"); ?>
            <h3>Parameters</h3>
            <div class="subdiv">
                <?php
                //pr($parameters);
                ?>
                <dl class="standard">
                    <?php
                    foreach ($parameters as $key => $values) {
                        echo "<dt>" . $key . "</dt>";
                        echo "<dd>";
                        echo "<div>";
                        foreach ($values as $value) {
                            if ($key == "InterPro domain") {
                                echo $html->link($value, array("controller" => "functional_annotation", "action" => "interpro", $exp_id, $value));
                            } else if ($key == "Subset") {
                                echo $html->link($value, array("controller" => "labels", "action" => "view", $exp_id, $value));
                            } else if ($key == "GO label") {
                                echo $html->link($value, array("controller" => "functional_annotation", "action" => "go", $exp_id, str_replace(":", "-", $value)));
                            } else if ($key == "Gene family") {
                                echo $html->link($value, array("controller" => "gene_family", "action" => "gene_family", $exp_id, $value));
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
                    echo $paginator->params['paging']['TranscriptsPagination']['count'];
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
                    $html->url(array("controller" => "trapid", "action" => "transcript_selection", implode("/", $this->passedArgs))),
                    "some_image.png"
                ),
            ));
            $this->set("toolbox", $toolbox);
            echo $this->element("toolbox");
            ?>
	</div>
	-->

            <h3>Transcripts</h3>
            <div class="subdiv">
                <?php echo $this->element("table_func"); ?>
            </div>

            <h3>Download</h3>
            <div class="subdiv">
                <?php
                $download_url = "";
                $this->set("download_url", $download_url);
                $this->set("allow_reference_aa_download", 0);
                echo $this->element("download");
                ?>
            </div>

        </div>
    </div>
<?php endif; ?>
