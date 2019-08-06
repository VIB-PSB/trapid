    <div class="page-header">
        <h1 class="text-primary">Search results</h1>
    </div>
    <div class="subdiv">
        <?php if ($search_result == "bad_search") : ?>
                <?php
                if (isset($error)) {
                    echo "<p class='lead text-danger'>" . $error . "</p><br/>\n";
                } else {
                    echo "<p class='lead'>Your search <code>" . $search_value . "</code> returned no results.</p>\n";
                }
                ?>

        <?php elseif ($search_result == "go"): ?>
                <?php echo $this->Html->script("sorttable"); ?>
                <?php if (count($transcripts_info) > 1) {
                    echo $this->element("sorttable");
                } ?>
                <table class="table table-hover table-condensed table-bordered table-striped sortable">
                    <thead>
                    <tr>
                        <th style="width:14%">GO term</th>
                        <th style="width:8%">GO type</th>
                        <th style="width:70%">GO description</th>
                        <th style="width:8%">#transcripts</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $i = 0;
                    foreach ($transcripts_info as $go => $data) {
                        $class = null;
                        if ($i++ % 2 == 0) {
                            $class = " class='altrow' ";
                        }
                        echo "<tr $class>";
                        echo "<td>" . $this->Html->link($go, array("controller" => "functional_annotation", "action" => "go", $exp_id, str_replace(":", "-", $go))) . "</td>";
                        echo "<td>" . $this->element("go_category_badge", array("go_category"=>$data['info'], "small_badge"=>false, "no_color"=>false)) . "</td>";
                        echo "<td>" . $data['desc'] . "</td>";
                        echo "<td>" . $data['count'] . "</td>";
                        echo "</tr>\n";
                    }
                    ?>
                    </tbody>
                </table>
                <?php if (count($transcripts_info) > 1) {
                    echo $this->element("sorttable");
                } ?>

        <?php elseif ($search_result == "interpro"): ?>
                <?php echo $this->Html->script("sorttable"); ?>
                <?php if (count($transcripts_info) > 1) {
                    echo $this->element("sorttable");
                } ?>
                <table cellpadding="0" cellspacing="0"  class="table table-hover table-condensed table-bordered table-striped sortable">
                    <thead>
                    <tr>
                        <th style="width:20%">InterPro</th>
                        <th style="width:70%">InterPro description</th>
                        <th style="width:10%">#transcripts</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $i = 0;
                    foreach ($transcripts_info as $interpro => $data) {
                        $class = null;
                        if ($i++ % 2 == 0) {
                            $class = " class='altrow' ";
                        }
                        echo "<tr $class>";
                        echo "<td>" . $this->Html->link($interpro, array("controller" => "functional_annotation", "action" => "interpro", $exp_id, $interpro)) . "</td>";
                        echo "<td>" . $data['desc'] . "</td>";
                        echo "<td>" . $data['count'] . "</td>";
                        echo "</tr>\n";
                    }
                    ?>
                    </tbody>
                </table>
                <?php if (count($transcripts_info)) {
                    echo $this->element("sorttable");
                } ?>

        <?php elseif ($search_result == "gene"): ?>
            <?php
            if($db_type == "eggnog") {
                echo "<dl class='standard dl-horizontal'>\n";
                echo "<dt>Gene identifier</dt>\n";
                echo "<dd>" . $search_value . "</dd>\n";
                if(sizeof($gf_info) > 1) {  // If multiple matching GFs, display a message for warn users
                    echo "</dl>";
                    echo "<p class='text-justify'><strong>Multiple TRAPID gene families found: </strong></p>\n";
                    echo "<dl class='standard dl-horizontal'>";
                }
                for($i=0; $i < sizeof($gf_info); $i++) {
                    $gf = $gf_info[$i]['GeneFamilies'];
                    echo "<dt>Ref. gene family</dt>\n";
                    if ($exp_info['datasource_URL']) {
                        $url = $exp_info['datasource_URL'] . "#/app/results?target_nogs=" . $gf['plaza_gf_id'];
                        echo "<dd><a target='_blank' class='linkout' href='" . $url . "'>" . $gf['plaza_gf_id'] . "</a></dd>\n";
                    }
                    else {
                        echo "<dd>" . $gf['gf_id'] . "</dd>\n";
                    }
                    echo "<dt>TRAPID gene family</dt>\n";
                    echo "<dd>" . $this->Html->link($gf['gf_id'], array("controller" => "gene_family", "action" => "gene_family", $exp_id, $gf['gf_id'])) . "</dd>\n";
                    echo "<dt>#Transcripts in family</dt>\n";
                    echo "<dd>" . $gf['num_transcripts'] . "</dd>\n";
                    echo "<div style='margin-bottom: 1em;'></div>";
                }
                 echo "</dl>\n";
            }

            else {
                echo "<dl class='standard dl-horizontal'>\n";
                echo "<dt>Gene identifier</dt>\n";
                echo "<dd>" . $search_value . "</dd>\n";
                if (array_key_exists('plaza_gf_id', $gf_info) && $gf_info['plaza_gf_id']) {
                    echo "<dt>Ref. gene family</dt>\n";
                    if ($exp_info['datasource_URL']) {
                        $url = $exp_info['datasource_URL'] . "gene_families/view/" . $gf_info['plaza_gf_id'];
                        echo "<dd><a target='_blank' class='linkout' href='" . $url . "'>" . $gf_info['plaza_gf_id'] . "</a></dd>\n";
                    } else {
                        echo "<dd>" . $gf_info['gf_id'] . "</dd>\n";
                    }
                }
                echo "<dt>TRAPID gene family</dt>\n";
                echo "<dd>" . $this->Html->link($gf_info['gf_id'], array("controller" => "gene_family", "action" => "gene_family", $exp_id, $gf_info['gf_id'])) . "</dd>\n";
                echo "<dt>#Transcripts in family</dt>\n";
                echo "<dd>" . $gf_info['num_transcripts'] . "</dd>\n";

                echo "</dl>\n";
            }
            ?>

        <?php elseif ($search_result == "transcript"): ?>
                <table class="table table-hover table-condensed table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>Transcript</th>
                        <th>Gene family</th>
                        <th>Meta annotation</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($transcripts_info as $k => $v) {
                        echo "<tr>";
                        echo "<td>" . $this->Html->link($k, array("controller" => "trapid", "action" => "transcript", $exp_id, urlencode($k))) . "</td>";
                        echo "<td>";
                        if ($v['gf_id']) {
                            echo $this->Html->link($v['gf_id'],
                                array("controller" => "gene_family", "action" => "gene_family", $exp_id, urlencode($v['gf_id'])));
                        } else {
                            echo "<span class='disabled'>Unavailable</span>";
                        }
                        echo "</td>";


                        echo "<td>" . $this->Html->link($v['meta_annotation'], array("controller" => "trapid", "action" => "transcript_selection", $exp_id, "meta_annotation", urlencode($v['meta_annotation']))) . "</td>";
                        echo "</tr>\n";
                    }
                    ?>
                    </tbody>
                </table>

        <?php elseif ($search_result == "ko"): ?>
            <?php echo $this->Html->script("sorttable"); ?>
            <?php if (count($transcripts_info) > 1) {
                echo $this->element("sorttable");
            } ?>
            <table cellpadding="0" cellspacing="0"  class="table table-hover table-condensed table-bordered table-striped sortable">
                <thead>
                <tr>
                    <th>KO term</th>
                    <th>KO description</th>
                    <th>#transcripts</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $i = 0;
                foreach ($transcripts_info as $interpro => $data) {
                    $class = null;
                    if ($i++ % 2 == 0) {
                        $class = " class='altrow' ";
                    }
                    echo "<tr $class>";
                    echo "<td>" . $this->Html->link($interpro, array("controller" => "functional_annotation", "action" => "interpro", $exp_id, $interpro)) . "</td>";
                    echo "<td>" . $data['desc'] . "</td>";
                    echo "<td>" . $data['count'] . "</td>";
                    echo "</tr>\n";
                }
                ?>
                </tbody>
            </table>
            <?php if (count($transcripts_info)) {
                echo $this->element("sorttable");
            }
            ?>

        <?php endif; ?>

    </div>
