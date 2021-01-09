<?php
// Replace tax. rank string used in the legend if not set.
if(!isset($legend_tax_str)) {
    $legend_tax_str = "taxa";
}
?>

<!-- Piechart div -->
<div id="<?php echo $chart_div_id; ?>" style="width:100%; height:400px;"></div>

<!-- Piechart JS -->
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
                backgroundColor: null,
                plotBorderWidth: null,
                plotShadow: false,
                type: 'pie'
            },
            title: {
                text: '<?php echo $chart_title; ?>'
            },
            subtitle: {
                text: '<?php echo $chart_subtitle; ?>'
            },
            tooltip: {
                pointFormat: '<strong>{point.y}</strong> transcripts ({point.percentage:.1f}%)',
                backgroundColor: "white"
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true
                    },
                    showInLegend: true,
                    borderWidth:0,
                    events: {
                        click: function (event) {
                            if(ctrlIsPressed && ctrlIsPressed==true) {
                                var selected_tax = event.point.options.name;
                                update_tax_list(tax_list, selected_tax);
                                console.log(selected_tax);
                            }
                        }
                    }
                }
            },
            legend: {
                title: {
                    // The legend changes based on the selected tax. rank (i.e. we need to write 'phyla', 'genera', ...)
                    text: 'Top ' + '<?php echo $legend_tax_str; ?>' + '<br><span style="font-size: 9px; color: #666; font-weight: normal"><em>Click to hide</em></span>'
                },
                align: 'right',
                verticalAlign: 'top',
                layout: 'vertical',
                x: 0,
                y: 100,
                // TODO: solve bug (conflict between use of `useHTML` and the templating?)
                 useHTML: true
            },
            series: [{
                name: 'Transcripts',
                colorByPoint: true,
                data: [
                <?php
                    // Get array keys and fetch last key
                    $data_keys = array_keys($chart_data);
                    $last_key = array_pop($data_keys);
                    foreach ($chart_data as $key=>$value) {
                        echo "{";
                        echo "name: '".$value[0]."', ";
                        if($value[0] == "Other"){
                            echo "color: '#e5e5e5', ";
                        }
                        if($value[0] == "Unclassified") {
                            echo "color: '#bcbcbc', ";
                        }
                        echo "y: ".$value[1];
                        if($key != $last_key) {
                            echo "}, ";
                        }
                        else {
                            echo "}";
                        }
                    }
                    ?>
        ]
    }]
    });
    });
</script>