<!-- Barchart div -->
<div class="hc" id="<?php echo $chart_div_id; ?>" style="width:100%; height:425px;"></div>

<!-- Barchart JS -->
<script type='text/javascript' defer="defer">
    $(function () {
        Highcharts.setOptions({
            colors: ["#F8766D", "#A3A500", "#00BF7D", "#00B0F6", "#E76BF3"]
        });
    });

// Set go term -> description dictionary
var ipr_descriptions = {
    <?php
    // Get array keys and fetch last key
    $all_ipr = array();
    foreach ($enrichment_results as $ipr=>$result) {
        if ($enrichment_results[$ipr]['is_hidden'] == 0) {
            array_push($all_ipr, $ipr);
        }
    }
    $last_index = array_pop(array_keys($all_ipr));
    foreach ($all_ipr as $ipr) {
        echo "\"" . $ipr . "\": \"" . $descriptions[$ipr][0] . "\"";
        if($ipr != $last_index) {
            echo ", ";
        }
    }

    ?>
};
console.log(ipr_descriptions);


    var myChart = Highcharts.chart('<?php echo $chart_div_id; ?>', {
        credits: {
            enabled:false
        },
        chart: {
            zoomType: 'x',
            plotBackgroundColor: "#e5e5e5"
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
                foreach ($enrichment_results as $ipr=>$result) {
                    if ($enrichment_results[$ipr]['is_hidden'] == 0) {
                        array_push($enrichment_data, $enrichment_results[$ipr]['ipr']);
                    }
                }
                $last_index = array_pop(array_keys($enrichment_data));
                foreach ($enrichment_data as $index=>$enrichment) {
                    echo "'" . $enrichment . "'";
                    if($index != $last_index) {
                        echo ", ";
                    }
                }
                ?>
            ],
            crosshair: true,
            gridLineWidth: 1,
            gridLineColor: "white",
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
                format: '{value}',
                style: {
                    color: Highcharts.getOptions().colors[1]
                }
            },
            title: {
                text: '-log10(p-value)',
                style: {
                    color: Highcharts.getOptions().colors[1]
                }
            },
            gridLineColor: "white",
            gridLineWidth: 1
        }, { // Secondary yAxis
            title: {
                text: 'Enrichment score',
                /*  style: {
                 color: "#F8766D"// Highcharts.getOptions().colors[0]
                 }*/
            },
            labels: {
                format: '{value}',
                /*                   style: {
                 color: Highcharts.getOptions().colors[0]
                 }
                 */               },
            opposite: true,
            gridLineWidth: 0
        }],
        tooltip: {
            shared: true,
            // crosshairs: [true, true],
            formatter: function () {
                var s = '<strong>' + ipr_descriptions[this.x] + '</strong>';
                s+=  '<br>ID: ' + this.x;
                s += '<br>' + this.points[0].series.name + ': ' + Highcharts.numberFormat(this.points[0].y, 3);
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
            yAxis: 1,
            color: "#F8766D",
            data: [
                <?php
                // Get array keys and fetch last key
                $enrichment_data = array();
                foreach ($enrichment_results as $ipr=>$result) {
                    if ($enrichment_results[$ipr]['is_hidden'] == 0) {
                        array_push($enrichment_data, $enrichment_results[$ipr]['enrichment']);
                    }
                }
                $last_index = array_pop(array_keys($enrichment_data));
                foreach ($enrichment_data as $index=>$enrichment) {
                    echo $enrichment;
                    if($index != $last_index) {
                        echo ", ";
                    }
                }
                ?>
            ]

        }, {
            name: 'P-value',
            type: 'spline',
            color: "#444444",
            data: [
                <?php
                // Get array keys and fetch last key
                $enrichment_data = array();
                foreach ($enrichment_results as $ipr=>$result) {
                    if ($enrichment_results[$ipr]['is_hidden'] == 0) {
                        array_push($enrichment_data, -log10($enrichment_results[$ipr]['p-value']));
                    }
                }
                $last_index = array_pop(array_keys($enrichment_data));
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
//        });
    });


</script>