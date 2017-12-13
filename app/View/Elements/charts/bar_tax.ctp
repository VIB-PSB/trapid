<!-- Barchart div -->
<div id="<?php echo $chart_div_id; ?>" style="width:100%; height:400px;"></div>

<!-- Barchart JS -->
<script type='text/javascript' defer="defer">
    $(function () {
        Highcharts.setOptions({
            colors: ['#4662a0', '#aadb87', '#da495b', '#66edc6', '#fde5a5', '#66ceed', '#fdb7a5', '#7ea45d', '#eace6b',
                '#7cb5ec', '#90ed7d', '#f7a35c', '#8085e9', '#f15c80', '#e4d354', '#2b908f', '#f45b5b', '#91e8e1']
        });
        var myChart = Highcharts.chart('<?php echo $chart_div_id; ?>', {
                credits: {
                    enabled:false
                },
                chart: {
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
                xAxis: {
                    categories: [
                    <?php
                        // Get array keys and fetch last key
                        $last_key = array_pop(array_keys($chart_data));
                        foreach ($chart_data as $key=>$value) {
                            if($key != $last_key) {
                                echo "'".$value[0]."', ";
                            }
                            else {
                                echo "'".$value[0]."'";
                            }
                        }
                        ?>
                        ]
    },
        yAxis: {
            maxPadding: 0,
                endOnTick: false,
                title: { text: '# Transcripts' },
            labels: {
                style: {
                    color: '#bbb'
                }
            }
        },
        tooltip: {
            pointFormat: '<strong>{point.y}</strong> transcripts',
                backgroundColor: "white"
        },
        plotOptions: {
            column: {
                stacking: 'normal'
            }
        },
        legend: {
            enabled: false,
                labelFormatter: function () {
                if(this.data.length > 0) {
                    return this.data[0].category;
                } else {
                    return this.name;
                }
            },
            title: {
                text: 'Top phyla<br><span style="font-size: 9px; color: #666; font-weight: normal"><em>Click to hide</em></span>'
            },
            align: 'right',
                verticalAlign: 'top',
                layout: 'vertical',
                x: 0,
                y: 100,
                useHTML: true
        },
        series: [
        <?php
            // Get array keys and fetch last key
            $last_key = array_pop(array_keys($chart_data));
            foreach ($chart_data as $key=>$value) {
                echo "{";
                echo "data: [{x: ".$key.", "."y: ".$value[1]."}], ";
                if($value[0] == "Other"){
                    echo "color: '#aaaaaa', ";
                }
                echo "name: '".$value[0]."'";
                if($key != $last_key) {
                    echo "}, ";
                }
                else {
                    echo "}";
                }
            }
            ?>
        ]
    });
    });
</script>