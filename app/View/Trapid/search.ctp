<div>
    <div class="page-header">
        <h1 class="text-primary">Search</h1>
    </div>
    <div class="subdiv">
        <?php // echo $this->element("trapid_experiment"); ?>

        <?php if ($search_result == "bad_search") : ?>
            <h3>Search results</h3>
            <div class="subdiv">
                <?php
                if (isset($error)) {
                    echo "<span class='error'>" . $error . "</span><br/>\n";
                } else {
                    echo "<span class='error'>The search returned no results.</span><br/>\n";
                }
                ?>
            </div>

        <?php elseif ($search_result == "go"): ?>
            <h3>Search results</h3>
            <div class="subdiv">
                <?php echo $this->Html->script("sorttable"); ?>
                <?php if (count($transcripts_info) > 1) {
                    echo $this->element("sorttable");
                } ?>
                <table class="table table-hover table-condensed table-bordered table-striped sortable">
                    <thead>
                    <tr>
                        <th style="width:20%">GO term</th>
                        <th style="width:70%">GO description</th>
                        <th style="width:10%">#transcripts</th>
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
            </div>

        <?php elseif ($search_result == "interpro"): ?>
            <h3>Search results</h3>
            <div class="subdiv">
                <?php echo $this->Html->script("sorttable"); ?>
                <?php if (count($transcripts_info) > 1) {
                    echo $this->element("sorttable");
                } ?>
                <table cellpadding="0" cellspacing="0" style="width:700px;" class="table table-hover table-condensed table-bordered table-striped sortable">
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

            </div>


        <?php elseif ($search_result == "gene"): ?>
            <h3>Search results</h3>
            <div class="subdiv">
                <?php
                if ($mvc) {
                    echo "<table class=\"table table-hover table-condensed table-bordered table-striped\">\n";
                    echo "<thead>\n";
                    echo "<tr>\n";
                    echo "<th>Gene identifier</th>\n";
                    echo "<th>PLAZA gene family</th>\n";
                    echo "<th>TRAPID gene family</th>\n";
                    echo "<th>#Transcripts in family</th>\n";
                    echo "</tr>\n";
                    echo "</thead>\n<tbody>\n";

                    foreach ($genes_info as $k => $v) {
                        echo "<tr>";
                        echo "<td>" . $this->Html->link($k, array("controller" => "trapid", "action" => "transcript", $exp_id, urlencode($k))) . "</td>";
                        echo "<td>";
                        if ($exp_info['datasource_URL']) {
                            $url = $exp_info['datasource_URL'] . "gene_families/view/" . $v['plaza_gf_id'];
                            echo "<a href='" . $url . "'>" . $v['plaza_gf_id'] . "</a>\n";
                        } else {
                            echo "<dd>" . $v['gf_id'] . "</dd>\n";
                        }
                        echo "</td>\n";

                        echo "<td>";
                        if ($v['gf_id']) {
                            echo $this->Html->link($v['gf_id'],
                                array("controller" => "gene_family", "action" => "gene_family", $exp_id, urlencode($v['gf_id'])));
                        } else {
                            echo "<span class='disabled'>Unavailable</span>";
                        }
                        echo "</td>";

                        echo "<td>";
                        if ($v['gf_id']) {
                            echo $v['num_transcripts'];
                        } else {
                            echo "<span class='disabled'>Unavailable</span>";
                        }
                        echo "</td>";

                        echo "</tr>";
                    }

                    echo "</tbody>\n";
                    echo "</table>\n";
                } else {
                    echo "<dl class='standard dl-horizontal'>\n";
                    echo "<dt>Gene identifier</dt>\n";
                    echo "<dd>" . $search_value . "</dd>\n";
                    if (array_key_exists('plaza_gf_id', $gf_info) && $gf_info['plaza_gf_id']) {
                        echo "<dt>Ref. gene family</dt>\n";
                        if ($exp_info['datasource_URL']) {
                            $url = $exp_info['datasource_URL'] . "gene_families/view/" . $gf_info['plaza_gf_id'];
                            echo "<dd><a class='linkout' href='" . $url . "'>" . $gf_info['plaza_gf_id'] . "</a></dd>\n";
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

            </div>

        <?php elseif ($search_result == "transcript"): ?>
            <h3>Search results</h3>
            <div class="subdiv">
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
            </div>

        <?php endif; ?>


<!--        <h3>Perform new search</h3>-->
<!--        <div class="subdiv">-->
<!--            <br/>-->
<!--            --><?php //echo $this->element("search_element"); ?>
<!--        </div>-->

    </div>
</div>
