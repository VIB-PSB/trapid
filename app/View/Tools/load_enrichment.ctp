<?php
if(isset($error)) {
    echo "<p class='lead text-danger'>Error: ".$error.".</p>";
}
else if(!isset($result)) {
    echo "<p class='lead text-danger'>Undefined error: no output data.</p>";
}
else if(count($result) == 0) {
    echo "<p class='lead'>No enriched terms found for selected subset and settings.</p>";
}
else {
    // Create a download URL
    $download_url = $this->Html->url(array("controller"=>"tools","action"=>"download_enrichment",$exp_id,$type,$subset,$selected_pvalue));
    /* Enrichment table view settings */
    // Define a class string to use for the enrichment tables
    $enr_table_class = "table table-hover table-striped table-condensed";
    $enr_table_decimals = 3;
?>

<script type='text/javascript'>
    // Convert enrichment table into interactive table (jquery datatable)
    // tableId: table html element id
    // orderCol: a 2d array of column indices / direction for default sorting of the table
    // filterIndex: index of column used for filtering select on top of the table. Set to '-1' to ignore filtering.
    // filterText: default text in the filtering element
    function enrichmentDataTable(tableId, orderCols, filterIndex, filterText) {
        $(`#${tableId}`).dataTable({
            // Layout of datatable elements
            dom: "<'row'<'#table-filter.col-md-5 col-sm-12'f><'col-md-7 col-sm-12 text-right'ipl>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-md-2'><'col-md-10 text-right'ipl>>",
            "language": {
                "lengthMenu": "_MENU_",
                "info": "_START_ - _END_ of _TOTAL_",
                "infoEmpty": "0 - 0 of 0",
                "infoFiltered": "(filtered from _MAX_)"
            },
            // Choices/labels in the length menu (i.e. 'show x entries')
            lengthMenu: [[10, 20, 50, 100, -1], [10, 20, 50, 100, "All"]],
            // Default page length
            pageLength: 20,
            // Column sorting (index - direction)
            order: orderCols,
            // Create filter select element
            initComplete: function () {
                if(filterIndex !== -1) {
                    this.api().columns([filterIndex]).every(function () {
                        var column = this;
                        var select = $('<select class=\'form-control input-sm\'><option value="" selected>' + filterText + '</option></select>')
                            .prependTo($("#table-filter"))
                            .on('change', function () {
                                var val = $.fn.dataTable.util.escapeRegex(
                                    $(this).val()
                                );
                                column
                                    .search(val ? `^${val}$` : '', true, false)
                                    .draw();
                            });
                        column.data().unique().sort().each(function (d, j) {
                            select.append(`<option value="${d}">${d}</option>`)
                        });
                    });
                }
                $(`#${tableId}`).width("100%");  // Needed because we've hidden the GO aspect string column
            }
        });
    }
</script>

    <?php if($type == "go") { ?>
        <section class="page-section-sm">
            <ul class="nav nav-tabs" id="tabs" data-tabs="tabs">
                <li class="active"><a href="#go-charts-tab" data-toggle="tab">GO enrichment charts</a></li>
                <li><a href="#go-table-tab" data-toggle="tab">GO enrichment table</a></li>
                <li><a href="#go-graph-tab" data-toggle="tab">GO enrichment graph</a></li>
                <div class="btn-group pull-right" role="group" style="margin-top: 10px;">
                    <form action="<?php echo $download_url; ?>" method="post">
                        <button class="btn btn-default btn-sm" type="submit"><span
                                    class="glyphicon glyphicon-download-alt"></span> Download results
                        </button>
                    </form>
                </div>
            </ul>
        </section>
        <div class="tab-content page-section-sm">

        <div class="tab-pane active" id="go-charts-tab"><br>
            <?php
            //CHARTS
            echo "<div>\n";
            echo "<div class='page-section' id='go-charts'>";
            $go_types_titles = array("MF" => "Molecular function", "CC" => "Cellular component", "BP" => "Biological process");
            foreach ($go_types as $go_type => $gos) {
                echo "<div class='row'><div class='col-md-10 col-md-offset-1'>";
                echo "<div class='panel panel-default'>";
                echo "<div class='panel-heading'>";
                echo $go_types_titles[$go_type];
                echo "</div>";
                echo "<div class='panel-body'>";
                $order_pval = array();
                $n_results = 0;
                foreach ($gos as $go) {
                    $order_pval[$go] = $result[$go]['p-value'];
                    if ($result[$go]['is_hidden'] == 0) {
                        $n_results += 1;
                    }
                }
                array_multisort($order_pval, SORT_ASC, $gos);

                if ($n_results > 0) {
                    echo "<div class='enrichment-chart-wrapper'>";
                    echo $this->element('charts/bar_go_enrichment', array("chart_title" => "GO enrichment results (" . $subset . ")", "chart_subtitle" => "GO aspect: " . strtolower($go_types_titles[$go_type]), "enrichment_results" => $result, "descriptions" => $go_descriptions, "go_type" => $go_type, "go_terms" => $gos, "chart_div_id" => "go_enrichment_chart_" . $go_type, "linkout" => $this->Html->Url(array("controller" => "functional_annotation", "action" => "go", $exp_id))));
                    echo "</div>";
                } else {
                    echo "<p class='text-justify'><strong>No GO enrichment chart to show for this aspect: no enriched GO term was found.</strong></p>";
                }
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
            echo "</div>\n";
            echo "</div>\n";
            ?>
        </div><!-- end GO charts tab -->

        <div class="tab-pane" id="go-table-tab">
        <div class="checkbox input-sm" id="go-parent-check-wrapper">
            <label>
                <input type="checkbox" id="go-parent-check" name="go-parent-check" value="y"> Show hidden
            </label>
        </div>

    <?php

    //TABLE
    echo "<table id='go-table' class='" . $enr_table_class . "'>\n";
    echo "<thead>";
    echo "<th class='col-aspect'>Aspect</th>";
    echo "<th class='hidden'>Aspect string</th>";  // Hidden but present for GO aspect filtering
    echo "<th>GO term</th>";
    echo "<th>Description</th>";
    echo "<th>Enrichment (log<sub>2</sub>)</th>";
    echo "<th>q-value</th>";
    echo "<th>Subset ratio</th>";
    echo "<th>Shown</th>";
    echo "</thead>\n";
    echo "<tbody>";
    foreach ($go_types as $go_type => $gos) {
        foreach ($gos as $counter => $go_id) {
            $go_web_id = str_replace(":", "-", $go_id);
            $res = $result[$go_id];
            $formatted_p_value = sprintf("%." . $enr_table_decimals . "e", $res["p-value"]);
            echo "<tr>";
            echo "<td class='text-center'>";
            echo $this->element("go_category_badge", array("go_category" => $go_type, "small_badge" => true, "no_color" => true));
            echo "</td>";
            echo "<td class='hidden'>" . $go_types_titles[$go_type] . "</td>";
            echo "<td>" . $this->Html->link($go_id, array("controller" => "functional_annotation", "action" => "go", $exp_id, $go_web_id)) . "</td>";
            echo "<td>" . $go_descriptions[$go_id][0] . "</td>";
            echo "<td>" . number_format($res['enrichment'], $enr_table_decimals) . "</td>";
            echo "<td>" . $formatted_p_value . "</td>";
            echo "<td>" . $this->Html->link(number_format($res["subset_ratio"], $enr_table_decimals) . "%", array("controller" => "trapid", "action" => "transcript_selection", $exp_id, "go", $go_web_id, "label", $subset)) . "</td>";
            if ($res['is_hidden']) {
                echo "<td class='text-center'><span class='material-icons md-18 text-danger'>close</span></td>";
            } else {
                echo "<td class='text-center'><span class='material-icons md-18 text-success'>check</span></td>";
            }
            echo "</tr>\n";
        }
    }
    echo "</tbody>";
    echo "</table>\n";
    ?>
        </div> <!-- End GO table tab -->

        <div class="tab-pane" id="go-graph-tab">
            <?php echo $this->element('enrichment_go_graph'); ?>
        </div>


        <script type='text/javascript'>
        // DataTables
        enrichmentDataTable("go-table", [[5, "asc"]], 1, "All aspects");
        $('#go-table').DataTable().column(7).search("check", true, false, false).draw(false);

        $('#go-parent-check').on('change', function () {
            if($(this).is(":checked")) {
                $('#go-table').DataTable().column(7).search("", true, false, false).draw(false);
            }
            else {
                $('#go-table').DataTable().column(7).search("check", true, false, false).draw(false);
            }

        });
    </script>
<?php
    }

    else if($type=="ipr"){
        ?>
        <section class="page-section-sm">
            <ul class="nav nav-tabs" id="tabs" data-tabs="tabs">
                <li class="active"><a href="#ipr-charts-tab" data-toggle="tab">Protein domain enrichment chart</a></li>
                <li><a href="#ipr-table-tab" data-toggle="tab">Protein domain enrichment table</a></li>
                <div class="btn-group pull-right" role="group" style="margin-top: 10px;">
                    <form action="<?php echo $download_url; ?>" method="post">
                        <button class="btn btn-default btn-sm" type="submit"><span
                                    class="glyphicon glyphicon-download-alt"></span> Download results</button>
                    </form>
                </div>

            </ul>
        </section>
        <div class="tab-content page-section-sm">
        <div class="tab-pane active" id="ipr-charts-tab"><br>


            <?php
        //CHARTS    
        echo "<div>\n";
        echo "<div class='row'><div class='col-lg-10 col-lg-offset-1'>";
        echo "<div class='panel panel-default'>";
        echo "<div class='panel-heading'>Protein domain enrichment chart</div>";
        echo "<div class='panel-body'>";
        $order_pval = array();
        $n_results = 0;
        foreach ($result as $res)
        {
            $order_pval[$res['ipr']] = $res['p-value'];
            if($res['is_hidden'] == 0) {
                $n_results +=1;
            }
        }
        array_multisort($order_pval, SORT_ASC, $result);

        if($n_results > 0) {
            echo "<div class='enrichment-chart-wrapper'>";
            echo $this->element('charts/bar_ipr_enrichment', array("chart_title"=>"Enrichment results (".$subset.")" , "chart_subtitle"=>"InterPro domains", "enrichment_results"=>$result, "descriptions"=>$ipr_descriptions, "chart_div_id"=>"ipr_enrichment_chart", "linkout"=>$this->Html->Url(array("controller"=>"functional_annotation","action"=>"interpro",$exp_id))));
            echo "</div>";
        }

        else {
            echo "<p class='text-justify'><strong>No protein domain enrichment chart to show: no enriched IPR term was found. </strong></p>";
        }
        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "</div>\n";
        ?>
        </div>
        <div class="tab-pane" id="ipr-table-tab"><br>

    <?php
        //TABLE
        echo "<table id='ipr-table' class='" . $enr_table_class . "'>\n";
        echo "<thead>";
        echo "<tr>";    
        echo "<th>Type</th>";
        echo "<th>Protein domain</th>";
        echo "<th>Description</th>";
        echo "<th>Enrichment (log<sub>2</sub>)</th>";
        echo "<th>q-value</th>";
        echo "<th>Subset ratio</th>";
        echo "<th>Shown</th>";
        echo "</tr>\n";
        echo "</thead>";
        echo "<tbody>";
        foreach($result as $ipr=>$res){
            $formatted_p_value = sprintf("%.". $enr_table_decimals . "e", $res["p-value"]);
            echo "<tr>";
            echo "<td>" . str_replace("_", " ", $ipr_types[$ipr][0]) . "</td>";
            echo "<td>".$this->Html->link($ipr,array("controller"=>"functional_annotation","action"=>"interpro",$exp_id,$ipr))."</td>";
            echo "<td>" . $ipr_descriptions[$ipr][0] . "</td>";
            echo "<td>".number_format($res['enrichment'],$enr_table_decimals)."</td>";
            echo "<td>" . $formatted_p_value . "</td>";
            echo "<td>".$this->Html->link(number_format($res["subset_ratio"],$enr_table_decimals)."%",array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"interpro",$ipr,"label",$subset))."</td>";
            if ($res['is_hidden']) {
                echo "<td class='text-center'><span class='material-icons md-18 text-danger'>close</span></td>";
            } else {
                echo "<td class='text-center'><span class='material-icons md-18 text-success'>check</span></td>";
            }
            echo "</tr>\n";
        }
                    
        echo "</tbody>\n";
        echo "</table>\n";

    // DataTables
    echo "<script type='text/javascript'>\n";
    echo "enrichmentDataTable(\"ipr-table\", [[4, \"asc\"]], 0, \"All types\");\n";
    echo "</script>\n";
    }

    else if($type == "ko") {
        ?>
        <section class="page-section-sm">
            <ul class="nav nav-tabs" id="tabs" data-tabs="tabs">
                <li class="active"><a href="#ko-charts-tab" data-toggle="tab">KO enrichment chart</a></li>
                <li><a href="#ko-table-tab" data-toggle="tab">KO enrichment table</a></li>
                <div class="btn-group pull-right" role="group" style="margin-top: 10px;">
                    <form action="<?php echo $download_url; ?>" method="post">
                        <button class="btn btn-default btn-sm" type="submit"><span
                                    class="glyphicon glyphicon-download-alt"></span> Download results</button>
                    </form>
                </div>

            </ul>
        </section>
        <div class="tab-content page-section-sm">
        <div class="tab-pane active" id="ko-charts-tab"><br>

            <?php
            //CHARTS
            echo "<div>\n";
            echo "<div class='row'><div class='col-lg-10 col-lg-offset-1'>";
            echo "<div class='panel panel-default'>";
            echo "<div class='panel-heading'>KO enrichment chart</div>";
            echo "<div class='panel-body'>";
            $order_pval = array();
            $n_results = 0;
            foreach ($result as $res)
            {
                $order_pval[$res['ko']] = $res['p-value'];
                if($res['is_hidden'] == 0) {
                    $n_results +=1;
                }
            }
            array_multisort($order_pval, SORT_ASC, $result);

            if($n_results > 0) {
                echo "<div class='enrichment-chart-wrapper'>";
                echo $this->element('charts/bar_ko_enrichment', array("chart_title"=>"Enrichment results (".$subset.")" , "chart_subtitle"=>"KEGG Orthology", "enrichment_results"=>$result, "descriptions"=>$ko_descriptions, "chart_div_id"=>"ko_enrichment_chart", "linkout"=>$this->Html->Url(array("controller"=>"functional_annotation","action"=>"ko",$exp_id))));
                echo "</div>";
            }

            else {
                echo "<p class='text-justify'><strong>No KO enrichment chart to show: no enriched KO term was found. </strong></p>";
            }
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>\n";
            ?>
        </div>
        <div class="tab-pane" id="ko-table-tab"><br>

        <?php
        //TABLE
        echo "<table id='ko-table' class='" . $enr_table_class . "'>\n";
        echo "<thead>";
        echo "<tr>";
        echo "<th>KO</th>";
        echo "<th>Description</th>";
        echo "<th>Enrichment (log<sub>2</sub>)</th>";
        echo "<th>q-value</th>";
        echo "<th>Subset ratio</th>";
        echo "<th>Shown</th>";
        echo "</tr>\n";
        echo "</thead>";
        echo "<tbody>";
        foreach($result as $ko=>$res){
            $formatted_p_value = sprintf("%.". $enr_table_decimals . "e", $res["p-value"]);
            echo "<tr>";
            echo "<td>".$this->Html->link($ko,array("controller"=>"functional_annotation","action"=>"ko",$exp_id,$ko))."</td>";
            echo "<td>" . $ko_descriptions[$ko][0] . "</td>";
            echo "<td>".number_format($res['enrichment'],$enr_table_decimals)."</td>";
            echo "<td>" . $formatted_p_value . "</td>";
            echo "<td>".$this->Html->link(number_format($res["subset_ratio"],$enr_table_decimals)."%",array("controller"=>"trapid","action"=>"transcript_selection",$exp_id,"ko",$ko,"label",$subset))."</td>";
            if ($res['is_hidden']) {
                echo "<td class='text-center'><span class='material-icons md-18 text-danger'>close</span></td>";
            } else {
                echo "<td class='text-center'><span class='material-icons md-18 text-success'>check</span></td>";
            }
            echo "</tr>\n";
        }

        echo "</tbody>\n";
        echo "</table>\n";

        // DataTables
        echo "<script type='text/javascript'>\n";
        echo "enrichmentDataTable(\"ko-table\", [[3, \"asc\"]], -1, null);\n";
        echo "</script>\n";
    }
}
?>
