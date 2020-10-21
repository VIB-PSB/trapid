<?php

/*
 * The integration of the GO hierarchy graph into TRAPID was largely adapted from the PLAZA 4.5 workbench code authored by
 * Michiel Van Bel.
*/
?>
<div class="row" id="go-graph-settings" style="margin: 0 0 1em; font-size: 88%;">
    <form action="" method="GET" class="form-inline">
        <div class="row">
            <div class='form-group'>
                <select class='form form-control input-sm' id='subtype' name='subtype' onchange="redrawHierarchy()">
                    <option value="biological_process" selected>Biological process</option>
                    <option value="molecular_function">Molecular function</option>
                    <option value="cellular_component">Cellular component</option>
                </select>
            </div>
            <!-- PVALUE FILTERING -->
            <div class='form-group'>
                <strong>&nbsp; | &nbsp; Filtering
                    <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['go_enrichment_graph_filter'], "tooltip_placement"=>"top", "override_span_class"=>"glyphicon glyphicon-question-sign help-tooltip-icon-inline")); ?>
                    &nbsp;  </strong>
                <label for='pvalue'>P-value  &#x2264; </label>
                <select class='form form-control input-sm' id='pvalue' name='pvalue' onchange="redrawHierarchy()">
                    <option value='0.05' selected='selected'>0.05</option>
                    <option value='0.01'>0.01</option>
                    <option value='1E-03'>1E-03</option>
                    <option value='1E-05'>1E-05</option>
                    <option value='1E-10'>1E-10</option>
                </select>
                <!-- ENRICHMENT FOLD FILTERING -->
                <label for='enrichment_fold'>
                    &nbsp; &#xb7; &nbsp; Enrichment (log<sub>2</sub>) &#x2265;
                </label>
                <select class='form form-control input-sm' id='enrichment_fold' name='enrichment_fold' onchange="redrawHierarchy()">
                    <?php
                    for($i=0;$i<=5;$i++){
                        $selected	= ($i==0)?"selected='selected'":"";
                        echo "<option value='".$i."' $selected>".$i."</option>";
                    }
                    ?>
                </select>
                <!-- PERCENTAGE PRESENT FILTERING -->
                <label for='perc_present'>
                    &nbsp; &#xb7; &nbsp; Subset ratio &#x2265;
                </label>
                <select class='form form-control input-sm' id='perc_present' name='perc_present' onchange='redrawHierarchy()'>
                    <?php
                    for($i=0;$i<=100;$i+=10){
                        $selected	= ($i==0)?"selected='selected'":"";
                        echo "<option value='".$i."' $selected>".$i."%</option>";
                    }
                    ?>
                </select>
            </div>
            <div class='form-group'>
                <strong>&nbsp; |&nbsp; </strong> <a onclick="toggleExtraSettings()" id="toggle-extra-settings"><span id="toggle-extra-settings-icon" class="glyphicon small-icon glyphicon-menu-right"></span> Toggle graph settings</a>
            </div>
            <!--            <span class="pull-right">-->
            <span class="pull-right" style="margin-top: 0.35rem;">
                <a href="#go-graph-modal" data-toggle="modal" data-target="#go-graph-modal">Legend & usage</a>
                <strong>  | Export as: </strong>
                <a id="export_png" class="btn btn-default btn-xs" title="Export Sankey diagram (PNG)">PNG</a>
                <a id="export_svg" class="btn btn-default btn-xs" title="Export Sankey diagram (SVG)">SVG</a>
            </span>
        </div>
        <div class="row hidden" id="go-graph-extra-settings" style="margin-top: 5px;">
            <div class='form-group'>
                <strong>Labels
                    <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['go_enrichment_graph_label'], "tooltip_placement"=>"top", "override_span_class"=>"glyphicon glyphicon-question-sign help-tooltip-icon-inline")); ?>
                    &nbsp;</strong>
                <label for='size_ontology_ids'>IDs</label>
                <select class='form form-control input-sm' id='size_ontology_ids' name='size_ontology_ids' onchange="redrawHierarchy()">
                    <option value='normal' selected='selected'>Normal</option>
                    <option value='small'>Small</option>
                </select>
                <!-- FONT-SIZE ONTOLOGY DESCRIPTIONS -->
                <label for='size_ontology_descriptions'>&nbsp; &#xb7; &nbsp; Descriptions</label>
                <select class='form form-control input-sm' id='size_ontology_descriptions' name='size_ontology_descriptions' onchange="redrawHierarchy()">
                    <option value='normal'>Normal</option>
                    <option value='small' selected='selected'>Small</option>
                    <option value='off'>Off</option>
                </select> &nbsp;&nbsp;
            </div>
            <!-- NODE-COLORING -->
            <div class='form-group'>
                <strong>| &nbsp; Display
                    <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['go_enrichment_graph_scaling'], "tooltip_placement"=>"top", "override_span_class"=>"glyphicon glyphicon-question-sign help-tooltip-icon-inline")); ?>
                    &nbsp;</strong>
                <label for='node_color_scaling'>Node color</label>
                <select class='form form-control input-sm' id='node_color_scaling' name='node_color_scaling' onchange="redrawHierarchy()">
                    <option value='adaptive' selected='selected'>Adaptive scaling</option>
                    <option value='fixed'>Present values scaling</option>
                </select>
            </div>
            <!-- HORIZONTAL DISTANCE MULTIPLIER BETWEEN NODES -->
            <div class='form-group'>
                <label for='graph_horizontal_multiplier'>&nbsp; &#xb7; &nbsp; Horizontal scaling</label>
                <select class='form form-control input-sm' id='graph_horizontal_multiplier' name='graph_horizontal_multiplier' onchange="redrawHierarchy()">
                    <option value='1'>1x</option>
                    <option value='2' selected='selected'>2x</option>
                    <option value='4'>4x</option>
                </select>
            </div>
            <!-- VERTICAL DISTANCE MULTIPLIER BETWEEN NODES -->
            <div class='form-group'>
                <label for='graph_vertical_multiplier'>&nbsp; &#xb7; &nbsp; Vertical scaling</label>
                <select class='form form-control input-sm' id='graph_vertical_multiplier' name='graph_vertical_multiplier' onchange="redrawHierarchy()">
                    <option value='1'>1x</option>
                    <option value='2' selected='selected'>2x</option>
                    <option value='4'>4x</option>
                </select>
            </div>
        </div>
    </form>
</div> <!-- end settings -->

<div class="row" id="go-graph-container">
    <div id='go-graph-viz' style='width:100%;height:780px;margin:0px;'></div>
</div>

<!-- GO graph usage modal -->
<div class="modal fade" id="go-graph-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-body">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button><br>
                <section class="page-section-sm">
                    <h4>General Information</h4>
                    <p class="text-justify">
                        This graph represents the hierarchy of the enriched GO terms.
                        Each node in the graph represents an enriched GO term.
                        Contrary to the GO graph present in TRAPID 1, in which all GO terms were shown, the intermediate GO terms that are not enriched are not displayed in this graph.</p>
                    <p class="text-justify">Each node has 3 different values (shown in a tooltip when hovering):
                    <ol>
                        <li>
                            <strong>Corrected p-value:</strong> statistical value indicating the odds that the enrichment-fold happens by chance. The lower the p-value, the better.
                        </li>
                        <li>
                            <strong>Enrichment fold: </strong> the overrepresentation of the GO term in the subset, compared to the experiment's background frequency. For convenience, the log<sub>2</sub>-value of the enrichment folds is used. As sub, an ernichment fold of <code>1</code> corresponds to a two-fold enrichment.
                        </li>
                        <li>
                            <strong>Subset ratio: </strong> the percentage of transcripts in the subset that are annotated with the enriched GO term. An enrichment can happen even with the minority of the genes in the workbench being annotated with the ontology term.
                        </li>
                    </ol>
                    </p>
                </section>
                <section class="page-section-sm">
                    <h4>Visualization information</h4>
                    <p class="text-justify">
                    <ul>
                        <li>
                            Node size is scaled by the p-value of the enriched GO term: the more significant (thus the lower the p-value), the larger the node size.
                        </li>
                        <li>
                            Node color is determined by the enrichment fold of the GO term. The color scale is set between green (highest) and red (lowest).
                        </li>
                        <li>
                            Node outer band is determined by the percentage of the subset's transcripts that are annotated with the enriched GO term.
                            The present percentage is the green part of the ring, the missing percentage is the red part of the ring.
                        </li>
                        <li>Right-clicking any node displays a contextual menu that enables node selection and hiding.
                        </li>
                    </ul>
                    </p>
                </section>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">

    // Toggle extra settings. Called on click of '#toggle-extra-settings' link.
    function toggleExtraSettings() {
        var extraSettingsDiv = "go-graph-extra-settings";
        var extraSettingsIcon = "toggle-extra-settings-icon";
        var iconElmt = document.getElementById(extraSettingsIcon);
        document.getElementById(extraSettingsDiv).classList.toggle("hidden");
        if(iconElmt.classList.contains("glyphicon-menu-right")) {
            iconElmt.classList.replace("glyphicon-menu-right", "glyphicon-menu-down");
        }
        else {
            iconElmt.classList.replace("glyphicon-menu-down", "glyphicon-menu-right");
        }
    }

    $(document).ready(function(){
        //If we only use the ready() function, then D3 cannot correctly determine the size of the canvas to draw on
        //Therefore we add an observer to notify the redraw function to fire if the tab containing this page is made visible.
        var observer	= new MutationObserver(function(mutations) {
            redrawHierarchy();
        });
        var target		= document.querySelector("#go-graph-tab");
        observer.observe(target, {attributes: true});

/*
        //Add the tooltips to the options
        $("[data-toggle='tooltip']").each(function(el_index,el){
            $(el).tooltip({html:true,container:'body',placement:'bottom',trigger:'hover'});
        });
*/
    });

    function redrawHierarchy(){
        initGraph();
    }

    /**
     * Create a D3 scaling based on scores from the data
     *
     * @param json data JSON object with data points for the ontology terms
     * @param json namespace_options Namespace options (target namespace, filter values per namespace)
     * @param json score_options Scoring options (score_name for the name of the score, score_transform for the transformation function)
     * @param json range_options Range options (target_range,fixed_range_scaling for the domain range)
     *
     * @return D3 scaling object
     */
    function createScaling(data,namespace_options,score_options,range_options){
        //First gather the (transformed) min and max value for the data score entry point
        var min_value		= Number.MAX_SAFE_INTEGER;
        var max_value		= 0;
        for(var ontology_term in data.nodes){
            var node		= data.nodes[ontology_term];
            var node_ns		= node.namespace;
            if(node.enricher && node.enricher.scores){
                //Here we filter on the per-namespace-scaling (or when it's disabled)
                if(!namespace_options.per_namespace_scaling || (namespace_options.per_namespace_scaling && namespace_options.namespace == node_ns)){
                    var value	= node.enricher.scores[score_options.score_name];
                    value		= score_options.score_transform(value);
                    min_value	= Math.min(min_value,value);
                    max_value	= Math.max(max_value,value);
                }
            }
        }
        var domain_range	= [min_value,max_value];
        //we can create the scale in two ways
        //1) [min,max]	: this uses the available data in a non-comparative way
        //	--> A positive enrichment fold will sometimes be green, while in other comparisons it will be red
        //	--> Coloring can thus be highly depended on other data points
        //2) [-1*(MAX(ABS(fold_min),ABS(fold_max))),MAX(ABS(fold_min),ABS(fold_max))]
        //	--> Positive enrichment folds will always be red, negative enrichment folds will always be green
        if(range_options.adaptive_range_scaling){
            var max_abs		= Math.max(Math.abs(min_value),Math.abs(max_value));
            domain_range	= [-1*max_abs,max_abs];
        }
        //Now construct the D3 scaling object
        var scale_result	= d3.scale.linear().domain(domain_range).range(range_options.target_range);
        return scale_result;
    }


    /**
     * Count GO terms present in the hierarchy data for a given namespace
     *
     * @param json data JSON object with data points for the ontology terms
     * @param string GO aspect/namespace string ('biological_process', 'molecular_function', or 'cellular_component')
     *
     * @return number GO term count
     */
    function countGOterms(data, namespace) {
        var go_count = 0;
        for(var ontology_term in data.nodes){
            const node_data = data.nodes[ontology_term];
            if(node_data.namespace === namespace) {
                go_count += 1;
            }
        }
        return go_count;
    }


    function initGraph(){
        //Data options
/*
        var data_all			= <?= json_encode($go_graph_data_all);?>;
        var data_shown			= ""<?php //echo json_encode($go_graph_data); ?>;
        var filtered_data		= ($("#show_hierarchy").val() === "filtered");	//boolean value now
        var data				= (filtered_data)?data_shown:data_all;	//select the correct data type
*/
        var data				= <?= json_encode($go_graph_data_all);?>;
        var filter_subtype		= $("#subtype").val();

        //Filter options
        var filter_pvalue			= parseFloat($("#pvalue").val());
        var filter_enrichment_fold	= parseInt($("#enrichment_fold").val());
        var filter_show_enrichments	= $("#show_enrichments").val();
        var filter_perc_present		= parseInt($("#perc_present").val());

        //Graph visualization options
        var size_ontology_ids			= $("#size_ontology_ids").val();
        var size_ontology_descriptions	= $("#size_ontology_descriptions").val();
        var node_color_scaling			= $("#node_color_scaling").val();
        var graph_horizontal_multiplier	= parseInt($("#graph_horizontal_multiplier").val());
        var graph_vertical_multiplier	= parseInt($("#graph_vertical_multiplier").val());

        //Scaling done over all namespaces, or only the selected namespace?
        var per_namespace_scaling		= true;	//Make this an option for the end-user?
        var adaptive_range_scaling		= (node_color_scaling == "adaptive")?true:false;

        //Determine horizontal distance between nodes in pixels
        var node_separation_distance	= 40;
//        var node_separation_distance	= 100;
/*
        if(filtered_data){node_separation_distance = node_separation_distance * 2;}	//increase node-separation of using the filtered GO set
*/
        node_separation_distance		= node_separation_distance * graph_horizontal_multiplier;

        //Determine vertical distance between nodes in pixels
        var level_separation_distance	= 95;
//        var level_separation_distance	= 140;
        level_separation_distance		= level_separation_distance * graph_vertical_multiplier;

        //Determine relative font-size (in em) for the ids and the descriptions
        var font_size_ontology_ids		= 1.0;
        if(size_ontology_ids == "small"){font_size_ontology_ids = 0.8;}

        var font_size_ontology_descs	= 1.0;
        if(size_ontology_descriptions == "small"){font_size_ontology_descs = 0.8;}
        if(size_ontology_descriptions == "off"){font_size_ontology_descs = 0;}

        // Base URL for GO term page
        var goBaseUrl = "<?php echo $this->Html->Url(array("controller" => "functional_annotation", "action" => "go", $exp_id)) . "/";?>";

        // Maximum allowed GO terms (nodes) to display the graph
        var max_graph_gos = 300;

        try{
            // Get number of GO terms to display for current aspect/namespace
            var num_graph_gos = countGOterms(data, filter_subtype);
            // If it is more than the allowed max, show an error message and hide the graph
            if(num_graph_gos > max_graph_gos) {
                const error_text = "<strong>Error:</strong> GO enrichment graph not shown. The number of enriched terms for this GO aspect is higher than the allowed maximum (" + max_graph_gos + "). <br>Using a lower p-value threshold should result in less enriched GO terms.";
                $("#go-graph-viz").html("<p class='text-danger go-graph-error'>" + error_text + "</p>");
            }
            else {
                var fold_scale = createScaling(
                    data,
                    {namespace: filter_subtype, per_namespace_scaling: per_namespace_scaling},
                    {
                        score_name: "enr_fold", score_transform: function (n) {
                        return n;
                    }
                    },
                    {target_range: [0, 1], adaptive_range_scaling: adaptive_range_scaling}
                );
                var pval_scale = createScaling(
                    data,
                    {namespace: filter_subtype, per_namespace_scaling: per_namespace_scaling},
                    {
                        score_name: "p-val", score_transform: function (n) {
                        return -1 * Math.log10(n);
                    }
                    },
                    {target_range: [30, 60], adaptive_range_scaling: adaptive_range_scaling}
                );

                // A quick fix to clear existing PNG/SVG event listeners
                ['export_png', 'export_svg'].forEach(function (export_btn) {
                    const export_elmt = document.getElementById(export_btn);
                    const export_elmt_clone = export_elmt.cloneNode(true);
                    export_elmt.parentNode.replaceChild(export_elmt_clone, export_elmt);
                });

                //Construct new enricher visualization
                $("#go-graph-viz").html("");
                new EnricherGo(document.getElementById("go-graph-viz"), data, {
                    export: {
                        png: 'export_png',
                        svg: 'export_svg'
                    },
                    namespace: filter_subtype,
                    nodeOpacity: function (n) {
                        var opacity = 1;
                        if (n.enricher) {
                            var local_pval = n.enricher.scores['p-val'];
                            var local_enr_fold = n.enricher.scores['enr_fold'];
                            var local_perc_present = n.enricher.counts['n_hits'] / n.enricher.counts['ftr_size'] * 100;
                            if (local_pval > filter_pvalue) {
                                opacity = 0.25;
                            }
                            if (Math.abs(local_enr_fold) < filter_enrichment_fold) {
                                opacity = 0.25;
                            }
                            //                        if(local_enr_fold>0 && filter_show_enrichments=='depletion'){opacity = 0.25;}
                            //                        if(local_enr_fold<0 && filter_show_enrichments=='enrichment'){opacity = 0.25;}
                            if (local_perc_present < filter_perc_present) {
                                opacity = 0.25;
                            }
                        }
                        else {
                            opacity = 0.25;
                        }
                        return opacity;
                    },
                    nodeValue: function (n) {
                        return fold_scale(n.enricher.scores['enr_fold']);
                    },
                    nodeSize: function (n) {
                        if (n.enricher) {
                            return pval_scale(-1 * Math.log10(n.enricher.scores['p-val']));
                        }
                        return 45;	//should be in range of the pval_scale function limits defined above
                    },
                    donutGraphValue: function (n) {
                        if (n.enricher) {
                            return n.enricher.counts['n_hits'] / n.enricher.counts['ftr_size'];
                        }
                        return 1;
                    },
                    donutGraphOuterWidth: function (n) {
                        if (n.enricher) {
                            return Math.round((this.nodeSize(n)) / 3);
                        }
                        return 20;
                    },
                    donutGraphInnerWidth: function (n) {
                        if (n.enricher) {
                            return Math.round((this.nodeSize(n)) / 5);
                        }
                        return 10;
                    },
                    levelSeparation: level_separation_distance,
                    nodeSeparation: node_separation_distance,
                    animationEnabled: true,
                    linkUrl: goBaseUrl,
                    linkTarget: '_blank',
                    tooltipFields: {
                        "P-value": function (n) {
                            if (n.enricher) {
                                return n.enricher.scores['p-val'].toExponential(3);
                            }
                            return "N/A";
                        },
                        "Enrichment (log<sub>2</sub>)": function (n) {
                            if (n.enricher) {
                                return n.enricher.scores['enr_fold'].toFixed(3);
                            }
                            return "N/A";
                        },
                        "Transcripts": function (n) {
                            if (n.enricher) {
                                var percentage = n.enricher.counts['n_hits'] / n.enricher.counts['ftr_size'] * 100;
                                var perc_string = percentage.toFixed(3) + "%";
                                var gene_string = n.enricher.counts['n_hits'] + " (" + perc_string + ")";
                                return gene_string;
                            }
                            return "N/A";
                        }
                    },
                    sidebarMiniMapShow: false,
                    ontologyDescriptionsTextSize: font_size_ontology_descs,
                    ontologyTermsTextSize: font_size_ontology_ids,
                    subsetName: '<?php echo $subset; ?>'
                });
            }
        }
        catch(err){
            $("#go-graph-settings").addClass("hidden");
            console.log(err);
            $("#go-graph-container").html("<p class='text-danger'><strong>Error:</strong> failed to construct GO enrichment graph.</p>");
        }
    }
</script>
<?php echo $this->element("help_tooltips/enable_tooltips",  array("container"=>"#go-graph-settings")); ?>
