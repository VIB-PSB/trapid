<!-- Barchart div -->
<div class="hc hc-enrichment" id="<?php echo $chart_div_id; ?>"></div>

<!-- Barchart JS -->
<script type='text/javascript' defer="defer">
    $(function () {
        Highcharts.setOptions({
            colors: ["#F8766D", "#A3A500", "#00BF7D", "#00B0F6", "#E76BF3"]
        });
    });

// Set go term -> description dictionary
var go_descriptions_<?php echo $go_type ?> = {
    <?php
    $go_descriptions_array = array();
    // Get array keys and fetch last key
        $all_go = array();
    foreach ($go_terms as $go_term) {
        if ($enrichment_results[$go_term]['is_hidden'] == 0) {
            array_push($all_go, $go_term);
//            echo "\"" . $go_term . "\": 'aaaaaaa';\n";
        }
    }
    $all_go_keys = array_keys($all_go);
    $last_index = array_pop($all_go_keys);
    foreach ($all_go as $index=>$go_term) {
        echo "\"" . $go_term . "\": \"" . $descriptions[$go_term][0] . "\"";
        if($index != $last_index) {
            echo ", ";
        }
    }

    ?>
};
//console.log(go_descriptions_<?php //echo $go_type ?>//);
        var myChart = Highcharts.chart('<?php echo $chart_div_id; ?>', {
            credits: {
                enabled:false
            },
            chart: {
                zoomType: 'x',
                plotBackgroundColor: "#ffffff"
            },
            title: {
                text: '<?php echo $chart_title; ?>'
            },
            subtitle: {
                text: '<?php echo $chart_subtitle; ?>'
            },
            xAxis: [{
                categories: [
                    <?php
                    // Get array keys and fetch last key
                    $enrichment_data = array();
                    foreach ($go_terms as $go_term) {
                        if ($enrichment_results[$go_term]['is_hidden'] == 0) {
                            array_push($enrichment_data, $enrichment_results[$go_term]['go']);
                        }
                    }
                    $enrichment_keys = array_keys($enrichment_data);
                    $last_index = array_pop($enrichment_keys);
                    foreach ($enrichment_data as $index=>$enrichment) {
                        echo "'" . $enrichment . "'";
                        if($index != $last_index) {
                            echo ", ";
                        }
                    }
                    ?>
                ],
                crosshair: true,
                gridLineWidth: 0,
                gridLineColor: "#e5e5e5",
                tickInterval: 1,
                labels: {
                    rotation: -45,
                    formatter: function () {
                        return '<a target=\'_blank\' href=\'<?php echo $linkout; ?>' + '/' + this.value.replace(":", "-") + "\'>" + this.value + '</a>'
                    },
                useHTML: true
                }
            }],
            yAxis: [{ // Primary yAxis
                labels: {
                    format: '{value}'
                },
                title: {
                    text: 'log<sub>2</sub>(enrichment)',
                    useHTML: true
                },
                gridLineWidth: 1,
                gridLineColor: "#e5e5e5"
            }, { // Secondary yAxis
                title: {
                    text: '-log<sub>10</sub>(q-value)',
                    useHTML: true
                },
                labels: {
                    format: '{value}'
                },
                opposite: true,
                gridLineWidth: 0
            }],
            tooltip: {
                shared: true,
                    // crosshairs: [true, true],
                formatter: function () {
                    var s = '<strong>' + go_descriptions_<?php echo $go_type ?>[this.x] + '</strong>';
                    // console.log(go_descriptions_<?php echo $go_type ?>[this.x]);
                    // console.log(this.x);
                    s+=  '<br>ID: ' + this.x;
                    s += '<br>' + this.points[0].series.name + ' (log<sub>2</sub>): ' + Highcharts.numberFormat(this.points[0].y, 3);
                    s += '<br>' + this.points[1].series.name + ' (-log<sub>10</sub>): ' + Highcharts.numberFormat(this.points[1].y, 3);
                    return s;
                    }
                },
            legend: {
                title: {
                    text: 'Data type  <br><span style="font-size: 11px; color: #666; font-weight: normal"><em>Click to hide</em></span>'
                },
                align: 'right',
                verticalAlign: 'middle',
                layout: 'vertical',
                x: 0,
//                y: 100,
                useHTML:true,
                backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF'
            },
            series: [{
                name: 'Enrichment',
                type: 'column',
                yAxis: 0,
                color: "#F8766D",
                data: [
                    <?php
                    // Get array keys and fetch last key
                    $enrichment_data = array();
                    foreach ($go_terms as $go_term) {
                        if ($enrichment_results[$go_term]['is_hidden'] == 0) {
                            array_push($enrichment_data, $enrichment_results[$go_term]['enrichment']);
                        }
                    }
                    $enrichment_keys = array_keys($enrichment_data);
                    $last_index = array_pop($enrichment_keys);
                    foreach ($enrichment_data as $index=>$enrichment) {
                        echo $enrichment;
                        if($index != $last_index) {
                            echo ", ";
                        }
                    }
                    ?>
                ]

            }, {
                name: 'q-value',
                type: 'spline',
                yAxis: 1,
                color: "#444444",
                data: [
                    <?php
                    // Get array keys and fetch last key
                    $enrichment_data = array();
                    foreach ($go_terms as $go_term) {
                        if ($enrichment_results[$go_term]['is_hidden'] == 0) {
                            array_push($enrichment_data, -log10(max($enrichment_results[$go_term]['p-value'], PHP_FLOAT_MIN )));
                        }
                    }
                    $enrichment_keys = array_keys($enrichment_data);
                    $last_index = array_pop($enrichment_keys);
                    foreach ($enrichment_data as $index=>$enrichment) {
                        echo $enrichment;
                        if($index != $last_index) {
                            echo ", ";
                        }
                    }
                    ?>
                ]
                }
            ]
    });
</script>