<?php
$min_url_str = '/min_transcript_length/';
$max_url_str = '/max_transcript_length/';
if (isset($sequence_type) && $sequence_type == 'orf') {
    $min_url_str = '/min_orf_length/';
    $max_url_str = '/max_orf_length/';
}
?>
<!-- Barchart div -->
<div class="hc hc-length-distribution" id="<?php echo $chart_div_id; ?>"></div>
<!-- Barchart JS -->
<script type='text/javascript' defer="defer">
    var lengthDistChart = Highcharts.chart('<?php echo $chart_div_id; ?>', {
        credits: {
            enabled: false
        },
        chart: {
            backgroundColor: null,
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
                // Get ranges + reformat them
                $n_ranges = sizeof($chart_data['values']);
                $i = 0;
                foreach ($chart_data['values'] as $chart_val) {
                    $i++;
                    $range_str = str_replace(',', ' - ', $chart_val['label']);
                    echo "'" . $range_str . "'";
                    if ($i < $n_ranges) {
                        echo ', ';
                    }
                }
                ?>
            ],
            crosshair: true,
            gridLineWidth: 1,
            gridLineColor: "#e5e5e5",
            tickInterval: 1,
            title: {
                text: 'Sequence nucleotide length'
            },
            labels: {
                rotation: -45,
                formatter: function() {
                    const lengthInterval = this.value.split(' - ');
                    const lblHrefParts = [
                        '<?php echo $this->Html->url([
                            'controller' => 'trapid',
                            'action' => 'transcript_selection',
                            $exp_id
                        ]); ?>'
                    ];
                    if (lengthInterval.length === 1 && lengthInterval[0].startsWith('>=')) {
                        const minLength = lengthInterval[0].replace('>=', '');
                        lblHrefParts.push('<?php echo $min_url_str; ?>', minLength);
                    } else {
                        lblHrefParts.push('<?php echo $min_url_str; ?>', lengthInterval[0], '<?php echo $max_url_str; ?>', lengthInterval[1]);
                    }
                    return `<a target='_blank' href='${lblHrefParts.join('')}'>${this.value}</a>`
                },
                useHTML: true
            }
        }],
        yAxis: {
            labels: {
                format: '{value}'
            },
            title: {
                text: 'Sequence count'
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
            formatter: function() {
                let tooltipContent = `<strong>Range: ${this.x} bp</strong>`;
                for (i = 0; i < this.points.length; i++) {
                    tooltipContent += `<br><i class="hc-tooltip-dot" style="background-color:${this.points[i].color}"></i> `;
                    tooltipContent += `${this.points[i].series.name}: <strong>${this.points[i].y}</strong>`;
                }
                return tooltipContent;
            }
        },
        legend: {
            title: {
                text: 'Data type  <br><span class="hc-hide-legend">Click to hide</span>'
            },
            align: 'right',
            verticalAlign: 'middle',
            layout: 'vertical',
            x: 0,
            useHTML: true,
            backgroundColor: 'transparent'
        },
        series: [
            <?php
            $n_series = sizeof($chart_data['label']);
            for ($i = 0; $i < $n_series; $i++): ?> {
                    name: '<?php echo $chart_data['label'][$i]; ?>',
                    type: 'column',
                    data: [<?php
                    $n_vals = sizeof($chart_data['values']);
                    $j = 0;
                    foreach ($chart_data['values'] as $chart_val) {
                        echo $chart_val['values'][$i];
                        $j++;
                        if ($j < $n_vals) {
                            echo ', ';
                        }
                    }
                    ?>]

                }
                <?php echo $i != $n_series - 1 ? ",\n" : ''; ?>
            <?php endfor; ?>
        ],
        plotOptions: {
            series: {
                stacking: '<?php echo $stacking_str; ?>'
            },
            column: {
                pointPadding: 0,
                borderWidth: 0,
                groupPadding: 0,
                shadow: false
            }
        }
    });
</script>
