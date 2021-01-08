<?php
if(isset($error)){
	echo "<span class='lead text-danger'>Error: ".$error."</span>\n";
}
else if(!isset($result)){
	echo "<p class='lead text-danger'>Undefined error: no output data</p>\n";
}
else if(count($result)==0){
	echo "<p class='lead'>No enriched terms found for selected subset and settings. </p>\n";
}
else{

    // Create a download URL
    $download_url = $this->Html->url(array("controller"=>"tools","action"=>"download_enrichment",$exp_id,$type,$subset,$selected_pvalue));

    /* Enrichment table view settings */
    // Define a class string to use for the enrichment tables
    $enr_table_class = "table table-hover table-striped table-condensed";
    $enr_table_decimals = 3;
    ?>

    <style>
/*        !* Override some dataTables styles *!
        .dataTables_length {
            display: inline-flex;
            !*text-align: right;*!
            vertical-align: top;
            margin-left: 15px;
        }
        .dataTables_info {
            display: inline-flex;
            !*text-align: right;*!
            vertical-align: top;
            margin-right: 15px;
            padding-top: 3px!important;
        }
        .dataTables_length select {
            width: auto!important;
        }

        .dataTables_filter {
            text-align: right;
            display: inline-flex;
            margin-left: 10px;
        }
        .dataTables_filter input {
            max-width: 120px!important;
        }

        !*.dataTables_filter label {*!
        !*font-weight: bold !important;*!
        !*}*!

        .dataTables_paginate {
            !*border: 2px purple solid;*!
            display: inline-flex;
        }
        .dataTables_info {
            color: #bbbbbb;
        }
        !*#go-filter label {*!
        !*font-weight: bold;*!
        !*}*!
        !*.col-aspect {*!
            !*width:1%;*!
            !*padding-right: 25px!important;*!
            !*white-space:nowrap;*!
        !*}*!*/
        #go-parent-div {
            padding-left: 0;
            margin-bottom:0;
        }
    </style>
    <script type='text/javascript'>
        // Convert enrichment table into interactive table (jquery datatable)
        // tableId: table html element id
        // orderCol: a 2d array of column indices / direction for default sorting of the table
        // filterIndex: index of column used for filtering select on top of the table. Set to '-1' to ignore filtering.
        // filterText: default text in the filtering element
        function enrichmentDataTable(tableId, orderCols, filterIndex, filterText, ) {
            $("#" + tableId).dataTable({
                // Layout of datatable elements
                dom:  // "<'row'<'#go-filter.col-sm-6'><'col-sm-6 text-right'f>>" +
                //                    "<'row'<'col-sm-4'><'col-sm-8 text-right'ipl>>" +
                "<'row'<'#table-filter.col-md-5 col-sm-12'f><'col-md-7 col-sm-12 text-right'ipl>>" +
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
                // Disable automatic width
                // autoWidth: false,
                // columnDefs: [
                //     { "width": "1%", "targets": 2}
                // ],
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
                                        .search(val ? '^' + val + '$' : '', true, false)
                                        .draw();
                                });
                            column.data().unique().sort().each(function (d, j) {
                                select.append('<option value="' + d + '">' + d + '</option>')
                            });
                        });
                    }
                    $("#" + tableId).width("100%");  // Needed because we've hidden the GO aspect string column
                }
            });
        }
    </script>

    <?php
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
	if($type=="go") {
        ?>
        <section class="page-section-sm">
            <ul class="nav nav-tabs" id="tabs" data-tabs="tabs">
                <!--            <li style="margin-top:10px;"><strong>View: </strong></li>-->
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
        <div class="tab-content">

        <div class="tab-pane active" id="go-charts-tab"><br>
            <?php
            //CHARTS
            echo "<div style='margin-bottom:20px;'>\n";
            echo "<div class='page-section' id='go-charts'>";
            $go_types_titles = array("MF" => "Molecular function", "CC" => "Cellular component", "BP" => "Biological process");
            foreach ($go_types as $go_type => $gos) {
                $data_chart = array("GO enrichment" => array("color" => "#123987", "font_size" => 11, "graph_style" => "bar"),
                    "p-value" => array("color" => "#789321", "font_size" => 11, "graph_style" => "lines")
                );

                echo "<div class='row'><div class='col-md-10 col-md-offset-1'>";
                echo "<div class='panel panel-default'>";
                echo "<div class='panel-heading'>";
//        echo "<h3 class='panel-title'>" . $go_types_titles[$go_type] . " " . $this->element("go_category_badge", array("go_category"=>$go_type, "small_badge"=>false, "no_color"=>true)) . "</h3>";
                echo "<h3 class='panel-title'>" . $go_types_titles[$go_type] . "</h3>";
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
                    echo "<div style='max-width:98%; margin: 0 auto;'>";
                    echo $this->element('charts/bar_go_enrichment', array("chart_title" => "GO enrichment results (" . $subset . ")", "chart_subtitle" => "GO aspect: " . strtolower($go_types_titles[$go_type]), "enrichment_results" => $result, "descriptions" => $go_descriptions, "go_type" => $go_type, "go_terms" => $gos, "chart_div_id" => "go_enrichment_chart_" . $go_type, "linkout" => $this->Html->Url(array("controller" => "functional_annotation", "action" => "go", $exp_id))));
                    echo "</div>";
                } else {
                    echo "<p class='text-justify'><strong>No GO enrichment chart to show for this aspect: no enriched GO term was found. </strong></p>";
                }
                echo "</div>";
/*                echo "<div class='panel-footer'>";
                if ($n_results > 0) {
                    echo $this->Html->link("View GO enrichment graph", array("controller" => "tools", "action" => "go_enrichment_graph", $exp_id, $subset, $go_type, $selected_pvalue));
                } else {
                    echo "<span class='text-muted'>View GO enrichment graph</span>";
                }
                echo "</div>";*/
                echo "</div>";
                echo "</div>";
                echo "</div>";


                $enrich_data = array();
                $pval_data = array();
                $links = array();
                $tips = array();
                $labels = array();
                $max_val = 0;
                $min_val = 0;
                $max_p_val = 5;
                foreach ($gos as $counter => $go_id) {
                    $web_go_id = str_replace(":", "-", $go_id);
                    $desc = $go_descriptions[$go_id][0];
                    $res = $result[$go_id];
                    if (!$res['is_hidden']) {
                        $val = number_format($res['enrichment'], 2);
                        $enrich_data[] = $val;
                        $split_p_value = explode("E", $res["p-value"]);
                        $p_value = 0;
                        if (count($split_p_value) == 2) {
                            if (substr($split_p_value[1], 0, 1) == '-') {
                                $p_value = substr($split_p_value[1], 1);
                            }
                        } else {
                            $p_t = $res['p-value'] / 1000.0;
                            $split_p_value = explode("E", $p_t);
                            if (count($split_p_value) == 2) {
                                if (substr($split_p_value[1], 0, 1) == '-') {
                                    $p_value = substr($split_p_value[1], 1);
                                    $p_value = $p_value - 3;
                                }
                            }
                        }
                        $pval_data[] = $p_value;
                        if ($val > $max_val) {
                            $max_val = $val;
                        }
                        if ($val < $min_val) {
                            $min_val = $val;
                        }
                        if ($p_value > $max_p_val) {
                            $max_p_val = $p_value;
                        }

                        $tips[] = str_replace(",", ";", $desc);
                        $links[] = $this->Html->url(array("controller" => "functional_annotation", "action" => "go", $exp_id, $web_go_id)
                            , true);
                        $labels[] = $go_id;
                    }
                }
                //attach extra empty data in case less than 4 GO's are present.
                if (count($enrich_data) < 4) {
                    for ($i = 0; $i <= (4 - count($enrich_data)); $i++) {
                        $enrich_data[] = 0;
                        $labels[] = "";
                        $tips[] = "";
                        $links[] = "";
                        $pval_data[] = -0.001;
                    }
                }

                array_multisort($pval_data, SORT_DESC, $enrich_data, $labels, $tips, $links);
                $max_p_val = intval($max_p_val) + 2;

                $data_chart["GO enrichment"]["data"] = $enrich_data;
                $data_chart["GO enrichment"]["links"] = $links;
                $data_chart["GO enrichment"]["tips"] = $tips;
                $data_chart["p-value"]["data"] = $pval_data;
                $data_chart["p-value"]["links"] = $links;
                $data_chart["p-value"]["tips"] = $tips;

                // echo "<center><span style='color:#153E7E;text-decoration:underline;'>".$go_types_titles[$go_type]."</span></center><br/><br/>";


            }

            echo "</div>\n";
            echo "</div>\n";
            ?>
        </div><!-- end GO charts tab -->

        <div class="tab-pane" id="go-table-tab">
        <div class="checkbox input-sm" id="go-parent-div">
            <label>
                <input type="checkbox" id="go-parent-check" name="go-parent-check" value="y"> Show hidden
            </label>
        </div>

    <?php

    //TABLE
    //	echo "<table class='table table-bordered table-striped' cellpadding='0' cellspacing='0' style='width:900px;'>\n";
    echo "<table id='go-table' class='" . $enr_table_class . "'>\n";
    echo "<thead>";
    echo "<th class='col-aspect'>Aspect</th>";
    echo "<th class='hidden'>Aspect string</th>";  // Hidden and just here for GO aspect filtering
    echo "<th>GO term</th>";
    echo "<th>Description</th>";
    echo "<th>Enrichment (log<sub>2</sub>)</th>";
    echo "<th>q-value</th>";
    echo "<th>Subset ratio</th>";
    echo "<th>Shown</th>";
    echo "</thead>\n";
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
    echo "</table>\n";
    ?>
        </div> <!-- End GO table tab -->

        <div class="tab-pane" id="go-graph-tab">
            <?php echo $this->element('enrichment_go_graph'); ?>
        </div>


        <script type='text/javascript'>
        // DataTables
        // $('#go-table').dataTable({"scrollY": "400px", "scrollCollapse": true, "paging": false});
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
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	else if($type=="ipr"){
	    ?>
        <section class="page-section-sm">
            <ul class="nav nav-tabs" id="tabs" data-tabs="tabs">
                <li class="active"><a href="#ipr-charts-tab" data-toggle="tab">Protein domain enrichment chart</a></li>
                <li><a href="#ipr-table-tab" data-toggle="tab">Protein domain enrichment table</a></li>
                <div class="btn-group" role="group" style="float: right; margin-top: 10px;">
                    <form action="<?php echo $download_url; ?>" method="post">
                        <button class="btn btn-default btn-sm" type="submit"><span
                                    class="glyphicon glyphicon-download-alt"></span> Download results</button>
                    </form>
                </div>

            </ul>
        </section>
        <div class="tab-content">
        <div class="tab-pane active" id="ipr-charts-tab"><br>


            <?php
		//CHARTS	
		echo "<div style='margin-bottom:20px;'>\n";
//		echo "<h4>Protein domain enrichment chart</h4><br>\n";
//		echo "<div style='width:860px;background-color:white;padding:20px;margin-top:10px;border:1px solid black;'>";


//        pr($result);
//        pr($ipr_descriptions);
        echo "<div class='row'><div class='col-lg-10 col-lg-offset-1'>";
        echo "<div class='panel panel-default'>";
        echo "<div class='panel-heading'><h3 class='panel-title'>Protein domain enrichment chart</h3></div>";
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
//        pr($n_results);
//        pr($order_pval);
        array_multisort($order_pval, SORT_ASC, $result);
//        pr($result);


        if($n_results > 0) {
            echo "<div style='max-width:98%; margin: 0 auto;'>";
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
//		echo "<h4>Protein domain enrichment data table</h4><br>\n";
        echo "<table id='ipr-table' class='" . $enr_table_class . "'>\n";
		echo "<thead>";
		echo "<tr>";	
		echo "<th>Type</th>";
		echo "<th>Protein domain</th>";
		echo "<th>Description</th>";
		echo "<th>Enrichment (log<sub>2</sub>)</th>";
		echo "<th>q-value</th>";
		echo "<th>Subset ratio</th>";
		echo "<th style='width:4%'>Shown</th>";
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
    //echo "$('#ipr-table').dataTable({\"scrollY\": \"400px\", \"scrollCollapse\": true, \"paging\": false});\n";
        echo "enrichmentDataTable(\"ipr-table\", [[4, \"asc\"]], 0, \"All types\");\n";
    echo "</script>\n";
	}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	else if($type == "ko") {
        ?>
        <section class="page-section-sm">
            <ul class="nav nav-tabs" id="tabs" data-tabs="tabs">
                <li class="active"><a href="#ko-charts-tab" data-toggle="tab">KO enrichment chart</a></li>
                <li><a href="#ko-table-tab" data-toggle="tab">KO enrichment table</a></li>
                <div class="btn-group" role="group" style="float: right; margin-top: 10px;">
                    <form action="<?php echo $download_url; ?>" method="post">
                        <button class="btn btn-default btn-sm" type="submit"><span
                                    class="glyphicon glyphicon-download-alt"></span> Download results</button>
                    </form>
                </div>

            </ul>
        </section>
        <div class="tab-content">
        <div class="tab-pane active" id="ko-charts-tab"><br>


            <?php
            //CHARTS
            echo "<div style='margin-bottom:20px;'>\n";
            echo "<div class='row'><div class='col-lg-10 col-lg-offset-1'>";
            echo "<div class='panel panel-default'>";
            echo "<div class='panel-heading'><h3 class='panel-title'>KO enrichment chart</h3></div>";
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
            //        pr($n_results);
            //        pr($order_pval);
            array_multisort($order_pval, SORT_ASC, $result);
            //        pr($result);


            if($n_results > 0) {
                echo "<div style='max-width:98%; margin: 0 auto;'>";
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
        echo "<th style='width:4%'>Shown</th>";
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