<!-- Core GFs barchart div -->
<div class="hc" id="<?php echo $chart_div_id; ?>" style="width:100%; height:400px;"></div>

<!-- Core GFs barchart JS -->
<script type='text/javascript' defer="defer">
    $(function () {
        /*
        Highcharts.setOptions({
            colors: ['#4662a0', '#aadb87', '#da495b', '#66edc6', '#fde5a5', '#66ceed', '#fdb7a5', '#7ea45d', '#eace6b',
                '#7cb5ec', '#90ed7d', '#f7a35c', '#8085e9', '#f15c80', '#e4d354', '#2b908f', '#f45b5b', '#91e8e1']
        });
        */
        var completenessBarchart = Highcharts.chart('<?php echo $chart_div_id; ?>', {
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
                categories: ["Represented", "Missing"]
            },
            yAxis: {
                maxPadding: 0,
                max: <?php echo $n_total; ?>,
                endOnTick: false,
                title: { text: 'Number of core GFs' },
                labels: {
                    style: {
                        color: '#bbb'
                    },
                    formatter: function () {
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
                labelFormatter: function () {
                    if(this.data.length > 0) {
                        return this.data[0].category;
                    } else {
                        return this.name;
                    }
                },
                title: {
                    text: 'Core GFs<br><span style="font-size: 9px; color: #666; font-weight: normal"><em>Click to hide</em></span>'
                },
                align: 'right',
                verticalAlign: 'top',
                layout: 'vertical',
                x: 0,
                y: 100,
                useHTML: true
            },
            series: [
                {
                    data: [{x: 0, y: <?php echo $n_represented; ?>}],
                    name: "Represented"
                },
                {
                    data: [{x: 1, y: <?php echo $n_missing; ?>}],
                    name: "Missing"
                }
                ]
        });
    });
</script>