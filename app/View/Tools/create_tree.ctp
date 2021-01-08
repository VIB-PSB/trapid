<?php
	// Load the necessary javascript libraries
    echo $this->Html->script(array('ftiens4.js', 'ua.js', 'd3-3.5.6.min.js', 'phyd3.min.js', 'msa.min.js'));
    // Load PhyD3 style
    echo $this->Html->css('phyd3.min.css');
?>

<div class="page-header">
    <h1 class="text-primary">MSA & phylogenetic tree</h1>
</div>

<section class="page-section-sm">
<p class="text-justify">
    <strong>Gene family: </strong> <?php echo $this->Html->link($gf_id,array("controller"=>"gene_family","action"=>"gene_family", $exp_id, $gf_id));?>
    (<?php echo $gf_info['GeneFamilies']['num_transcripts'];?> transcripts). </p>
<?php
if((isset($previous_result) && $previous_result==false) || !isset($previous_result)) {
    if (isset($run_pipeline)) {
        echo "<p class='lead'>\n";
        echo "A job for creating the MSA / phylogenetic tree has been added to the queue. ";
        echo "An email will be sent when the job has finished.\n";
        echo "</p>\n";
    }
}
?>


<?php if(isset($previous_results) && $previous_results['msa']==true): ?>
<p class="text-justify">
    <a id="new-tree-link" class="tree-results-link"><span class="glyphicon glyphicon-chevron-right"></span> Create MSA / phylogenetic tree with different species or settings</a>
    <a id="current-tree-link" class="tree-results-link hidden"><span class="glyphicon glyphicon-eye-open"></span> View current MSA / phylogenetic tree</a>
</p>
<?php endif; ?>
</section>

<?php
//$hide_options	= false;
if(isset($previous_results) && $previous_results['msa']==true): ?>

    <section class="page-section-sm" id="tree-results">
        <ul class="nav nav-tabs nav-justified" id="msa-tree-tabs" data-tabs="tabs">
            <?php if ($previous_results['tree'] == true): ?>
                <li class="active"><a href="#tree-tab" data-toggle="tab">Phylogenetic tree</a></li>
                <li id="msa-tab-li"><a href="#msa-tab" data-toggle="tab">Multiple sequence alignment(s)</a></li>
            <?php else: ?>
                <li class="disabled"><a>Phylogenetic tree</a></li>
                <li class="active"><a href="#msa-tab" data-toggle="tab">Multiple sequence alignment(s)</a></li>
            <?php endif; ?>
            <li><a href="#files-extra-tab" data-toggle="tab">Files & extra</a></li>
        </ul>

<div class="tab-content">
    <?php if ($previous_results['tree'] == true): ?>
    <div id="tree-tab" class="tab-pane active"><br>
    <!-- PhyD3 controls copied from the example given in the doc, then adapted. -->
            <div id="phyd3-row" class="row phyd3-controls" style="margin-top: 10px;">
                <!-- PhyD3 settings -->
                <div id="phyd3-settings" class="col-sm-3">
                    <div class="panel panel-default">
                        <div class="panel-heading">Visualization settings & export</div>
                        <div class="panel-body">



                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input id="dynamicHide" type="checkbox" checked="checked"> dynamic node hiding
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="input-group checkbox">
                                    <label class="top-padding">
                                        <input id="invertColors" type="checkbox"> invert colors
                                    </label>
                                    <span class="input-group-btn">
                            <div class="input-group colorpicker-component" id="foregroundColorButton">
                                <input type="text" class="form-control hidden" name="foregroundColor" id="foregroundColor" />
                                <span class="input-group-addon btn btn-fab btn-fab-mini"><i></i></span>
                            </div>
                        </span>
                                    <span class="input-group-btn">
                            <div class="input-group colorpicker-component" id="backgroundColorButton">
                                <input type="text" class="form-control hidden" name="backgroundColor" id="backgroundColor" />
                                <span class="input-group-addon btn btn-fab btn-fab-mini"><i></i></span>
                            </div>
                        </span>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input id="phylogram" type="checkbox" checked="checked"> show phylogram
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input id="lineupNodes" type="checkbox" checked="checked"> lineup nodes
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input id="lengthValues" type="checkbox"> show branch length values
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-4 text-right left-dropdown middle-padding">decimals</div>
                                <div class="col-xs-3 no-padding">
                                    <input id="maxDecimalsLengthValues" type="number" min="0" id="domainLevel" class="form-control no-padding col-sm-6"  value="3" disabled />
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input id="supportValues" type="checkbox"> show support values
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-4 text-right left-dropdown middle-padding">decimals</div>
                                <div class="col-xs-3 no-padding">
                                    <input id="maxDecimalsSupportValues" type="number" min="0" id="domainLevel" class="form-control no-padding col-sm-6" value="3" disabled />
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input id="nodeNames" type="checkbox" checked="checked"> show node names
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-3 text-right left-dropdown middle-padding">for</div>
                                <div class="col-xs-5 no-padding">
                                    <select id="nodesType" class="form-control">
                                        <option selected="selected">all</option>
                                        <option>only leaf</option>
                                        <option>only inner</option>
                                    </select>
                                </div>
                                <div class="col-xs-4 text-left right-dropdown middle-padding">
                                    nodes
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input id="nodeLabels" type="checkbox" checked="checked"> show node labels
                                    </label>
                                </div>
                            </div>
<!--                            <div class="form-group">-->
<!--                                <div class="checkbox">-->
<!--                                    <label>-->
<!--                                        <input id="sequences" type="checkbox"> show additional node information-->
<!--                                    </label>-->
<!--                                </div>-->
<!--                            </div>-->
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input id="taxonomy" type="checkbox" checked="checked"> show taxonomy
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input id="taxonomyColors" type="checkbox" checked="checked"> taxonomy colorization
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-11 text-right left-dropdown middle-padding"><a class="pointer" data-toggle="modal" data-target="#taxonomyColorsModal">show taxonomy colors table</a></div>
                            </div>
                            <br>
                            <div class="row">
                                <div class="col-xs-3">
                                    node size
                                </div>
                                <div class="col-xs-3 text-right">
                                    <button id="nodeHeightLower" class="btn btn-primary" title="make them smaller"><span class="glyphicon glyphicon-zoom-out" aria-hidden="true"></span></button>
                                </div>
                                <div class="col-xs-3 text-center middle-padding">
                                    <input type="text" id="nodeHeight" disabled="disabled" class="form-control no-padding" />
                                </div>
                                <div class="col-xs-3 text-left">
                                    <button id="nodeHeightHigher" class="btn btn-primary" title="make them bigger"><span class="glyphicon glyphicon-zoom-in" aria-hidden="true"></span></button>
                                </div>
                            </div>
                            <br>
                            <div class="row">
                                <div class="col-xs-4 col-xs-offset-4 text-center">
                                    <button id="zoominY" class="btn btn-primary" title="zoom in along Y axis"><span class="glyphicon glyphicon-zoom-in" aria-hidden="true"></span> Y</button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-4 text-center">
                                    <button id="zoomoutX" class="btn btn-primary" title="zoom out along X axis"><span class="glyphicon glyphicon-zoom-out" aria-hidden="true"></span> X</button>
                                </div>
                                <div class="col-xs-4 text-center">
                                    <button id="resetZoom" class="btn btn-link">RESET</button>
                                </div>
                                <div class="col-xs-4 text-center">
                                    <button id="zoominX" class="btn btn-primary" title="zoom in along X axis"><span class="glyphicon glyphicon-zoom-in" aria-hidden="true"></span> X</button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-4 col-xs-offset-4 text-center">
                                    <button id="zoomoutY" class="btn btn-primary" title="zoom out alongY axis"><span class="glyphicon glyphicon-zoom-out" aria-hidden="true"></span> Y</button>
                                </div>
                            </div>
                            <br>
<!--                            <div class="form-group">-->
<!--                                <div class="checkbox">-->
<!--                                    <label>-->
<!--                                        <input id="domains" type="checkbox" checked="checked"> show domain architecture-->
<!--                                    </label>-->
<!--                                </div>-->
<!--                            </div>-->
<!--                            <div class="form-group">-->
<!--                                <div class="checkbox">-->
<!--                                    <label>-->
<!--                                        <input id="domainNames" type="checkbox"> show domain names-->
<!--                                    </label>-->
<!--                                </div>-->
<!--                            </div>-->
<!--                            <div class="form-group">-->
<!--                                <div class="checkbox">-->
<!--                                    <label>-->
<!--                                        <input id="domainColors" type="checkbox" checked="checked"> domain colorization-->
<!--                                    </label>-->
<!--                                </div>-->
<!--                            </div>-->
<!--                            <div class="row">-->
<!--                                <div class="col-xs-3">-->
<!--                                    domain scale-->
<!--                                </div>-->
<!--                                <div class="col-xs-3 text-right">-->
<!--                                    <button id="domainWidthLower" class="btn btn-primary" title="make them shorter"><span class="glyphicon glyphicon-zoom-out" aria-hidden="true"></span></button>-->
<!--                                </div>-->
<!--                                <div class="col-xs-3 text-center middle-padding">-->
<!--                                    <input type="text" id="domainWidth" disabled="disabled" class="form-control no-padding" />-->
<!--                                </div>-->
<!--                                <div class="col-xs-3 text-left">-->
<!--                                    <button id="domainWidthHigher" class="btn btn-primary" title="make them longer"><span class="glyphicon glyphicon-zoom-in" aria-hidden="true"></span></button>-->
<!--                                </div>-->
<!--                            </div>-->
<!--                            <br />-->
<!--                            <div class="row">-->
<!--                                <div class="col-xs-3">-->
<!--                                    p &nbsp; value-->
<!--                                </div>-->
<!--                                <div class="col-xs-3 text-right">-->
<!--                                    <button id="domainLevelLower" class="btn btn-primary" title="lower the threshold">-</button>-->
<!--                                </div>-->
<!--                                <div class="col-xs-3 text-center middle-padding">-->
<!--                                    <input type="text" id="domainLevel" disabled="disabled" class="form-control no-padding" />-->
<!--                                </div>-->
<!--                                <div class="col-xs-3 text-left">-->
<!--                                    <button id="domainLevelHigher" class="btn btn-primary" title="higher the threshold">+</button>-->
<!--                                </div>-->
<!--                            </div>-->
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input id="graphs" type="checkbox" checked="checked"> show graphs
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input id="graphLegend" type="checkbox" checked="checked"> show graphs legend
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-3">
                                    graph scale
                                </div>
                                <div class="col-xs-3 text-right">
                                    <button id="graphWidthLower" class="btn btn-primary" title="make them shorter"><span class="glyphicon glyphicon-zoom-out" aria-hidden="true"></span></button>
                                </div>
                                <div class="col-xs-3 text-center middle-padding">
                                    <input type="text" id="graphWidth" disabled="disabled" class="form-control" />
                                </div>
                                <div class="col-xs-3 text-left">
                                    <button id="graphWidthHigher" class="btn btn-primary" title="make them longer"><span class="glyphicon glyphicon-zoom-in" aria-hidden="true"></span></button>
                                </div>
                            </div>
                            <br>
                            <strong>Search in tree</strong> (regexp supported):
                                <input type="text" id="searchQuery" placeholder="Type your search..." class="form-control no-padding form-inline" />
                        </div> <!--  end .panel-body -->
                        <div class="panel-footer">
                            <strong>Export as:</strong>
                            <button class="btn btn-sm btn-primary" id="linkSVG">SVG</button>
                            <button class="btn btn-sm btn-primary" id="linkPNG">PNG</button>
                        </div>
                    </div> <!--  end .panel -->
                </div> <!--  end column -->
                <!-- PhyD3 viewer -->
                <div id="phyd3-viewer" class="col-sm-9"></div>
            </div>
        <div class="row" id='legend-row' style="font-size:95%;">
            <div class="col-md-6">
                Use the mouse to drag, zoom and modify the tree. <strong>Actions:</strong>
                    <ul class="list-inline text-justify">
                    <li>
                        <kbd><kbd>ctrl</kbd> + <kbd>wheel</kbd></kbd> scale Y
                    </li>
                    <li>
                        <kbd><kbd>alt</kbd> + <kbd>wheel</kbd></kbd> scale X
                    </li>
                    <li>
                        <kbd><kbd>mouse click</kbd></kbd> show node info
                    </li>
                </ul>
            </div>
            <div class="col-md-6">
                <div class="pull-right">
                    <!-- Hide when no meta-annotation / subset information? -->
                    <ul class="list-inline text-right">
                        <li>
                            <strong>Meta-annotation:</strong>
                        </li>
                        <li>
                            <span class="meta-square meta-ni"></span> No Information
                        </li>
                        <li>
                            <span class="meta-square meta-p"></span> Partial
                        </li>
                        <li>
                            <span class="meta-square meta-qfl"></span> Quasi Full Length
                        </li>
                        <li>
                            <span class="meta-square meta-fl"></span> Full Length
                        </li>
                    </ul>
                    <ul class="list-inline text-right">
                        <li>
                            <strong>Subsets:</strong>
                        </li>
                        <li>
                            <span class="subsets-circle-in"></span> Comprised in subset
                        </li>
                        <li>
                            <span class="subsets-circle-out"></span> Not in subset
                        </li>
                    </ul>
                </div>
            </div>
        </div>
            <!-- Taxonomy colors modal, opened when user clicks on the 'show colors' link  -->
            <div class="modal fade" id="taxonomyColorsModal" tabindex="-1" role="dialog" aria-labelledby="taxonomyColorsModalLabel">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="taxonomyColorsModalLabel">Taxonomy colors</h4>
                        </div>
                        <div class="modal-body phyd3-modal">
                            &nbsp;
                            <form class="row form-horizontal" id="taxonomyColorsList">
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" id="applyTaxonomyColors">Apply</button>
                        </div>
                    </div>
                </div>
            </div>
    </div> <!-- End phylogenetic tree tab content -->
    <?php endif; ?>

    <div id="msa-tab" class="tab-pane <?php if($previous_results['tree'] == false){ echo "active";}?>"><br>
        <div class="row" style="margin-bottom:6px;">
            <ul class="nav nav-pills small-nav pull-right" id="nav-pills-msas" data-tabs="pills">
                <li><strong>MSA type: &nbsp;</strong></li>
                <li class="active"><a data-toggle="pill" href="#full-msa-view-tab">Unedited (<?php echo $full_msa_length; ?>)</a></li>
                <?php if($previous_results['msa_stripped'] == true): ?>
                <li><a data-toggle="pill" href="#stripped-msa-view-tab">Edited (<?php echo $stripped_msa_length; ?>)</a></li>
                <?php else: ?>
                <li class="disabled"><a>Edited</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="tab-content" id="msa-view-tab-content">
            <div id="full-msa-view-tab" class="tab-pane active">
                <div id="full-msa-wrapper" class="row msa-wrapper">
                     <div id="full-msa-view">
                        <p class="text-center text-muted">
                            <br>
                            <?php echo $this->Html->image('ajax-loader.gif'); ?>
                            <br>
                            Loading MSA viewer...
                        </p>
                    </div>
                </div>
            </div>
            <?php if($previous_results['msa_stripped'] == true): ?>
            <div id="stripped-msa-view-tab" class="tab-pane">
                <div id="stripped-msa-wrapper" class="row msa-wrapper">
                    <div id="stripped-msa-view">
                        <p class="text-center text-muted">
                            <br>
                            <?php echo $this->Html->image('ajax-loader.gif'); ?>
                            <br>
                            Loading MSA viewer...
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

<script type="text/javascript">
    var msaDiv = document.getElementById("full-msa-view");
    var strippedMsaDiv = document.getElementById("stripped-msa-view");
    var msaUrl = "<?php echo $this->Html->url(array("controller"=>"tools","action"=>"get_msa",$exp_id, $gf_id)); ?>";
    var strippedMsaUrl = "<?php echo $this->Html->url(array("controller"=>"tools","action"=>"get_msa",$exp_id, $gf_id,"stripped")); ?>";
    var msaOpts = {
        el: msaDiv,
        // colorscheme: {"scheme": "hydro"},
        importURL: msaUrl,
        vis: {
            labelId: false,
            conserv:true
            // overviewbox:false,
            // seqlogo:true
        },
        zoomer: {
            menuFontsize:"12px",
            autoResize:true,
            alignmentHeight:300,
            labelNameLength: 150,
            labelFontsize: 12
            // labelLineHeight: "12px"
        },
        menu:"small",
        bootstrapMenu:true,
    };
    var strippedMsaOpts = {
        el: strippedMsaDiv,
        // colorscheme: {"scheme": "hydro"},
        importURL: strippedMsaUrl,
        vis: {
            labelId: false,
            conserv:true
            // overviewbox:false,
            // seqlogo:true
        },
        zoomer: {
            menuFontsize:"12px",
            autoResize:true,
            alignmentHeight:300,
            labelNameLength: 150,
            labelFontsize: 12
            // labelLineHeight: "12px"
        },
        menu:"small",
        bootstrapMenu:true,
    };
    var m = msa(msaOpts);
    var sm = msa(strippedMsaOpts);


    function redraw_msa(msa_viewer_div, msa_wrapper_div, msa_options) {
        // If the wrapper div is currently visible ...
        if(document.getElementById(msa_wrapper_div).offsetParent !== null) {
            // Get width of wrapper element
            var wrapper_width = document.getElementById(msa_wrapper_div).offsetWidth;
            // Get width of msa viewer
            var msa_seqblock_width = document.getElementById(msa_viewer_div).getElementsByClassName('biojs_msa_seqblock')[0].offsetWidth;
            var msa_labelblock_width = document.getElementById(msa_viewer_div).getElementsByClassName('biojs_msa_labelblock')[0].offsetWidth;
            var msa_width = msa_seqblock_width + msa_labelblock_width;
            // If its width is larger than the wrapper's, redraw the MSA (otherwise there is no need to do anything)
            if(msa_width > wrapper_width) {
                $('#' + msa_viewer_div).unwrap();  // Replace by vanilla JS?
                document.getElementById(msa_viewer_div).innerHTML = "";
                // Remove all MSA menu bars
                var msa_menu_bars = document.getElementById(msa_wrapper_div).getElementsByClassName("smenubar");
                for(var i=0;i < msa_menu_bars.length; i++) {
                    msa_menu_bars[i].remove();
                }
                // Reload MSA
                msa(msa_options);
            }
        }
    }



    // Redraw MSA on various events: when MSA tab is shown, and when any 'MSA type' item is clicked.
    $('#msa-tree-tabs a').on('shown.bs.tab', function(){
        redraw_msa('full-msa-view', 'full-msa-wrapper', msaOpts);
        <?php if($previous_results['msa_stripped'] == true): ?>
        redraw_msa('stripped-msa-view', 'stripped-msa-wrapper', strippedMsaOpts);
        <?php endif; ?>
    });
    $('#nav-pills-msas a').on('shown.bs.tab', function(){
        redraw_msa('full-msa-view', 'full-msa-wrapper', msaOpts);
        <?php if($previous_results['msa_stripped'] == true): ?>
        redraw_msa('stripped-msa-view', 'stripped-msa-wrapper', strippedMsaOpts);
        <?php endif; ?>
    });
</script>


</div> <!-- End MSA tab content -->

    <div id="files-extra-tab" class="tab-pane"><br>
    <section class="page-section">
    <h4>Download</h4>
    <?php if($previous_results['tree'] == true): ?>
    <section class="page-section-sm">
        <h5>Phylogenetic tree</h5>
        <ul>
            <li><a href="<?php echo $this->Html->url(array("controller"=>"tools","action"=>"view_tree",$hashed_user_id,$exp_id,$gf_id,"newick")); ?>">Download Newick tree</a></li>
            <li><a href="<?php echo $this->Html->url(array("controller"=>"tools","action"=>"view_tree",$hashed_user_id,$exp_id,$gf_id)); ?>">Download PhyloXML tree</a></li>
        </ul>
    </section>
    <?php endif;?>

    <section class="page-section-sm">
        <h5>Multiple Sequence Alignment(s)</h5>
        <ul>
            <li><strong>Unedited MSA</strong> (<?php echo $full_msa_length; ?> amino acids):
                <a href="<?php echo $this->Html->url(array("controller"=>"tools","action"=>"view_msa",$hashed_user_id,$exp_id,$gf_id,"normal")); ?>">download MSA (.faln)</a>
            </li>
            <?php if($previous_results['msa_stripped'] == true): ?>
            <li>
                <strong>Edited MSA</strong> (<?php echo $stripped_msa_length; ?> amino acids):
                <a href="<?php echo $this->Html->url(array("controller"=>"tools","action"=>"view_msa",$hashed_user_id,$exp_id,$gf_id,"stripped")); ?>">download MSA (.faln)</a>
            </li>
            <?php endif; ?>
        </ul>
    </section>
    </section>
        <section class="page-section">
            <h4>Used software</h4>
            <ul>
                <?php if($previous_results['tree'] == true): ?>
                <li><strong>Phylogenetic tree: </strong>
                    <?php echo "<code>" . $tree_programs[$gf_info['GeneFamilies']['tree_params']] . "</code>"; ?>
                </li>
                <?php endif; ?>
                <li><strong>Multiple Sequence Alignment: </strong>
                    <?php echo "<code>" . $msa_programs[$gf_info['GeneFamilies']['msa_params']] . "</code>"; ?>
                </li>
                <?php if($previous_results['msa_stripped'] == true): ?>
                <li><strong>Alignment editing: </strong>
                    <?php echo "<code>" . $gf_info['GeneFamilies']['msa_stripped_params'] . "</code>"; ?>
                </li>
                <?php endif; ?>
            </ul>
            <p class="text-justify">More information regarding used software versions, command-lines, and parameters can be found in the
                <?php echo $this->Html->link("documentation", array("controller"=>"documentation","action"=>"tools_parameters", "#"=>"msa-phylogeny"), array("class"=>"linkout", "target"=>"_blank")); ?>
            </p>
        </section>
    </div> <!-- End files/extra tab content -->
</div>
</section> <!-- End current results section -->
<?php endif;?>

<?php
$new_msa_tree_div_style		= null;
if(isset($previous_results) && $previous_results['msa']==true) {
    $new_msa_tree_div_style = " class='hidden'";
}
?>

<div id="new_msa_tree_div" <?php echo $new_msa_tree_div_style;?>>
<?php echo $this->Form->create(false, array("url"=>array("controller"=>"tools", "action"=>"create_tree", $exp_id, $gf_id), "type"=>"post")); ?>
<?php if(!isset($run_pipeline)):?>
<div class="row" id="tree-creation-row">
<?php
	$no_sub	= false;
    if(isset($error)){
        $no_sub	= true;
        echo "<div class=\"alert alert-warning alert-dismissible\" role=\"alert\">\n";
        echo "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button>\n";
        echo "<strong>Error:</strong> " . $error . "\n";
        echo "</div>\n";
    }
?>
    <?php if($exp_info['hit_results']):?>
    <div class="col-md-9">
    <?php else: ?>
    <div class="col-md-12">
    <?php endif; ?>
        <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">MSA / tree creation settings</h3>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                      <h5>Multiple sequence alignment</h5>
                      <div class="form-group">
                           <label for="msa_program"><strong>MSA algorithm</strong></label>
                           &nbsp;<?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['msatree_msa_program'], "tooltip_placement"=>"top")); ?>
                    <br>
                    <label class="radio-inline">
                      <input type="radio" name="msa_program" id="msa_program_mafft" value="mafft" checked> MAFFT
                    </label>
                    <label class="radio-inline">
                      <input type="radio" name="msa_program" id="msa_program_muscle" value="muscle"> MUSCLE
                    </label>&nbsp;&nbsp;
                      </div>
                      <div class="form-group">
                           <label for="posThreshold"><strong>MSA editing</strong></label>
                           &nbsp;<?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['msatree_msa_editing'], "tooltip_placement"=>"top")); ?>
                    <select class="form-control" id="editing_mode" name="editing_mode">
                        <option value="none" selected>No editing</option>
                        <option value="column">Positions only</option>
                        <option value="row">Genes only</option>
                        <option value="column_row">Position and genes</option>
                    </select>
                      </div>
                    </div>
              <div class="col-md-6">
              <h5>Phylogenetic tree</h5>
                <div class="form-group">
                     <label for="tree_program"><strong>Tree construction algorithm</strong></label>
                     &nbsp;<?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['msatree_tree_program'], "tooltip_placement"=>"top")); ?>
                     <br>
                    <label class="radio-inline">
                      <input type="radio" name="tree_program" id="tree_program_fasttree" value="fasttree" checked> FastTree
                    </label>&nbsp;&nbsp;
                    <label class="radio-inline">
                      <input type="radio" name="tree_program" id="tree_program_iqtree" value="iqtree"> IQ-TREE
                    </label>&nbsp;&nbsp;
                    <label class="radio-inline">
                      <input type="radio" name="tree_program" id="tree_program_phyml" value="phyml"> PhyML
                    </label>
                    <label class="radio-inline">
                      <input type="radio" name="tree_program" id="tree_program_raxml" value="raxml"> RaxML
                    </label>&nbsp;&nbsp;
                </div>
                <div class="form-group">
                    <label for=""><strong>Tree annotation</strong></label>
                    &nbsp;<?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['msatree_tree_annotation'], "tooltip_placement"=>"top")); ?>
                    <br>
                    <?php
			$checked	= "checked";
			if(isset($include_subsets) && $include_subsets){$checked = " checked='checked' ";}
			echo "<label class=\"checkbox-inline\">\n";
			echo "<input name='include_subsets' id='include_subsets' $checked type='checkbox' value='y'>Include subsets </label>&nbsp;&nbsp;\n";
			$checked2	= "checked";
//			if($checked){$checked2 = null;}
            echo "<label class=\"checkbox-inline\">\n";
			echo "<input name='include_meta_annotation' id='include_meta_annotation' $checked2 type='checkbox' value='y'>\n";
			echo "Include meta-annotation\n";
			echo "</label>\n";
			?>
            </div>
        </div>
</div> <!-- end row -->
<div class="row">
<div class="col-md-6">
<div class="checkbox">
    <label for="msa_only">
    <input id="msa_only" name="msa_only" value="y" type="checkbox">
    <strong>Generate MSA only</strong> &nbsp;
    </label>
    <?php echo $this->element("help_tooltips/create_tooltip", array("tooltip_text"=>$tooltips['msatree_only_msa'], "tooltip_placement"=>"top")); ?>
</div>
</div>
</div>
        </div>
        </div>

        <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Input data</h3>
        </div>
        <div class="panel-body">

    <ul class="nav nav-tabs nav-justified" id="tabs" data-tabs="tabs">
        <li class="active"><a href="#species-tree-tab" data-toggle="tab">Species / clade selection</a></li>
        <li><a href="#transcripts-tab" data-toggle="tab">Transcript exclusion</a></li>
    </ul>
    <div class="tab-content">
            <div id="species-tree-tab" class="tab-pane active"><br>
            <p class="text-justify">Please select the species for which you want to include its associated genes.</p>
	<div class="subdiv">
	     <div style="float:left;width:100%; max-height: 500px;overflow-y: auto;">
		<script type="text/javascript">
		//<![CDATA[
		var USEFRAMES 		= 0;
		var WRAPTEXT 		= 1;
		var USETEXTLINKS  	= 0;
		var STARTALLOPEN  	= 0;
		var HIGHLIGHT 	  	= 0;
		var PRESERVESTATE 	= 0;
		var USEICONS	  	= 0;
		var BUILDALL		= 1;
		var ICONPATH 		= <?php echo "\"".$this->Html->url("/",true)."img/tree_view/"."\""; ?>;

		function generateCheckBoxClade(parentfolderObject,itemLabel,checkBoxDOMId,checkBoxName,is_checked){
			var newObj;
			newObj = insFld(parentfolderObject,gFld(itemLabel,""));
			var prep 	  = "<td valign=middle><input class='clade' type=checkbox id='"+checkBoxDOMId+"' name='"+checkBoxName+"' ";
			if(is_checked){prep+=" checked='checked' ";}
			prep		+= " onchange=\"javascript:changeClade('"+checkBoxDOMId+"');\"></td>";
			newObj.prependHTML=prep;
			return newObj;
		}

		//function to generate the checkboxes in the tree
		function generateCheckBoxLeaf(parentfolderObject, itemLabel, checkBoxDOMId,checkBoxName,is_checked,is_disabled) {
  			var newObj;
			newObj = insDoc(parentfolderObject, gLnk("R", itemLabel,""));
			var prep	  = "<td valign=middle><input class='species' type=checkbox id='"+checkBoxDOMId+"' name='"+checkBoxName+"' ";
			if(is_checked){prep+=" checked='checked' ";}
			if(is_disabled){prep+=" disabled='disabled' ";}
			prep		+= " onchange=\"javascript:changeSpecies('"+checkBoxDOMId+"');\"></td>";
			newObj.prependHTML=prep;
		}


		//here, we create the actual tree
		var foldersTree = gFld("", "");
		foldersTree.treeID = "cladeSpeciesSelectionTree";
		<?php
		function plotTree($arr,$parent,$counter,$ast,$ac,$cok,$cp, $sel_clades,$sel_species,$phylo_profile){
		    foreach($arr as $k=>$v){
			if(!(is_numeric($k)  && is_numeric($v))){
				$aux	 = "aux".$counter++;
				$num_total_species_clade	= count($ac[$k]);
				$num_gf_species_clade		= count($cok[$k]);
				$num_genes_clade = $cp[$k];
//                foreach($sel_species as $sel){
//                    echo "console.log('" . $k . "');";
//                    echo "console.log('" . $v . "');";
//                    $total_selected_genes+=$phylo_profile[$available_species_tax[$sel]['species']];
//                }

				if($num_gf_species_clade==0){
//				    $c_label = "<u>".$k."</u> (" . $num_total_species_clade." species in total, <b>".$num_gf_species_clade."</b> in the gene family &mdash; " . $num_genes_clade . " genes)";
				    $c_label = "<u>".$k."</u> (" . $num_total_species_clade." species in total, <b>".$num_gf_species_clade."</b> in the gene family)";
				    echo "generateCheckBoxLeaf(".$parent.",\"".$c_label."\",\"".$k."\",\"".$k."\",0,1);\n";
				}
				else{
				    $genes_clade_label = $num_genes_clade . " gene";
				    $genes_clade_label .= ($num_genes_clade > 1 ? 's': '');
				    $c_label = "<u>".$k."</u> (" . $num_total_species_clade." species in total, ".$num_gf_species_clade." in the gene family &mdash; " . $genes_clade_label . ")";
    				    $selected_clade	= 0; if(array_key_exists($k,$sel_clades)){$selected_clade=1;}
				    echo "var ".$aux." = generateCheckBoxClade(".$parent.",\"".$c_label."\",\"".$k."\",\"".$k."\",".$selected_clade.");\n";
				    //now, based on content of '$v' variable, perform different functionality (subclades or leaves).
				    $c		= 0;
				    $has_called_child	= false;
				    foreach($v as $v1=>$v2){
					    //if both v1 and v2 are numeric -> leaf
					    if(is_numeric($v1) && is_numeric($v2)){
						    $spec_info	= $ast[$v2];
    						    //check on whether the species is present in the gene family, and whether the
						    //species is selected by a previous run (or prev form submission).
                            // If the species is represented in the GF, display its number of genes as well
						    $num_genes	= 0;
						    if(array_key_exists($spec_info['species'],$phylo_profile)){
							$num_genes = $phylo_profile[$spec_info['species']];
						    }
    						    if($num_genes==0){
							echo "generateCheckBoxLeaf(".$aux.",\"".$spec_info['common_name']."\",\"taxid_".$v2."\",\"".$v2."\",0,1);\n";
    						    }
    						    else{
    						        $genes_label = $num_genes . " gene";
                                    $genes_label .= ($num_genes > 1 ? 's': '');
    							$selected_species = 0; if(array_key_exists($v2,$sel_species)){$selected_species=1;}
							echo "generateCheckBoxLeaf(".$aux.",\"".$spec_info['common_name']." (" . $genes_label . ")\",\"taxid_".$v2."\",\"".$v2."\",".$selected_species.",0);\n";
    						    }
					    }
					    else{
						    if(!$has_called_child){
							    $new_counter		= $counter."".$c++;
							    $has_called_child	= true;
							    plotTree($v,$aux,$new_counter,$ast,$ac,$cok,$cp, $sel_clades,$sel_species,$phylo_profile);
						    }

					    }
				    }
				}
			}

		    }
 		}

		$clades	= array_keys($available_clades);
		sort($clades);
		$clades_phylo			= array();
		$clades_ok_species		= array();
		foreach($clades as $clade){
			$ok_species		= array();
			$num_genes		= 0;
			foreach($available_clades[$clade] as $tax_id){
				$species	= $available_species_tax[$tax_id]['species'];
				if(array_key_exists($species,$phylo_profile)){
					$num_genes+=$phylo_profile[$species];
					$ok_species[]	= $tax_id;
				}
			}
			$clades_phylo[$clade]		= $num_genes;
			$clades_ok_species[$clade]	= $ok_species;
		}
		$sel_clades	= $available_clades;
		$sel_species	= $available_species_tax;
		if(isset($selected_clades)){$sel_clades=$selected_clades;}
		if(isset($selected_species)){$sel_species=$selected_species;}

		plotTree($full_tree,"foldersTree",0,$available_species_tax,$available_clades,$clades_ok_species,$clades_phylo,$sel_clades,$sel_species,$phylo_profile);


		?>
		//]]>
		</script>

		<table style="background-color:transparent;border:0px;">
	   		<tr style="border:0px;"><td style="border:0px;">
				<a style="font-size:7pt;text-decoration:none;color:black;font-weight:normal;" href="http://www.treemenu.net/" target="_blank"></a>
	   		</td></tr>
		</table>


		<div class="TreeviewSpanArea">
			<script type="text/javascript">
			//<![CDATA[
			initializeDocument();
			//]]>
			</script>
	 		<noscript>Please enable Javascript in your browser</noscript>
 		</div>
	    </div> <!-- End float -->
<!--	    <div style='clear:both;width:800px;'>&nbsp;</div>-->
	</div>


            </div> <!-- end species/clade selection tab content -->
            <div id="transcripts-tab" class="tab-pane"><br>
        	<script type="text/javascript">
	    //<![CDATA[
	    <?php
		$total_selected_species	= 0;
		$total_selected_genes	= 0;
		$phylo_profile_tax	= array();
		foreach($available_species_tax as $txid=>$spec_info){
			$ng	= 0;
			if(array_key_exists($spec_info['species'],$phylo_profile)){
				$ng =  (int) $phylo_profile[$spec_info['species']];
			}
			$phylo_profile_tax[$txid] = $ng;
		}

		if(isset($selected_species)){
			$total_selected_species	= count($selected_species);
			foreach($selected_species as $sel){
				$total_selected_genes+=$phylo_profile[$available_species_tax[$sel]['species']];
			}
		}
		else{
			foreach($available_species_tax as $txid=>$spec_info){
				$ng	= 0;
				if(array_key_exists($spec_info['species'],$phylo_profile)){
					$ng =  $phylo_profile[$spec_info['species']];
				}
				if($ng!=0){
					$total_selected_species++;
					$total_selected_genes+=$ng;
				}
			}
		}
	    ?>
	    var total_selected_species	= <?php echo $total_selected_species;?>;
	    var total_selected_genes	= <?php echo $total_selected_genes;?>;

	    var clades_to_species	= <?php echo json_encode($clades_ok_species);?>;
	    var species_phylo		= <?php echo json_encode($phylo_profile_tax);?>;
	    var parent_child_clades	= <?php echo json_encode($parent_child_clades);?>;
	    var MAX_GENES		= <?php echo $MAX_GENES;?>;


			function changeClade(clade) {
				var element	= document.getElementById(clade);
                clades_to_species[clade].forEach(function (sp) {
					var sp_el		= document.getElementById("taxid_"+sp);
					if(sp_el!=null) {
					    if(element.checked) {	// Add new genes and species from total count
						    if(sp_el.checked!=element.checked) {
							    total_selected_species++;
							    total_selected_genes+=species_phylo[sp];
						    }
					    }
					    else {	// Remove genes and species from total count
						    if(sp_el.checked!=element.checked) {
							    total_selected_species--;
							    total_selected_genes-=species_phylo[sp];
						    }
					    }
    					    if(sp_el.disabled) {}
    					    else {
    					        sp_el.checked	= element.checked;
    					    }
					}
				});
				// Check or uncheck child-clades as well. This is purely for visualization purposes.
				parent_child_clades[clade].forEach(function(child_clade){
					try {
						var cc		= document.getElementById(child_clade);
						if(cc!=null) {
							if(cc.disabled) {}
							else {
							    cc.checked	= element.checked;
							}
						}
					}
					catch(exc) {
					}
				});

				updateCounts();
			}


			function changeSpecies(sp_id){
				var element	= document.getElementById(sp_id);
				var sp		= sp_id.substr(6);
				if(element.checked){
					total_selected_species++;
					total_selected_genes+=species_phylo[sp];
				}
				else{
					total_selected_species--;
					total_selected_genes-=species_phylo[sp];
				}
				updateCounts();
			}


		function updateCounts(){
			//alert(total_selected_genes);
            document.getElementById("num_species").innerHTML = total_selected_species;
            document.getElementById("num_genes").innerHTML = total_selected_genes;
			if(total_selected_genes > MAX_GENES) {
                document.getElementById("status").innerHTML	= "<span class='text-danger'>Error<span class='hidden-sm'>: too many genes selected (max: "+MAX_GENES+"). Please deselect species.</span></span>";
                document.getElementById("submit_button").disabled = "disabled";
			}
			else if(total_selected_genes <= 0){
                document.getElementById("status").innerHTML	= "<span class='text-danger'>Error<span class='hidden-sm'>: No species/genes selected. Please select at least one.</span></span>";
                document.getElementById("submit_button").disabled = "disabled";
			}
			else {
                document.getElementById("status").innerHTML	= "<span class='text-success'>OK</span>";
                document.getElementById("submit_button").disabled = false;
			}
		}

	//]]>
	</script>
    <p class="text-justify">Selected transcript will be <strong>excluded</strong> from the tree. </p>
	<div class="subdiv">
		<?php
		$met_dict = array("Full Length"=>"FL", "Quasi Full Length"=>"QFL", "Partial"=>"P", "No Information"=>"NI");
		$disabled	= null;
		if($num_partial_transcripts==0){$disabled=" disabled='disabled' ";}
		echo "<input type='checkbox' name='no_partial_transcripts' id='no_partial_transcripts' $disabled />\n";
		echo "<span style='margin-left:10px;'>Exclude partial transcripts (".$num_partial_transcripts." partial transcripts detected in this gene family)</span>\n";
		echo "<br>\n";

		?>
		<div style="margin-top:5px;font-size: 85%; max-height: 500px;overflow-y: auto;" id="transcript_select_div">
				<?php
				$k = 0;
				foreach($gf_transcripts as $tr=>$met){
				    // Shorten transcript name if it longer than 25 `max_len` characters!
				    $max_len = 26;
                    $tr_text = $tr;
                    if(strlen($tr_text) > $max_len) {
                        $tr_text = substr($tr, 0,10) . "..." . substr($tr, -10);
                    }
					echo "<div class='col-lg-3 col-md-6 col-sm-6 col-xs-12'>";
					echo "<input type='checkbox' name='exclude_".$tr."' id='exclude_".$tr."' />";
					echo $this->Html->link($tr_text, array("controller"=>"trapid","action"=>"transcript",$exp_id,urlencode($tr)), array("title"=>$tr));
					// echo "<span class=\"pull-right\">(<abbr title=\"" . $met . "\">" . $met_dict[$met] . "</abbr>)</span>";
					echo " (<abbr title=\"" . $met . "\">" . $met_dict[$met] . "</abbr>)";
					// echo " (" . $met . ")";
					echo "</div>";
//					$k++;
//					if(($k % 3) == 0) {
//					}
				}
				?>
		</div>
		<script type="text/javascript">
			//<![CDATA[
			var transcript_meta	= <?php echo json_encode($gf_transcripts);?>;
			/*$("#single_transcript_selection").change(function(){
				if(document.getElementById("single_transcript_selection").checked){
                    document.getElementById("transcript_select_div").style.display="block";
				}
				else{
					Object.keys(transcript_meta).forEach(function(tr){
						var cb	= "exclude_"+tr;
                        document.getElementById(cb).checked = null;
					});
                    document.getElementById("transcript_select_div").style.display="none";
				}
			});*/
			$("#no_partial_transcripts").change(function(){
				if(document.getElementById("no_partial_transcripts").checked){
//                    document.getElementById("single_transcript_selection").checked = "checked";
//                    document.getElementById("transcript_select_div").style.display="block";
					Object.keys(transcript_meta).forEach(function(tr){
						var meta = transcript_meta[tr];
						if(meta === "Partial") {
							var cb	= "exclude_"+tr;
                            document.getElementById(cb).checked = "checked";
						}
					});
				}
				else{
					// document.getElementById("single_transcript_selection").checked = null;
					// document.getElementById("transcript_select_div").style.display="none";
					Object.keys(transcript_meta).forEach(function(tr){
					    console.log(transcript_meta[tr]);
						var meta = transcript_meta[tr];
						if(meta === "Partial"){
							var cb	= "exclude_"+tr;
                            document.getElementById(cb).checked = null;
						}
					});
				}
			});


            // Toggle visibility of HTML elements if the user chooses to build a new tree.
            function toggleElementsNewMsa() {
                // Toggle visibility of current results / submission form
                document.getElementById('new_msa_tree_div').classList.toggle("hidden");
//                document.getElementById('rerun_div').classList.toggle("hidden");
                document.getElementById('tree-results').classList.toggle("hidden");
                // Toggle visibility of links
                document.getElementById('new-tree-link').classList.toggle("hidden");
                document.getElementById('current-tree-link').classList.toggle("hidden");
            }
            // Call function on click of tree results links
            var tree_results_links = document.getElementsByClassName("tree-results-link");
            for(var i=0;i < tree_results_links.length; i++) {
                tree_results_links[i].onclick = function() {
                    toggleElementsNewMsa();
                }
            }
			//]]>
		</script>
	</div>



            </div> <!-- end transcripts selection tab content -->
    </div><!-- end tab content wrapper -->
        </div>
        <div class="panel-footer">
		<strong># Species: </strong><span id='num_species'><?php echo $total_selected_species;?></span> &mdash;
            <strong># Genes: </strong><span id='num_genes'><?php echo $total_selected_genes; ?></span>
        <span class="pull-right"><strong>Status: </strong>
			<span id='status'>
			<?php
			if($total_selected_genes>$MAX_GENES){
				echo "<span class='text-danger'>Error<span class='hidden-sm'>: too many genes selected (max: " . $MAX_GENES . "). Please deselect species.</span></span>";
				$no_sub	= true;
			}
			elseif ($total_selected_genes <= 0) {
				echo "<span class='text-danger'>Error<span class='hidden-sm'>: No species/genes selected. Please select at least one.</span></span>";
				$no_sub	= true;
			}
			else{
				echo "<span class='text-success'>OK</span>";
			}
			?>
			</span>
        </span>
        </div>
        </div>

<p class="text-center">
    <input type="submit" value="Create MSA / Tree" id="submit_button" <?php if($no_sub){echo "disabled='disabled'";}?> class="btn btn-primary"/>
</p>

    </div> <!-- end col -->

    <?php if($exp_info['hit_results']):?>
    <div class="col-md-3 hidden-sm">


            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Species similarity hits</h3>
                </div>
                <div class="panel-body" style="font-size: 85%;">
<!--                <p class="text-justify">Best similarity search hit for transcripts of the experiment.</p>-->
                    <div id="species_simsearch_selection_div">
                            <?php
                            $hit_results	= explode(";",$exp_info['hit_results']);
                            $max_species = 20;  // Max. number of species to show by default.
                            $extra_div = false;
                            $tmp		= array();
                            $sum		= 0;
                            foreach($hit_results as $s){$k = explode("=",$s); $tmp[$k[0]]=$k[1]; $sum+=$k[1];}
                            arsort($tmp);
                            $species_keys = array_keys($tmp);
                            $last_species = end($species_keys);
                            foreach($tmp as $k=>$v){
                                if(array_search($k, $species_keys) == $max_species) {
                                    $extra_div = true;
                                    echo "<a id=\"toggle-extra-hits\" onclick=\"toggleExtraHits()\">";
                                    echo "<span id=\"toggle-extra-hits-icon\" class=\"glyphicon small-icon glyphicon-menu-right\"></span> ";
                                    echo "Show all...";
                                    echo "</a>\n";
                                    echo "<div id='extra-hits' class='hidden'>\n";
                                }
                                echo "<div class='row'>";
                                echo "<div class=\"col-md-9 stats-metric\">" . $available_species_species[$k]['common_name'] . "</div>";
                                $perc	= round(100*$v/$sum,0);
                                $perc2	= $perc;
                                $css1	= "background: linear-gradient(left,rgb(202, 230, 252) ".$perc."%,rgb(255, 255, 255) ".$perc2."%); ";
                                $css2	= "background: -o-linear-gradient(left,rgb(202, 230, 252) ".$perc."%,rgb(255, 255, 255) ".$perc2."%); ";
                                $css3	= "background: -moz-linear-gradient(left,rgb(202, 230, 252) ".$perc."%,rgb(255, 255, 255) ".$perc2."%); ";
                                $css4	= "background: -webkit-linear-gradient(left,rgb(202, 230, 252) ".$perc."%,rgb(255, 255, 255) ".$perc2."%); ";
                                $css5	= "background: -ms-linear-gradient(left,rgb(202, 230, 252) ".$perc."%,rgb(255, 255, 255) ".$perc2."%); ";
                                $css6	= "background: -webkit-gradient(linear,left top,right top,color-stop(".($perc/100).", rgb(202, 230, 252)),color-stop(".($perc2/100).", rgb(255, 255, 255)));";
                                $css	= $css1.$css2.$css3.$css4.$css5.$css6;
                                echo "<div class=\"col-md-3\" style=\"" . $css . "\">" . $v . "</div>";
                                echo "</div>\n";
                                if($extra_div && $k == $last_species) {
                                    echo "</div>";
                                }
                            }
                            ?>
                            <!-- TODO: move JS block once the page is refactored!! -->
                            <script>
                                        // Toggle extra similarity search hits. Called on click of 'toggle-extra-hits' link.
                                        function toggleExtraHits() {
                                            var extraHitsDiv = "extra-hits";
                                            var extraHitsIcon = "toggle-extra-hits-icon";
                                            var ehIconElmt = document.getElementById(extraHitsIcon);
                                            document.getElementById(extraHitsDiv).classList.toggle("hidden");
                                            if(ehIconElmt.classList.contains("glyphicon-menu-right")) {
                                                ehIconElmt.classList.replace("glyphicon-menu-right", "glyphicon-menu-down");
                                            }
                                            else {
                                                ehIconElmt.classList.replace("glyphicon-menu-down", "glyphicon-menu-right");
                                            }
                                        }

                                        // Disable / enable tree-related form elements.
                                        function toggleTreeOptions() {
                                            var msa_only_checked = document.getElementById("msa_only").checked;
                                            // Disable MSA editing select element
                                            document.getElementById("editing_mode").disabled = msa_only_checked;
                                            // Disable tree program radio buttons
                                            var tree_radios = document.getElementsByName("tree_program");
                                            for (var i = 0; i < tree_radios.length; i++) {
                                                tree_radios[i].disabled = msa_only_checked;
                                            }
                                            // Disable tree annotation checkboxes
                                            document.getElementById("include_subsets").disabled = msa_only_checked;
                                            document.getElementById("include_meta_annotation").disabled = msa_only_checked;
                                        }

                                        // Toggle tree form elements on checkbox change
                                        document.getElementById("msa_only").onchange = function(){ toggleTreeOptions() };

                            </script>
                    </div>
                </div>
            </div><!-- end panel -->
    </div><!-- end col -->
    <?php endif;?>
</div>
<?php echo $this->Form->end();?>
<?php echo $this->element("help_tooltips/enable_tooltips",  array("container"=>"#tree-creation-row")); ?>
<?php endif;?>

	</div>

</div>
</div>
<?php if(isset($previous_results) && $previous_results['tree']==true): ?>
    <script type="text/javascript">
        var treeUrl = "<?php echo $this->Html->url(array("controller"=>"tools","action"=>"get_tree",$exp_id, $gf_id, "xml_tree")); ?>";
        console.log(treeUrl);
        var phyD3Elmt = "phyd3-viewer";
        var opts = {
            dynamicHide: true,
            height: 780,
            invertColors: false,
            lineupNodes: true,
            // showDomains: true,
            // showDomainNames: false,
            // showDomainColors: true,
            showGraphs: true,
            showGraphLegend: true,
            showLength: false,
            showNodeNames: true,
            showNodesType: "only leaf",
            showPhylogram: false,
            showTaxonomy: true,
            showFullTaxonomy: false,
            showSequences: false,
            showTaxonomyColors: true,
            backgroundColor: "#f5f5f5",
            foregroundColor: "#000000",
            nanColor: "#f5f5f5",
            maxDecimalsLengthValues: 3,
            maxDecimalsSupportValues: 3
        };


        function loadPhyd3() {
            jQuery('#foregroundColor').val(opts.foregroundColor);
            jQuery('#backgroundColor').val(opts.backgroundColor);
            jQuery('#foregroundColorButton').colorpicker({color: opts.foregroundColor});
            jQuery('#backgroundColorButton').colorpicker({color: opts.backgroundColor});
            d3.select("#" + phyD3Elmt).text("Loading tree viewer... ");
            d3.xml(treeUrl, "application/xml", function (xml) {
                d3.select("#" + phyD3Elmt).text(null);
                var tree = phyd3.phyloxml.parse(xml);
                phyd3.phylogram.build("#" + phyD3Elmt, tree, opts);
            });
        }

        // Load tree on page load
        $(document).ready(function () {
            loadPhyd3();
        });
</script>
<?php endif; ?>