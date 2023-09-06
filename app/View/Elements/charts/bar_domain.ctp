<!-- Barchart div -->
<div id="<?php echo $chart_div_id; ?>" class="hc-bar-tax"></div>

<!-- Barchart JS -->
<script type="text/javascript" defer="defer">
    $(function() {
        Highcharts.setOptions({
            lang: {
                numericSymbols: null
            },
            colors: ["#F8766D", "#00BF7D", "#00B0F6", "#E76BF3", "#A3A500"]
        });
        var myChart = Highcharts.chart('<?php echo $chart_div_id; ?>', {
            credits: {
                enabled: false
            },
            chart: {
                backgroundColor: null,
                plotBorderWidth: null,
                plotShadow: false,
                type: 'column'
            },
            title: {
                text: '<?php echo $chart_title; ?>'
            },
            subtitle: {
                text: '<?php echo $chart_subtitle; ?>'
            },
            yAxis: {
                maxPadding: 0,
                endOnTick: false,
                title: {
                    text: '# Transcripts (log<sub>10</sub> scale)',
                    useHTML: true
                },
                type: 'logarithmic'
            },
            xAxis: {
                type: 'category'
            },
            tooltip: {
                pointFormat: '<strong>{point.y}</strong> transcripts',
                backgroundColor: "white"
            },
            plotOptions: {
                column: {
                    events: {
                        click: function(event) {
                            if (ctrlIsPressed && ctrlIsPressed == true) {
                                var selected_tax = this.points[event.point.category].name;
                                update_tax_list(tax_list, selected_tax);
                                console.log(selected_tax);
                            }
                        }
                    }
                }
            },
            legend: {
                enabled: false
            },
            series: [{
                name: 'Transcripts',
                colorByPoint: true,
                data: [
                    <?php
                    // Get array keys and fetch last key
                    $data_keys = array_keys($chart_data);
                    $last_key = array_pop($data_keys);
                    foreach ($chart_data as $key => $value) {
                        echo '{ ' . "name: '" . $value[0] . "', ";
                        if ($value[0] == 'Unclassified') {
                            echo "color: '#bcbcbc', ";
                        }
                        echo 'y: ' . $value[1];
                        if ($key != $last_key) {
                            echo '}, ';
                        } else {
                            echo '}';
                        }
                    }
                    ?>
                ]
            }]
        });
    });
</script>
