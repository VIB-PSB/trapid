<?php
// TODO: host locally
// Highcahrts
echo $javascript->link('http://code.highcharts.com/highcharts.js');
echo $javascript->link('http://code.highcharts.com/modules/exporting.js');
echo $javascript->link('d3-3.5.6.min.js');
echo $javascript->link('https://rawgit.com/unipept/unipept-visualizations/master/dist/unipept-visualizations.min.js');
?>

<div class="page-header">
    <h1 class="text-primary">Transcripts taxonomic binning</h1>
</div>
<!--<p class="lead text-justify">Explore taxonomic binning results with different visualization! </p>-->
<p class="text-justify"><strong>Usage:</strong> use the tabs to switch from one to the other. </p>

<div id="content">
    <ul class="nav nav-tabs nav-justified" id="tabs" data-tabs="tabs">
        <li class="active"><a href="#krona" data-toggle="tab">Krona</a></li>
        <li><a href="#tree-view" data-toggle="tab">Tree</a></li>
        <li><a href="#piecharts" data-toggle="tab">Pie</a></li>
        <li><a href="#barcharts" data-toggle="tab">Bar</a></li>
        <!--<li><a href="#raw-data" data-toggle="tab">Raw data</a></li>-->
    </ul>
    <div class="tab-content">
        <div id="krona" class="tab-pane active"><br>
            <p class="text-justify">Simply an <code>object</code> element that loads Krona HTML: </p>
            <object type="text/html" class="krona-display" data="<?php echo $display_krona_url; ?>">
                Default content. If this appears, Krona HTML file failed to load.
            </object>
        </div>

        <div id="tree-view" class="tab-pane"><br>
            <p class="text-justify">Unipept's tree viewer, inspired from this <a href="http://bl.ocks.org/robschmuecker/7880033" target="_blank">D3.js code snippet</a>.</p>
            <div id="d3-tree-view" style="border:1px solid #aaaaaa;"></div>

        </div>

        <div id="piecharts" class="tab-pane"><br>
<!--            <p class="text-justify">Generated using Highcharts.</p>-->
            <div class="row">
                <div class="col-md-4">
                    <?php echo
                        $this->element('charts/pie_domain', array("chart_title"=>"Domain composition", "chart_subtitle"=>"Total transcripts: ".$domain_sum_transcripts, "chart_data"=>$top_tax_domain, "chart_div_id"=>"top_tax_piechart"));
                    ?>
                </div>
                <div class="col-md-8">
                    <?php echo
                    $this->element('charts/pie_tax', array("chart_title"=>"Phylum composition", "chart_subtitle"=>"Total transcripts classified at that level: ".$phylum_sum_transcripts, "chart_data"=>$top_tax_phylum, "chart_div_id"=>"phylum_top_tax_piechart"));
                    ?>
                </div>
            </div>
        </div>

        <div id="barcharts" class="tab-pane"><br>
<!--            <p class="text-justify">Generated using Highcharts.</p>-->
            <div class="row">
                <div class="col-md-4">
                    <?php echo
                        $this->element('charts/bar_domain', array("chart_title"=>"Domain composition", "chart_subtitle"=>"Total transcripts: ".$domain_sum_transcripts, "chart_data"=>$top_tax_domain, "chart_div_id"=>"top_tax_barchart"));
                    ?>
                </div>
                <div class="col-md-8">
                    <?php echo
                    $this->element('charts/bar_tax', array("chart_title"=>"Phylum composition", "chart_subtitle"=>"Total transcripts classified at that level: ".$phylum_sum_transcripts, "chart_data"=>$top_tax_phylum, "chart_div_id"=>"phylum_top_tax_barchart"));
                    ?>
                </div>
            </div>
        </div>

        <!--<div id="raw-data" class="tab-pane"><br>
        <pre></pre>
        </div>-->
    </div>
</div>

<!--<div id="contig-table">-->
<!--<p class="text-justify">Insert transcripts table here? </p>-->
<!--</div>-->

<script type="text/javascript">
    // Get proper width for tree viewer
    // TODO: on window resize event, wait a bit and resize chart.
    function viz_width(){
        return(window.innerWidth - 390);
    }
    console.log(viz_width().toString());
    $(function () {
        d3.json("<?php echo $treeview_json_url; ?>", function (error, data) {
            if (error) return console.warn(error);
            $("#d3-tree-view").treeview(data, {
//                width: 1120,
                width: viz_width(),
                height: 650,
                getTooltip: function (d) {
                    let numberFormat = d3.format(",d");
                    return "<b>" + d.name + "</b> (" + d.data.rank + ")<br/>" + numberFormat(!d.data.self_count ? "0" : d.data.self_count) + (d.data.self_count && d.data.self_count === 1 ? " sequence" : " sequences") +
                        " specific to this level<br/>" + numberFormat(!d.data.count ? "0" : d.data.count) + (d.data.count && d.data.count === 1 ? " sequence" : " sequences") + " specific to this level or lower";
                }
            });
        });
    });
</script>
