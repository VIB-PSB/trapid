<!-- Core GFs barchart div -->
<div class="hc hc-core-gfs" id="<?php echo $chart_div_id; ?>"></div>

<!-- Core GFs barchart JS -->
<script type="text/javascript" defer="defer">
    $(function() {
        var completenessBarchart = Highcharts.chart('<?php echo $chart_div_id; ?>', {
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
            xAxis: {
                categories: ["Represented", "Missing"]
            },
            yAxis: {
                maxPadding: 0,
                max: <?php echo $n_total; ?>,
                endOnTick: false,
                title: {
                    text: 'Number of core GFs'
                },
                labels: {
                    style: {
                        color: '#bbb'
                    },
                    formatter: function() {
                        return this.value;
                    }
                }
            },
            tooltip: {
                pointFormat: '<strong>{point.y}</strong> core GFs',
                backgroundColor: "white"
            },
            plotOptions: {
                column: {
                    stacking: 'normal'
                }
            },
            legend: {
                enabled: true,
                labelFormatter: function() {
                    if (this.data.length > 0) {
                        return this.data[0].category;
                    } else {
                        return this.name;
                    }
                },
                title: {
                    text: 'Core GFs<br><span class="hc-hide-legend">Click to hide</span>'
                },
                align: 'right',
                verticalAlign: 'top',
                layout: 'vertical',
                x: 0,
                y: 100,
                useHTML: true
            },
            series: [{
                    data: [{
                        x: 0,
                        y: <?php echo $n_represented; ?>
                    }],
                    name: "Represented"
                },
                {
                    data: [{
                        x: 1,
                        y: <?php echo $n_missing; ?>
                    }],
                    name: "Missing"
                }
            ]
        });
    });
</script>
