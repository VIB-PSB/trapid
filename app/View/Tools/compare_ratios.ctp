<div class="page-header">
    <h1 class="text-primary">Compare subsets</h1>
</div>
<section class="page-section">
    <p class="text-justify">
        This module enables functional comparison of transcript subsets, by reporting functional annotation frequencies (ratios) between subsets and subset-specific annotations.
    </p>
    <p class="text-justify">
        <strong>Usage: </strong> select a functional annotation type and the two transcript subsets you wish to compare.
    </p>
    <!-- Subset comparison submission form -->
    <?php echo $this->Form->create(false, array("url" => array("controller" => "tools", "action" => "compare_ratios", $exp_id), "type" => "post", "id" => "subset-ratios-form")); ?>
    <div class="panel panel-default" id="choices">
        <div class="panel-heading">
            Subset & annotation selection
        </div>
        <div class="panel-body">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="subset1"><strong>Subset 1</strong></label>
                    <select class="form-control" id="subset1" name="subset1">
                        <?php
                        foreach ($subsets as $subset => $count) {
                            if (isset($subset1) && $subset1 == $subset) {
                                echo "<option value='" . $subset . "' selected='selected'>" . $subset . " (" . $count . " transcripts)</option>\n";
                            } else {
                                echo "<option value='" . $subset . "'>" . $subset . " (" . $count . " transcripts)</option>\n";
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="subset2"><strong>Subset 2</strong></label>
                    <select class="form-control" id="subset2" name="subset2">
                        <?php
                        foreach ($subsets as $subset => $count) {
                            if (isset($subset2) && $subset2 == $subset) {
                                echo "<option value='" . $subset . "' selected='selected'>" . $subset . " (" . $count . " transcripts)</option>\n";
                            } else {
                                echo "<option value='" . $subset . "'>" . $subset . " (" . $count . " transcripts)</option>\n";
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="type"><strong>Functional annotation type</strong></label><br>
                    <?php
                    $i = 0;
                    foreach ($available_types as $type_id => $type_str) {
                        if (isset($type) && $type == $type_id) {
                            $checked = "checked";
                        } elseif ($i == 0) {
                            $checked = "checked";
                        } else {
                            $checked = "";
                        }
                        echo "<label><input type='radio' name='type' id='type' value='" . $type_id . "' " . $checked . "> " . $type_str . "</label>&nbsp; &nbsp;";
                        $i++;
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="panel-footer">
            <div class="text-right">
                <button type="submit" class="btn btn-primary btn-sm" title="Compute subset ratios">Compute ratios</button> | <a onclick="document.getElementById('subset-ratios-form').reset();" style="cursor:pointer;">Reset all</a>
            </div>
        </div>
    </div>
    <?php
    if (isset($error)) {
        echo "<p class='text-danger text-justify'><strong>Error: </strong>" . $error . "</p><br>\n";
    }
    echo $this->Form->end();
    ?>
</section>

<?php
//indication that results are present
if (isset($data_subset1)) :
    $table_classes = "table table-condensed table-hover table-striped fa-ratio-table";
    $table_download_text = "<span class=\"glyphicon glyphicon-download-alt\"></span> Download data";
?>
    <hr>
    <section class='page-section'>
        <h2>Results</h2>
        <br>
        <ul class='nav nav-tabs nav-justified'>
            <li role='presentation' class='active'><a href='#both' data-toggle='tab'>Functional annotation present in both subsets</a></li>
            <li role='presentation'><a href='#subset1specific' data-toggle='tab'>Functional annotation specific to <code><?= $subset1 ?></code></a></li>
            <li role='presentation'><a href='#subset2specific' data-toggle='tab'>Functional annotation specific to <code><?= $subset2 ?></code></a></li>
        </ul>
        <br>
        <div class="tab-content">
            <?php
            /*
             * Protein domains / InterPro
             */
            if ($type == "ipr") :
            ?>
                <div id="both" class="tab-pane active"><br>
                    <h4><?= $available_types[$type] ?> present in <code><?= $subset1 ?></code> and <code><?= $subset2 ?></code></h4>
                    <?php
                    echo "<div class='row row-table-download'>";
                    echo $this->Html->link(
                        $table_download_text,
                        array("controller" => "tools", "action" => "download_compare_ratios", $exp_id, "ipr", "1", urlencode($subset1), urlencode($subset2)),
                        array("class" => "btn btn-default btn-sm pull-right", "escape" => false)
                    );
                    echo "</div>";

                    echo "<table class='" . $table_classes . "'>\n";
                    echo "<thead>";
                    echo "<tr>";
                    echo "<th>" . $available_types[$type] . "</th>";
                    echo "<th>Description</th>";
                    echo "<th>Subset 1</th>";
                    echo "<th>Subset 2</th>";
                    echo "<th>Ratio subset 1</th>";
                    echo "<th>Ratio subset 2</th>";
                    echo "<th>Ratio subset 1/2</th>";
                    echo "</tr>\n";
                    echo "</thead>";
                    echo "<tbody>";
                    $counter = 0;
                    foreach ($data_subset1 as $k => $v) {
                        if (array_key_exists($k, $data_subset2)) {
                            echo "<tr>";
                            $wk = str_replace(":", "-", $k);
                            if ($type == 'go') {
                                echo "<td>" . $this->Html->link($k, array("controller" => "functional_annotation", "go", $exp_id, $wk)) . "</td>";
                            } else if ($type == 'ipr') {
                                echo "<td>" . $this->Html->link($k, array("controller" => "functional_annotation", "action" => "interpro", $exp_id, $wk)) . "</td>";
                            }
                            echo "<td>" . $descriptions[$k] . "</td>";
                            echo "<td>" . $this->Html->link($data_subset1[$k], array("controller" => "trapid", "action" => "transcript_selection", $exp_id, "label", urlencode($subset1), "interpro", $wk)) . "</td>";
                            echo "<td>" . $this->Html->link($data_subset2[$k], array("controller" => "trapid", "action" => "transcript_selection", $exp_id, "label", urlencode($subset2), "interpro", $wk)) . "</td>";
                            $ratio_subset1 = 100 * $data_subset1[$k] / $subset1_size;
                            $ratio_subset2 = 100 * $data_subset2[$k] / $subset2_size;
                            echo "<td>" . number_format($ratio_subset1, 1) . "%</td>";
                            echo "<td>" . number_format($ratio_subset2, 1) . "%</td>";
                            echo "<td>" . number_format($ratio_subset1 / $ratio_subset2, 2) . "</td>";
                            echo "</tr>\n";
                        }
                    }
                    echo "</tbody>\n";
                    echo "</table>\n";
                    ?>
                </div>
                <div id='subset1specific' class='tab-pane'>
                    <h4><?= $available_types[$type] ?> present in <code><?= $subset1 ?></code> and not in <code><?= $subset2 ?></code></h4>
                    <?php
                    echo "<div class='row row-table-download'>";
                    echo $this->Html->link(
                        $table_download_text,
                        array("controller" => "tools", "action" => "download_compare_ratios", $exp_id, "ipr", "2", urlencode($subset1), urlencode($subset2)),
                        array("class" => "btn btn-default btn-sm pull-right", "escape" => false)
                    );
                    echo "</div>";
                    echo "<table class='" . $table_classes . "'>\n";
                    echo "<thead>";
                    echo "<tr>";
                    echo "<th>" . $available_types[$type] . "</th>";
                    echo "<th>Description</th>";
                    echo "<th>Subset 1</th>";
                    echo "<th>Ratio subset 1</th>";
                    echo "</tr>\n";
                    echo "</thead>";
                    echo "<tbody>";
                    $counter = 0;
                    foreach ($data_subset1 as $k => $v) {
                        if (!array_key_exists($k, $data_subset2)) {
                            echo "<tr>";
                            $wk = str_replace(":", "-", $k);
                            if ($type == 'go') {
                                echo "<td>" . $this->Html->link($k, array("controller" => "functional_annotation", "go", $exp_id, $wk)) . "</td>";
                            } else if ($type == 'ipr') {
                                echo "<td>" . $this->Html->link($k, array("controller" => "functional_annotation", "action" => "interpro", $exp_id, $wk)) . "</td>";
                            }
                            echo "<td>" . $descriptions[$k] . "</td>";
                            //echo "<td>".$data_subset1[$k]."</td>";
                            echo "<td>" . $this->Html->link($data_subset1[$k], array("controller" => "trapid", "action" => "transcript_selection", $exp_id, "label", urlencode($subset1), "interpro", $wk)) . "</td>";
                            $ratio_subset1 = 100 * $data_subset1[$k] / $subset1_size;
                            echo "<td>" . number_format($ratio_subset1, 1) . "%</td>";
                            echo "</tr>\n";
                        }
                    }

                    echo "</tbody>\n";
                    echo "</table>\n";
                    ?>
                </div>

                <div id='subset2specific' class='tab-pane'>
                    <h4><?= $available_types[$type] ?> present in <code><?= $subset2 ?></code> and not in <code><?= $subset1 ?></code></h4>
                    <?php
                    echo "<div class='row row-table-download'>";
                    echo $this->Html->link(
                        $table_download_text,
                        array("controller" => "tools", "action" => "download_compare_ratios", $exp_id, "ipr", "3", urlencode($subset1), urlencode($subset2)),
                        array("class" => "btn btn-default btn-sm pull-right", "escape" => false)
                    );
                    echo "</div>";
                    echo "<table class='" . $table_classes . "'>\n";
                    echo "<thead>";
                    echo "<tr>";
                    echo "<th>" . $available_types[$type] . "</th>";
                    echo "<th>Description</th>";
                    echo "<th>Subset 2</th>";
                    echo "<th>Ratio subset 2</th>";
                    echo "</tr>\n";
                    echo "</thead>";
                    echo "<tbody>";
                    $counter = 0;
                    foreach ($data_subset2 as $k => $v) {
                        if (!array_key_exists($k, $data_subset1)) {
                            echo "<tr>";
                            $wk = str_replace(":", "-", $k);
                            if ($type == 'go') {
                                echo "<td>" . $this->Html->link($k, array("controller" => "functional_annotation", "go", $exp_id, $wk)) . "</td>";
                            } else if ($type == 'ipr') {
                                echo "<td>" . $this->Html->link($k, array("controller" => "functional_annotation", "action" => "interpro", $exp_id, $wk)) . "</td>";
                            }
                            echo "<td>" . $descriptions[$k] . "</td>";
                            //echo "<td>".$data_subset2[$k]."</td>";
                            echo "<td>" . $this->Html->link($data_subset2[$k], array("controller" => "trapid", "action" => "transcript_selection", $exp_id, "label", urlencode($subset2), "interpro", $wk)) . "</td>";
                            $ratio_subset2 = 100 * $data_subset2[$k] / $subset2_size;
                            echo "<td>" . number_format($ratio_subset2, 1) . "%</td>";
                            echo "</tr>\n";
                        }
                    }

                    echo "</tbody>\n";
                    echo "</table>\n";
                    ?>
                </div>

            <?php endif; ?>
            <?php
            /*
        * GO terms
        */
            if ($type == "go") :
            ?>
                <div id="both" class="tab-pane active">
                    <h4><?= $available_types[$type] ?> present in <code><?= $subset1 ?></code> and <code><?= $subset2 ?></code></h4>
                    <ul class='tabbed-header list-unstyled list-inline padded-top-1em'>
                        <li><strong>Show GO aspect: </strong></li>
                        <li id='tab_both_BP' class='selected_tab tab_both'><a href='javascript:switchtab("tab_both_BP",".tab_both","tabdiv_both_BP",".tabdiv_both");'>Biological process</a></li>
                        <li id='tab_both_MF' class='tab_both'><a href='javascript:switchtab("tab_both_MF",".tab_both","tabdiv_both_MF",".tabdiv_both");'>Molecular function</a></li>
                        <li id='tab_both_CC' class='tab_both'><a href='javascript:switchtab("tab_both_CC",".tab_both","tabdiv_both_CC",".tabdiv_both");'>Cellular component</a></li>
                    </ul>
                    <?php
                    foreach ($type_desc as $go_type => $go_type_desc) {
                        //file for download is written immediately at the same time
                        $style = null;
                        if ($go_type == "BP") {
                            $style = " style='display:block;' ";
                        }
                        echo "<div id='tabdiv_both_" . $go_type . "' class='tabbed_div tabdiv_both tabbed_div2' $style>\n";
                        echo "<div class='row row-table-download'>";
                        echo $this->Html->link(
                            $table_download_text,
                            array("controller" => "tools", "action" => "download_compare_ratios", $exp_id, "go", "1", urlencode($subset1), urlencode($subset2), $go_type),
                            array("class" => "btn btn-default btn-sm pull-right", "escape" => false)
                        );
                        echo "</div>";
                        echo "<table class='" . $table_classes . "'>\n";
                        echo "<thead>";
                        echo "<tr>";
                        echo "<th>" . $available_types[$type] . "</th>";
                        echo "<th>Description</th>";
                        echo "<th>Subset 1</th>";
                        echo "<th>Subset 2</th>";
                        echo "<th>Ratio subset 1</th>";
                        echo "<th>Ratio subset 2</th>";
                        echo "<th>Ratio subset 1/2</th>";
                        echo "</tr>\n";
                        echo "</thead>";
                        echo "<tbody>";
                        $counter = 0;
                        foreach ($data_subset1 as $k => $v) {
                            if (array_key_exists($k, $data_subset2) && $go_types[$k] == $go_type) {
                                echo "<tr>";
                                $wk = str_replace(":", "-", $k);
                                echo "<td>" . $this->Html->link($k, array("controller" => "functional_annotation", "action" => "go", $exp_id, $wk)) . "</td>";
                                echo "<td>" . $descriptions[$k] . "</td>";
                                echo "<td>" . $this->Html->link($data_subset1[$k], array("controller" => "trapid", "action" => "transcript_selection", $exp_id, "label", urlencode($subset1), "go", $wk)) . "</td>";
                                echo "<td>" . $this->Html->link($data_subset2[$k], array("controller" => "trapid", "action" => "transcript_selection", $exp_id, "label", urlencode($subset2), "go", $wk)) . "</td>";
                                $ratio_subset1 = 100 * $data_subset1[$k] / $subset1_size;
                                $ratio_subset2 = 100 * $data_subset2[$k] / $subset2_size;
                                echo "<td>" . number_format($ratio_subset1, 1) . "%</td>";
                                echo "<td>" . number_format($ratio_subset2, 1) . "%</td>";
                                echo "<td>" . number_format($ratio_subset1 / $ratio_subset2, 2) . "</td>";
                                echo "</tr>\n";
                            }
                        }
                        echo "</tbody>\n";
                        echo "</table>\n";
                        echo "</div>\n";
                    }

                    ?>
                </div>

                <div id='subset1specific' class="tab-pane">
                    <h4><?= $available_types[$type] ?> present in <code><?= $subset1 ?></code> and not in <code><?= $subset2 ?></code></h4>

                    <ul class='tabbed-header list-unstyled list-inline padded-top-1em'>
                        <li><strong>Show GO aspect: </strong></li>
                        <li id='tab_sub1_BP' class='selected_tab tab_sub1'><a href='javascript:switchtab("tab_sub1_BP",".tab_sub1","tabdiv_sub1_BP",".tabdiv_sub1");'>Biological process</a></li>
                        <li id='tab_sub1_MF' class='tab_sub1'><a href='javascript:switchtab("tab_sub1_MF",".tab_sub1","tabdiv_sub1_MF",".tabdiv_sub1");'>Molecular function</a></li>
                        <li id='tab_sub1_CC' class='tab_sub1'><a href='javascript:switchtab("tab_sub1_CC",".tab_sub1","tabdiv_sub1_CC",".tabdiv_sub1");'>Cellular component</a></li>
                    </ul>
                    <?php
                    foreach ($type_desc as $go_type => $go_type_desc) {
                        $style = null;
                        if ($go_type == "BP") {
                            $style = " style='display:block;' ";
                        }
                        echo "<div id='tabdiv_sub1_" . $go_type . "' class='tabbed_div tabdiv_sub1 tabbed_div2' $style>\n";

                        echo "<div class='row row-table-download'>";
                        echo $this->Html->link(
                            $table_download_text,
                            array("controller" => "tools", "action" => "download_compare_ratios", $exp_id, "go", "2", urlencode($subset1), urlencode($subset2), $go_type),
                            array("class" => "btn btn-default btn-sm pull-right", "escape" => false)
                        );
                        echo "</div>";
                        echo "<table class='" . $table_classes . "'>\n";
                        echo "<thead>";
                        echo "<tr>";
                        echo "<th>" . $available_types[$type] . "</th>";
                        echo "<th>Description</th>";
                        echo "<th>Subset 1</th>";
                        echo "<th>Ratio subset 1</th>";
                        echo "</tr>\n";
                        echo "</thead>";
                        echo "<tbody>";
                        $counter = 0;
                        foreach ($data_subset1 as $k => $v) {
                            if (!array_key_exists($k, $data_subset2) && $go_types[$k] == $go_type) {
                                echo "<tr>";
                                $wk = str_replace(":", "-", $k);
                                echo "<td>" . $this->Html->link($k, array("controller" => "functional_annotation", "action" => "go", $exp_id, $wk)) . "</td>";
                                echo "<td>" . $descriptions[$k] . "</td>";
                                echo "<td>" . $this->Html->link($data_subset1[$k], array("controller" => "trapid", "action" => "transcript_selection", $exp_id, "label", urlencode($subset1), "go", $wk)) . "</td>";
                                $ratio_subset1 = 100 * $data_subset1[$k] / $subset1_size;
                                echo "<td>" . number_format($ratio_subset1, 1) . "%</td>";
                                echo "</tr>\n";
                            }
                        }
                        echo "</tbody>\n";
                        echo "</table>\n";

                        echo "</div>\n";
                    }
                    ?>

                </div>



                <div id='subset2specific' class="tab-pane">
                    <h4><?= $available_types[$type] ?> present in <code><?= $subset2 ?></code> and not in <code><?= $subset1 ?></code></h4>
                    <ul class='tabbed-header list-unstyled list-inline padded-top-1em'>
                        <li><strong>Show GO aspect: </strong></li>
                        <li id='tab_sub2_BP' class='selected_tab tab_sub2'><a href='javascript:switchtab("tab_sub2_BP",".tab_sub2","tabdiv_sub2_BP",".tabdiv_sub2");'>Biological process</a></li>
                        <li id='tab_sub2_MF' class='tab_sub2'><a href='javascript:switchtab("tab_sub2_MF",".tab_sub2","tabdiv_sub2_MF",".tabdiv_sub2");'>Molecular function</a></li>
                        <li id='tab_sub2_CC' class='tab_sub2'><a href='javascript:switchtab("tab_sub2_CC",".tab_sub2","tabdiv_sub2_CC",".tabdiv_sub2");'>Cellular component</a></li>
                    </ul>
            <?php
                foreach ($type_desc as $go_type => $go_type_desc) {
                    $style = null;
                    if ($go_type == "BP") {
                        $style = " style='display:block;' ";
                    }
                    echo "<div id='tabdiv_sub2_" . $go_type . "' class='tabbed_div tabdiv_sub2 tabbed_div2' $style>\n";

                    echo "<div class='row row-table-download'>";
                    echo $this->Html->link(
                        $table_download_text,
                        array("controller" => "tools", "action" => "download_compare_ratios", $exp_id, "go", "3", urlencode($subset1), urlencode($subset2), $go_type),
                        array("class" => "btn btn-default btn-sm pull-right", "escape" => false)
                    );
                    echo "</div>";
                    echo "<table class='" . $table_classes . "'>\n";
                    echo "<thead>";
                    echo "<tr>";
                    echo "<th>" . $available_types[$type] . "</th>";
                    echo "<th>Description</th>";
                    echo "<th>Subset 2</th>";
                    echo "<th>Ratio subset 2</th>";
                    echo "</tr>\n";
                    echo "</thead>";
                    echo "<tbody>";
                    $counter = 0;
                    foreach ($data_subset2 as $k => $v) {
                        if (!array_key_exists($k, $data_subset1) && $go_types[$k] == $go_type) {
                            echo "<tr>";
                            $wk = str_replace(":", "-", $k);
                            echo "<td>" . $this->Html->link($k, array("controller" => "functional_annotation", "action" => "go", $exp_id, $wk)) . "</td>";
                            echo "<td>" . $descriptions[$k] . "</td>";
                            echo "<td>" . $this->Html->link($data_subset2[$k], array("controller" => "trapid", "action" => "transcript_selection", $exp_id, "label", urlencode($subset2), "go", $wk)) . "</td>";
                            $ratio_subset2 = 100 * $data_subset2[$k] / $subset2_size;
                            echo "<td>" . number_format($ratio_subset2, 1) . "%</td>";
                            echo "</tr>\n";
                        }
                    }
                    echo "</tbody>\n";
                    echo "</table>\n";


                    echo "</div>\n";
                }
                echo "</div>\n";

            endif;

            echo "</div>\n";
            echo "</div>\n";
        endif;
            ?>


            <script type='text/javascript'>
                // Toggle table visibility depending on user's choice
                function switchtab(tabid, tabclass, divid, divclass) {
                    $(divclass).each(function() {
                        $(this).css("display", "none");
                    });
                    $("#" + divid).css("display", "block");
                    $(tabclass).each(function(entity) {
                        try {
                            $(this).removeClass('selected_tab');
                        } catch (exception) {}
                    });
                    $("#" + tabid).addClass('selected_tab');
                }

                // DataTables
                $(".fa-ratio-table").dataTable({
                    // Layout of dataTable elements
                    dom: "<'row'<'col-md-5 col-sm-12'f><'col-md-7 col-sm-12 text-right'ipl>>" +
                        "<'row'<'col-sm-12'tr>>" +
                        "<'row'<'col-md-2'><'col-md-10 text-right'ipl>>",
                    "language": {
                        "lengthMenu": "_MENU_",
                        "info": "_START_ - _END_ of _TOTAL_",
                        "infoEmpty": "0 - 0 of 0",
                        "infoFiltered": "(filtered from _MAX_)"
                    },
                    // Choices/labels in the length menu (i.e. 'show x entries')
                    lengthMenu: [
                        [10, 20, 50, 100, -1],
                        [10, 20, 50, 100, "All"]
                    ],
                    // Default page length
                    pageLength: 20
                })
            </script>