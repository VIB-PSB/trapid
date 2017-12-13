<div>
    <div class="page-header">
        <h1 class="text-primary">Gene Family</h1>
    </div>
    <div class="subdiv">
        <?php echo $this->element("trapid_experiment"); ?>

        <h3>Overview</h3>
        <div class="subdiv">
            <dl class="standard">
                <dt>Gene Family</dt>
                <dd><?php echo $gf_info['gf_id']; ?></dd>
                <dt>Transcript count</dt>
                <dd><?php echo $gf_info['num_transcripts']; ?></dd>
                <?php
                if ($exp_info['genefamily_type'] == "HOM") {
                    echo "<dt>PLAZA Gene Family</dt>\n";
                    echo "<dd>" . $this->Html->link($gf_info['plaza_gf_id'], $basic_linkout . "/gene_families/view/" . $gf_info['plaza_gf_id']) . "</dd>\n";
                }
                ?>
            </dl>
        </div>

        <h3>Transcripts</h3>
        <div class="subdiv">
            <table cellpadding="0" cellspacing="0" style="width:400px;">
                <tr>
                    <th>Transcript</th>
                </tr>
                <?php
                foreach ($transcripts as $transcript) {
                    $tr = $transcript['Transcripts']['transcript_id'];
                    echo "<tr>";
                    echo "<td>" . $this->Html->link($tr, array("controller" => "trapid", "action" => "transcript", $exp_id, urlencode($tr))) . "</td>";
                    echo "</tr>\n";
                }
                ?>
            </table>
        </div>


        <?php
        //pr($exp_info);
        //pr($gf_info);
        //pr($transcripts);
        ?>


    </div>
</div>