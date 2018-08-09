<div>
    <div class="page-header">
        <h1 class="text-primary">RNA similarity hits</h1>
    </div>
    <div class="subdiv">
        <section class="page-section">
        <h3>Transcript overview</h3>
            <dl class="standard dl-horizontal">
                <dt>Transcript id</dt>
                <dd><?php echo $this->Html->link($transcript_info['transcript_id'], array("controller" => "trapid", "action" => "transcript", $exp_id, $transcript_info['transcript_id'])); ?></dd>
                <dt>RNA family</dt>
                <dd>
                    <?php
                    if ($transcript_info['rf_ids'] == "") {
                        echo "<span class='disabled'>Unavailable</span>\n";
                    } else {
                        echo $this->Html->link($transcript_info['rf_ids'], array("controller" => "rna_family", "action" => "rna_family", $exp_id, $transcript_info['rf_ids']));
                    }
                    ?>
                </dd>
            </dl>
        </section>

        <?php
//        if (isset($error)) {
//            echo "<br/><br/><span class='error'>" . $error . "</span><br/><br/>";
//        }
//        if (isset($message)) {
//            echo "<br/><br/><span class='message'>" . $message . "</span><br/><br/>";
//        }
        ?>

        <section class="page-section">
        <h3>RNA similarity hits</h3>
            <table class="table table-hover table-condensed table-bordered table-striped">
                <thead>
                <tr>
                    <th>RF acc. </th>
                    <th>RF id</th>
                    <th>Clan acc.</th>
                    <th>E-value</th>
                    <th>Score</th>
                    <th>Bias</th>
                    <th>Model from</th>
                    <th>Model to</th>
                    <th>Truncated?</th>
                    <th>Seq. from</th>
                    <th>Seq. to</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($sim_hits as $rf_id => $simd) {
                    foreach ($simd as $index => $sim) {
                        echo "<tr>";
                        // RFAM family information (accession, id/name, clan if any)
                        if ($index == 0) {
                            echo "<td><a class='linkout' target='_blank' href='". $rfam_linkouts["base_url"] . $rfam_linkouts["family"] . $rf_id . "'>" . $rf_id . "</a></td>";
                            echo "<td>" . $sim[1] . "</td>";
                            if(isset($sim[2])) {
                                echo "<td><a class='linkout' target='_blank' href='". $rfam_linkouts["base_url"] . $rfam_linkouts["clan"] . $sim[2] . "'>" . $sim[2] . "</a></td>";
                            }
                            else {
                                echo "<td>-</td>";
                            }
                        } else {
                            echo "<td></td>";
                            echo "<td></td>";
                            echo "<td></td>";
                        }
                        foreach(range(3, sizeof($sim)-1) as $i) {
                            echo "<td>" . $sim[$i] . "</td>";
                        }
                        echo "</tr>\n";
                    }

                }
                ?>
                </tbody>
            </table>
        </section>
    </div>
</div>