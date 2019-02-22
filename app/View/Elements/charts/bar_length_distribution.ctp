<!-- Barchart div -->
<div class="hc" id="<?php echo $chart_div_id; ?>" style="width:100%; height:475px;"></div>

<style>
    .highcharts-tooltip span {
        background-color:white;
        border:1px solid #2196f3;
        opacity:0.8;
        padding: 7px;
        z-index:9999;
    }
    .tooltip-dot {
        height: 8px;
        width: 8px;
        border-radius: 50%;
        border: 0px solid #fff;
        display: inline-block;
        margin-right: 2px;
    }
</style>
<?php
// $length_ranges = $chart_data['labels'];
?>
<!-- Barchart JS -->
<script type='text/javascript' defer="defer">
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
                // Get ranges + reformat them
                $n_ranges = sizeof($chart_data['values']);
                $i = 0;
                foreach ($chart_data['values'] as $chart_val) {
                    $i++;
                    $range_str = str_replace(',',' - ', $chart_val['label']);
                    echo "'" . $range_str . "'";
                    if($i < $n_ranges) {
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
                    return '<a target=\'_blank\' href=\'<?php echo $this->Html->url(array("controller"=>"trapid","action"=>"transcript_selection",$exp_id)); ?>' + '/min_transcript_length/' + this.value.split(' - ')[0] + '/max_transcript_length/' + this.value.split(' - ')[1] + "\'>" + this.value + '</a>'
                },
                useHTML: true,
                title: {
                    text: 'Length'
                }
            }
        }],
        yAxis: { // Primary yAxis
            labels: {
                format: '{value}' //,
            },
            title: {
                text: 'Transcript count'
            },
            gridLineColor: "white",
            gridLineWidth: 1
        },
        tooltip: {
            borderWidth: 0,
            backgroundColor: "rgba(255,255,255,0)",
            borderRadius: 0,
            shadow: false,
            shared: true,
            useHTML: true,
            formatter: function () {
                console.log(this);
                var s = '<strong>Range: ' + this.x + ' bp</strong>';
                var n_series = this.points.length;
                for (i = 0; i < n_series; i++) {
                    s += '<br><i class="tooltip-dot" style="background-color:' + this.points[i].color + ';"></i> ';
                    s += this.points[i].series.name + ': <strong>' + this.points[i].y + '</strong>';

                }
//                s += '<br>' + this.points[1].series.name + ' (-log<sub>10</sub>): ' + Highcharts.numberFormat(this.points[1].y, 3);
//                s = '<div class="tooltip">' + s + '</div>';
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
            useHTML:true,
            backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF'
        },
        series: [
            <?php
            $n_series = sizeof($chart_data['label']);
            for ($i = 0; $i < $n_series; $i++): ?>
            {
                name: '<?php echo $chart_data['label'][$i]?>',
                type: 'column',
                data: [<?php
                        $n_vals = sizeof($chart_data['values']);
                        $j = 0;
                        foreach($chart_data['values'] as $chart_val) {
                            echo $chart_val['values'][$i];
                            $j++;
                            if($j < $n_vals) {
                                echo ", ";
                            }
                        }
                    ?>]

            }<?php if($i != ($n_series-1)) {  echo ",\n"; } ?>
                <?php endfor; ?>
            ],
        plotOptions: {
            series: {
                stacking: '<?php echo $stacking_str; ?>'
            }
        }


    });

</script>

<?php // echo $this->element('sql_dump');  // Dump all MySQL queries (debug) ?>