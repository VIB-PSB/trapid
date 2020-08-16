<div>
    <div class="page-header">
        <h1 class="text-primary">Similarity hits</h1>
    </div>
    <div class="subdiv">
        <?php // echo $this->element("trapid_experiment"); ?>

        <section class="page-section">
        <h3>Transcript overview</h3>
            <dl class="standard dl-horizontal">
                <dt>Transcript id</dt>
                <dd><?php echo $this->Html->link($transcript_info['transcript_id'], array("controller" => "trapid", "action" => "transcript", $exp_id, $transcript_info['transcript_id'])); ?></dd>
                <dt>Gene family</dt>
                <dd>
                    <?php
                    if ($transcript_info['gf_id'] == "") {
                        echo "<span class='disabled'>Unavailable</span>\n";
                    } else {
                        echo $this->Html->link($transcript_info['gf_id'], array("controller" => "gene_family", "action" => "gene_family", $exp_id, $transcript_info['gf_id']));
                    }
                    ?>
                </dd>
            </dl>
        </section>

        <?php
        if (isset($error)) {
            echo "<br/><br/><span class='error'>" . $error . "</span><br/><br/>";
        }
        if (isset($message)) {
            echo "<br/><br/><span class='message'>" . $message . "</span><br/><br/>";
        }
        ?>


        <section class="page-section">
        <h3>Similarity hits</h3>
            <table class="table table-hover table-condensed table-bordered table-striped">
                <thead>
                <tr>
                    <th>Gene id</th>
                    <th>E-value</th>
                    <th>Alignment length</th>
                    <th>Percent identity</th>
                    <th>Gene family (ext.)</th>
                    <th>#genes</th>
                    <th>Gene family (TRAPID)</th>
                    <th>#transcripts</th>
                    <th>Select as GF</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $prev_gf = null;
                foreach ($sim_hits as $gene_id => $simd) {
                    /*if($exp_info['genefamily_type']=="HOM"){
                        $plaza_gf_id	= $gf_ids[$gene_id];
                        if($prev_gf==null){$prev_gf=$plaza_gf_id;}
                        if($prev_gf!=$plaza_gf_id){
                            $prev_gf	= $plaza_gf_id;
                        }
                    }
                    */
                    foreach ($simd as $index => $sim) {
                        echo "<tr>";
                        //gene identifier
                        if ($index == 0) {
                            if (!$exp_info['allow_linkout'] || $db_type == 'eggnog') {
                                echo "<td>" . $gene_id . "</td>";
                            } else {
                                echo "<td>" . $this->Html->link($gene_id,
                                        $exp_info['datasource_URL'] . "genes/view/" . urlencode($gene_id)) . "</td>";
                            }
                        } else {
                            echo "<td></td>";
                        }


                        $e_value = $sim[1];
                        $e_value_loc = strpos($e_value, "E");
                        if ($e_value_loc) {
                            $e_value = number_format(substr($e_value, 0, $e_value_loc), 2) . substr($e_value, $e_value_loc);
                        } else {
                            $e_value = number_format($e_value, 4);
                        }
                        echo "<td>" . $e_value . "</td>";
                        echo "<td>" . $sim[3] . "</td>";
                        echo "<td>" . round($sim[4], 1) . "%</td>";

                        if ($exp_info['genefamily_type'] == "HOM" && array_key_exists($gene_id, $gf_ids)) {
                            $plaza_gf_id = $gf_ids[$gene_id];
                            $display_change_form = false;
                            $disabled_change_form = null;
                            if ($prev_gf != $plaza_gf_id) {
                                $prev_gf = $plaza_gf_id;
                                $display_change_form = true;
                            }

                            //PLAZA GF
                            if (!$exp_info['allow_linkout']) {
                                echo "<td>" . $plaza_gf_id . "</td>";
                            } else {
                                $gf_linkout_base = "gene_families/view/";
                                if($db_type == 'eggnog') {
                                    $gf_linkout_base = "#/app/results?target_nogs=";
                                }
                                echo "<td>" . $this->Html->link($plaza_gf_id,
                                        $exp_info['datasource_URL'] . $gf_linkout_base . urlencode($plaza_gf_id),
                                        array("class"=>"linkout", "target"=>"_blank")) . "</td>";
                            }
                            //NUM GENES PLAZA GF
                            echo "<td>" . $plaza_gf_counts[$plaza_gf_id] . "</td>";

                            $trapid_gf_id = null;
                            //TRAPID GF & TRANSCRIPT COUNT
                            if (array_key_exists($plaza_gf_id, $transcript_gfs1)) {
                                $trapid_gf_id = $transcript_gfs1[$plaza_gf_id];
                                if ($trapid_gf_id == $transcript_info['gf_id']) {
                                    $disabled_change_form = " disabled='disabled' ";
                                }
                                echo "<td>" . $this->Html->link($trapid_gf_id,
                                        array("controller" => "gene_family", "action" => "gene_family", $exp_id, $trapid_gf_id)) . "</td>";
                                $trapid_gf_count = $transcript_gfs2[$trapid_gf_id];
                                echo "<td>" . $trapid_gf_count . "</td>";
                            } else {
                                echo "<td class='text-muted'>Unavailable</td>";
                                echo "<td class='text-muted'>Unavailable</td>";
                            }

                            //change gene gene family form
                            if ($display_change_form) {
                                echo "<td>";
                                echo "<div>";
                                echo $this->Form->create(false, array("url"=>array("controller"=>"trapid", "action" => "similarity_hits", $exp_id, $transcript_info['transcript_id']), "type" => "post"));
                                echo "<input type='hidden' name='plaza_gf_id' value='" . $plaza_gf_id . "' />";
                                if ($trapid_gf_id != null) {
                                    echo "<input type='hidden' name='trapid_gf_id' value='" . $trapid_gf_id . "' />";
                                }
                                echo "<input type='submit' class='btn btn-sm' value='Set as gene family' $disabled_change_form />";
                                echo "</form>";
                                echo "</div>";
                                echo "</td>";
                            } else {
                                echo "<td></td>";
                            }

                        } else {    //IORTHO or no GF
                            echo "<td><span class='text-muted'>Unavailable</span></td>";
                            echo "<td><span class='text-muted'>Unavailable</span></td>";
                            echo "<td><span class='text-muted'>Unavailable</span></td>";
                            echo "<td><span class='text-muted'>Unavailable</span></td>";
                            echo "<td><span class='text-muted'>Unavailable</span></td>";
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